<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = ['bpjs_ref_icd', 'puskesmas', 'kecamatan', 'desa'];

        // Disable foreign key checks temporarily during alterations
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if ($table === 'desa') {
                    // Convert desa to InnoDB first
                    DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
                }
                
                // Convert table character set and collation to align with Laravel defaults
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration required, keeping UTF-8 is correct for all environments
    }
};
