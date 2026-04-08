<?php

use Illuminate\Database\Eloquent\Model;

class Offices extends Model {
    protected $table = 'offices';
    protected $fillable = [
        'id',
        'name',
        'tax_type',
        'address',
        'npwp',
        'qris_image_path',
        'allow_cash',
    ];
    
    public $timestamps = true;
    
}