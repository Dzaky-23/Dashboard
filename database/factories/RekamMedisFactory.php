<?php

namespace Database\Factories;

use App\Models\RekamMedis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RekamMedis>
 */
class RekamMedisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['Baru', 'Lama']);
        
        return [
            'tanggal' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            // 'no_reg' and 'kpusk' will be overridden in seeder
            'kdSadar' => $this->faker->randomElement(['Compos Mentis', 'Apatis', 'Somnolen', 'Sopor', 'Koma']),
            'kdPrognosa' => $this->faker->randomElement(['Sanam', 'Bonam', 'Malam', 'Dubia']),
            'respRate' => $this->faker->numberBetween(16, 24),
            'heartRate' => $this->faker->numberBetween(60, 100),
            'suhu' => $this->faker->randomFloat(1, 36, 39),
            'bb' => $this->faker->numberBetween(45, 90),
            'tb' => $this->faker->numberBetween(150, 180),
            'sistole' => $this->faker->numberBetween(100, 140),
            'diastole' => $this->faker->numberBetween(70, 90),
            'anamnesa' => $this->faker->paragraph(2),
            'fisik' => $this->faker->paragraph(1),
            'kode_penyakit' => $this->faker->randomElement(['J00', 'A09', 'E11', 'I10', 'K30']),
            'status' => $status,
            'kode_obat' => 'OBT-' . $this->faker->numberBetween(100, 999),
            'jumlah' => $this->faker->numberBetween(10, 30),
            'dosis' => $this->faker->randomElement(['3x1', '2x1', '1x1']),
            'edukasi' => $this->faker->sentence(),
            'diisi_pada' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
