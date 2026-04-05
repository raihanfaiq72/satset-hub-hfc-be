<?php

class AuthController extends BaseController {
    private $jwt;
     
    public function __construct() {
        $this->jwt = new JWT();
    }
    
    public function register() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, [
            'noHp',
            'username',
            'password'
        ]);

        if ($validation) return $validation;
        
        if (!preg_match('/^(\\+62|62|0)8[1-9][0-9]{6,10}$/', $data['noHp'])) {
            return $this->validationError('Invalid phone number format');
        }
        
        try {
            $existingUser = Customer::where('noHp', $data['noHp'])->where('username', $data['username'])->first();
            if ($existingUser) {
                return $this->conflict('Phone number or username already registered');
            }
            
            $user = new Customer();
            $user->username = $data['username'];
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->namaCustomer = $data['namaCustomer'] ?? '-';
            $user->idCustomer = $data['idCustomer'] ?? 'default';
            $user->tglRegister = date('Y-m-d H:i:s');
            $user->noHp = $data['noHp'];
            $user->save();
            
            $token = $this->jwt->generateToken([
                'id' => $user->id,
                'noHp' => $user->noHp,
                'exp' => time() + 86400
            ]);
            
            return $this->created([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'namaCustomer' => '-',
                    'noHp' => $user->noHp,
                    'created_at' => $user->created_at
                ],
                'token' => $token
            ], 'User registered successfully');
            
        } catch (Exception $e) {
            return $this->serverError('Registration failed: ' . $e->getMessage());
        }
    }
    
    public function login() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, [
            'username',
            'password'
        ]);
        if ($validation) return $validation;
        
        try {
            $user = Customer::where('username', $data['username'])->first();
            
            if (!$user || !$user->verifyPassword($data['password'])) {
                return $this->unauthorized('Invalid username or password');
            }
            
            $token = $this->jwt->generateToken([
                'id' => $user->id,
                'noHp' => $user->noHp,
                'exp' => time() + 86400
            ]);
            
            return $this->success([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'noHp' => $user->noHp,
                    'created_at' => $user->created_at
                ],
                'token' => $token
            ], 'Login successful');
            
        } catch (Exception $e) {
            return $this->serverError('Login failed: ' . $e->getMessage());
        }
    }

    public function otpForgotPassword() 
    {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['noHp']);
        if ($validation) return $validation;
        
        try {
            $user = Customer::where('noHp', $data['noHp'])->first();
            
            if (!$user) {
                return $this->notFound('Phone number not registered');
            }
            
            $otp = rand(100000, 999999);
            
            $user->otp = $otp;
            $user->otp_expires = time() + 300; 
            $user->save();
            
            
            return $this->success([
                'otp_debug' => $otp 
            ], 'OTP sent to registered phone number');
            
        } catch (Exception $e) {
            return $this->serverError('Failed to send OTP: ' . $e->getMessage());
        }
    }

    public function verifyOtp() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['noHp', 'otp']);
        if ($validation) return $validation;
        
        try {
            $user = Customer::where('noHp', $data['noHp'])->first();
            
            if (!$user) {
                return $this->notFound('Phone number not registered');
            }
            
            if (!$user->otp) {
                return $this->unauthorized('OTP not found, request new OTP');
            }
            
            if ((string)$data['otp'] !== (string)$user->otp) {
                return $this->unauthorized('Invalid OTP');
            }
            
            if (time() > $user->otp_expires) {
                return $this->unauthorized('OTP has expired');
            }
            
            $user->otp = null;
            $user->otp_expires = null;
            $user->save();
            
            return $this->success(null, 'OTP verified successfully');
            
        } catch (Exception $e) {
            return $this->serverError('Failed to verify OTP: ' . $e->getMessage());
        }
    }

    // ini untuk self auth di web lain kalau mau akses API ini, cukup masukan password dan dicocokan dengan tb_karyawan. karena di web itu nanti akan kirim username jadi kita tinggal cocokan password untuk mendapatkan token akses
    public function selfAuth() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['password']);
        if ($validation) return $validation;
        
        try {
            $karyawan = Karyawan::where('password', $data['password'])->first();
            
            if (!$karyawan) {
                return $this->unauthorized('Invalid password');
            }
            
            $token = $this->jwt->generateToken([
                'id' => $karyawan->id,
                'namaKaryawan' => $karyawan->namaKaryawan,
                'exp' => time() + 86400
            ]);
            
            return $this->success([
                'karyawan' => [
                    'id' => $karyawan->id,
                    'namaKaryawan' => $karyawan->namaKaryawan,
                    'created_at' => $karyawan->created_at
                ],
                'token' => $token
            ], 'Authentication successful');
            
        } catch (Exception $e) {
            return $this->serverError('Authentication failed: ' . $e->getMessage());
        }
    }

}