<?php

namespace Database\Seeders;

use App\Models\Lb1Penta;
use Illuminate\Database\Seeder;

class Lb1PentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lb1Penta::factory()->count(30000)->create();
    }
}
