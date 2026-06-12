<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    protected $primaryKey = 'kode_kc';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id_kecamatan',
        'kecamatan',
        'kode_kc',
    ];

    public function puskesmas(): HasMany
    {
        return $this->hasMany(Puskesmas::class, 'kode_kc', 'kode_kc');
    }
}
