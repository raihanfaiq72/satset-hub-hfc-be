<?php

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $table = 'tb_inquiry';
    public $timestamps = false;
    protected $fillable = [
        'kodeInquiry',
        'idCustomer',
        'idLokasi',
        'idLayanan',
        'idSubLayanan',
        'status',
        'tax_type',
        'tglSurvey',
        'approval',
        'tglInput',
        'tglStatus',
        'assign',
        'payment_method',
        'tracking_token',
        'office_id',
        'voucher_type',
        'voucher_code',
        'voucher_discount_amount',
    ];

    protected $casts = [
        'tglSurvey' => 'datetime',
        'tglInput' => 'datetime',
        'tglStatus' => 'datetime',
        'voucher_discount_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'idCustomer');
    }

    public function layanan()
    {
        return $this->belongsTo(Layanan::class, 'idLayanan');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'idInquiry');
    }

    public function logs()
    {
        return $this->hasMany(LogInquiry::class, 'idInquiry')->orderBy('tgl', 'desc');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'idLokasi');
    }
}
