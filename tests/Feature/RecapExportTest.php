<?php

use App\Models\RefPuskesmas;
use App\Models\RekapPenyakitTop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('ref_puskesmas')) {
        Schema::create('ref_puskesmas', function (Blueprint $table) {
            $table->string('kode_puskesmas')->primary();
            $table->string('puskesmas')->nullable();
            $table->string('kode_kecamatan')->nullable();
            $table->string('kodePuskesmas')->nullable();
        });
    }

    RefPuskesmas::query()->insert([
        [
            'kode_puskesmas' => 'P001',
            'puskesmas' => 'Puskesmas Miroto',
            'kode_kecamatan' => 'KC01',
            'kodePuskesmas' => 'P001',
        ],
        [
            'kode_puskesmas' => 'P002',
            'puskesmas' => 'Puskesmas Poncol',
            'kode_kecamatan' => 'KC01',
            'kodePuskesmas' => 'P002',
        ],
        [
            'kode_puskesmas' => 'P003',
            'puskesmas' => 'Puskesmas Bandarharjo',
            'kode_kecamatan' => 'KC02',
            'kodePuskesmas' => 'P003',
        ],
    ]);

    RekapPenyakitTop::query()->insert([
        [
            'scope' => 'global',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => '',
            'kode_kecamatan' => '',
            'kode_penyakit' => 'A01',
            'nama_penyakit' => 'Demam Tifoid',
            'jumlah_kasus' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'kecamatan',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => '',
            'kode_kecamatan' => 'KC01',
            'kode_penyakit' => 'A01',
            'nama_penyakit' => 'Demam Tifoid',
            'jumlah_kasus' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'kecamatan',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => '',
            'kode_kecamatan' => 'KC02',
            'kode_penyakit' => 'B02',
            'nama_penyakit' => 'Varisela',
            'jumlah_kasus' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'puskesmas',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => 'P001',
            'kode_kecamatan' => 'KC01',
            'kode_penyakit' => 'A01',
            'nama_penyakit' => 'Demam Tifoid',
            'jumlah_kasus' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'puskesmas',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => 'P002',
            'kode_kecamatan' => 'KC01',
            'kode_penyakit' => 'A02',
            'nama_penyakit' => 'Paratifoid',
            'jumlah_kasus' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'puskesmas',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => 'P003',
            'kode_kecamatan' => 'KC02',
            'kode_penyakit' => 'B02',
            'nama_penyakit' => 'Varisela',
            'jumlah_kasus' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

function actingAsAdmin(): User
{
    $user = User::factory()->admin()->create();
    test()->actingAs($user);

    return $user;
}

it('renders the recap index page for admin', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.index'));

    $response->assertOk();
    $response->assertSee('Daftar Rekapitulasi Wilayah');
    $response->assertSee('Filter Wilayah Export');
});

it('filters kecamatan export to only selected kecamatan', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.export', [
        'format' => 'pdf',
        'period_type' => 'year',
        'year' => 2026,
        'export_scope' => ['kecamatan'],
        'top_n_kecamatan' => 10,
        'kecamatan_filter_mode' => 'selected',
        'selected_kecamatan' => ['KC01'],
    ]));

    $response->assertOk();
    $response->assertSee('Kec. SEMARANG TENGAH');
    $response->assertDontSee('Kec. SEMARANG UTARA');
});

it('filters puskesmas export to only selected puskesmas', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.export', [
        'format' => 'pdf',
        'period_type' => 'year',
        'year' => 2026,
        'export_scope' => ['puskesmas'],
        'top_n_puskesmas' => 10,
        'puskesmas_filter_mode' => 'selected',
        'selected_puskesmas' => ['P001'],
    ]));

    $response->assertOk();
    $response->assertSee('Pkm. Puskesmas Miroto');
    $response->assertDontSee('Pkm. Puskesmas Poncol');
    $response->assertDontSee('Pkm. Puskesmas Bandarharjo');
});

it('returns an excel response for selected puskesmas export', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.export', [
        'format' => 'excel',
        'period_type' => 'year',
        'year' => 2026,
        'export_scope' => ['puskesmas'],
        'top_n_puskesmas' => 10,
        'puskesmas_filter_mode' => 'selected',
        'selected_puskesmas' => ['P001'],
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))->toContain('.xlsx');
});
