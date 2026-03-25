<!-- Col 1-2: Grafik Neumorphic -->
<div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Tren Penyakit Teratas
        </h4>
    </div>
    
    <div class="p-6 md:p-8 flex-grow flex flex-col justify-center min-h-[340px]">
        @if(isset($rekapChartData) && $rekapChartData->isNotEmpty())
            <div class="space-y-4 w-full">
                @foreach($rekapChartData as $index => $item)
                    @php
                        $widthPercentage = ($maxChartWidth > 0 ? ($item->count / $maxChartWidth) * 100 : 0);
                    @endphp
                    <div class="flex items-center gap-4 group">
                        <div class="w-16 md:w-20 flex-shrink-0 text-right">
                            <span class="text-xs md:text-sm font-bold text-slate-700">{{ $item->kode_penyakit }}</span>
                        </div>
                        <div class="flex-grow flex items-center gap-3">
                            <div class="w-full bg-slate-100 rounded-md h-7 overflow-hidden flex items-center shadow-inner">
                                <div class="bg-rose-600 hover:bg-rose-400 h-full rounded-md transition-all duration-1000 ease-out" style="width: {{ max($widthPercentage, 1) }}%"></div>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-slate-600 w-12">{{ number_format($item->count) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center text-slate-400 py-10 w-full h-full">
                <svg class="w-10 h-10 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <p class="text-sm font-medium">Data grafis tidak tersedia.</p>
            </div>
        @endif
    </div>
</div>
