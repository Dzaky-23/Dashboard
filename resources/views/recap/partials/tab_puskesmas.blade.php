<!-- TAB 3: HANYA PUSKESMAS -->
<div x-show="activeFilter === 'puskesmas'" x-transition class="tab-puskesmas grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-5">
    @foreach ($groupedByPusk as $puskesmas => $dataPenyakit)
        @php
            $totalKasusPusk = $dataPenyakit->sum('count');
            $topPenyakitPusk = $dataPenyakit->first();
        @endphp
        <a data-search-key="{{ strtolower($puskesmas ?? '') }}" x-show="search === '' || '{{ strtolower($puskesmas ?? '') }}'.includes(search.toLowerCase())" href="{{ route('recap.show', $puskesmas) }}" class="group block bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:border-red-300 hover:shadow-md hover:ring-1 hover:ring-red-300 transition-all duration-300 relative">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-bold text-slate-800 text-base group-hover:text-red-700 transition-colors duration-200 pr-2 truncate">
                    {{ $puskesmas ?? 'Tidak Diketahui' }}
                </h4>
            </div>
            <p class="text-[11px] font-bold text-slate-400 mb-4 flex items-center uppercase tracking-wider">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Kec. {{ $mapping[$puskesmas] ?? 'Tidak Terdaftar' }}
            </p>
            <div class="text-red-600 font-semibold text-[11px] text-right float-right mt-1.5 group-hover:underline flex items-center justify-end gap-1">Detail <span>&rarr;</span></span>
            </div>
        </a>
    @endforeach
</div>
