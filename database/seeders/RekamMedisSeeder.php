<?php

namespace Database\Seeders;

use App\Models\Pasien;
use App\Models\RekamMedis;
use Illuminate\Database\Seeder;

class RekamMedisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pasien::query()->each(function (Pasien $pasien): void {
            RekamMedis::factory(rand(1, 4))->create([
                'no_reg' => $pasien->no_reg,
                'kpusk' => $pasien->kpusk,
            ]);
        });
    }
}
