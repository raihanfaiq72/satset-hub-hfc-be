<?php

class QRCodeController extends BaseController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function generateReceiveQR() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['user_id']);
        if ($validation) return $validation;

        try {
            $userId = $data['user_id'];
            $timestamp = time();
            $nonce = uniqid();
            
            $qrData = [
                'type' => 'receive_voucher',
                'user_id' => $userId,
                'timestamp' => $timestamp,
                'nonce' => $nonce
            ];

            $qrCode = base64_encode(json_encode($qrData));
            
            return $this->success([
                'qr_code' => $qrCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ], 'QR code generated successfully for receiving vouchers');

        } catch (Exception $e) {
            return $this->serverError('Failed to generate receive QR code: ' . $e->getMessage());
        }
    }

    public function scanAndTransfer() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'qr_code', 
            'voucher_type', 
            'voucher_id', 
            'otp'
        ]);
        if ($validation) return $validation;

        try {
            $qrData = json_decode(base64_decode($data['qr_code']), true);
            
            if (!$qrData || !in_array($qrData['type'], ['receive_voucher', 'receive_promo_voucher', 'receive_payment_voucher'])) {
                return $this->badRequest('Invalid QR code');
            }

            $toUserId = $qrData['user_id'];
            $qrTimestamp = $qrData['timestamp'];
            
            if (time() - $qrTimestamp > 600) {
                return $this->badRequest('QR code expired');
            }

            if (!$this->verifyOTP($toUserId, $data['otp'], 'receive')) {
                return $this->badRequest('Invalid or expired OTP');
            }

            $fromUserId = $this->auth->user()['id'];

            if ($data['voucher_type'] === 'payment') {
                return $this->transferPaymentVoucher($fromUserId, $toUserId, $data['voucher_id']);
            } elseif ($data['voucher_type'] === 'promo') {
                return $this->transferPromoVoucher($fromUserId, $toUserId, $data['voucher_id']);
            } else {
                return $this->badRequest('Invalid voucher type');
            }

        } catch (Exception $e) {
            return $this->serverError('Failed to process transfer: ' . $e->getMessage());
        }
    }

    public function generateRedeemQR() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['voucher_id', 'voucher_type']);
        if ($validation) return $validation;

        try {
            $voucherId = $data['voucher_id'];
            $voucherType = $data['voucher_type'];
            $timestamp = time();
            $nonce = uniqid();
            
            $qrData = [
                'type' => 'redeem_voucher',
                'voucher_id' => $voucherId,
                'voucher_type' => $voucherType,
                'timestamp' => $timestamp,
                'nonce' => $nonce
            ];

            $qrCode = base64_encode(json_encode($qrData));
            
            return $this->success([
                'qr_code' => $qrCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ], 'QR code generated successfully for voucher redemption');

        } catch (Exception $e) {
            return $this->serverError('Failed to generate redeem QR code: ' . $e->getMessage());
        }
    }

    public function scanAndSendOTP() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['qr_code']);
        if ($validation) return $validation;

        try {
            $qrData = json_decode(base64_decode($data['qr_code']), true);
            if (!$qrData || !isset($qrData['type'])) {
                return $this->badRequest('Invalid QR code');
            }

            $targetUserId = null;
            $type = '';
            
            if (in_array($qrData['type'], ['receive_voucher', 'receive_promo_voucher', 'receive_payment_voucher'])) {
                $targetUserId = $qrData['user_id'];
                $type = 'receive';
            } elseif (in_array($qrData['type'], ['redeem_voucher', 'redeem_promo_voucher', 'redeem_payment_voucher'])) {
                $voucherId = $qrData['voucher_id'];
                $voucherType = $qrData['voucher_type'] ?? (strpos($qrData['type'], 'promo') !== false ? 'promo' : 'payment');
                $type = 'redeem';
                
                if ($voucherType === 'payment') {
                    $voucher = PvVouchers::find($voucherId);
                } else {
                    $voucher = PmVouchers::find($voucherId);
                }
                
                if (!$voucher) {
                    return $this->notFound('Voucher not found');
                }
                $targetUserId = $voucher->current_owner_id;
            } else {
                return $this->badRequest('Unsupported QR type');
            }

            if (!$targetUserId) {
                return $this->badRequest('Could not identify target user');
            }

            $user = Customer::find($targetUserId);
            if (!$user || !$user->noHp) {
                return $this->notFound('Target user or phone number not found');
            }

            $otp = $this->generateOTP();
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $this->storeOTPSession($targetUserId, $otp, $otpExpiry, $type, json_encode($qrData));

            $message = "Kode OTP Satset Anda adalah: {$otp}. Kode ini berlaku selama 5 menit. Rahasiakan kode ini dari siapapun.";
            $waResult = WaHelper::sendMessage($user->noHp, $message, $_ENV['WA_API_KEY'] ?? '');

            if (!$waResult['success']) {
                return $this->serverError('Failed to send OTP via WhatsApp: ' . $waResult['message']);
            }

            return $this->success([
                'target_phone' => substr($user->noHp, 0, 4) . '****' . substr($user->noHp, -4),
                'expires_at' => $otpExpiry
            ], 'OTP has been sent to the user\'s WhatsApp');

        } catch (Exception $e) {
            return $this->serverError('Failed to process QR scan: ' . $e->getMessage());
        }
    }

    public function scanAndRedeem() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'qr_code',
            'otp',
            'lat' => 'optional',
            'lng' => 'optional',
            'transaction_id' => 'optional'
        ]);
        if ($validation) return $validation;

        try {
            $qrData = json_decode(base64_decode($data['qr_code']), true);
            
            if (!$qrData || !in_array($qrData['type'], ['redeem_voucher', 'redeem_promo_voucher', 'redeem_payment_voucher'])) {
                return $this->badRequest('Invalid QR code');
            }

            $voucherId = $qrData['voucher_id'];
            $voucherType = $qrData['voucher_type'];
            $qrTimestamp = $qrData['timestamp'];
            
            if (time() - $qrTimestamp > 600) {
                return $this->badRequest('QR code expired');
            }

            $currentUserId = $this->auth->user()['id'];

            if (!$this->verifyOTP($currentUserId, $data['otp'], 'redeem')) {
                return $this->badRequest('Invalid or expired OTP');
            }

            if ($voucherType === 'payment') {
                return $this->redeemPaymentVoucher($voucherId, $currentUserId, $data);
            } elseif ($voucherType === 'promo') {
                return $this->redeemPromoVoucher($voucherId, $currentUserId, $data);
            } else {
                return $this->badRequest('Invalid voucher type');
            }

        } catch (Exception $e) {
            return $this->serverError('Failed to process redemption: ' . $e->getMessage());
        }
    }

    private function transferPaymentVoucher($fromUserId, $toUserId, $voucherId) {
        $voucher = PvVouchers::find($voucherId);
        if (!$voucher) {
            return $this->notFound('Payment voucher not found');
        }

        if ($voucher->current_owner_id != $fromUserId) {
            return $this->forbidden('You do not own this voucher');
        }

        if ($voucher->status !== 'sold') {
            return $this->badRequest('Voucher is not available for transfer');
        }

        DB::beginTransaction();
        try {
            $voucher->current_owner_id = $toUserId;
            $voucher->save();

            PvTransfers::create([
                'voucher_id' => $voucher->id,
                'from_customer_id' => $fromUserId,
                'to_customer_id' => $toUserId,
                'reference_id' => uniqid('QR_TRANSFER_'),
                'notes' => 'Transfer via QR code with OTP verification'
            ]);

            $this->clearOTPSession($toUserId, 'receive');

            DB::commit();
            return $this->success($voucher, 'Payment voucher transferred successfully');

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function transferPromoVoucher($fromUserId, $toUserId, $voucherId) {
        $voucher = PmVouchers::find($voucherId);
        if (!$voucher) {
            return $this->notFound('Promo voucher not found');
        }

        if ($voucher->current_owner_id != $fromUserId) {
            return $this->forbidden('You do not own this voucher');
        }

        if ($voucher->status !== 'claimed') {
            return $this->badRequest('Voucher is not available for transfer');
        }

        DB::beginTransaction();
        try {
            $voucher->current_owner_id = $toUserId;
            $voucher->save();

            PmTransfers::create([
                'voucher_id' => $voucher->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'transfer_type' => 'qr_transfer',
                'notes' => 'Transfer via QR code with OTP verification'
            ]);

            $this->clearOTPSession($toUserId, 'receive');

            DB::commit();
            return $this->success($voucher, 'Promo voucher transferred successfully');

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function redeemPaymentVoucher($voucherId, $userId, $data) {
        $voucher = PvVouchers::find($voucherId);
        if (!$voucher) {
            return $this->notFound('Payment voucher not found');
        }

        if ($voucher->current_owner_id != $userId) {
            return $this->forbidden('You do not own this voucher');
        }

        if ($voucher->status !== 'sold') {
            return $this->badRequest('Voucher is not available for redemption');
        }

        $batch = PvBatches::find($voucher->batch_id);
        if (!$batch) {
            return $this->notFound('Voucher batch not found');
        }

        if (strtotime($batch->valid_until) < time()) {
            return $this->badRequest('Voucher has expired');
        }

        DB::beginTransaction();
        try {
            $voucher->status = 'used';
            $voucher->used_at = date('Y-m-d H:i:s');
            $voucher->save();

            PvRedemptions::create([
                'voucher_id' => $voucher->id,
                'user_id' => $userId,
                'id_layanan' => $data['id_layanan'] ?? null,
                'redeemed_value' => $voucher->face_value
            ]);

            $this->clearOTPSession($userId, 'redeem');

            DB::commit();
            return $this->success($voucher, 'Payment voucher redeemed successfully');

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function redeemPromoVoucher($voucherId, $userId, $data) {
        $voucher = PmVouchers::find($voucherId);
        if (!$voucher) {
            return $this->notFound('Promo voucher not found');
        }

        if ($voucher->current_owner_id != $userId) {
            return $this->forbidden('You do not own this voucher');
        }

        if ($voucher->status !== 'claimed') {
            return $this->badRequest('Voucher is not available for redemption');
        }

        $campaign = PmCampaigns::find($voucher->campaign_id);
        if (!$campaign) {
            return $this->notFound('Campaign not found');
        }

        if (!$campaign->is_active) {
            return $this->badRequest('Campaign is not active');
        }

        if (strtotime($campaign->valid_until) < time()) {
            return $this->badRequest('Voucher has expired');
        }

        if (strtotime($campaign->valid_from) > time()) {
            return $this->badRequest('Voucher is not yet valid');
        }

        if (isset($data['lat']) && isset($data['lng'])) {
            $distance = $this->calculateDistance(
                $data['lat'], $data['lng'], 
                $campaign->lat, $campaign->lng
            );
            
            if ($distance > $campaign->radius_meter) {
                return $this->badRequest('You are outside the valid radius for this voucher');
            }
        }

        DB::beginTransaction();
        try {
            $voucher->status = 'used';
            $voucher->used_at = date('Y-m-d H:i:s');
            $voucher->save();

            PmRedemptions::create([
                'voucher_id' => $voucher->id,
                'user_id' => $userId,
                'transaction_id' => $data['transaction_id'] ?? null,
                'discount_amount' => $this->calculateDiscount($campaign, $data['transaction_amount'] ?? 0),
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null
            ]);

            $this->clearOTPSession($userId, 'redeem');

            DB::commit();
            return $this->success($voucher, 'Promo voucher redeemed successfully');

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateOTP() {
        return sprintf('%06d', mt_rand(0, 999999));
    }

    private function storeOTPSession($userId, $otp, $expiry, $type, $qrData = null) {
        return OtpSessions::create([
            'user_id' => $userId,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => $expiry,
            'qr_data' => $qrData,
            'is_used' => false
        ]);
    }

    private function verifyOTP($userId, $otp, $type) {
        $otpSession = OtpSessions::forUser($userId)
            ->byType($type)
            ->active()
            ->where('otp', $otp)
            ->first();

        if (!$otpSession) {
            return false;
        }

        return $otpSession->isValid();
    }

    private function clearOTPSession($userId, $type) {
        OtpSessions::forUser($userId)
            ->byType($type)
            ->active()
            ->update(['is_used' => true]);
    }

}
