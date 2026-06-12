<?php

use App\Http\Controllers\Api\RekapPenyakitController;
use Illuminate\Support\Facades\Route;

Route::get('/rekap-penyakit/tanggal', [RekapPenyakitController::class, 'rekapByTanggal']);
