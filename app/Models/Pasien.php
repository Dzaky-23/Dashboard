<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    // Disable timestamps if not using created_at/updated_at, but we have submited_at
    public $timestamps = false;

    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class, 'no_reg', 'no_reg')->orderBy('tanggal', 'desc');
    }
}
