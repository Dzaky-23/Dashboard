<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lb1Penta extends Model
{
    use HasFactory;

    protected $table = 'lb1_penta';

    protected $primaryKey = 'id_lb1';

    protected $fillable = [
        'tanggal',
        'nik',
        'kpusk',
        'no_reg',
        'diagnosa',
        'status',
        'kdesa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class, 'kpusk', 'kode_p');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'kdesa', 'kode');
    }

    public function referensiIcd(): BelongsTo
    {
        return $this->belongsTo(BpjsRefIcd::class, 'diagnosa', 'kdDiag');
    }
}
