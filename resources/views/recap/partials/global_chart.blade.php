<!-- GRAFIK GLOBAL & SMART ANALYSIS SECTION -->
<div class="mb-8 grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- PANEL 1: GRAFIK GLOBAL NEUMORPHIC (Col 1-2) -->
    <div class="bg-slate-50 border border-slate-200 rounded-3xl shadow-sm text-center overflow-hidden xl:col-span-2 flex flex-col">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-white shadow-sm z-10">
            <h4 class="text-sm font-bold text-slate-600 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Top 10 Penyakit Global
            </h4>
        </div>

        <div class="px-6 pb-6 pt-16 md:px-8 md:pb-8 md:pt-20 overflow-x-auto bg-gradient-to-b from-slate-50 to-white flex-grow flex items-end justify-center min-w-max gap-4 sm:gap-6 md:gap-8 min-h-[380px]">
            @if(isset($chartData) && $chartData->isNotEmpty())
                @foreach($chartData as $index => $item)
                    @php
                        $heightPercentage = ($item->total / $maxChartWidth) * 100;
                        $colors = ['from-pink-400 to-pink-500', 'from-amber-400 to-orange-500', 'from-emerald-400 to-teal-500', 'from-sky-400 to-blue-500', 'from-violet-400 to-indigo-500', 'from-rose-400 to-red-500'];
                        $color = $colors[$index % count($colors)];
                        
                        // Menentukan penedek status
                        $shortStatus = str_contains($item->status, 'LOLOS') ? 'LOLOS' : str_replace('HAMPIR (', '', str_replace(' unit)', '', $item->status));
                    @endphp
                    <div class="flex flex-col items-center group relative cursor-pointer">
                        <!-- Floating Tooltip -->
                        <div class="absolute -top-14 opacity-0 group-hover:opacity-100 transition-all duration-300 z-10 bg-slate-800 shadow-xl rounded-xl px-4 py-2.5 text-sm font-bold text-white whitespace-nowrap pointer-events-none scale-95 group-hover:scale-100">
                            {{ number_format($item->total) }} Kasus
                            <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 transform rotate-45"></div>
                        </div>

                        <!-- Neumorphic Bar Container -->
                        <div class="h-56 md:h-64 w-12 md:w-16 bg-slate-100/50 rounded-full p-1.5 shadow-[inset_4px_4px_8px_rgba(0,0,0,0.05),inset_-4px_-4px_8px_rgba(255,255,255,0.8)] border border-white flex flex-col justify-end">
                            <div class="w-full bg-gradient-to-t {{ $color }} rounded-full relative transition-[height] duration-1000 ease-[cubic-bezier(0.34,1.56,0.64,1)] shadow-[0_4px_10px_rgba(0,0,0,0.1)] group-hover:brightness-110" style="height: {{ max($heightPercentage, 8) }}%">
                                <div class="absolute top-1 left-1.5 right-1.5 h-1/4 bg-white/40 rounded-full blur-[1px]"></div>
                            </div>
                        </div>

                        <!-- Bottom Labels -->
                        <div class="mt-5 flex flex-col items-center justify-start h-16 w-16 md:w-20">
                            <span class="block text-xs md:text-sm font-bold text-slate-700 truncate w-full px-1" title="{{ $item->label }}">{{ $item->label }}</span>
                            
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-slate-500 text-sm py-10 my-auto text-center w-full">Data penyakit kosong.</p>
            @endif
        </div>
    </div>

    <!-- PANEL 2: VISUAL NOTES METODOLOGI DATA (Col 3) -->
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-7 md:p-8 relative overflow-hidden flex flex-col justify-center h-full">
        <!-- Background Decorations -->
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-red-500 opacity-5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-48 h-48 bg-orange-500 opacity-5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <h4 class="text-[11px] font-black text-red-500 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Catatan Visual
            </h4>

            <h3 class="text-2xl font-bold tracking-tight text-slate-800 mb-6">Metodologi Data</h3>
            
            <div class="space-y-4 text-sm text-slate-600 font-medium leading-relaxed">
                <p>
                    Grafik ini mengolah <span class="text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded">{{ number_format($totalKasus) }}</span> rekam medis dari seluruh puskesmas terdaftar.
                </p>
                <p>
                    Sistem mengelompokkan diagnosa berdasarkan kode ICD-X pasien, lalu menyortir <span class="text-red-600 font-bold bg-red-50 px-1.5 py-0.5 rounded-md border border-red-100">10 penyakit tersering</span> lintas kecamatan untuk ditampilkan sebagai diagram batang visual.
                </p>
            </div>
        </div>
    </div>
</div>
