<?php

use App\Models\Lb1Penta;
use App\Models\Lb1PentaClean;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('filters and cleans data from lb1_penta to lb1_penta_clean', function () {
    // 1. Insert dirty data in lb1_penta
    DB::table('lb1_penta')->insert([
        // Valid row
        [
            'tanggal' => '2026-05-15',
            'nik' => '1234567890123456',
            'kpusk' => ' P001 ', // has spaces
            'no_reg' => 'REG001',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid NIK (too short)
        [
            'tanggal' => '2026-05-15',
            'nik' => '123456789012345',
            'kpusk' => 'P001',
            'no_reg' => 'REG002',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid NIK (too long)
        [
            'tanggal' => '2026-05-15',
            'nik' => '12345678901234567',
            'kpusk' => 'P001',
            'no_reg' => 'REG003',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid Tanggal (null)
        [
            'tanggal' => null,
            'nik' => '1234567890123456',
            'kpusk' => 'P001',
            'no_reg' => 'REG004',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid Tanggal (before 2010)
        [
            'tanggal' => '2009-12-31',
            'nik' => '1234567890123456',
            'kpusk' => 'P001',
            'no_reg' => 'REG005',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid Kdesa (null)
        [
            'tanggal' => '2026-05-15',
            'nik' => '1234567890123456',
            'kpusk' => 'P001',
            'no_reg' => 'REG006',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        // Invalid Kdesa (empty string)
        [
            'tanggal' => '2026-05-15',
            'nik' => '1234567890123456',
            'kpusk' => 'P001',
            'no_reg' => 'REG007',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Run clean command
    $exitCode = Artisan::call('rekap:clean-penta', ['--all' => true]);
    expect($exitCode)->toBe(0);

    // Verify cleaning results
    $cleanRecords = Lb1PentaClean::all();
    expect($cleanRecords)->toHaveCount(1);

    $cleanRecord = $cleanRecords->first();
    expect($cleanRecord->tanggal->toDateString())->toBe('2026-05-15');
    expect($cleanRecord->nik)->toBe('1234567890123456');
    expect($cleanRecord->kpusk)->toBe('P001'); // spaces trimmed
    expect($cleanRecord->kdesa)->toBe('KD01');
});
