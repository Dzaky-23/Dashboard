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
        Schema::dropIfExists('rekap_penyakit_top');

        Schema::create('rekap_penyakit_top', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->string('period_type', 20);
            $table->unsignedSmallInteger('year')->default(0);
            $table->unsignedTinyInteger('month')->default(0);
            $table->unsignedTinyInteger('quarter')->default(0);
            $table->unsignedTinyInteger('semester')->default(0);
            $table->string('kpusk', 50)->default('');
            $table->string('kode_kecamatan', 50)->default('');
            $table->string('kode_penyakit', 50);
            $table->string('nama_penyakit', 255)->nullable();
            $table->unsignedInteger('jumlah_kasus');
            $table->unsignedInteger('total_kasus')->default(0);
            $table->timestamps();

            $table->unique(
                ['scope', 'period_type', 'year', 'month', 'quarter', 'semester', 'kpusk', 'kode_kecamatan', 'kode_penyakit'],
                'rekap_penyakit_top_unique'
            );

            $table->index(['scope', 'period_type']);
            $table->index(['scope', 'period_type', 'year', 'month', 'quarter', 'semester'], 'rekap_penyakit_top_period_idx');
            $table->index(['scope', 'kpusk', 'period_type', 'year', 'month'], 'rekap_penyakit_top_pusk_idx');
            $table->index(['scope', 'kode_kecamatan', 'period_type', 'year', 'month'], 'rekap_penyakit_top_kec_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For down, we don't recreate the old completely, but just drop it if needed.
        // It's mostly a one-way migration for architecture change. 
        // We'll leave down empty or drop if exists.
        Schema::dropIfExists('rekap_penyakit_top');
    }
};
