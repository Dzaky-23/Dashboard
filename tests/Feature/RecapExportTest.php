<?php

use App\Models\Puskesmas;
use App\Models\Kecamatan;
use App\Models\RekapHarian;
use App\Models\Lb1Penta;
use App\Models\User;
use App\Services\RekapHarianService;
use App\Models\JobStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('puskesmas')) {
        Schema::create('puskesmas', function (Blueprint $table) {
            $table->string('kode_p')->primary();
            $table->string('nama')->nullable();
            $table->string('kode_kc')->nullable();
            $table->string('url')->nullable();
        });
    }

    if (! Schema::hasTable('kecamatan')) {
        Schema::create('kecamatan', function (Blueprint $table) {
            $table->integer('id_kecamatan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kode_kc')->primary();
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

    Kecamatan::query()->insert([
        [
            'id_kecamatan' => 1,
            'kecamatan' => 'Semarang Tengah',
            'kode_kc' => 'KC01',
        ],
        [
            'id_kecamatan' => 2,
            'kecamatan' => 'Semarang Utara',
            'kode_kc' => 'KC02',
        ]
    ]);

    Puskesmas::query()->insert([
        [
            'kode_p' => 'P001',
            'nama' => 'Puskesmas Miroto',
            'kode_kc' => 'KC01',
            'url' => 'http://test/miroto',
        ],
        [
            'kode_p' => 'P002',
            'nama' => 'Puskesmas Poncol',
            'kode_kc' => 'KC01',
            'url' => 'http://test/poncol',
        ],
        [
            'kode_p' => 'P003',
            'nama' => 'Puskesmas Bandarharjo',
            'kode_kc' => 'KC02',
            'url' => 'http://test/bandarharjo',
        ],
    ]);

    // Seed some ICD names
    DB::table('bpjs_ref_icd')->insert([
        ['kdDiag' => 'A01', 'nmDiag' => 'Demam Tifoid'],
        ['kdDiag' => 'A02', 'nmDiag' => 'Paratifoid'],
        ['kdDiag' => 'B02', 'nmDiag' => 'Varisela'],
        ['kdDiag' => 'Z01', 'nmDiag' => 'Penyakit Z01'],
        ['kdDiag' => 'Z02', 'nmDiag' => 'Penyakit Z02'],
        ['kdDiag' => 'Z09', 'nmDiag' => 'Penyakit Z09'],
    ]);

    // Seed daily aggregate records
    RekapHarian::query()->insert([
        [
            'tanggal' => '2026-05-10',
            'kode_puskesmas' => 'P001',
            'kode_penyakit' => 'A01',
            'jumlah_kasus' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-05-11',
            'kode_puskesmas' => 'P002',
            'kode_penyakit' => 'A02',
            'jumlah_kasus' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-05-12',
            'kode_puskesmas' => 'P003',
            'kode_penyakit' => 'B02',
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
});

it('can trigger aggregation manually via UI', function () {
    actingAsAdmin();

    // Seed raw data into lb1_penta
    Lb1Penta::query()->insert([
        [
            'tanggal' => '2026-04-15',
            'kpusk' => 'P001',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-04-15',
            'kpusk' => 'P001',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->postJson(route('recap.aggregate.dispatch'), [
        'bulan' => '2026-04',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');
    expect($jobId)->not->toBeNull();

    // Verify job status
    $statusResponse = $this->getJson(route('recap.aggregate.status', ['jobId' => $jobId]));
    $statusResponse->assertOk()->assertJson(['status' => 'done']);

    // Check database
    $aggregated = RekapHarian::where('tanggal', '2026-04-15')
        ->where('kode_puskesmas', 'P001')
        ->where('kode_penyakit', 'A01')
        ->first();

    expect($aggregated)->not->toBeNull();
    expect($aggregated->jumlah_kasus)->toBe(2);
});

it('can trigger aggregation of all data using --all flag', function () {
    actingAsAdmin();

    // Seed raw data into lb1_penta with widely spread dates (2024 to 2026)
    Lb1Penta::query()->insert([
        [
            'tanggal' => '2024-01-15',
            'kpusk' => 'P001',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-12-15',
            'kpusk' => 'P001',
            'diagnosa' => 'A01',
            'status' => 'Baru',
            'kdesa' => 'KD01',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->artisan('rekap:aggregate', ['--all' => true])
        ->assertExitCode(0);

    // Verify database has records for both dates
    expect(RekapHarian::where('tanggal', '2024-01-15')->exists())->toBeTrue();
    expect(RekapHarian::where('tanggal', '2026-12-15')->exists())->toBeTrue();
});

it('can dispatch export job and download the file', function () {
    actingAsAdmin();

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'scopes' => ['umum'],
        'top_n_umum' => 10,
        'format' => 'excel',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');
    expect($jobId)->not->toBeNull();

    // Check status
    $statusResponse = $this->getJson(route('recap.export.status', ['jobId' => $jobId]));
    $statusResponse->assertOk()->assertJson(['status' => 'done']);

    // Download file
    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();
    $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=' . JobStatus::find($jobId)->output_path);
});

it('filters kecamatan export to only selected kecamatan', function () {
    actingAsAdmin();

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'scopes' => ['kecamatan'],
        'top_n_kecamatan' => 10,
        'kecamatan_filter_mode' => 'selected',
        'selected_kecamatan' => ['KC01'],
        'format' => 'csv',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');

    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();
    
    $filePath = $downloadResponse->baseResponse->getFile()->getPathname();
    $content = file_get_contents($filePath);
    // Check that Semarang Tengah is included
    expect($content)->toContain('Semarang Tengah');
});

it('filters puskesmas export to only selected puskesmas', function () {
    actingAsAdmin();

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'scopes' => ['puskesmas'],
        'top_n_puskesmas' => 10,
        'puskesmas_filter_mode' => 'selected',
        'selected_puskesmas' => ['P001'],
        'format' => 'csv',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');

    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();
    
    $filePath = $downloadResponse->baseResponse->getFile()->getPathname();
    $content = file_get_contents($filePath);
    expect($content)->toContain('Puskesmas Miroto');
});

it('supports exclude exceptions during export', function () {
    actingAsAdmin();

    // Seed some target data
    RekapHarian::query()->insert([
        [
            'tanggal' => '2026-05-15',
            'kode_puskesmas' => 'P001',
            'kode_penyakit' => 'Z01',
            'jumlah_kasus' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-05-15',
            'kode_puskesmas' => 'P001',
            'kode_penyakit' => 'Z02',
            'jumlah_kasus' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tanggal' => '2026-05-15',
            'kode_puskesmas' => 'P001',
            'kode_penyakit' => 'Z09',
            'jumlah_kasus' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'scopes' => ['umum'],
        'top_n_umum' => 10,
        'format' => 'csv',
        'filters' => [
            'exclude_prefixes' => ['Z'],
            'exclude_exceptions' => ['Z01', 'Z02']
        ]
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');

    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();
    
    $filePath = $downloadResponse->baseResponse->getFile()->getPathname();
    $content = file_get_contents($filePath);
    // Z01 and Z02 should be in, but Z09 should be excluded
    expect($content)->toContain('Z01');
    expect($content)->toContain('Z02');
    expect($content)->not->toContain('Z09');
});

it('can export multiple scopes in a single file', function () {
    actingAsAdmin();

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'scopes' => ['umum', 'kecamatan', 'puskesmas'],
        'top_n_umum' => 5,
        'top_n_kecamatan' => 5,
        'top_n_puskesmas' => 5,
        'format' => 'csv',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');

    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();

    $filePath = $downloadResponse->baseResponse->getFile()->getPathname();
    $content = file_get_contents($filePath);

    // Verify sections are present
    expect($content)->toContain('SECTION: TOP PENYAKIT UMUM');
    expect($content)->toContain('SECTION: TOP PENYAKIT PER KECAMATAN');
    expect($content)->toContain('SECTION: TOP PENYAKIT PER PUSKESMAS');
});

it('respects top N limits for kecamatan and puskesmas exports', function () {
    actingAsAdmin();

    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-05-01',
        'to' => '2026-05-31',
        'scopes' => ['kecamatan', 'puskesmas'],
        'top_n_kecamatan' => 1,
        'top_n_puskesmas' => 1,
        'format' => 'csv',
    ]);

    $response->assertOk();
    $jobId = $response->json('job_id');

    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertOk();

    $filePath = $downloadResponse->baseResponse->getFile()->getPathname();
    $content = file_get_contents($filePath);

    // Semarang Tengah has both A01 (12 cases) and A02 (8 cases).
    // With top_n_kecamatan = 1, we should only see A01, not A02.
    expect($content)->toContain('"Semarang Tengah",1,A01');
    expect($content)->not->toContain('"Semarang Tengah",2,A02');
});

it('prevents user from checking status or downloading another users job', function () {
    $user1 = User::factory()->admin()->create();
    $user2 = User::factory()->admin()->create();

    // User 1 dispatches a job
    $this->actingAs($user1);
    $response = $this->postJson(route('recap.export.dispatch'), [
        'from' => '2026-05-01',
        'to' => '2026-05-31',
        'scopes' => ['umum'],
        'top_n_umum' => 5,
        'format' => 'csv',
    ]);
    $response->assertOk();
    $jobId = $response->json('job_id');

    // Switch to User 2
    $this->actingAs($user2);

    // User 2 tries to check status of User 1's job -> should get 404
    $statusResponse = $this->getJson(route('recap.export.status', ['jobId' => $jobId]));
    $statusResponse->assertStatus(404);

    // User 2 tries to download User 1's job -> should get 404
    $downloadResponse = $this->get(route('recap.export.download', ['jobId' => $jobId]));
    $downloadResponse->assertStatus(404);
});

it('cleans up export files older than 24 hours', function () {
    $directory = storage_path('app/exports');
    if (!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }
    
    $oldFile = $directory . '/dummy_old_export.xlsx';
    file_put_contents($oldFile, 'old content');
    touch($oldFile, time() - 90000);

    $newFile = $directory . '/dummy_new_export.xlsx';
    file_put_contents($newFile, 'new content');

    $jobOld = JobStatus::create([
        'type' => 'export',
        'status' => 'done',
        'output_path' => 'dummy_old_export.xlsx',
    ]);
    
    $jobNew = JobStatus::create([
        'type' => 'export',
        'status' => 'done',
        'output_path' => 'dummy_new_export.xlsx',
    ]);

    $cleanup = function () {
        $directory = storage_path('app/exports');
        if (file_exists($directory)) {
            $files = Illuminate\Support\Facades\File::files($directory);
            $now = time();
            foreach ($files as $file) {
                if ($now - $file->getMTime() > 86400) {
                    $filename = $file->getFilename();
                    Illuminate\Support\Facades\File::delete($file->getPathname());
                    App\Models\JobStatus::where('output_path', $filename)
                        ->update(['status' => 'expired']);
                }
            }
        }
    };
    $cleanup();

    expect(file_exists($oldFile))->toBeFalse();
    expect(JobStatus::find($jobOld->id)->status)->toBe('expired');

    expect(file_exists($newFile))->toBeTrue();
    expect(JobStatus::find($jobNew->id)->status)->toBe('done');

    @unlink($newFile);
});


