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
            $existingUser = Customer::where('noHp', $data['noHp'])->first();
            if ($existingUser) {
                return $this->conflict('Phone number already registered');
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
}