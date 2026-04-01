<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_penyakit_top', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->string('period_type', 20);
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->string('kpusk', 255)->nullable();
            $table->string('kode_kecamatan', 255)->nullable();
            $table->unsignedSmallInteger('rank');
            $table->string('kode_penyakit', 255);
            $table->string('nama_penyakit', 255)->nullable();
            $table->unsignedInteger('jumlah_kasus');
            $table->unsignedInteger('total_kasus');
            $table->timestamps();

            $table->index(['scope', 'period_type']);
            $table->index(['scope', 'period_type', 'year', 'month', 'quarter', 'semester'], 'rekap_penyakit_top_period_idx');
            $table->index(['scope', 'kpusk', 'period_type', 'year', 'month'], 'rekap_penyakit_top_pusk_idx');
            $table->index(['scope', 'kode_kecamatan', 'period_type', 'year', 'month'], 'rekap_penyakit_top_kec_idx');
            $table->index(['scope', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_penyakit_top');
    }
};
