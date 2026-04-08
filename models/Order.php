<?php

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'tb_order';
    public $timestamps = false;
    protected $fillable = [
        'idCustomer',
        'idInquiry',
        'status',
        'idLayanan',
        'tglPekerjaan',
        'tglStatus',
        'tglOrder',
        'idMitra',
        'idSubLayanan',
        'idLokasi',
        'payment_method',
        'reminder_settings',
        'proof_of_payment'
    ];
    
    protected $casts = [
        'reminder_settings' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'idCustomer');
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class, 'idInquiry');
    }

    public function layanan()
    {
        return $this->belongsTo(Layanan::class, 'idLayanan');
    }

    public function subLayanan()
    {
        return $this->belongsTo(Layanan::class, 'idSubLayanan');
    }

}
