<?php

class CustomerController extends BaseController {
    private $auth;
    
    public function __construct() {
        $this->auth = new AuthMiddleware();
    }
    
    public function index() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'user_id',
        ]);
        if ($validation) return $validation;
        $user_id = $data['user_id'];
        if (!$user_id) {
            return $this->validationError('user_id is required');
        }
        
        try {
            $customers = Customer::where('id', $user_id)->first();
            
            return $this->success($customers);
            
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch customers: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $this->auth->authenticate();
        $data = $this->getRequestData();

        $validation = $this->validateRequired($data, [
            'user_id',
            'namaCustomer',
            'email',
            'nickname',
            'info',
            'title',
            'status'
        ]);

        if ($validation) return $validation;
        $user_id = $data['user_id'];

        if (!$user_id) {
            return $this->validationError('user_id is required');
        }
        
        try {
            $customer = Customer::find($user_id);
            
            if (!$customer) {
                return $this->notFound('Customer not found');
            }

            $customer->namaCustomer = $data['namaCustomer'];
            $customer->email = $data['email'];
            $customer->nickname = $data['nickname'];
            $customer->info = $data['info'];
            $customer->title = $data['title'];
            $customer->status = $data['status'];
            
            if ($customer->save()) {
                return $this->success($customer, 'Customer updated successfully');
            } else {
                return $this->serverError('Failed to update customer');
            }
            
        } catch (Exception $e) {
            return $this->serverError('Failed to fetch customer: ' . $e->getMessage());
        }
    }

    public function requestUpdateOtp() {
        $this->auth->authenticate();
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['user_id', 'username', 'noHp']);
        if ($validation) return $validation;
        
        try {
            $user = Customer::find($data['user_id']);
            if (!$user) {
                return $this->notFound('Customer not found');
            }
            
            // Check if new username or phone number is already taken by someone else
            $existing = Customer::where('id', '!=', $user->id)
                ->where(function($query) use ($data) {
                    $query->where('username', $data['username'])
                          ->orWhere('noHp', $data['noHp']);
                })->first();
                
            if ($existing) {
                return $this->conflict('Username or phone number already in use by another account');
            }
            
            $otp = rand(100000, 999999);
            $user->otp = $otp;
            $user->otp_expires = time() + 300;
            $user->save();
            
            $message = "Kode OTP Anda untuk update profil: {$otp}. Kode ini berlaku selama 5 menit.";
            $waResult = WaHelper::sendMessage($user->noHp, $message, $_ENV['WA_API_KEY']);
            
            if (!$waResult['success']) {
                return $this->serverError('Failed to send OTP via WhatsApp: ' . $waResult['message']);
            }
            
            return $this->success(null, 'OTP sent to your new phone number');
            
        } catch (Exception $e) {
            return $this->serverError('Failed to request OTP: ' . $e->getMessage());
        }
    }

    public function verifyUpdateOtp() {
        $this->auth->authenticate();
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['user_id', 'otp', 'username', 'noHp']);
        if ($validation) return $validation;
        
        try {
            $user = Customer::find($data['user_id']);
            if (!$user) {
                return $this->notFound('Customer not found');
            }
            
            if (!$user->otp || (string)$data['otp'] !== (string)$user->otp) {
                return $this->unauthorized('Invalid OTP');
            }
            
            if (time() > $user->otp_expires) {
                return $this->unauthorized('OTP has expired');
            }
            
            $existing = Customer::where('id', '!=', $user->id)
                ->where(function($query) use ($data) {
                    $query->where('username', $data['username'])
                          ->orWhere('noHp', $data['noHp']);
                })->first();
                
            if ($existing) {
                return $this->conflict('Username or phone number already in use by another account');
            }
            
            $user->username = $data['username'];
            $user->noHp = $data['noHp'];
            $user->otp = null;
            $user->otp_expires = null;
            $user->save();
            
            return $this->success($user, 'Profile updated successfully');
            
        } catch (Exception $e) {
            return $this->serverError('Failed to verify OTP: ' . $e->getMessage());
        }
    }
}