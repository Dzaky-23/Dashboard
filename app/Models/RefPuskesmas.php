<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefPuskesmas extends Model
{
    protected $table = 'ref_puskesmas';
    
    protected $primaryKey = 'kode_puskesmas';
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    public $timestamps = false;
    
    protected $fillable = [
        'kode_puskesmas',
        'puskesmas',
        'kode_kecamatan',
        'kodePuskesmas'
    ];
}
