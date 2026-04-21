<?php

class PromoVoucherController extends BaseController {
    private $auth;

    public function __construct() {
        $this->auth = new AuthMiddleware();
    }

    public function index() {
        $this->auth->authenticate();
        try{
            $campaigns = PmCampaigns::get();
            return $this->success($campaigns);
        }catch(Exception $e){
            return $this->serverError('Failed to fetch promo vouchers campaigns: ' . $e->getMessage());
        }
    }

    public function campaigns() {
        $this->auth->authenticate();
        try{
            $campaigns = PmCampaigns::get();
            return $this->success($campaigns);
        }catch(Exception $e){
            return $this->serverError('Failed to fetch promo vouchers campaigns: ' . $e->getMessage());

        }
    }

    public function campaignCreate() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'name', 
            'description', 
            'discount_type',
            'discount_value',
            'max_discount',
            'min_transaction',
            'quota',
            'per_user_limit',
            'valid_from',
            'valid_until',
            'lat',
            'lng',
            'radius_meter',
            'is_active',]);
        if ($validation) return $validation;

        try {
            $campaign = PmCampaigns::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'max_discount' => $data['max_discount'],
                'min_transaction' => $data['min_transaction'],
                'quota' => $data['quota'],
                'per_user_limit' => $data['per_user_limit'],
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'radius_meter' => $data['radius_meter'],
                'is_active' => $data['is_active'],
            ]);

            return $this->success($campaign, 'Campaign created successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to create campaign: ' . $e->getMessage());
        }
    }

    public function campaignEdit($id) {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        try {
            $campaign = PmCampaigns::find($id);
            if (!$campaign) {
                return $this->notFound('Campaign not found');
            }

            $campaign->name = $data['name'] ?? $campaign->name;
            $campaign->description = $data['description'] ?? $campaign->description;
            $campaign->discount_type = $data['discount_type'] ?? $campaign->discount_type;
            $campaign->discount_value = $data['discount_value'] ?? $campaign->discount_value;
            $campaign->max_discount = $data['max_discount'] ?? $campaign->max_discount;
            $campaign->min_transaction = $data['min_transaction'] ?? $campaign->min_transaction;
            $campaign->quota = $data['quota'] ?? $campaign->quota;
            $campaign->per_user_limit = $data['per_user_limit'] ?? $campaign->per_user_limit;
            $campaign->valid_from = $data['valid_from'] ?? $campaign->valid_from;
            $campaign->valid_until = $data['valid_until'] ?? $campaign->valid_until;
            $campaign->lat = $data['lat'] ?? $campaign->lat;
            $campaign->lng = $data['lng'] ?? $campaign->lng;
            $campaign->radius_meter = $data['radius_meter'] ?? $campaign->radius_meter;
            $campaign->is_active = $data['is_active'] ?? $campaign->is_active;
            $campaign->save();

            return $this->success($campaign, 'Campaign updated successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to update campaign: ' . $e->getMessage());
        }
    }

    public function campaignDelete($id) {
        $this->auth->authenticate();
        try {
            $campaign = PmCampaigns::find($id);
            if (!$campaign) {
                return $this->notFound('Campaign not found');
            }

            $campaign->delete();

            return $this->success(null, 'Campaign deleted successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to delete campaign: ' . $e->getMessage());
        }
    }

    public function campaignDetail($id) {
        $this->auth->authenticate();
        try{
            $campaign = PmCampaigns::find($id);
            if (!$campaign) {
                return $this->notFound('Campaign not found');
            }
            return $this->success($campaign);
        }catch(Exception $e){
            return $this->serverError('Failed to fetch promo voucher campaign detail: ' . $e->getMessage());
        }
    }

    public function claim() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['campaign_id']);
        if ($validation) return $validation;

        try {
            $campaign = PmCampaigns::find($data['campaign_id']);
            if (!$campaign) {
                return $this->notFound('Campaign not found');
            }

            $existingVoucher = PmVouchers::where('campaign_id', $data['campaign_id'])
                ->where('current_owner_id', $this->auth->user()['id'])
                ->first();

            if ($existingVoucher) {
                return $this->badRequest('You have already claimed this voucher');
            }

            $voucher = PmVouchers::create([
                'campaign_id' => $data['campaign_id'],
                'voucher_code' => strtoupper(uniqid('PROMO-')),
                'current_owner_id' => $this->auth->user()['id'],
                'original_owner_id' => $this->auth->user()['id'],
                'status' => 'claimed',
                'claimed_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->success($voucher, 'Voucher claimed successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to claim voucher: ' . $e->getMessage());
        }
    }

    public function transfer() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['voucher_id', 'to_user_id']);
        if ($validation) return $validation;

        try {
            $voucher = PmVouchers::find($data['voucher_id']);
            if (!$voucher) {
                return $this->notFound('Voucher not found');
            }

            if ($voucher->current_owner_id != $this->auth->user()['id']) {
                return $this->forbidden('You do not own this voucher');
            }

            $voucher->current_owner_id = $data['to_user_id'];
            $voucher->save();

            PmTransfers::create([
                'voucher_id' => $voucher->id,
                'from_user_id' => $this->auth->user()['id'],
                'to_user_id' => $data['to_user_id'],
                'transfer_type' => 'transfer',
                'notes' => 'Voucher transferred by user',
            ]);

            return $this->success($voucher, 'Voucher transferred successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to transfer voucher: ' . $e->getMessage());
        }
    }

    public function use() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['voucher_id']);
        if ($validation) return $validation;

        try {
            $voucher = PmVouchers::find($data['voucher_id']);
            if (!$voucher) {
                return $this->notFound('Voucher not found');
            }

            if ($voucher->current_owner_id != $this->auth->user()['id']) {
                return $this->forbidden('You do not own this voucher');
            }

            if ($voucher->status == 'used') {
                return $this->badRequest('Voucher has already been used');
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
                    'user_id' => $this->auth->user()['id'],
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'discount_amount' => $this->calculateDiscount($campaign, $data['transaction_amount'] ?? 0),
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null
                ]);

                DB::commit();
                return $this->success($voucher, 'Voucher used successfully');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->serverError('Failed to use voucher: ' . $e->getMessage());
        }
    }

    public function userVouchers() {
        $this->auth->authenticate();
        try {
            $vouchers = PmVouchers::where('current_owner_id', $this->auth->user()['id'])->get();
            return $this->success($vouchers);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch user vouchers: ' . $e->getMessage());
        }
    }

    public function userVoucherHistory() {
        $this->auth->authenticate();
        try {
            $vouchers = PmVouchers::where('original_owner_id', $this->auth->user()['id'])->get();
            return $this->success($vouchers);
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch user voucher history: ' . $e->getMessage());
        }
    }

    public function generateImageVoucher($id) {
        $this->auth->authenticate();
        try {
            $voucher = PmVouchers::find($id);
            if (!$voucher) {
                return $this->notFound('Voucher not found');
            }

            $imagePath = 'path/to/generated/voucher/image/' . $voucher->voucher_code . '.png';
            
            return $this->success(['image_url' => $imagePath], 'Voucher image generated successfully');
        } catch (Exception $e) {
            return $this->serverError('Failed to generate voucher image: ' . $e->getMessage());
        }
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
                'type' => 'receive_promo_voucher',
                'user_id' => $userId,
                'timestamp' => $timestamp,
                'nonce' => $nonce
            ];

            $qrCode = base64_encode(json_encode($qrData));
            
            return $this->success([
                'qr_code' => $qrCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ], 'QR code generated successfully for receiving promo vouchers');

        } catch (Exception $e) {
            return $this->serverError('Failed to generate receive QR code: ' . $e->getMessage());
        }
    }

    public function generateRedeemQR() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, ['voucher_id']);
        if ($validation) return $validation;

        try {
            $voucherId = $data['voucher_id'];
            $timestamp = time();
            $nonce = uniqid();
            
            $qrData = [
                'type' => 'redeem_promo_voucher',
                'voucher_id' => $voucherId,
                'timestamp' => $timestamp,
                'nonce' => $nonce
            ];

            $qrCode = base64_encode(json_encode($qrData));
            
            return $this->success([
                'qr_code' => $qrCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ], 'QR code generated successfully for promo voucher redemption');

        } catch (Exception $e) {
            return $this->serverError('Failed to generate redeem QR code: ' . $e->getMessage());
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
}