<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'kpusk',
        'no_reg',
        'nik',
        'sapaan',
        'nik_ibu',
        'nama',
        'kk',
        'ibu',
        'rt_rw',
        'kdesa',
        'jalan',
        'domisili',
        'telp',
        't_lahir',
        'tg_lahir',
        'jkl',
        'gd',
        'status',
        'cara_bayar',
        'no_asn',
        'jenis_bpjs',
        'pekerjaan',
        'berat',
        'prolanis',
        'alergi',
        'catatan',
        'submited_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tg_lahir' => 'date',
            'submited_at' => 'datetime',
            'berat' => 'integer',
        ];
    }
}
