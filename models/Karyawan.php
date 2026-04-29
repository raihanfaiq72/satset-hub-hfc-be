<?php

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model {
    protected $table = 'tb_karyawan';
    protected $fillable = [
        'Id',
        'Nama',
        'Password'
    ];
    
    public $timestamps = false;
    
}