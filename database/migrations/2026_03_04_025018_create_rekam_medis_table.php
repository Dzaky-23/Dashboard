<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekam_medis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('kpusk')->nullable();
            $table->string('no_reg')->nullable();
            $table->string('kdSadar')->nullable();
            $table->string('alergiMakan')->nullable();
            $table->string('alergiUdara')->nullable();
            $table->string('alergiObat')->nullable();
            $table->string('alergiMakananSS')->nullable();
            $table->string('alergiLingkunganSS')->nullable();
            $table->string('alergiObatSS')->nullable();
            $table->string('kdPrognosa')->nullable();
            $table->string('respRate')->nullable();
            $table->string('heartRate')->nullable();
            $table->string('suhu')->nullable();
            $table->string('bb')->nullable();
            $table->string('tb')->nullable();
            $table->string('sistole')->nullable();
            $table->string('diastole')->nullable();
            $table->integer('lingkarPerut')->nullable();
            $table->text('anamnesa')->nullable();
            $table->text('fisik')->nullable();
            $table->string('kode_penyakit')->nullable();
            $table->string('status')->nullable()->default('Baru');
            $table->text('kode_obat')->nullable();
            $table->string('jumlah')->nullable();
            $table->string('dosis')->nullable();
            $table->string('racikan')->nullable();
            $table->string('kode_tindakan')->nullable();
            $table->string('kode_tindakan_icd')->nullable();
            $table->text('edukasi')->nullable();
            $table->string('jenis_perawatan')->nullable();
            $table->string('unit')->nullable();
            $table->string('rujukan')->nullable();
            $table->text('poli_rs')->nullable();
            $table->string('cara_bayar')->nullable();
            $table->string('kode_pemeriksa')->nullable();
            $table->timestamp('diisi_pada')->useCurrent();
            $table->longText('rekomendasi_diet')->nullable();
            $table->timestamps();

            $table->index(['no_reg', 'kpusk']);
            $table->index('kode_penyakit');
            $table->index('status');
            $table->index('tanggal');
            $table->index('diisi_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekam_medis');
    }
};
