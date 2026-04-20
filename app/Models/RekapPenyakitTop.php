<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapPenyakitTop extends Model
{
    protected $table = 'rekap_penyakit_top';

    protected $fillable = [
        'scope',
        'period_type',
        'year',
        'month',
        'quarter',
        'semester',
        'kpusk',
        'kode_kecamatan',
        'rank',
        'kode_penyakit',
        'nama_penyakit',
        'jumlah_kasus',
    ];
}
