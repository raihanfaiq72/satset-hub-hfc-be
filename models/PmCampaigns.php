<?php

use Illuminate\Database\Eloquent\Model;

class PmCampaigns extends Model
{
    protected $table = 'pm_campaigns';
    public $timestamps = true;
    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_transaction',
        'quota',
        'per_user_limit',
        'valid_from',
        'valid_until',
        'lat',
        'lang',
        'radius_meter',
        'is_active',
    ];
}
