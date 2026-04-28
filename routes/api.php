<?php

use App\Http\Controllers\Api\RekamMedisController;
use Illuminate\Support\Facades\Route;

Route::get('/rekap-penyakit/tanggal', [RekamMedisController::class, 'rekapByTanggal']);
