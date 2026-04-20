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
        Schema::table('rekap_penyakit_top', function (Blueprint $table) {
            if (Schema::hasColumn('rekap_penyakit_top', 'total_kasus')) {
                $table->dropColumn('total_kasus');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_penyakit_top', function (Blueprint $table) {
            if (!Schema::hasColumn('rekap_penyakit_top', 'total_kasus')) {
                $table->unsignedInteger('total_kasus')->default(0)->after('jumlah_kasus');
            }
        });
    }
};
