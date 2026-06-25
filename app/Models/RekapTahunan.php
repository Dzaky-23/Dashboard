<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapTahunan extends Model
{
    protected $table = 'rekap_tahunan';

    protected $fillable = [
        'tahun',
        'kode_kecamatan',
        'kode_puskesmas',
        'kode_penyakit',
        'jumlah_kasus',
    ];

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class, 'kode_puskesmas', 'kode_p');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kecamatan', 'kode_kc');
    }

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(BpjsRefIcd::class, 'kode_penyakit', 'kdDiag');
    }
}
