<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 text-slate-900" x-data="{ 
                    filterType: '{{ $filterKecamatan ? 'kecamatan' : ($filterPuskesmas ? 'puskesmas' : 'all') }}', 
                    search: '',
                    get hasResults() {
                        if (this.search === '') return true;
                        const q = this.search.toLowerCase();
                        return Array.from($el.querySelectorAll('[data-pusk-name]')).some(el => el.dataset.puskName.includes(q));
                    }
                }">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-4 mb-6">
                        <h3 class="text-xl font-bold text-slate-800">Daftar Rekapitulasi Penyakit</h3>
                    </div>


                    <!-- Dynamic Filter Form -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-8">
                        <form id="filterForm" method="GET" action="{{ route('recap.index') }}" class="flex flex-col gap-4">
                            
                            <!-- Filter Type Selection -->
                            <div>
                                <p class="text-sm font-semibold text-slate-700 mb-2">Pilih Jenis Filter:</p>
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" x-model="filterType" value="all" class="text-red-600 focus:ring-red-500" @change="$event.target.form.submit()">
                                        <span class="ml-2 text-sm text-slate-700 font-medium">Tampilkan Semua</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" x-model="filterType" value="kecamatan" class="text-red-600 focus:ring-red-500" @change="document.getElementById('kecamatan').value=''">
                                        <span class="ml-2 text-sm text-slate-700 font-medium">Berdasarkan Kecamatan</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row items-end gap-5 border-t border-slate-200 pt-4" x-show="filterType !== 'all'" x-cloak>
                                <!-- Dropdown Kecamatan -->
                                <div class="w-full md:w-1/3" x-show="filterType === 'kecamatan'">
                                    <label for="kecamatan" class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Kecamatan</label>
                                    <select 
                                        name="kecamatan" 
                                        id="kecamatan" 
                                        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                    >
                                        <option value="">-- Pilih --</option>
                                        @foreach($listKecamatan as $kec)
                                            <option value="{{ $kec }}" {{ $filterKecamatan == $kec ? 'selected' : '' }}>{{ $kec }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="w-full md:w-auto flex space-x-3">
                                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                        Terapkan Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Ringkasan Kecamatan (Jika Filter Aktif) -->
                    @if(isset($kecamatanSummary) && $kecamatanSummary)
                        <div class="mb-8 p-6 bg-orange-50 border border-orange-200 rounded-xl shadow-sm">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Info Utama -->
                                <div class="w-full md:w-1/3">
                                    <h4 class="text-sm font-bold text-orange-900 uppercase tracking-widest mb-1">Rekapitulasi Wilayah</h4>
                                    <h2 class="text-2xl font-bold text-slate-900 mb-4">Kecamatan {{ $kecamatanSummary['nama'] }}</h2>
                                    
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center border-b border-orange-100 pb-2">
                                            <span class="text-orange-900 font-medium">Banyaknya Puskesmas</span>
                                            <span class="text-xl font-bold text-slate-900">{{ $kecamatanSummary['total_puskesmas'] }}</span>
                                        </div>
                                        <div class="flex justify-between items-center border-b border-orange-100 pb-2">
                                            <span class="text-orange-900 font-medium">Total Akumulasi Kasus</span>
                                            <span class="text-xl font-bold text-red-600">{{ number_format($kecamatanSummary['total_kasus']) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-orange-100/60">
                                         <a href="{{ route('recap.kecamatan.show', $kecamatanSummary['nama']) }}" class="inline-flex items-center text-sm font-bold text-orange-900 hover:text-red-600 hover:underline transition-colors group">
                                            Lihat Detail Penyakit Selengkapnya
                                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Sebaran Penyakit List -->
                                <div class="w-full md:w-2/3 bg-white p-4 rounded-lg border border-orange-100 overflow-hidden">
                                    <h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Penyakit Teratas di wilayah ini
                                    </h4>
                                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($kecamatanSummary['penyakit_teratas'] as $penyakit)
                                        <li class="flex justify-between items-center py-1 border-b border-slate-50 last:border-0 hover:bg-slate-50 px-2 rounded -mx-2 transition-colors">
                                            <span class="font-medium text-slate-600">{{ $penyakit->kode_penyakit }}</span>
                                            <span class="text-sm font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded">{{ number_format($penyakit->count) }} kasus</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- List Puskesmas Header with Search -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-2 mb-4 gap-4">
                        <h4 class="text-lg font-bold text-slate-800">Daftar Rekapitulasi per Puskesmas</h4>
                        <div class="relative w-full md:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input x-model="search" type="text" placeholder="Cari nama puskesmas..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 sm:text-sm transition duration-150 ease-in-out">
                            <button x-show="search.length > 0" x-transition @click="search = ''" x-cloak class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-4 w-4 text-slate-400 hover:text-slate-600 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                    
                    @if($groupedByPusk->isEmpty())
                        <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg border border-yellow-200">
                            Masih belum ada data penyebaran penyakit yang tercatat.
                        </div>
                    @else
                        <!-- Grid Layout untuk Menampilkan List Puskesmas Ringkas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 border border-slate-100 p-4 bg-slate-50/50 rounded-xl">
                            @foreach ($groupedByPusk as $puskesmas => $dataPenyakit)
                                @php
                                    $totalKasusPusk = $dataPenyakit->sum('count');
                                    $topPenyakitPusk = $dataPenyakit->first();
                                @endphp
                                <a data-pusk-name="{{ strtolower($puskesmas ?? '') }}" x-show="search === '' || '{{ strtolower($puskesmas ?? '') }}'.includes(search.toLowerCase())" x-transition href="{{ route('recap.show', $puskesmas) }}" class="group block bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-red-300 hover:shadow-md hover:ring-1 hover:ring-red-300 transition-all duration-300 relative">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-800 text-base group-hover:text-red-700 transition-colors duration-200">
                                            {{ $puskesmas ?? 'Tidak Diketahui' }}
                                        </h4>
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-700 bg-red-50 border border-red-100 rounded-full">
                                            {{ $totalKasusPusk }} Kasus
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mb-4 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Kec. {{ $mapping[$puskesmas] ?? 'Tidak Terdaftar' }}
                                    </p>
                                    <div class="text-sm text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                        <span class="font-medium text-slate-500 text-xs uppercase tracking-wide block mb-0.5">ICD-X Terbanyak</span>
                                        <span class="font-semibold text-slate-800">{{ $topPenyakitPusk->kode_penyakit ?? '-' }}</span> 
                                        <span class="text-red-600 font-semibold text-xs text-right float-right mt-1 group-hover:underline">Lihat detail &rarr;</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Info Puskesmas Kosong -->
                        <div x-show="!hasResults" x-cloak class="p-10 mt-6 text-center bg-slate-50 border border-dashed border-slate-300 rounded-xl">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 mb-4">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Puskesmas Tidak Ditemukan</h3>
                            <p class="text-sm text-slate-500 mt-1">Sistem tidak menemukan fasilitas yang cocok dengan sebutan "<span class="font-semibold text-slate-700" x-text="search"></span>".</p>
                            <button @click="search = ''" class="mt-4 text-sm font-semibold text-red-600 hover:text-red-500 transition-colors">Lihat semua daftar</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
