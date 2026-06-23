<?php

namespace Database\Factories;

use App\Models\Lb1Penta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lb1Penta>
 */
class Lb1PentaFactory extends Factory
{
    protected $model = Lb1Penta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $kpuskCodes = null;
        static $icdCodes = null;
        static $kdesaCodes = null;

        if ($kpuskCodes === null) {
            $kpuskCodes = DB::table('puskesmas')->pluck('kode_p')->filter()->all();
        }

        if ($icdCodes === null) {
            $icdCodes = DB::table('bpjs_ref_icd')
                ->whereNotNull('kdDiag')
                ->where('kdDiag', '!=', '')
                ->inRandomOrder()
                ->limit(100)
                ->pluck('kdDiag')
                ->all();
        }

        if ($kdesaCodes === null) {
            $kdesaCodes = DB::table('desa')->pluck('kode')->filter()->all();
        }

        $kpusk = !empty($kpuskCodes) ? $this->faker->randomElement($kpuskCodes) : 'P01';
        $diagnosa = !empty($icdCodes) ? $this->faker->randomElement($icdCodes) : 'A00';
        $kdesa = !empty($kdesaCodes) ? $this->faker->randomElement($kdesaCodes) : 'KD01';

        return [
            'tanggal' => $this->faker->dateTimeBetween('2024-01-01', '2026-05-31')->format('Y-m-d'),
            'nik' => $this->faker->numerify('################'),
            'kpusk' => $kpusk,
            'no_reg' => date('Ymd') . $this->faker->unique()->numberBetween(10000, 99999),
            'diagnosa' => $diagnosa,
            'status' => $this->faker->randomElement(['Baru', 'Lama']),
            'kdesa' => $kdesa,
        ];
    }
}
