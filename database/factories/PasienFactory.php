<?php

namespace Database\Factories;

use App\Models\Pasien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pasien>
 */
class PasienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['Baru', 'Lama']);
        $cara_bayar = $this->faker->randomElement(['Umum', 'BPJS']);
        
        return [
            'tanggal' => $this->faker->dateTimeBetween('2024-01-01', '2026-02-28')->format('Y-m-d'),
            'kpusk' => $this->faker->randomElement(array_keys(\App\Services\RecapLogicService::MAPPING_KECAMATAN)),
            'no_reg' => date('Ymd') . $this->faker->unique()->numberBetween(1000, 9999),
            'nik' => $this->faker->numerify('################'),
            'sapaan' => $this->faker->title(),
            'nik_ibu' => $this->faker->numerify('################'),
            'nama' => $this->faker->name(),
            'kk' => $this->faker->numerify('################'),
            'ibu' => $this->faker->name('female'),
            'rt_rw' => $this->faker->numberBetween(1, 10) . '/' . $this->faker->numberBetween(1, 10),
            'kdesa' => 'Desa ' . $this->faker->city(),
            'jalan' => $this->faker->streetAddress(),
            'domisili' => $this->faker->address(),
            'telp' => $this->faker->phoneNumber(),
            't_lahir' => $this->faker->city(),
            'tg_lahir' => $this->faker->dateTimeBetween('-60 years', '-5 years')->format('Y-m-d'),
            'jkl' => $this->faker->randomElement(['L', 'P']),
            'gd' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'status' => $status,
            'cara_bayar' => $cara_bayar,
            'no_asn' => $cara_bayar == 'BPJS' ? $this->faker->numerify('##################') : null,
            'jenis_bpjs' => $cara_bayar == 'BPJS' ? $this->faker->randomElement(['PBI', 'Non-PBI', 'Mandiri Kelas 1', 'Mandiri Kelas 2', 'Mandiri Kelas 3']) : null,
            'pekerjaan' => $this->faker->jobTitle(),
            'berat' => $this->faker->numberBetween(2000, 4500), // in grams
            'prolanis' => $this->faker->boolean(20) ? 'Ya' : null, // 20% chance of being prolanis
            'alergi' => $this->faker->boolean(30) ? $this->faker->randomElement(['Amoxicillin', 'Paracetamol', 'Seafood', 'Kacang', 'Debu']) : null,
            'catatan' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'submited_at' => $this->faker->dateTimeBetween('2024-01-01', '2026-02-28'),
        ];
    }
}
