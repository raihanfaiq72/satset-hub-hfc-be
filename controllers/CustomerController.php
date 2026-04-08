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
}