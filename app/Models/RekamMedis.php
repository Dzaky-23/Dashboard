<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekamMedis extends Model
{
    protected $table = 'rekam_medis';

    protected $fillable = [
        'tanggal',
        'kpusk',
        'no_reg',
        'kdSadar',
        'alergiMakan',
        'alergiUdara',
        'alergiObat',
        'alergiMakananSS',
        'alergiLingkunganSS',
        'alergiObatSS',
        'kdPrognosa',
        'respRate',
        'heartRate',
        'suhu',
        'bb',
        'tb',
        'sistole',
        'diastole',
        'lingkarPerut',
        'anamnesa',
        'fisik',
        'kode_penyakit',
        'status',
        'kode_obat',
        'jumlah',
        'dosis',
        'racikan',
        'kode_tindakan',
        'kode_tindakan_icd',
        'edukasi',
        'jenis_perawatan',
        'unit',
        'rujukan',
        'poli_rs',
        'cara_bayar',
        'kode_pemeriksa',
        'diisi_pada',
        'rekomendasi_diet',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diisi_pada' => 'datetime',
            'lingkarPerut' => 'integer',
        ];
    use HasFactory;

    protected $guarded = ['id'];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'no_reg', 'no_reg');
    }
}
