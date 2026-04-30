<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 text-slate-900" x-data="{ 
                    activeFilter: 'semua', 
                    search: '',
                    openExportModal: false,
                    exportFormat: 'pdf',
                    exportIncludePrefixes: [],
                    exportExcludePrefixes: [],
                    exportIncludeCodes: [],
                    exportExcludeCodes: [],
                    exportIncludeCodeSearch: '',
                    exportExcludeCodeSearch: '',
                    includeCodeOptions: [],
                    excludeCodeOptions: [],
                    includeCodeLoading: false,
                    excludeCodeLoading: false,
                    exportScope: { umum: true, kecamatan: true, puskesmas: true },
                    kecamatanFilterMode: 'all',
                    selectedKecamatan: [],
                    kecamatanSearch: '',
                    kecamatanOptions: @js($exportKecamatanOptions),
                    puskesmasFilterMode: 'all',
                    selectedPuskesmas: [],
                    puskesmasSearch: '',
                    puskesmasKecamatanCodes: [],
                    puskesmasOptions: @js($exportPuskesmasOptions),
                    exportPeriodType: 'year',
                    exportYear: '{{ date('Y') }}',
                    exportMonth: '{{ date('n') }}',
                    exportSemester: '1',
                    exportQuarter: '1',
                    exportStartDate: '{{ date('Y-m-01') }}',
                    exportEndDate: '{{ date('Y-m-d') }}',
                    isIncludeOpen: false,
                    isExcludeOpen: false,
                    icdSearchUrl: '{{ route('recap.icd.search') }}',
                    letters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
                    get filteredKecamatanOptions() {
                        const q = this.kecamatanSearch.trim().toLowerCase();
                        if (q === '') {
                            return this.kecamatanOptions;
                        }
                        return this.kecamatanOptions.filter(option =>
                            option.name.toLowerCase().includes(q) || option.code.toLowerCase().includes(q)
                        );
                    },
                    get filteredPuskesmasOptions() {
                        const q = this.puskesmasSearch.trim().toLowerCase();
                        return this.puskesmasOptions.filter(option => {
                            const matchKecamatan = this.puskesmasKecamatanCodes.length === 0
                                || this.puskesmasKecamatanCodes.includes(option.kecamatan_code);
                            if (!matchKecamatan) {
                                return false;
                            }
                            if (q === '') {
                                return true;
                            }
                            return option.name.toLowerCase().includes(q)
                                || option.code.toLowerCase().includes(q)
                                || option.kecamatan_name.toLowerCase().includes(q)
                                || option.kecamatan_code.toLowerCase().includes(q);
                        });
                    },
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
                    },
                    getKecamatanName(code) {
                        const option = this.kecamatanOptions.find(item => item.code === code);
                        return option ? option.name : code;
                    },
                    getPuskesmasName(code) {
                        const option = this.puskesmasOptions.find(item => item.code === code);
                        return option ? option.name : code;
                    },
                    toggleSelection(type, code) {
                        const key = type === 'kecamatan' ? 'selectedKecamatan' : 'selectedPuskesmas';
                        this[key] = this[key].includes(code)
                            ? this[key].filter(item => item !== code)
                            : [...this[key], code];
                    },
                    selectAllFiltered(type) {
                        const source = type === 'kecamatan' ? this.filteredKecamatanOptions : this.filteredPuskesmasOptions;
                        const key = type === 'kecamatan' ? 'selectedKecamatan' : 'selectedPuskesmas';
                        const next = new Set(this[key]);
                        source.forEach(option => next.add(option.code));
                        this[key] = Array.from(next);
                    },
                    clearSelections(type) {
                        if (type === 'kecamatan') {
                            this.selectedKecamatan = [];
                            return;
                        }
                        if (type === 'puskesmas') {
                            this.selectedPuskesmas = [];
                            return;
                        }
                        this.puskesmasKecamatanCodes = [];
                    },
                    togglePuskesmasKecamatan(code) {
                        this.puskesmasKecamatanCodes = this.puskesmasKecamatanCodes.includes(code)
                            ? this.puskesmasKecamatanCodes.filter(item => item !== code)
                            : [...this.puskesmasKecamatanCodes, code];

                        if (this.puskesmasKecamatanCodes.length === 0) {
                            return;
                        }

                        const allowed = new Set(
                            this.puskesmasOptions
                                .filter(option => this.puskesmasKecamatanCodes.includes(option.kecamatan_code))
                                .map(option => option.code)
                        );
                        this.selectedPuskesmas = this.selectedPuskesmas.filter(codeItem => allowed.has(codeItem));
                    },
                    togglePrefix(type, prefix) {
                        const key = type === 'include' ? 'exportIncludePrefixes' : 'exportExcludePrefixes';
                        this[key] = this[key].includes(prefix)
                            ? this[key].filter(item => item !== prefix)
                            : [...this[key], prefix];
                    },
                    removeCode(type, code) {
                        const key = type === 'include' ? 'exportIncludeCodes' : 'exportExcludeCodes';
                        this[key] = this[key].filter(item => item.code !== code);
                    },
                    async searchIcd(type) {
                        const searchKey = type === 'include' ? 'exportIncludeCodeSearch' : 'exportExcludeCodeSearch';
                        const resultsKey = type === 'include' ? 'includeCodeOptions' : 'excludeCodeOptions';
                        const loadingKey = type === 'include' ? 'includeCodeLoading' : 'excludeCodeLoading';
                        const query = this[searchKey].trim();

                        if (query.length < 2) {
                            this[resultsKey] = [];
                            return;
                        }

                        this[loadingKey] = true;

                        try {
                            const response = await fetch(`${this.icdSearchUrl}?q=${encodeURIComponent(query)}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const payload = await response.json();
                            const selected = new Set(this[type === 'include' ? 'exportIncludeCodes' : 'exportExcludeCodes'].map(item => item.code));
                            this[resultsKey] = (payload.data || []).filter(item => !selected.has(item.code));
                        } catch (error) {
                            this[resultsKey] = [];
                        } finally {
                            this[loadingKey] = false;
                        }
                    },
                    addCode(type, option) {
                        const codesKey = type === 'include' ? 'exportIncludeCodes' : 'exportExcludeCodes';
                        const searchKey = type === 'include' ? 'exportIncludeCodeSearch' : 'exportExcludeCodeSearch';
                        const resultsKey = type === 'include' ? 'includeCodeOptions' : 'excludeCodeOptions';

                        if (this[codesKey].some(item => item.code === option.code)) {
                            return;
                        }

                        this[codesKey] = [...this[codesKey], option];
                        this[searchKey] = '';
                        this[resultsKey] = [];
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
                                        <template x-for="prefix in exportIncludePrefixes" :key="'include-prefix-' + prefix">
                                            <input type="hidden" name="include_prefixes[]" :value="prefix">
                                        </template>
                                        <template x-for="prefix in exportExcludePrefixes" :key="'exclude-prefix-' + prefix">
                                            <input type="hidden" name="exclude_prefixes[]" :value="prefix">
                                        </template>
                                        <template x-for="item in exportIncludeCodes" :key="'include-code-' + item.code">
                                            <input type="hidden" name="include_codes[]" :value="item.code">
                                        </template>
                                        <template x-for="item in exportExcludeCodes" :key="'exclude-code-' + item.code">
                                            <input type="hidden" name="exclude_codes[]" :value="item.code">
                                        </template>
                                        <input type="hidden" name="kecamatan_filter_mode" :value="kecamatanFilterMode">
                                        <template x-for="code in selectedKecamatan" :key="'selected-kecamatan-' + code">
                                            <input type="hidden" name="selected_kecamatan[]" :value="code">
                                        </template>
                                        <input type="hidden" name="puskesmas_filter_mode" :value="puskesmasFilterMode">
                                        <template x-for="code in selectedPuskesmas" :key="'selected-puskesmas-' + code">
                                            <input type="hidden" name="selected_puskesmas[]" :value="code">
                                        </template>

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
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.umum ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="umum" x-model="exportScope.umum" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.umum ? 'text-slate-800' : 'text-slate-500'">Top N Umum</span>
                                                        </label>
                                                        <input type="number" name="top_n_umum" value="10" min="1" :disabled="!exportScope.umum" :class="exportScope.umum ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Secara keseluruhan wilayah</p>
                                                    </div>
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.kecamatan ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="kecamatan" x-model="exportScope.kecamatan" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.kecamatan ? 'text-slate-800' : 'text-slate-500'">Top N Per Kecamatan</span>
                                                        </label>
                                                        <input type="number" name="top_n_kecamatan" value="10" min="1" :disabled="!exportScope.kecamatan" :class="exportScope.kecamatan ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Ranking di tiap kecamatan</p>
                                                    </div>
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.puskesmas ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="puskesmas" x-model="exportScope.puskesmas" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.puskesmas ? 'text-slate-800' : 'text-slate-500'">Top N Per Puskesmas</span>
                                                        </label>
                                                        <input type="number" name="top_n_puskesmas" value="10" min="1" :disabled="!exportScope.puskesmas" :class="exportScope.puskesmas ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Ranking di tiap faskes</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-6">
                                                <h4 class="text-sm font-semibold text-slate-800 mb-3">3. Filter Wilayah Export</h4>
                                                <div class="space-y-4">
                                                    <div class="rounded-xl border border-slate-200 bg-white p-4" :class="!exportScope.kecamatan ? 'opacity-60' : ''">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <h5 class="text-sm font-bold text-slate-800">Top N Per Kecamatan</h5>
                                                                <p class="mt-1 text-xs text-slate-500">Pilih semua kecamatan atau batasi hanya kecamatan tertentu saat section ini ikut di-export.</p>
                                                            </div>
                                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700" x-text="kecamatanFilterMode === 'selected' && selectedKecamatan.length > 0 ? selectedKecamatan.length + ' dipilih' : 'Semua kecamatan'"></span>
                                                        </div>

                                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                            <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                                                                :class="kecamatanFilterMode === 'all' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                                                <input type="radio" value="all" x-model="kecamatanFilterMode" :disabled="!exportScope.kecamatan" class="text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-semibold text-slate-800">Semua Kecamatan</div>
                                                                    <div class="text-[11px] text-slate-500">Perilaku default seperti export saat ini.</div>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                                                                :class="kecamatanFilterMode === 'selected' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                                                <input type="radio" value="selected" x-model="kecamatanFilterMode" :disabled="!exportScope.kecamatan" class="text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-semibold text-slate-800">Kecamatan Tertentu</div>
                                                                    <div class="text-[11px] text-slate-500">Export hanya untuk kecamatan yang dipilih.</div>
                                                                </div>
                                                            </label>
                                                        </div>

                                                        <div x-show="exportScope.kecamatan && kecamatanFilterMode === 'selected'" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                                            <p class="text-[11px] text-slate-500">Jika belum ada kecamatan yang dipilih, export tetap memakai semua kecamatan.</p>
                                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                                                <input
                                                                    type="text"
                                                                    x-model="kecamatanSearch"
                                                                    placeholder="Cari nama atau kode kecamatan..."
                                                                    class="w-full md:max-w-xs border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 bg-white"
                                                                >
                                                                <div class="flex flex-wrap gap-2">
                                                                    <button type="button" @click="selectAllFiltered('kecamatan')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Pilih hasil pencarian</button>
                                                                    <button type="button" @click="clearSelections('kecamatan')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kosongkan</button>
                                                                </div>
                                                            </div>

                                                            <div class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white divide-y divide-slate-100">
                                                                <template x-for="option in filteredKecamatanOptions" :key="'kec-option-' + option.code">
                                                                    <label class="flex items-start gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                                                                        <input type="checkbox" :checked="selectedKecamatan.includes(option.code)" @change="toggleSelection('kecamatan', option.code)" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                                        <div>
                                                                            <div class="text-sm font-semibold text-slate-800" x-text="option.name"></div>
                                                                            <div class="text-[11px] text-slate-500" x-text="option.code"></div>
                                                                        </div>
                                                                    </label>
                                                                </template>
                                                                <div x-show="filteredKecamatanOptions.length === 0" x-cloak class="px-3 py-4 text-xs text-slate-500">Kecamatan tidak ditemukan.</div>
                                                            </div>

                                                            <div x-show="selectedKecamatan.length > 0" class="flex flex-wrap gap-2">
                                                                <template x-for="code in selectedKecamatan" :key="'kec-chip-' + code">
                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                                                        <span x-text="getKecamatanName(code)"></span>
                                                                        <button type="button" @click="toggleSelection('kecamatan', code)" class="text-red-500 hover:text-red-700">&times;</button>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="rounded-xl border border-slate-200 bg-white p-4" :class="!exportScope.puskesmas ? 'opacity-60' : ''">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <h5 class="text-sm font-bold text-slate-800">Top N Per Puskesmas</h5>
                                                                <p class="mt-1 text-xs text-slate-500">Gunakan filter kecamatan sebagai penyaring daftar, lalu pilih puskesmas yang memang ingin diikutkan dalam export.</p>
                                                            </div>
                                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700" x-text="puskesmasFilterMode === 'selected' && selectedPuskesmas.length > 0 ? selectedPuskesmas.length + ' dipilih' : 'Semua puskesmas'"></span>
                                                        </div>

                                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                            <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                                                                :class="puskesmasFilterMode === 'all' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                                                <input type="radio" value="all" x-model="puskesmasFilterMode" :disabled="!exportScope.puskesmas" class="text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-semibold text-slate-800">Semua Puskesmas</div>
                                                                    <div class="text-[11px] text-slate-500">Seluruh puskesmas tetap ikut pada section ini.</div>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                                                                :class="puskesmasFilterMode === 'selected' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                                                <input type="radio" value="selected" x-model="puskesmasFilterMode" :disabled="!exportScope.puskesmas" class="text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-semibold text-slate-800">Puskesmas Tertentu</div>
                                                                    <div class="text-[11px] text-slate-500">Export hanya untuk unit puskesmas yang dipilih.</div>
                                                                </div>
                                                            </label>
                                                        </div>

                                                        <div x-show="exportScope.puskesmas && puskesmasFilterMode === 'selected'" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                                            <p class="text-[11px] text-slate-500">Jika belum ada puskesmas yang dipilih, export tetap memakai semua puskesmas.</p>
                                                            <div>
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <label class="block text-xs font-bold text-slate-500">Saring daftar puskesmas berdasarkan kecamatan</label>
                                                                    <button type="button" @click="clearSelections('puskesmas-kecamatan')" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Reset filter kecamatan</button>
                                                                </div>
                                                                <div class="mt-2 flex flex-wrap gap-2">
                                                                    <template x-for="option in kecamatanOptions" :key="'pusk-kec-filter-' + option.code">
                                                                        <button type="button" @click="togglePuskesmasKecamatan(option.code)"
                                                                            :class="puskesmasKecamatanCodes.includes(option.code) ? 'border-red-300 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                                                            class="rounded-full border px-3 py-1.5 text-[11px] font-semibold transition-colors">
                                                                            <span x-text="option.name"></span>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </div>

                                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                                                <input
                                                                    type="text"
                                                                    x-model="puskesmasSearch"
                                                                    placeholder="Cari nama, kode, atau kecamatan puskesmas..."
                                                                    class="w-full md:max-w-xs border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 bg-white"
                                                                >
                                                                <div class="flex flex-wrap gap-2">
                                                                    <button type="button" @click="selectAllFiltered('puskesmas')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Pilih hasil pencarian</button>
                                                                    <button type="button" @click="clearSelections('puskesmas')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kosongkan pilihan</button>
                                                                </div>
                                                            </div>

                                                            <div class="max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white divide-y divide-slate-100">
                                                                <template x-for="option in filteredPuskesmasOptions" :key="'pusk-option-' + option.code">
                                                                    <label class="flex items-start gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                                                                        <input type="checkbox" :checked="selectedPuskesmas.includes(option.code)" @change="toggleSelection('puskesmas', option.code)" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                                        <div>
                                                                            <div class="text-sm font-semibold text-slate-800" x-text="option.name"></div>
                                                                            <div class="text-[11px] text-slate-500">
                                                                                <span x-text="option.code"></span>
                                                                                <span> | </span>
                                                                                <span x-text="option.kecamatan_name"></span>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>
                                                                <div x-show="filteredPuskesmasOptions.length === 0" x-cloak class="px-3 py-4 text-xs text-slate-500">Puskesmas tidak ditemukan dengan filter saat ini.</div>
                                                            </div>

                                                            <div x-show="selectedPuskesmas.length > 0" class="flex flex-wrap gap-2">
                                                                <template x-for="code in selectedPuskesmas" :key="'pusk-chip-' + code">
                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                                                        <span x-text="getPuskesmasName(code)"></span>
                                                                        <button type="button" @click="toggleSelection('puskesmas', code)" class="text-red-500 hover:text-red-700">&times;</button>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-800 mb-3">4. Filter Spesifik</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 bg-white p-4 border border-slate-200 rounded-lg">
                                                    <div class="col-span-1 md:col-span-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                                        <h5 class="text-xs font-bold uppercase tracking-wide text-blue-800">Panduan Filter</h5>
                                                        <div class="mt-2 space-y-1 text-xs text-blue-900 leading-relaxed">
                                                            <p><span class="font-semibold">Include Awalan Kode</span> untuk menampilkan kelompok kode berdasarkan huruf depan, misalnya <span class="font-mono">A</span>.</p>
                                                            <p><span class="font-semibold">Include Kode Spesifik</span> untuk menampilkan kode tertentu saja, misalnya <span class="font-mono">A01</span>.</p>
                                                            <p><span class="font-semibold">Exclude</span> untuk mengeluarkan data dari hasil export, baik per awalan maupun per kode spesifik.</p>
                                                            <p><span class="font-semibold">Prioritas filter:</span> jika kode masuk include dan exclude sekaligus, maka <span class="font-semibold">exclude</span> yang akan dipakai.</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-span-1 md:col-span-2">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Periode Export</label>
                                                        <select name="period_type" x-model="exportPeriodType" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer bg-slate-50">
                                                            <option value="year">Per Tahun</option>
                                                            <option value="semester">Per Semester</option>
                                                            <option value="quarter">Per Triwulan</option>
                                                            <option value="month">Per Bulan</option>
                                                            <option value="custom_date">Rentang Tanggal</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-span-1 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="exportPeriodType === 'custom_date'" x-cloak>
                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
                                                            <input type="date" name="start_date" x-model="exportStartDate" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Selesai</label>
                                                            <input type="date" name="end_date" x-model="exportEndDate" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2">
                                                        </div>
                                                    </div>
                                                    <div x-show="exportPeriodType === 'semester'" x-cloak>
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Semester</label>
                                                        <select name="semester" x-model="exportSemester" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer">
                                                            <option value="1">Semester 1 (Jan - Jun)</option>
                                                            <option value="2">Semester 2 (Jul - Des)</option>
                                                        </select>
                                                    </div>
                                                    <div x-show="exportPeriodType === 'quarter'" x-cloak>
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Triwulan</label>
                                                        <select name="quarter" x-model="exportQuarter" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer">
                                                            <option value="1">Triwulan I (Q1)</option>
                                                            <option value="2">Triwulan II (Q2)</option>
                                                            <option value="3">Triwulan III (Q3)</option>
                                                            <option value="4">Triwulan IV (Q4)</option>
                                                        </select>
                                                    </div>
                                                    <div x-show="exportPeriodType === 'month'" x-cloak>
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Bulan</label>
                                                        <select name="month" x-model="exportMonth" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer">
                                                            @php
                                                                $bulanIndoStr = ['1'=>'Januari', '2'=>'Februari', '3'=>'Maret', '4'=>'April', '5'=>'Mei', '6'=>'Juni', '7'=>'Juli', '8'=>'Agustus', '9'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];
                                                            @endphp
                                                            @foreach([1,2,3,4,5,6,7,8,9,10,11,12] as $m)
                                                                <option value="{{ $m }}">{{ $bulanIndoStr[$m] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div x-show="exportPeriodType !== 'custom_date'" :class="exportPeriodType === 'year' ? 'col-span-1 md:col-span-2' : ''">
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Tahun</label>
                                                        <select name="year" x-model="exportYear" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer">
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
                                                    <div class="pt-2 space-y-3">
                                                        <label class="block text-xs font-bold text-slate-500">Include Awalan Kode</label>
                                                        <div class="relative w-full" @click.outside="isIncludeOpen = false">
                                                            <div @click="isIncludeOpen = !isIncludeOpen; isExcludeOpen = false" class="w-full border border-slate-300 rounded-md px-3 py-2 cursor-pointer bg-white min-h-[38px] flex items-center justify-between shadow-sm transition-colors hover:bg-slate-50" :class="isIncludeOpen ? 'ring-2 ring-red-500 border-red-500' : ''">
                                                                <span x-show="exportIncludePrefixes.length === 0" class="text-slate-400 text-sm">Pilih awalan kode ICD...</span>
                                                                <div x-show="exportIncludePrefixes.length > 0" class="flex flex-wrap gap-1">
                                                                    <template x-for="prefix in exportIncludePrefixes" :key="prefix">
                                                                        <span class="bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded text-[10px]" x-text="prefix"></span>
                                                                    </template>
                                                                </div>
                                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                            </div>
                                                            <div x-show="isIncludeOpen" x-transition class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl p-3" style="display: none;">
                                                                <div class="grid grid-cols-6 sm:grid-cols-7 gap-1.5">
                                                                    <template x-for="prefix in letters" :key="prefix">
                                                                        <button type="button" @click.stop="togglePrefix('include', prefix)"
                                                                        :class="exportIncludePrefixes.includes(prefix) ? 'bg-red-600 text-white border-red-600 shadow-inner' : 'bg-white text-slate-600 border-slate-200 hover:bg-red-50 hover:border-red-300'"
                                                                        class="rounded border py-1.5 text-xs font-bold transition-colors shadow-sm" x-text="prefix"></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Include Kode Spesifik</label>
                                                            <input
                                                                type="text"
                                                                x-model="exportIncludeCodeSearch"
                                                                @input.debounce.300ms="searchIcd('include')"
                                                                placeholder="Cari kode atau nama penyakit, mis. A01"
                                                                class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2"
                                                            >
                                                            <p class="text-[10px] text-slate-400 mt-1">Ketik minimal 2 karakter untuk mencari ICD spesifik.</p>
                                                            <div x-show="includeCodeLoading" class="text-xs text-slate-400 mt-2">Mencari kode ICD...</div>
                                                            <div x-show="includeCodeOptions.length > 0" class="mt-2 max-h-40 overflow-y-auto rounded-md border border-slate-200 bg-white divide-y divide-slate-100">
                                                                <template x-for="option in includeCodeOptions" :key="option.code">
                                                                    <button type="button" @click="addCode('include', option)" class="w-full px-3 py-2 text-left hover:bg-slate-50">
                                                                        <div class="text-sm font-semibold text-slate-800" x-text="option.code"></div>
                                                                        <div class="text-xs text-slate-500" x-text="option.name"></div>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                            <div x-show="exportIncludeCodes.length > 0" class="mt-2 flex flex-wrap gap-2">
                                                                <template x-for="item in exportIncludeCodes" :key="item.code">
                                                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 border border-red-200">
                                                                        <span x-text="item.code"></span>
                                                                        <button type="button" @click="removeCode('include', item.code)" class="text-red-500 hover:text-red-700">&times;</button>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="pt-2 space-y-3">
                                                        <label class="block text-xs font-bold text-slate-500">Exclude Awalan Kode</label>
                                                        <div class="relative w-full" @click.outside="isExcludeOpen = false">
                                                            <div @click="isExcludeOpen = !isExcludeOpen; isIncludeOpen = false" class="w-full border border-slate-300 rounded-md px-3 py-2 cursor-pointer bg-white min-h-[38px] flex items-center justify-between shadow-sm transition-colors hover:bg-slate-50" :class="isExcludeOpen ? 'ring-2 ring-red-500 border-red-500' : ''">
                                                                <span x-show="exportExcludePrefixes.length === 0" class="text-slate-400 text-sm">Pilih awalan kode ICD...</span>
                                                                <div x-show="exportExcludePrefixes.length > 0" class="flex flex-wrap gap-1">
                                                                    <template x-for="prefix in exportExcludePrefixes" :key="prefix">
                                                                        <span class="bg-slate-200 text-slate-700 font-bold px-1.5 py-0.5 rounded text-[10px]" x-text="prefix"></span>
                                                                    </template>
                                                                </div>
                                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                            </div>
                                                            <div x-show="isExcludeOpen" x-transition class="absolute bottom-full mb-1 z-50 w-full bg-white border border-slate-200 rounded-md shadow-xl p-3" style="display: none;">
                                                                <div class="grid grid-cols-6 sm:grid-cols-7 gap-1.5">
                                                                    <template x-for="prefix in letters" :key="prefix">
                                                                        <button type="button" @click.stop="togglePrefix('exclude', prefix)"
                                                                        :class="exportExcludePrefixes.includes(prefix) ? 'bg-slate-700 text-white border-slate-700 shadow-inner' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300'"
                                                                        class="rounded border py-1.5 text-xs font-bold transition-colors shadow-sm" x-text="prefix"></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Exclude Kode Spesifik</label>
                                                            <input
                                                                type="text"
                                                                x-model="exportExcludeCodeSearch"
                                                                @input.debounce.300ms="searchIcd('exclude')"
                                                                placeholder="Cari kode atau nama penyakit yang ingin dikecualikan"
                                                                class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2"
                                                            >
                                                            <p class="text-[10px] text-slate-400 mt-1">Contoh: A01 agar kode tersebut tidak masuk ke hasil export.</p>
                                                            <div x-show="excludeCodeLoading" class="text-xs text-slate-400 mt-2">Mencari kode ICD...</div>
                                                            <div x-show="excludeCodeOptions.length > 0" class="mt-2 max-h-40 overflow-y-auto rounded-md border border-slate-200 bg-white divide-y divide-slate-100">
                                                                <template x-for="option in excludeCodeOptions" :key="option.code">
                                                                    <button type="button" @click="addCode('exclude', option)" class="w-full px-3 py-2 text-left hover:bg-slate-50">
                                                                        <div class="text-sm font-semibold text-slate-800" x-text="option.code"></div>
                                                                        <div class="text-xs text-slate-500" x-text="option.name"></div>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                            <div x-show="exportExcludeCodes.length > 0" class="mt-2 flex flex-wrap gap-2">
                                                                <template x-for="item in exportExcludeCodes" :key="item.code">
                                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">
                                                                        <span x-text="item.code"></span>
                                                                        <button type="button" @click="removeCode('exclude', item.code)" class="text-slate-500 hover:text-slate-700">&times;</button>
                                                                    </span>
                                                                </template>
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
