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
        // Drop old table
        Schema::dropIfExists('rekap_penyakit_top');

        Schema::create('rekap_harian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->string('kode_puskesmas', 255);
            $table->string('kode_penyakit', 255);
            $table->integer('jumlah_kasus')->unsigned();
            $table->timestamps();

            // Indices
            $table->unique(['tanggal', 'kode_puskesmas', 'kode_penyakit'], 'rekap_harian_unique');
            $table->index(['tanggal', 'kode_puskesmas'], 'rekap_harian_tgl_pusk_idx');
            $table->index(['tanggal', 'kode_penyakit'], 'rekap_harian_tgl_penyakit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_harian');
    }
};
