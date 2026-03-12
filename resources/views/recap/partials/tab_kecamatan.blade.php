<!-- TAB 2: HANYA KECAMATAN -->
<div x-show="activeFilter === 'kecamatan'" x-transition class="tab-kecamatan grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
    @foreach ($kecamatanDataList as $kecName => $kecData)
        <a href="{{ route('recap.kecamatan.show', $kecName) }}" data-search-key="{{ strtolower($kecName) }}" x-show="search === '' || '{{ strtolower($kecName) }}'.includes(search.toLowerCase())" class="group block bg-gradient-to-br from-red-50/50 to-white border border-red-100 rounded-2xl p-6 shadow-sm hover:border-red-300 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-red-500 opacity-5 rounded-bl-full pointer-events-none group-hover:scale-110 transition-transform duration-500"></div>
            <div class="flex justify-between items-start mb-2 relative z-10">
                <div>
                    <span class="text-red-600 text-[10px] font-black uppercase tracking-widest bg-red-100/80 px-2 py-1 rounded mb-3 inline-block">Kecamatan</span>
                    <h4 class="font-extrabold text-slate-800 text-xl group-hover:text-red-700 transition-colors duration-200 truncate pr-2">
                        {{ $kecName }}
                    </h4>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mt-6 relative z-10">
                <div class="bg-white border border-slate-100 rounded-xl p-3 shadow-sm">
                    <span class="text-[10px] text-slate-400 font-bold uppercase block mb-1 tracking-wider">Total Faskes</span>
                    <span class="font-bold text-slate-700 flex items-center text-sm">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        {{ $kecData['total_puskesmas'] }} Unit
                    </span>
                </div>
                <div class="bg-white border border-slate-100 rounded-xl p-3 shadow-sm">
                    <span class="text-[10px] text-slate-400 font-bold uppercase block mb-1 tracking-wider">Top Diagnosa</span>
                    <span class="font-bold text-rose-600 flex items-center text-sm">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        {{ $kecData['top_penyakit']->kode_penyakit ?? '-' }} 
                    </span>
                </div>
            </div>
            
            <div class="mt-5 pt-4 border-t border-red-100/50 flex justify-between items-center relative z-10 w-full group-hover:border-red-200 transition-colors">
                <span class="text-xs text-slate-500 font-medium">Lihat rincian penuh</span>
                <span class="bg-red-600 text-white rounded-lg p-1.5 shadow-sm group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </span>
            </div>
        </a>
    @endforeach
</div>
