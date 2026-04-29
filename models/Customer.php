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

    protected static function boot() {
        parent::boot();
        
        static::saving(function ($model) {
            if (empty($model->idCustomer) || !preg_match('/^C\d{4}-\d{3}$/', $model->idCustomer)) {
                $date = $model->tglRegister ? date('ym', strtotime($model->tglRegister)) : date('ym');
                $model->idCustomer = self::generateIdCustomer($date);
            }
        });
    }
    
    public static function generateIdCustomer($date = null) {
        if (!$date) $date = date('ym');
        $prefix = "C" . $date;
        
        $lastCustomer = self::where('idCustomer', 'LIKE', $prefix . '-%')
            ->orderBy('idCustomer', 'DESC')
            ->first();

        if ($lastCustomer) {
            $lastCode = $lastCustomer->idCustomer;
            $lastNumber = (int)substr($lastCode, -3);
            $count = $lastNumber + 1;
        } else {
            $count = 1;
        }

        return $prefix . "-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
}