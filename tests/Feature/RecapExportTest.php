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

    if (! Schema::hasTable('bpjs_ref_icd')) {
        Schema::create('bpjs_ref_icd', function (Blueprint $table) {
            $table->id('id_icd');
            $table->string('kdDiag')->nullable();
            $table->string('nmDiag')->nullable();
            $table->boolean('nonSpesialis')->nullable();
            $table->timestamp('last_update')->nullable();
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

it('returns an excel response with detail sheet for global export', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.export', [
        'format' => 'excel',
        'period_type' => 'year',
        'year' => 2026,
        'export_scope' => ['umum'],
        'top_n_umum' => 10,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    
    // Save response content to a temp file and load it with PhpSpreadsheet
    $tempFile = tempnam(sys_get_temp_dir(), 'excel_test');
    file_put_contents($tempFile, $response->getContent());

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($tempFile);
    
    // Check sheet names
    $sheetNames = $spreadsheet->getSheetNames();
    expect($sheetNames)->toContain('Worksheet');
    expect($sheetNames)->toContain('Detail Sebaran Penyakit');

    // Check content in detail sheet
    $detailSheet = $spreadsheet->getSheetByName('Detail Sebaran Penyakit');
    expect($detailSheet->getCell('A1')->getValue())->toContain('Demam Tifoid'); // First ranked disease in beforeEach seeding is A01 (Demam Tifoid)

    unlink($tempFile);
});

it('exports using custom date range without errors', function () {
    actingAsAdmin();

    $response = $this->get(route('recap.export', [
        'format' => 'excel',
        'period_type' => 'custom_date',
        'start_date' => '2024-01-15',
        'end_date' => '2024-11-25',
        'export_scope' => ['umum'],
        'top_n_umum' => 10,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('supports exclude exceptions during export', function () {
    actingAsAdmin();

    // Seed test data with codes starting with Z
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
            'kode_penyakit' => 'Z01',
            'nama_penyakit' => 'Penyakit Z01',
            'jumlah_kasus' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'global',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => '',
            'kode_kecamatan' => '',
            'kode_penyakit' => 'Z02',
            'nama_penyakit' => 'Penyakit Z02',
            'jumlah_kasus' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'scope' => 'global',
            'period_type' => 'year',
            'year' => 2026,
            'month' => 0,
            'quarter' => 0,
            'semester' => 0,
            'kpusk' => '',
            'kode_kecamatan' => '',
            'kode_penyakit' => 'Z09',
            'nama_penyakit' => 'Penyakit Z09',
            'jumlah_kasus' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Request export with exclude_prefixes = Z and exclude_exceptions = Z01, Z02
    $response = $this->get(route('recap.export', [
        'format' => 'excel',
        'period_type' => 'year',
        'year' => 2026,
        'export_scope' => ['umum'],
        'top_n_umum' => 10,
        'exclude_prefixes' => ['Z'],
        'exclude_exceptions' => 'Z01, Z02',
    ]));

    $response->assertOk();
    
    // Save response to temp file and parse with PhpSpreadsheet
    $tempFile = tempnam(sys_get_temp_dir(), 'excel_test');
    file_put_contents($tempFile, $response->getContent());

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($tempFile);
    $detailSheet = $spreadsheet->getSheetByName('Detail Sebaran Penyakit');
    
    // Check that we see Penyakit Z01 and Penyakit Z02 but NOT Penyakit Z09
    $content = '';
    foreach ($detailSheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $content .= ' ' . $cell->getValue();
        }
    }

    expect($content)->toContain('Penyakit Z01');
    expect($content)->toContain('Penyakit Z02');
    expect($content)->not->toContain('Penyakit Z09');

    unlink($tempFile);
});


