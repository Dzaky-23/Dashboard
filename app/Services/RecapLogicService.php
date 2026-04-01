<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecapLogicService
{


    /**
     * Mapping Kode Kecamatan ke Nama Kecamatan
     */
    public const MAPPING_NAMA_KECAMATAN = [
        'KC01' => 'SEMARANG TENGAH',
        'KC02' => 'SEMARANG UTARA',
        'KC03' => 'SEMARANG TIMUR',
        'KC04' => 'SEMARANG SELATAN',
        'KC05' => 'SEMARANG BARAT',
        'KC06' => 'GAYAMSARI',
        'KC07' => 'CANDISARI',
        'KC08' => 'GAJAH MUNGKUR',
        'KC09' => 'GENUK',
        'KC10' => 'PEDURUNGAN',
        'KC11' => 'TEMBALANG',
        'KC12' => 'BANYUMANIK',
        'KC13' => 'GUNUNGPATI',
        'KC14' => 'MIJEN',
        'KC15' => 'NGALIYAN',
        'KC16' => 'TUGU'
    ];

    public static function getMappingKodeToKecamatan(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('mapping_kode_kecamatan', 600, function() {
            $refData = \App\Models\RefPuskesmas::all();
            $mapping = [];
            foreach($refData as $ref) {
                $kodeKecamatan = strtoupper(trim($ref->kode_kecamatan));
                $namaKecamatan = self::MAPPING_NAMA_KECAMATAN[$kodeKecamatan] ?? $kodeKecamatan;
                $mapping[$ref->kode_puskesmas] = $namaKecamatan;
            }
            return $mapping;
        });
    }

    public static function getPuskesmasNames(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('mapping_kode_nama_pusk', 600, function() {
            return \App\Models\RefPuskesmas::pluck('puskesmas', 'kode_puskesmas')->toArray();
        });
    }

    /**
     * Translate array of ICD codes into [Code => Name] dictionary
     * Menggunakan array kodes untuk meminimalisasi bebaan RAM dengan format "IN (...)".
     */
    public static function getIcdNames(array $icdCodes): array
    {
        if (empty($icdCodes)) return [];
        
        // Memaksa cache per hash array agar query tak diulang di detik yang sama
        $hash = md5(implode(',', $icdCodes));
        return \Illuminate\Support\Facades\Cache::remember("icd_names_{$hash}", 300, function() use ($icdCodes) {
            return \App\Models\BpjsRefIcd::whereIn('kdDiag', $icdCodes)
                ->pluck('nmDiag', 'kdDiag')
                ->toArray();
        });
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
