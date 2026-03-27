<div class="mb-8 grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-slate-50 border border-slate-200 rounded-3xl shadow-sm text-center overflow-hidden xl:col-span-2 flex flex-col">
        <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-white shadow-sm z-10">
            <h4 class="text-sm font-bold text-slate-600 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Top 10 Penyakit Global
            </h4>
            
            <form id="filterYearForm" action="{{ route('recap.index') }}" method="GET" class="flex items-center gap-2">
                <label for="year" class="text-xs font-bold text-slate-500 hidden sm:block">Periode:</label>
                <div class="relative">
                    <select name="year" id="year" onchange="document.getElementById('filterYearForm').submit()" class="appearance-none bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg focus:ring-red-500 focus:border-red-500 block w-full pl-3 pr-8 py-1.5 cursor-pointer hover:bg-slate-100 transition-colors shadow-sm">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $yearInput == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </form>
        </div>

        <div class="px-6 py-6 md:px-8 md:py-8 overflow-y-auto bg-gradient-to-b from-slate-50 to-white flex-grow flex flex-col items-stretch justify-center w-full gap-2 sm:gap-3 min-h-[250px] scrollbar-thin scrollbar-thumb-slate-200 scrollbar-track-transparent">
            @if(isset($chartData) && $chartData->isNotEmpty())
                @foreach($chartData as $index => $item)
                    @php
                        $percentage = ($item->total / $maxChartWidth) * 100;
                        $widthPercentage = max($percentage, 8);
                        $colors = ['from-pink-400 to-pink-500', 'from-amber-400 to-orange-500', 'from-emerald-400 to-teal-500', 'from-sky-400 to-blue-500', 'from-violet-400 to-indigo-500', 'from-rose-400 to-red-500'];
                        $color = $colors[$index % count($colors)];
                        
                        $shortStatus = str_contains($item->status, 'LOLOS') ? 'LOLOS' : str_replace('HAMPIR (', '', str_replace(' unit)', '', $item->status));
                    @endphp
                    <div class="flex flex-row items-center w-full group relative cursor-pointer">
                        <!-- Tooltip on hover -->
                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-10 bg-slate-800 shadow-xl rounded-xl px-4 py-2 text-sm font-bold text-white whitespace-nowrap pointer-events-none scale-95 group-hover:scale-100">
                            {{ number_format($item->total) }} Kasus
                            <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 transform rotate-45"></div>
                        </div>

                        <!-- Label (ICD-X) -->
                        <div class="mr-3 md:mr-4 flex flex-shrink-0 items-center justify-end w-8 md:w-10">
                            <span class="block text-[11px] md:text-xs font-bold text-slate-700 truncate" title="{{ $item->label }}">{{ $item->label }}</span>
                        </div>

                        <!-- Bar Track -->
                        <div class="flex-grow h-6 md:h-8 bg-slate-100/50 rounded-full p-1 shadow-[inset_2px_2px_6px_rgba(0,0,0,0.05),inset_-2px_-2px_6px_rgba(255,255,255,0.8)] border border-white flex flex-row justify-start">
                            <!-- Colored Filled Bar -->
                            <div class="h-full bg-gradient-to-r {{ $color }} rounded-full relative transition-[width] duration-1000 ease-[cubic-bezier(0.34,1.56,0.64,1)] shadow-[0_2px_5px_rgba(0,0,0,0.1)] group-hover:brightness-110 flex items-center justify-end pr-2" style="width: {{ $widthPercentage }}%">
                                <!-- Text Inside Bar for Data (Optional, hiding it on very small screens or keeping it simple, let's keep it visible inside the bar right end!) -->
                                <span class="text-[9px] md:text-[10px] font-bold text-white/90 truncate">{{ number_format($item->total) }}</span>
                                
                                <!-- Glass Effect Reflection on Right Edge -->
                                <div class="absolute right-1 top-1 bottom-1 w-1/4 max-w-[12px] bg-white/40 rounded-full blur-[1px]"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-slate-500 text-sm py-10 my-auto text-center w-full">Data penyakit kosong.</p>
            @endif
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-7 md:p-8 relative overflow-hidden flex flex-col justify-between h-full">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-red-500 opacity-5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-48 h-48 bg-orange-500 opacity-5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10">
            <h4 class="text-[11px] font-black text-red-500 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Catatan Visual
            </h4>

            <h3 class="text-2xl font-bold tracking-tight text-slate-800 mb-6">Metodologi Data</h3>
            
            <div class="space-y-4 text-sm text-slate-600 font-medium leading-relaxed mb-6">
                <p>
                    Grafik ini mengolah <span class="text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded">{{ number_format($totalKasus ?? 0) }}</span> rekam medis dari seluruh puskesmas terdaftar.
                </p>
                <p>
                    Sistem mengelompokkan diagnosa berdasarkan kode ICD-X pasien, lalu menyortir <span class="text-red-600 font-bold bg-red-50 px-1.5 py-0.5 rounded-md border border-red-100">10 penyakit tersering</span> lintas kecamatan untuk ditampilkan sebagai diagram batang visual.
                </p>
            </div>
        </div>

        <div class="relative z-10 pt-5 mt-auto border-t border-slate-100">
            <button @click="openExportModal = true" type="button" class="w-full inline-flex justify-center items-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-red-500 active:scale-95">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Laporan Rekap
            </button>
        </div>
    </div>
</div>