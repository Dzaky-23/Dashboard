<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pasien;
use App\Models\RekamMedis;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Admin 2',
            'email' => 'admin2@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        Pasien::factory(50)->create()->each(function ($pasien) {
            RekamMedis::factory(rand(1, 4))->create([
                'no_reg' => $pasien->no_reg,
                'kpusk' => $pasien->kpusk
            ]);
        });
    }
}
