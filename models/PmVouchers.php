<?php

use Illuminate\Database\Eloquent\Model;

class PmVouchers extends Model
{
    protected $table = 'pm_vouchers';
    public $timestamps = true;
    protected $fillable = [
        'campaign_id',
        'voucher_code',
        'current_owner_id',
        'original_owner_id',
        'status',
        'claimed_at',
        'used_at',
    ];
}
