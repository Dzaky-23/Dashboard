<?php

namespace Database\Factories;

use App\Models\BpjsRefIcd;
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
        static $icdCodes = null;
        if ($icdCodes === null) {
            $icdCodes = BpjsRefIcd::query()
                ->whereNotNull('kdDiag')
                ->where('kdDiag', '!=', '')
                ->inRandomOrder()
                ->limit(100)
                ->pluck('kdDiag')
                ->values()
                ->all();
        }
        $kodePenyakit = !empty($icdCodes)
            ? $this->faker->randomElement($icdCodes)
            : $this->faker->randomElement(range('A', 'Z')) . $this->faker->numberBetween(1, 5);
        
        return [
            'tanggal' => $this->faker->dateTimeBetween('2024-01-01', '2026-02-28')->format('Y-m-d'),
            // 'no_reg' and 'kpusk' will be overridden in seeder
            'kdSadar' => $this->faker->randomElement(['Compos Mentis', 'Apatis', 'Somnolen', 'Sopor', 'Koma']),
            
            // Alergi
            'alergiMakan' => $this->faker->boolean(30) ? $this->faker->randomElement(['Seafood', 'Kacang', 'Telur', 'Susu Sapi']) : null,
            'alergiUdara' => $this->faker->boolean(20) ? $this->faker->randomElement(['Debu', 'Dingin', 'Bulu Kucing']) : null,
            'alergiObat' => $this->faker->boolean(15) ? $this->faker->randomElement(['Amoxicillin', 'Ibuprofen', 'Penicillin', 'Aspirin']) : null,
            'alergiMakananSS' => $this->faker->boolean(30) ? $this->faker->randomElement(['Ringan', 'Sedang', 'Berat']) : null,
            'alergiLingkunganSS' => $this->faker->boolean(20) ? $this->faker->randomElement(['Ringan', 'Sedang', 'Berat']) : null,
            'alergiObatSS' => $this->faker->boolean(15) ? $this->faker->randomElement(['Ringan', 'Sedang', 'Berat']) : null,
            
            // TTV Tambahan
            'kdPrognosa' => $this->faker->randomElement(['Sanam', 'Bonam', 'Malam', 'Dubia']),
            'respRate' => $this->faker->numberBetween(16, 24),
            'heartRate' => $this->faker->numberBetween(60, 100),
            'suhu' => $this->faker->randomFloat(1, 36, 39),
            'bb' => $this->faker->numberBetween(45, 90),
            'tb' => $this->faker->numberBetween(150, 180),
            'sistole' => $this->faker->numberBetween(100, 140),
            'diastole' => $this->faker->numberBetween(70, 90),
            'lingkarPerut' => $this->faker->numberBetween(60, 110),
            
            // SOAP Tambahan
            'anamnesa' => $this->faker->paragraph(2),
            'fisik' => $this->faker->paragraph(1),
            'kode_penyakit' => $kodePenyakit,
            'status' => $status,
            'kode_obat' => 'OBT-' . $this->faker->numberBetween(100, 999),
            'jumlah' => $this->faker->numberBetween(10, 30),
            'dosis' => $this->faker->randomElement(['3x1', '2x1', '1x1']),
            'racikan' => $this->faker->boolean(20) ? 'Racikan Puyer ' . $this->faker->numberBetween(1, 4) : null,
            
            // Tindakan & Rujukan
            'kode_tindakan' => $this->faker->randomElement(['TND-010', 'TND-015', 'TND-020']),
            'kode_tindakan_icd' => $this->faker->randomElement(['89.01', '89.02', '89.52']),
            'edukasi' => $this->faker->sentence(),
            'rekomendasi_diet' => $this->faker->boolean(40) ? $this->faker->randomElement(['Diet Rendah Garam', 'Diet Tinggi Kalori Tinggi Protein', 'Diet Rendah Purin']) : null,
            
            'jenis_perawatan' => $this->faker->randomElement(['Rawat Jalan', 'Rawat Inap', 'IGD']),
            'unit' => $this->faker->randomElement(['Poli Umum', 'Poli Gigi', 'Poli KIA', 'UGD']),
            'rujukan' => $this->faker->boolean(10) ? 'RSUD Setempat' : null,
            'poli_rs' => $this->faker->boolean(10) ? $this->faker->randomElement(['Penyakit Dalam', 'Bedah', 'Mata']) : null,
            
            // Administrasi
            'cara_bayar' => $this->faker->randomElement(['BPJS', 'Umum', 'Asuransi Lain']),
            'kode_pemeriksa' => 'PAY-' . strtoupper($this->faker->lexify('????')) . $this->faker->numberBetween(100, 999),
            
            'diisi_pada' => $this->faker->dateTimeBetween('2024-01-01', '2026-02-28'),
        ];
    }
}
