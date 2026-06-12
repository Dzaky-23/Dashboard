<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapHarian extends Model
{
    protected $table = 'rekap_harian';

    protected $fillable = [
        'tanggal',
        'kode_puskesmas',
        'kode_penyakit',
        'jumlah_kasus',
    ];

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class, 'kode_puskesmas', 'kode_p');
    }

    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(BpjsRefIcd::class, 'kode_penyakit', 'kdDiag');
    }
}
