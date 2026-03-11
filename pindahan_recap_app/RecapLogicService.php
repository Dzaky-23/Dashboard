<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecapLogicService
{
    /**
     * Mapping nama Puskesmas ke Kecamatan
     */
    protected const MAPPING_KECAMATAN = [
        'PONCOL' => 'SEMARANG TENGAH', 'MIROTO' => 'SEMARANG TENGAH',
        'BANDARHARJO' => 'SEMARANG UTARA', 'BULU LOR' => 'SEMARANG UTARA',
        'HALMAHERA' => 'SEMARANG TIMUR', 'KARANGDORO' => 'SEMARANG TIMUR', 'BUGANGAN' => 'SEMARANG TIMUR',
        'LAMPER TENGAH' => 'SEMARANG SELATAN', 'PANDANARAN' => 'SEMARANG SELATAN',
        'LEBDOSARI' => 'SEMARANG BARAT', 'KROBOKAN' => 'SEMARANG BARAT', 'MANYARAN' => 'SEMARANG BARAT',
        'NGEMPLAK SIMONGAN' => 'SEMARANG BARAT', 'KARANGAYU' => 'SEMARANG BARAT',
        'GAYAMSARI' => 'GAYAMSARI', 'CANDILAMA' => 'CANDISARI', 'KAGOK' => 'CANDISARI',
        'PEGANDAN' => 'GAJAHMUNGKUR', 'BANGETAYU' => 'GENUK', 'GENUK' => 'GENUK',
        'TLOGOSARI KULON' => 'PEDURUNGAN', 'TLOGOSARI WETAN' => 'PEDURUNGAN', 'PLAMONGANSARI' => 'PEDURUNGAN',
        'ROWOSARI' => 'TEMBALANG', 'KEDUNGMUNDU' => 'TEMBALANG', 'BULUSAN' => 'TEMBALANG',
        'NGEREP' => 'BANYUMANIK', 'PADANGSARI' => 'BANYUMANIK', 'PUPAY' => 'BANYUMANIK', 'SRONDOL' => 'BANYUMANIK',
        'SEKARAN' => 'GUNUNGPATI', 'GUNUNGPATI' => 'GUNUNGPATI', 'MIJEN' => 'MIJEN', 'KARANGMALANG' => 'MIJEN',
        'PURWOYOSO' => 'NGALIYAN', 'TAMBAKAJI' => 'NGALIYAN', 'NGALIYAN' => 'NGALIYAN',
        'KARANGANYAR' => 'TUGU', 'MANGKANG' => 'TUGU'
    ];

    /**
     * Membersihkan dan memproses baris data mentah dari Excel.
     * 
     * [!] PENTING: Di PHP/Laravel membaca Excel biasanya memakai package `maatwebsite/excel`.
     * Asumsi method ini menerima `$rawRows` yang merupakan array hasil import()
     * yang sudah melewati proses pembacaan file Excel.
     * 
     * @param array|\Traversable|Collection $rawRows Baris data dari Excel.
     * @param string $fileName Nama file (tanpa ekstensi untuk nama puskesmas).
     * @return array ['data' => Collection, 'log' => array]
     */
    public function cleanAndProcessData($rawRows, string $fileName): array
    {
        $rows = collect($rawRows);
        $log = ['file' => $fileName, 'status' => 'SUCCESS', 'message' => 'Berhasil diproses.'];
        
        try {
            // Ambil nama puskesmas dari ekstensi yang sudah dihapus
            $namaPusk = Str::upper(trim($fileName));
            $kecamatan = self::MAPPING_KECAMATAN[$namaPusk] ?? 'TIDAK TERDAFTAR';

            $cleanedData = collect();

            foreach ($rows as $index => $row) {
                // Di maatwebsite, jika tanpa WithHeadingRow maka ini indexed array.
                // Jika WithHeadingRow maka ini associative array.
                $rowArray = is_array($row) ? $row : (array) $row;

                /**
                 * SESUAIKAN kuncinya (keys) dengan library Excel yang Anda gunakan.
                 * Di Python logic aslinya (berbasis positional):
                 * - 'Jenis Penyakit' kemungkinan ada di kolom C (index 2) atau B dsb.
                 * - 'ICD X' ada di B (index 1) dsb.
                 * - Data angka penyakit ada di index 3 sampai 50.
                 */
                
                // Fallback pencarian key fleksibel
                $jenisPenyakit = $rowArray['Jenis Penyakit'] ?? $rowArray['jenis_penyakit'] ?? $rowArray[2] ?? '';
                $icdX = $rowArray['ICD X'] ?? $rowArray['icd_x'] ?? $rowArray[1] ?? '';
                
                $jenisPenyakitStr = (string) $jenisPenyakit;
                
                // 1. Filter Baris Sampah
                if (preg_match('/TOTAL|JUMLAH|SUB TOTAL/i', $jenisPenyakitStr)) {
                    continue;
                }

                // 2. Sum Kolom Data Angka (Menyesuaikan index asli pandas df.iloc[:, 3:51])
                $totalKasus = 0;
                // Jika data yang didapat berupa numeric associative (misal dari row[3] hingga row[50])
                for ($i = 3; $i <= 50; $i++) {
                    $val = $rowArray[$i] ?? 0;
                    if (is_numeric($val)) {
                        $totalKasus += (float) $val;
                    }
                }
                
                // *Opsional:* Jika array berbentuk associative column ('kasus_a', 'kasus_b'),
                // Anda butuh logic foreach loop pada `$rowArray` dan mengecek array key-nya.

                // 3. Standardisasi Teks
                $jenisPenyakitStr = Str::upper(trim($jenisPenyakitStr));
                $icdXStr = Str::upper(trim((string) $icdX));

                // 4. Hanya ambil yang ada kasusnya
                if ($totalKasus > 0) {
                    $cleanedData->push([
                        'Jenis Penyakit' => $jenisPenyakitStr,
                        'ICD X' => $icdXStr,
                        'Total_Kasus' => $totalKasus,
                        'Puskesmas' => $namaPusk,
                        'Kecamatan' => $kecamatan
                    ]);
                }
            }

            if ($cleanedData->isEmpty()) {
                $log['status'] = 'WARNING';
                $log['message'] = 'File valid tapi tidak ada data kasus (>0).';
            }

            return ['data' => $cleanedData, 'log' => $log];

        } catch (\Exception $e) {
            $log['status'] = 'ERROR';
            $log['message'] = 'Gagal memproses: ' . $e->getMessage();
            return ['data' => collect(), 'log' => $log];
        }
    }

    /**
     * Menghitung Top N penyakit berdasarkan grup (Kecamatan/Puskesmas).
     * Padanan fungsi `hitung_ranking` dari python.
     * 
     * @param Collection $df Data yang sudah clean dari hasil cleanAndProcessData().
     * @param array $groupCols Kolom groupBy (misal ['Puskesmas'] atau ['Kecamatan'])
     * @param int $topN Limit Data Top N
     * @return Collection
     */
    public function calculateRankings(Collection $df, array $groupCols, int $topN = 10): Collection
    {
        // 1. Grouping unik: menggabungkan penyakit yang sama dalam grup tersebut (sum Total_Kasus)
        $grouped = $df->groupBy(function ($item) use ($groupCols) {
            $keyParts = array_map(fn($col) => $item[$col], $groupCols);
            $keyParts[] = $item['Jenis Penyakit'];
            $keyParts[] = $item['ICD X'];
            return implode('|', $keyParts);
        })->map(function ($items) use ($groupCols) {
            $first = $items->first();
            $row = [
                'Jenis Penyakit' => $first['Jenis Penyakit'],
                'ICD X' => $first['ICD X'],
                'Total_Kasus' => $items->sum('Total_Kasus')
            ];
            foreach ($groupCols as $col) {
                $row[$col] = $first[$col];
            }
            return $row;
        });

        // 2. Apply Top N per group key 
        $result = collect();
        
        $groups = $grouped->groupBy(function ($item) use ($groupCols) {
            return implode('|', array_map(fn($col) => $item[$col], $groupCols));
        });

        foreach ($groups as $groupKey => $groupItems) {
            // Sort by Total_Kasus Desc
            $sorted = $groupItems->sortByDesc('Total_Kasus')->take($topN);
            foreach ($sorted as $item) {
                $result->push($item);
            }
        }

        return $result->values(); // reset id/keys
    }

    /**
     * Mencari penyakit yang paling sering muncul di Top 10 berbagai wilayah.
     * Padanan fungsi `cari_penyakit_umum` dari python.
     * 
     * @param Collection $dfRanking Hasil dari fungsi calculateRankings()
     * @param string $groupCol Kolom grouping yang dipakai ('Kecamatan' / 'Puskesmas')
     * @param int $topN Limit akhir
     * @return Collection
     */
    public function findCommonDiseases(Collection $dfRanking, string $groupCol, int $topN = 5): Collection
    {
        $totalGroups = $dfRanking->pluck($groupCol)->unique()->count();

        // Frekuensi: Berapa kali Penyakit & ICD X muncul lintas-grup
        $freqTotalCollection = $dfRanking->groupBy(function($item) {
            return $item['Jenis Penyakit'] . '|' . $item['ICD X'];
        })->map(function($items) use ($totalGroups) {
            $first = $items->first();
            $frekuensi = $items->count();
            $totalKasus = $items->sum('Total_Kasus');
            
            $status = ($frekuensi === $totalGroups) 
                ? "LOLOS (Ada di SEMUA)" 
                : sprintf("HAMPIR (Absen di %d unit)", $totalGroups - $frekuensi);

            return [
                'Jenis Penyakit' => $first['Jenis Penyakit'],
                'ICD X' => $first['ICD X'],
                'Frekuensi' => $frekuensi,
                'Total_Kasus' => $totalKasus,
                'Status' => $status,
            ];
        });

        // Sort by Frekuensi (Desc), lalu Total_Kasus (Desc) dan ambil top N
        $sorted = $freqTotalCollection->sort(function($a, $b) {
            if ($a['Frekuensi'] === $b['Frekuensi']) {
                return $b['Total_Kasus'] <=> $a['Total_Kasus'];
            }
            return $b['Frekuensi'] <=> $a['Frekuensi'];
        })->take($topN)->values();

        return collect($sorted);
    }
}
