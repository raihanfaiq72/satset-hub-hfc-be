<?php

use Illuminate\Database\Eloquent\Model;

class PmTransfers extends Model
{
    protected $table = 'pm_transfers';
    public $timestamps = true;
    protected $fillable = [
        'voucher_id',
        'from_user_id',
        'to_user_id',
        'transfer_type',
        'notes',
    ];
}
