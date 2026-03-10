# Dokumentasi Logic Rekap M-Health (Versi Laravel/PHP)

File `RecapLogicService.php` berisi terjemahan dari _logic processing_ Pandas (Python) aplikasi rekap kesehatan ke spesifikasi standar PHP dengan menggunakan library `Illuminate\Support\Collection` bawaan dari Laravel framework.

## 1. MAPPING WILAYAH
Sama halnya dengan versi Python, mapping dilakukan dengan array constant `MAPPING_KECAMATAN`. Tujuannya untuk mencocokkan asal Puskesmas di-mapping ke Kecamatan mana secara otomatis saat import file excel.

## 2. METODE (`METHODS`)
Class `RecapLogicService` mempunyai 3 metode utama sebagai core bussines logic.

### `cleanAndProcessData(array $rawRows, string $fileName) : array`
Methode ini berfungsi sebagai _Data Cleaning_ (*pengganti* fungsi `baca_dan_bersihkan_file` di Python).

**Alur Kerja:**
1. Menerima array baris dari file excel, misal via `Maatwebsite\Excel`.
2. Melakukan ekstraksi string nama pustkesmas dari nama filenya, kemudian di-mapping menjadi nama kelurahan/kecamatan.
3. Looping semua baris. Dalam prosesnya baris kategori yang sifatnya "TOTAL", "JUMLAH", "SUB TOTAL" **akan dilewati/di-skip**, karena hanya ingin mengambil detail kasus spesifiknya, bukan hasil summing dari template raw excel-nya.
4. Nilai-nilai pada array _index 3 sampai 50_ dijumlahkan untuk mendapatkan variabel *Total Kasus* penyakit.
5. Memfilter penyakit yang memiliki Total Kasus > 0. Baris dengan total 0 akan dibuang.
6. Retur tipe datanya adalah sebuah array terasosiasi: `['data' => Collection(), 'log' => array()]` di mana `Collection()` berupa array dari struct penyakit yang sudah *clean* beserta jumlah kasus dan lokasi Puskesmasnya.

### `calculateRankings(Collection $df, array $groupCols, int $topN = 10) : Collection`
Method ini berfungsi untuk melakukan Ranking Top N penyakit. Metodenya ekuivalen denga _method_ `hitung_ranking` dari Python.

**Alur Kerja (Setara Pandas `groupby().apply(sort_values(...).head(topN))`):**
1. Data terlebih dahulu di-mapping dikelompokan/grouping berdasarkan `$groupCols` (Misalnya _Puskesmas_ atau _Kecamatan_) serta string `Jenis Penyakit`.
2. Kasus-kasus sejenis dijumlahkan lagi apabila ada duplikasi jenis penyakit.
3. Setelah dikelompokkan, lalu diurutkan _Descending_ berdasarkan Total Kasus penyakit yang dipunyai masing-masing.
4. Diambil sebanyak batas `topN` (default 10 terbesar).

### `findCommonDiseases(Collection $dfRanking, string $groupCol, int $topN = 5) : Collection`
Method ini berfungsi untuk menganalisa **Penyebaran Dominasi Penyakit**. Metodenya ekuivalen denga _method_ `cari_penyakit_umum` dari Python.
Method ini membantu mencari tahu: _"Penyakit apakah yang frekuensinya secara terus-menerus muncul Top N diseluruh Kecamatan/Puskesmas?"_

**Alur Kerja:**
1. Metode ini tidak menerima Data mentah/clean, melainkan menerima data dari result fungsi Top 10 `calculateRankings()`.
2. Pertama script menghitung total _unique value_ dari grouping yang diminta (Misal: ada berapa Puskesmas atau Kecamatan secara total).
3. Setiap jenis Penyakit dihitung kemunculanya. Apakah frekuensi kemunculannya **sama dengan total unit** Puskesmas/Kecamatan tersebut?
4. Jika **Ya** (Sama persis), penyakit tersebut diberi Label Status: `"LOLOS (Ada di SEMUA)"`.
5. Jika **Tidak**, maka diberi label Status: `"HAMPIR (Absen di ... unit)"`.
6. Di sorting kembali berdasarkan seberapa sering ia eksis (`Frekuensi`) secara _Desc_, kalau jumlah kemunculan frekuensinya seri sama-sama 10 kali contohnya, baru di sorting turun berdasarkan total kasus mentahnya.

## 3. PANDUAN PENGGUNAAN DI LARAVEL CONTROLLER

Untuk memakai service class ini di Controller (misal saat submit file `import()`), bisa dipakai seperti berikut:

```php
use App\Services\RecapLogicService;
use Maatwebsite\Excel\Facades\Excel; // Contoh package yang umum dipakai di Laravel
use Illuminate\Http\Request;

class RekapController extends Controller {

    public function processUpload(Request $request, RecapLogicService $logicService) {
        
        // 1. Ambil file Upload..
        $file = $request->file('kesehatan_excel');
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // "PONCOL"

        // 2. Baca isi baris excel (bisa memakai Class Import Maatwebsite tanpa Model)
        $rawArray = Excel::toArray(new stdClass, $file)[0]; // Sheet 1
        
        // 3. Proses Data Bersih
        $result = $logicService->cleanAndProcessData($rawArray, $fileName);
        
        $cleanDataCollection = $result['data'];
        $logStatus = $result['log'];

        // 4. Proses Fitur Analisa Lanjutan (Dari data bersih tersebut)
        
        // Top 10 Kecamatan
        $topKecamatan = $logicService->calculateRankings($cleanDataCollection, ['Kecamatan'], 10);
        
        // Top 10 Puskesmas  
        $topPuskesmas = $logicService->calculateRankings($cleanDataCollection, ['Puskesmas'], 10);
        
        // Cek Penyakit Umum secara luas di semua Kecamatan
        $commonDiseases = $logicService->findCommonDiseases($topKecamatan, 'Kecamatan', 5);

        // Render hasil
        return view('rekap.result', compact('cleanDataCollection', 'topKecamatan', 'topPuskesmas', 'commonDiseases'));
    }
}
```
