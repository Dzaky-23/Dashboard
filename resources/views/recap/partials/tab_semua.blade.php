<!-- TAB 1: TAMPILKAN SEMUA (Nested View) -->
<div x-show="activeFilter === 'semua'" x-transition class="tab-semua space-y-10">
    @foreach ($kecamatanDataList as $kecName => $kecData)
        <div x-data="{ 
            kecMatch: search === '' || '{{ strtolower($kecName) }}'.includes(search.toLowerCase()),
            get hasVisiblePusk() {
                if (this.search === '') return true;
                return Array.from($el.querySelectorAll('.sub-puskesmas')).some(el => el.dataset.searchKey.includes(this.search.toLowerCase()));
            }
        }" 
        x-show="kecMatch || hasVisiblePusk" class="bg-slate-50 border border-slate-200 rounded-2xl p-6" data-search-key="{{ strtolower($kecName) }}">
            
            <!-- Header Kecamatan di Tab Semua -->
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-200/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-xl">{{ $kecName }}</h4>
                        <p class="text-sm text-slate-500">{{ $kecData['total_puskesmas'] }} Instansi Puskesmas</p>
                    </div>
                </div>
                <a href="{{ route('recap.kecamatan.show', $kecName) }}" class="text-sm font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white transition-colors px-4 py-2 rounded-lg border border-indigo-100 shadow-sm hidden sm:inline-flex items-center gap-1">
                    Analitik Kecamatan <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <!-- Grid Puskesmas di bawahnya -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-5">
                @foreach ($kecData['list_puskesmas'] as $puskesmas)
                    @if(isset($groupedByPusk[$puskesmas]))
                        @php
                            $dataPenyakit = $groupedByPusk[$puskesmas];
                            $totalKasusPusk = $dataPenyakit->sum('count');
                            $topPenyakitPusk = $dataPenyakit->first();
                        @endphp
                        <a href="{{ route('recap.show', $puskesmas) }}" data-search-key="{{ strtolower($puskesmas) }}" x-show="search === '' || '{{ strtolower($puskesmas) }}'.includes(search.toLowerCase())" class="sub-puskesmas group block bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-red-300 hover:shadow-md hover:ring-1 hover:ring-red-300 transition-all duration-300 relative">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-slate-800 text-base group-hover:text-red-700 transition-colors duration-200 truncate pr-2">
                                    {{ $puskesmas }}
                                </h4>
                                <span class="inline-flex items-center justify-center px-2 py-1 flex-shrink-0 text-xs font-bold leading-none text-red-700 bg-red-50 border border-red-100 rounded-full">
                                    {{ number_format($totalKasusPusk) }} Kasus
                                </span>
                            </div>
                            <div class="text-sm text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 mt-4">
                                <span class="font-medium text-slate-500 text-xs uppercase tracking-wide block mb-0.5">ICD-X Terbanyak</span>
                                <span class="font-semibold text-slate-800">{{ $topPenyakitPusk->kode_penyakit ?? '-' }}</span> 
                                <span class="text-red-600 font-semibold text-xs text-right float-right mt-1 group-hover:underline">Lihat detail &rarr;</span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
