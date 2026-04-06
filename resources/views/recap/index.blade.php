<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 text-slate-900" x-data="{ 
                    activeFilter: 'semua', 
                    search: '',
                    openExportModal: false,
                    exportFormat: 'pdf',
                    exportIncludeLetters: [],
                    exportExcludeLetters: [],
                    exportPeriodType: 'year',
                    exportYear: '{{ date('Y') }}',
                    exportMonth: '{{ date('n') }}',
                    isIncludeOpen: false,
                    isExcludeOpen: false,
                    letters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
                    get hasResults() {
                        if (this.search === '') return true;
                        const q = this.search.toLowerCase();
                        
                        let elements = [];
                        if (this.activeFilter === 'semua') {
                            elements = Array.from($el.querySelectorAll('.tab-semua [data-search-key]'));
                        } else if (this.activeFilter === 'kecamatan') {
                            elements = Array.from($el.querySelectorAll('.tab-kecamatan [data-search-key]'));
                        } else {
                            elements = Array.from($el.querySelectorAll('.tab-puskesmas [data-search-key]'));
                        }
                        return elements.some(el => el.dataset.searchKey.includes(q));
                    }
                }">
                    @if($groupedByPusk->isEmpty())
                        <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg border border-yellow-200">
                            Masih belum ada data penyebaran penyakit yang tercatat.
                        </div>
                    @else
                        @include('recap.global_chart')

                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-4 mb-6 gap-4">
                            <h3 class="text-xl font-bold text-slate-800">Daftar Rekapitulasi Wilayah</h3>
                            
                            <div class="relative w-full md:w-72">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input x-model="search" type="text" placeholder="Cari wilayah/puskesmas..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm transition duration-150 ease-in-out shadow-sm">
                                <button x-show="search.length > 0" x-transition @click="search = ''" x-cloak class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="h-4 w-4 text-slate-400 hover:text-slate-600 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-8 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 sm:inline-flex w-full sm:w-auto shadow-inner">
                            <button @click="activeFilter = 'semua'" :class="{'bg-white text-red-700 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'semua', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'semua'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out">
                                Tampilkan Semua
                            </button>
                            <button style="display: none;" @click="activeFilter = 'kecamatan'" :class="{'bg-white text-red-700 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'kecamatan', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'kecamatan'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out flex items-center justify-center gap-2">
                                {{-- <span x-show="activeFilter === 'kecamatan'" class="w-1.5 h-1.5 rounded-full bg-red-600"></span> --}}
                                Hanya Kecamatan
                            </button>
                            <button @click="activeFilter = 'puskesmas'" :class="{'bg-white text-red-700 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'puskesmas', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'puskesmas'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out flex items-center justify-center gap-2">
                                {{-- <span x-show="activeFilter === 'puskesmas'" class="w-1.5 h-1.5 rounded-full bg-red-600"></span> --}}
                                Hanya Puskesmas
                            </button>
                        </div>

                        <div x-show="hasResults" class="min-h-[300px]">
                            @include('recap.tab_semua')
                            @include('recap.tab_kecamatan')
                            @include('recap.tab_puskesmas')
                        </div>

                        <div x-show="!hasResults" x-cloak class="p-10 mt-6 text-center bg-slate-50 border border-dashed border-slate-300 rounded-xl">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 mb-4">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Puskesmas/Kecamatan Tidak Ditemukan</h3>
                            <p class="text-sm text-slate-500 mt-1">Sistem tidak menemukan fasilitas yang cocok dengan sebutan "<span class="font-semibold text-slate-700" x-text="search"></span>".</p>
                            <button @click="search = ''" class="mt-4 text-sm font-semibold text-red-600 hover:text-red-500 transition-colors">Lihat semua daftar</button>
                        </div>

                        <div x-show="openExportModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div x-show="openExportModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openExportModal = false"></div>

                            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="openExportModal" 
                                    x-transition:enter="ease-out duration-300" 
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                                    x-transition:leave="ease-in duration-200" 
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                    class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-2xl">
                                    
                                    <form action="{{ route('recap.export') }}" method="GET">
                                        <!-- Hidden Inputs untuk A-Z -->
                                        <input type="hidden" name="include_icd" :value="exportIncludeLetters.join(',')">
                                        <input type="hidden" name="exclude_icd" :value="exportExcludeLetters.join(',')">

                                        <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center rounded-t-2xl">
                                            <div>
                                                <h3 class="text-lg font-bold text-slate-800" id="modal-title">Cetak Laporan Penyakit</h3>
                                                <p class="text-xs text-slate-500 mt-0.5">Sesuaikan parameter rekapitulasi data yang ingin di-export.</p>
                                            </div>
                                            {{-- <button type="button" @click="openExportModal = false" class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-full p-2 transition-colors">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button> --}}
                                        </div>

                                        <div class="px-6 py-5 bg-slate-50/50">
                                            <div class="mb-6">
                                                <h4 class="text-sm font-semibold text-slate-800 mb-3">1. Format Dokumen</h4>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200"
                                                        :class="exportFormat === 'pdf' ? 'border-red-500 bg-white shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                                        <input type="radio" name="format" value="pdf" x-model="exportFormat" class="sr-only">
                                                        <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 9h1.5m1.5 0H15m-4.5 4h.01M9 17h6"></path></svg>
                                                        <div>
                                                            <span class="block text-sm font-bold text-slate-800">PDF Document</span>
                                                            <span class="block text-xs text-slate-500">Cocok untuk dicetak (.pdf)</span>
                                                        </div>
                                                        <div x-show="exportFormat === 'pdf'" class="absolute right-4 text-red-500"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg></div>
                                                    </label>

                                                    <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200"
                                                        :class="exportFormat === 'excel' ? 'border-green-500 bg-white shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                                        <input type="radio" name="format" value="excel" x-model="exportFormat" class="sr-only">
                                                        <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17h6"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 8h-5v5h5V8z"></path></svg>
                                                        <div>
                                                            <span class="block text-sm font-bold text-slate-800">Excel Spreadsheet</span>
                                                            <span class="block text-xs text-slate-500">Analisis lanjutan (.xlsx)</span>
                                                        </div>
                                                        <div x-show="exportFormat === 'excel'" class="absolute right-4 text-green-600"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg></div>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mb-6">
                                                <h4 class="text-sm font-semibold text-slate-800 mb-3">2. Pengaturan Ranking (Tampilkan Top N)</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div class="bg-white p-3 border border-slate-200 rounded-lg">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Top N Umum</label>
                                                        <input type="number" name="top_n_umum" value="10" min="1" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2">
                                                        <p class="text-[10px] text-slate-400 mt-1 leading-tight">Secara keseluruhan wilayah</p>
                                                    </div>
                                                    <div class="bg-white p-3 border border-slate-200 rounded-lg">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Top N Per Kecamatan</label>
                                                        <input type="number" name="top_n_kecamatan" value="10" min="1" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2">
                                                        <p class="text-[10px] text-slate-400 mt-1 leading-tight">Ranking di tiap kecamatan</p>
                                                    </div>
                                                    <div class="bg-white p-3 border border-slate-200 rounded-lg">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Top N Per Puskesmas</label>
                                                        <input type="number" name="top_n_puskesmas" value="10" min="1" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2">
                                                        <p class="text-[10px] text-slate-400 mt-1 leading-tight">Ranking di tiap faskes</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-800 mb-3">3. Filter Spesifik</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 bg-white p-4 border border-slate-200 rounded-lg">
                                                    <div class="col-span-1 md:col-span-2">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Periode Export</label>
                                                        <select name="period_type" x-model="exportPeriodType" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2 cursor-pointer bg-slate-50">
                                                            <option value="year">Per Tahun</option>
                                                            <option value="month">Per Bulan</option>
                                                        </select>
                                                    </div>
                                                    <div x-show="exportPeriodType === 'month'" x-cloak>
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Bulan</label>
                                                        <select name="month" x-model="exportMonth" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2 cursor-pointer">
                                                            @php
                                                                $bulanIndoStr = ['1'=>'Januari', '2'=>'Februari', '3'=>'Maret', '4'=>'April', '5'=>'Mei', '6'=>'Juni', '7'=>'Juli', '8'=>'Agustus', '9'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];
                                                            @endphp
                                                            @foreach([1,2,3,4,5,6,7,8,9,10,11,12] as $m)
                                                                <option value="{{ $m }}">{{ $bulanIndoStr[$m] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div :class="exportPeriodType === 'month' ? '' : 'col-span-1 md:col-span-2'">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Tahun</label>
                                                        <select name="year" x-model="exportYear" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-3 py-2 cursor-pointer">
                                                            @if(isset($availableYears) && $availableYears->isNotEmpty())
                                                                @foreach($availableYears as $y)
                                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                                @endforeach
                                                            @else
                                                                <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                                                <option value="{{ date('Y') - 1 }}">{{ date('Y') - 1 }}</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <div class="pt-2">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Include Kategori (A-Z)</label>
                                                        <div class="relative w-full" @click.outside="isIncludeOpen = false">
                                                            <div @click="isIncludeOpen = !isIncludeOpen; isExcludeOpen = false" class="w-full border border-slate-300 rounded-md px-3 py-2 cursor-pointer bg-white min-h-[38px] flex items-center justify-between shadow-sm transition-colors hover:bg-slate-50" :class="isIncludeOpen ? 'ring-2 ring-red-500 border-red-500' : ''">
                                                                <span x-show="exportIncludeLetters.length === 0" class="text-slate-400 text-sm">Pilih Kategori (Bisa >1)...</span>
                                                                <div x-show="exportIncludeLetters.length > 0" class="flex flex-wrap gap-1">
                                                                    <template x-for="l in exportIncludeLetters" :key="l">
                                                                        <span class="bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded text-[10px]" x-text="l"></span>
                                                                    </template>
                                                                </div>
                                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                            </div>
                                                            <!-- Menu Dropdown Jatuh ke bawah -->
                                                            <div x-show="isIncludeOpen" x-transition class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl p-3" style="display: none;">
                                                                <div class="grid grid-cols-6 sm:grid-cols-7 gap-1.5">
                                                                    <template x-for="l in letters" :key="l">
                                                                        <button type="button" @click.stop="exportIncludeLetters.includes(l) ? exportIncludeLetters = exportIncludeLetters.filter(x => x !== l) : exportIncludeLetters.push(l)"
                                                                        :class="exportIncludeLetters.includes(l) ? 'bg-red-600 text-white border-red-600 shadow-inner' : 'bg-white text-slate-600 border-slate-200 hover:bg-red-50 hover:border-red-300'"
                                                                        class="rounded border py-1.5 text-xs font-bold transition-colors shadow-sm" x-text="l"></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="pt-2">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Exclude Kategori (A-Z)</label>
                                                        <div class="relative w-full" @click.outside="isExcludeOpen = false">
                                                            <div @click="isExcludeOpen = !isExcludeOpen; isIncludeOpen = false" class="w-full border border-slate-300 rounded-md px-3 py-2 cursor-pointer bg-white min-h-[38px] flex items-center justify-between shadow-sm transition-colors hover:bg-slate-50" :class="isExcludeOpen ? 'ring-2 ring-red-500 border-red-500' : ''">
                                                                <span x-show="exportExcludeLetters.length === 0" class="text-slate-400 text-sm">Pilih Kategori (Bisa >1)...</span>
                                                                <div x-show="exportExcludeLetters.length > 0" class="flex flex-wrap gap-1">
                                                                    <template x-for="l in exportExcludeLetters" :key="l">
                                                                        <span class="bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded text-[10px]" x-text="l"></span>
                                                                    </template>
                                                                </div>
                                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                            </div>
                                                            <!-- Menu Dropdown Jatuh ke atas (bottom-full) agar tidak terpotong window jika di HP -->
                                                            <div x-show="isExcludeOpen" x-transition class="absolute bottom-full mb-1 z-50 w-full bg-white border border-slate-200 rounded-md shadow-xl p-3" style="display: none;">
                                                                <div class="grid grid-cols-6 sm:grid-cols-7 gap-1.5">
                                                                    <template x-for="l in letters" :key="l">
                                                                        <button type="button" @click.stop="exportExcludeLetters.includes(l) ? exportExcludeLetters = exportExcludeLetters.filter(x => x !== l) : exportExcludeLetters.push(l)"
                                                                        :class="exportExcludeLetters.includes(l) ? 'bg-red-600 text-white border-red-600 shadow-inner' : 'bg-white text-slate-600 border-slate-200 hover:bg-red-50 hover:border-red-300'"
                                                                        class="rounded border py-1.5 text-xs font-bold transition-colors shadow-sm" x-text="l"></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 rounded-b-2xl border-t border-slate-100">
                                            <button type="button" @click="openExportModal = false" class="inline-flex justify-center items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                                                Batal
                                            </button>
                                            <button type="submit" class="inline-flex justify-center items-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                Download Laporan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>