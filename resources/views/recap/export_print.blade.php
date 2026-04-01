<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Penyakit - {{ date('d M Y') }}</title>
    <!-- Tailwind CSS (CDN for printing ease without build process attached on print view if desired, but we can use app.css since it's local) -->
    @vite(['resources/css/app.css'])
    <style>
        body { background: white; color: black; font-family: 'Inter', sans-serif; }
        @media print {
            @page { margin: 1cm; }
            body { padding: 0; background: white; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            h2, h3, h4 { page-break-after: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8 antialiased">
    
    <div class="max-w-[1000px] mx-auto bg-white p-10 min-h-screen shadow-xl" id="printable-area">
        
        <!-- Action Buttons -->
        <div class="mb-8 flex justify-end gap-3 no-print">
            <a href='{{ route('recap.index') }}'" class="px-4 py-2 border border-slate-300 rounded text-sm font-semibold text-slate-600 hover:bg-slate-50">Tutup</a>
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-bold shadow hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak / Simpan PDF
            </button>
        </div>

        <!-- Header Kop Laporan -->
        <div class="border-b-4 border-slate-800 pb-5 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/dkk_logo.png') }}" alt="Logo" class="h-16 w-auto">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-widest leading-none">Dinas Kesehatan Kabupaten</h1>
                    <p class="text-sm font-medium text-slate-500 mt-1">Sistem Informasi Rekapitulasi Data Penyakit</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-slate-500 uppercase">Dokumen Dicetak Pada</p>
                <p class="text-base font-bold text-slate-800">{{ date('d F Y, H:i') }}</p>
            </div>
        </div>

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-slate-800 uppercase underline underline-offset-4 mb-2">Laporan Rekapitulasi Penyakit</h2>
            
            <p class="text-sm text-slate-600 font-medium font-mono bg-slate-100 inline-block px-3 py-1 rounded">
                @if($startDate && $endDate)
                    Periode: {{ date('d M Y', strtotime($startDate)) }} s/d {{ date('d M Y', strtotime($endDate)) }}
                @else
                    Periode: Sepanjang Waktu
                @endif
            </p>
            
            @if(!empty($includeLetters))
            <p class="text-xs text-blue-600 font-bold mt-2">
                *Hanya Menampilkan Kategori: {{ implode(', ', $includeLetters) }}
            </p>
            @endif
            @if(!empty($excludeLetters))
            <p class="text-xs text-red-600 font-bold mt-1">
                *Mengecualikan Kategori: {{ implode(', ', $excludeLetters) }}
            </p>
            @endif
        </div>

        <!-- Bagian 1: Keseluruhan Wilayah -->
        <div class="mb-10">
            <div class="bg-slate-800 text-white px-4 py-2 font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Top {{ $topNUmum }} Penyakit - Keseluruhan Wilayah Kabupten
            </div>
            
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-slate-300">
                        <th class="py-2 px-4 font-bold text-slate-700 w-16 text-center">No</th>
                        <th class="py-2 px-4 font-bold text-slate-700">Kode Penyakit (ICD-X)</th>
                        <th class="py-2 px-4 font-bold text-slate-700">Nama Penyakit</th>
                        <th class="py-2 px-4 font-bold text-slate-700 text-right w-40">Jumlah Kasus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($topUmum as $index => $item)
                        <tr>
                            <td class="py-2 px-4 text-center text-slate-500 font-medium">{{ $index + 1 }}</td>
                            <td class="py-2 px-4 font-bold text-slate-800">{{ $item->kode_penyakit }}</td>
                            <td class="py-2 px-4 text-slate-700">{{ $item->nama_penyakit ?? $item->kode_penyakit }}</td>
                            <td class="py-2 px-4 text-right font-semibold">{{ number_format($item->count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-400 italic">Tidak ada data tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Bagian 2: Per Kecamatan -->
        <div class="mb-10">
            <div class="bg-slate-800 text-white px-4 py-2 font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Top {{ $topNKecamatan }} Penyakit di Setiap Kecamatan
            </div>

            <div class="grid grid-cols-2 gap-8">
                @foreach($kecamatanData as $kecName => $kData)
                <div>
                    <h4 class="font-bold text-red-700 mb-2 border-b border-red-200 pb-1">Kec. {{ $kecName }}</h4>
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500">
                                <th class="py-1 px-2">ICD X</th>
                                <th class="py-1 px-2">Nama</th>
                                <th class="py-1 px-2 text-right">Kasus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kData as $item)
                                <tr>
                                    <td class="py-1.5 px-2 font-semibold">{{ $item->kode_penyakit }}</td>
                                    <td class="py-1.5 px-2 text-slate-600">{{ $item->nama_penyakit ?? $item->kode_penyakit }}</td>
                                    <td class="py-1.5 px-2 text-right">{{ number_format($item->count) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-2 text-center text-slate-400">Kosong</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Bagian 3: Per Fasilitas Kesehatan (Puskesmas) -->
        <div class="mb-8" style="page-break-before: always;">
            <div class="bg-slate-800 text-white px-4 py-2 font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Top {{ $topNPuskesmas }} Penyakit per Unit Puskesmas
            </div>

            <div class="grid grid-cols-3 gap-6">
                @foreach($puskesmasData as $puskName => $pData)
                <div class="border border-slate-200 p-3 rounded break-inside-avoid">
                    <h4 class="font-bold text-blue-700 text-sm mb-2 pb-1 border-b border-blue-100 truncate" title="{{ $puskName }}">Pkm. {{ $puskName }}</h4>
                    <table class="w-full text-[11px] text-left">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pData as $item)
                                <tr>
                                    <td class="py-1 font-semibold text-slate-700">{{ $item->kode_penyakit }}</td>
                                    <td class="py-1 text-slate-600">{{ $item->nama_penyakit ?? $item->kode_penyakit }}</td>
                                    <td class="py-1 text-right font-medium">{{ number_format($item->count) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-2 text-center text-slate-400">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endforeach
            </div>
        </div>
        
        <div class="pt-8 mt-12 border-t border-slate-300 text-center text-xs text-slate-400 print:bottom-0">
            Dihasilkan oleh Sistem Informasi Rekam Medis (Auto-Generated)
        </div>

    </div>

    <!-- Script to Auto-Trigger Print Dialog when ready -->
    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 800); // Tunggu render css
        };
    </script>
</body>
</html>
