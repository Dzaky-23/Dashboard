<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BpjsRefIcd extends Model
{
    protected $table = 'bpjs_ref_icd';
    
    // Asumsikan id_icd adalah primary key bawaan Laravel,
    // Jika kdDiag yang digunakan secara ekstensif untuk relasi (FK):
    protected $primaryKey = 'id_icd';
    
    public $timestamps = false;
    
    protected $fillable = [
        'kdDiag',
        'nmDiag',
        'nonSpesialis',
        'last_update'
    ];
}
