<?php

use Illuminate\Database\Eloquent\Model;

class Customer extends Model {
    protected $table = 'tb_customer';
    protected $fillable = [
        'id',
        'namaCustomer',
        'idCustomer',
        'username',
        'tglRegister',
        'noHp',
        'email',
        'password',
        'nickname',
        'info',
        'title',
        'status',
        'referral_company',
        'referral_name',
        'referral_note',
        'referral_id'
    ];
    
    public $timestamps = false;
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
}