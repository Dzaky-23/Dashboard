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
        Schema::table('rekap_harian', function (Blueprint $table) {
            $table->string('kode_puskesmas', 255)->change();
            $table->string('kode_penyakit', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_harian', function (Blueprint $table) {
            $table->string('kode_puskesmas', 50)->change();
            $table->string('kode_penyakit', 50)->change();
        });
    }
};
