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
        Pasien::query()
            ->select(['id', 'no_reg', 'kpusk'])
            ->chunkById(500, function ($pasiens): void {
                foreach ($pasiens as $pasien) {
                    RekamMedis::factory(rand(30, 40))->create([
                        'no_reg' => $pasien->no_reg,
                        'kpusk' => $pasien->kpusk,
                    ]);
                }
            });
    }
}
