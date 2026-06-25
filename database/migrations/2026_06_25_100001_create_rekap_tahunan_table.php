<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_tahunan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('tahun')->unsigned();
            $table->string('kode_kecamatan', 50)->nullable();
            $table->string('kode_puskesmas', 255);
            $table->string('kode_penyakit', 255);
            $table->integer('jumlah_kasus')->unsigned();
            $table->timestamps();

            // Unique Constraint untuk Upsert
            $table->unique(['tahun', 'kode_puskesmas', 'kode_penyakit'], 'rekap_tahunan_unique');

            // Indexes untuk optimasi query
            $table->index(['tahun', 'kode_penyakit'], 'rekap_tahunan_tahun_penyakit_idx');
            $table->index(['tahun', 'kode_puskesmas'], 'rekap_tahunan_tahun_pusk_idx');
            $table->index(['tahun', 'kode_kecamatan'], 'rekap_tahunan_tahun_kec_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_tahunan');
    }
};
