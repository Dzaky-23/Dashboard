<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JobStatus extends Model
{
    use HasUuids;

    protected $table = 'job_statuses';

    protected $fillable = [
        'id',
        'type',
        'status',
        'payload',
        'output_path',
        'error',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
