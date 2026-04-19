<?php

use Illuminate\Database\Eloquent\Model;

class PmRedemptions extends Model
{
    protected $table = 'pm_redemptions';
    public $timestamps = true;
    protected $fillable = [
        'voucher_id',
        'user_id',
        'transaction_id',
        'discount_amount',
        'lat',
        'lang',
    ];
}
