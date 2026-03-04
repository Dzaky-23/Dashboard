<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected $guarded = ['id'];
    
    // Disable timestamps if not using created_at/updated_at, but we have submited_at
    public $timestamps = false;

    public function rekamMedis()
    {
        return $this->hasMany(RekamMedis::class, 'no_reg', 'no_reg')->orderBy('tanggal', 'desc');
    }
}
