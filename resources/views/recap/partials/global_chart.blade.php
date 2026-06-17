{{-- ==================== TOP 10 PENYAKIT GLOBAL (Full Width) ==================== --}}
<div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Top 10 Penyakit Global</h4>
                <p class="text-xs text-slate-400 mt-0.5">Diagnosis teratas berdasarkan jumlah kasus keseluruhan</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Stats strip --}}
            <div class="hidden sm:flex items-center gap-4 text-xs text-slate-500">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    <span class="font-semibold text-slate-700">{{ number_format($chartData->sum('total')) }}</span> Total Kasus
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    <span class="font-semibold text-slate-700">{{ $chartData->count() }}</span> Penyakit
                </div>
            </div>
            
            {{-- Year selector --}}
            <form id="filterYearForm" action="{{ route('recap.index') }}" method="GET">
                <select name="year" id="year" onchange="document.getElementById('filterYearForm').submit()" class="appearance-none bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg focus:ring-red-500 focus:border-red-500 pl-3 pr-8 py-2 cursor-pointer hover:bg-slate-100 transition-colors shadow-sm">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $yearInput == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Chart --}}
    <div class="px-6 py-5 md:px-8 space-y-2.5">
        @if(isset($chartData) && $chartData->isNotEmpty())
            @foreach($chartData as $index => $item)
                @php
                    $percentage = ($item->total / $maxChartWidth) * 100;
                    $widthPercentage = max($percentage, 8);
                    $redShades = ['bg-red-900', 'bg-red-800', 'bg-red-700', 'bg-red-600', 'bg-red-500', 'bg-red-500', 'bg-red-400', 'bg-red-400', 'bg-red-400', 'bg-red-400'];
                    $color = $redShades[min($index, count($redShades) - 1)];
                @endphp
                <div class="flex flex-row items-center w-full group relative cursor-pointer">
                    {{-- Tooltip on hover --}}
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300 bg-slate-800 shadow-xl rounded-xl px-4 py-2 text-sm font-bold text-white whitespace-nowrap pointer-events-none scale-95 group-hover:scale-100 z-50">
                        {{ $item->status }} ({{ number_format($item->total) }} Kasus)
                        <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 transform rotate-45"></div>
                    </div>

                    {{-- Label --}}
                    <div class="mr-3 md:mr-4 flex flex-shrink-0 items-center justify-end w-12 md:w-14">
                        <span class="block text-[11px] md:text-xs font-bold text-slate-600 truncate" title="{{ $item->label }}">{{ $item->label }}</span>
                    </div>

                    {{-- Bar Track --}}
                    <div class="flex-grow h-7 md:h-8 bg-slate-100/50 rounded-full p-1 shadow-[inset_2px_2px_6px_rgba(0,0,0,0.05),inset_-2px_-2px_6px_rgba(255,255,255,0.8)] border border-white flex flex-row justify-start">
                        <div class="h-full {{ $color }} rounded-full relative transition-[width] duration-1000 ease-[cubic-bezier(0.34,1.56,0.64,1)] shadow-[0_2px_5px_rgba(0,0,0,0.1)] group-hover:brightness-110 flex items-center justify-end pr-2.5" style="width: {{ $widthPercentage }}%">
                            <span class="text-[9px] md:text-[10px] font-bold text-white/90 truncate">{{ number_format($item->total) }}</span>
                            <div class="absolute right-1 top-1 bottom-1 w-1/4 max-w-[12px] bg-white/40 rounded-full blur-[1px]"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-slate-500 text-sm py-10 text-center w-full">Data penyakit kosong.</p>
        @endif
    </div>
</div>