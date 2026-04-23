<?php

use Illuminate\Database\Eloquent\Model;

class PvTransfers extends Model {
    protected $table = 'pv_transfers';
    protected $fillable = [
        'id',
        'voucher_id',
        'from_user_id',
        'to_user_id',
        'transfer_type',
        'reference_id',
        'notes'
    ];
    
    public $timestamps = true;
    
}