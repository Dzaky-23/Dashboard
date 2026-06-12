<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puskesmas extends Model
{
    protected $table = 'puskesmas';

    protected $primaryKey = 'kode_p';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'kode_kc',
        'kode_p',
        'url',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kode_kc', 'kode_kc');
    }

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class, 'kode_p', 'kode_p');
    }
}
