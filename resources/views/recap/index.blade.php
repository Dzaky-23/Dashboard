<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 text-slate-900" x-data="recapDashboard">
                    @if($groupedByPusk->isEmpty())
                        <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg border border-yellow-200">
                            Masih belum ada data penyebaran penyakit yang tercatat.
                        </div>
                    @else
                        @include('recap.partials.global_chart')
                        @include('recap.partials.analytics')

                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-4 mb-6 gap-4">
                            <h3 class="text-xl font-bold text-slate-800">Daftar Rekapitulasi Puskesmas</h3>
                            
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

                        <div x-show="hasResults" class="min-h-[300px]">
                            @include('recap.partials.tab_puskesmas')
                        </div>

                        <!-- Pagination Controls -->
                        <div x-show="hasResults" class="mt-6 pt-6 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <!-- Info Text -->
                            <div>
                                <!-- Info for Tab Puskesmas -->
                                <span class="text-sm text-slate-500 font-medium">
                                    Menampilkan <span class="font-bold text-slate-800" x-text="filteredPuskesmas.length === 0 ? 0 : (currentPagePuskesmas - 1) * perPagePuskesmas + 1"></span>
                                    sampai <span class="font-bold text-slate-800" x-text="Math.min(currentPagePuskesmas * perPagePuskesmas, filteredPuskesmas.length)"></span>
                                    dari <span class="font-bold text-slate-800" x-text="filteredPuskesmas.length"></span> Puskesmas
                                </span>
                            </div>

                            <!-- Buttons -->
                            <div>
                                <!-- Pagination for Tab Semua -->
                                <nav x-show="activeFilter === 'semua' && Math.ceil(filteredKecamatans.length / perPageSemua) > 1" class="inline-flex items-center -space-x-px rounded-lg border border-slate-300 bg-white overflow-hidden shadow-sm" aria-label="Pagination Semua">
                                    <button type="button" @click="currentPageSemua = Math.max(1, currentPageSemua - 1)" :disabled="currentPageSemua === 1" class="inline-flex h-10 w-10 items-center justify-center border-r border-slate-300 bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                                    </button>
                                    
                                    <template x-for="(page, idx) in getPages(Math.ceil(filteredKecamatans.length / perPageSemua), currentPageSemua)" :key="'page-semua-' + idx">
                                        <button 
                                            type="button"
                                            @click="page !== '...' ? currentPageSemua = page : null" 
                                            :class="page === '...' ? 'cursor-default pointer-events-none text-slate-400 border-r border-slate-300 bg-white' : (currentPageSemua === page ? 'bg-slate-100 text-slate-800 font-semibold border-r border-slate-300' : 'text-slate-600 hover:bg-slate-50 border-r border-slate-300')" 
                                            class="inline-flex h-10 min-w-[2.5rem] px-3 items-center justify-center text-sm font-medium transition-colors"
                                            x-text="page">
                                        </button>
                                    </template>

                                    <button type="button" @click="currentPageSemua = Math.min(Math.ceil(filteredKecamatans.length / perPageSemua), currentPageSemua + 1)" :disabled="currentPageSemua === Math.ceil(filteredKecamatans.length / perPageSemua)" class="inline-flex h-10 w-10 items-center justify-center bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                    </button>
                                </nav>

                                <!-- Pagination for Tab Puskesmas -->
                                <nav x-show="activeFilter === 'puskesmas' && Math.ceil(filteredPuskesmas.length / perPagePuskesmas) > 1" class="inline-flex items-center -space-x-px rounded-lg shadow-sm" aria-label="Pagination Puskesmas">
                                    <button type="button" @click="currentPagePuskesmas = Math.max(1, currentPagePuskesmas - 1)" :disabled="currentPagePuskesmas === 1" class="inline-flex h-10 w-10 items-center justify-center border-r border-slate-300 bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                                    </button>
                                    
                                    <template x-for="(page, idx) in getPages(Math.ceil(filteredPuskesmas.length / perPagePuskesmas), currentPagePuskesmas)" :key="'page-pusk-' + idx">
                                        <button 
                                            type="button"
                                            @click="page !== '...' ? currentPagePuskesmas = page : null" 
                                            :class="page === '...' ? 'cursor-default pointer-events-none text-slate-400 border-r border-slate-300 bg-white' : (currentPagePuskesmas === page ? 'bg-slate-100 text-slate-800 font-semibold border-r border-slate-300' : 'text-slate-600 hover:bg-slate-50 border-r border-slate-300')" 
                                            class="inline-flex h-10 min-w-[2.5rem] px-3 items-center justify-center text-sm font-medium transition-colors"
                                            x-text="page">
                                        </button>
                                    </template>

                                    <button type="button" @click="currentPagePuskesmas = Math.min(Math.ceil(filteredPuskesmas.length / perPagePuskesmas), currentPagePuskesmas + 1)" :disabled="currentPagePuskesmas === Math.ceil(filteredPuskesmas.length / perPagePuskesmas)" class="inline-flex h-10 w-10 items-center justify-center bg-white text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                    </button>
                                </nav>
                            </div>
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
                                    class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl">
                                    
                                    <form @submit.prevent="submitExport()">
                                        
                                            <div x-show="exportErrorMsg" class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold" x-text="exportErrorMsg"></div>
                                            <div class="bg-gradient-to-r from-red-600 to-rose-500 px-6 py-5 flex justify-between items-center rounded-t-2xl shadow-sm">
                                                <div class="flex items-center gap-3">
                                                    <div class="bg-white/20 p-2 rounded-xl text-white backdrop-blur-sm">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-extrabold text-white" id="modal-title">Cetak Laporan Penyakit</h3>
                                                        <p class="text-xs text-red-50 mt-0.5 font-medium">Sesuaikan parameter rekapitulasi data yang ingin diunduh.</p>
                                                    </div>
                                                </div>
                                                <button type="button" @click="openExportModal = false" class="text-white hover:text-red-100 hover:bg-white/10 rounded-full p-2 transition-colors focus:outline-none">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>

                                            <div class="px-6 py-6 bg-white space-y-8">
                                            <div class="mb-6">
                                                <h4 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs">1</span> Format Dokumen</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

                                                    <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200"
                                                        :class="exportFormat === 'csv' ? 'border-blue-500 bg-white shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                                        <input type="radio" name="format" value="csv" x-model="exportFormat" class="sr-only">
                                                        <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                        <div>
                                                            <span class="block text-sm font-bold text-slate-800">CSV Delimited</span>
                                                            <span class="block text-xs text-slate-500">Export data ringan (.csv)</span>
                                                        </div>
                                                        <div x-show="exportFormat === 'csv'" class="absolute right-4 text-blue-500"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg></div>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mb-6">
                                                <h4 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs">2</span> Pengaturan Ranking (Top N)</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.umum ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="umum" x-model="exportScope.umum" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.umum ? 'text-slate-800' : 'text-slate-500'">Top N Umum</span>
                                                        </label>
                                                        <input type="number" name="top_n_umum" x-model.number="exportTopNUmum" min="1" max="20" :disabled="!exportScope.umum" :class="exportScope.umum ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Secara keseluruhan wilayah</p>
                                                    </div>
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.kecamatan ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="kecamatan" x-model="exportScope.kecamatan" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.kecamatan ? 'text-slate-800' : 'text-slate-500'">Top N Per Kecamatan</span>
                                                        </label>
                                                        <input type="number" name="top_n_kecamatan" x-model.number="exportTopNKecamatan" min="1" max="20" :disabled="!exportScope.kecamatan" :class="exportScope.kecamatan ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Ranking di tiap kecamatan</p>
                                                    </div>
                                                    <div class="p-3 border rounded-lg transition-colors flex flex-col" :class="exportScope.puskesmas ? 'bg-white border-red-200 ring-1 ring-red-500' : 'bg-slate-50 border-slate-200 opacity-75'">
                                                        <label class="flex items-center cursor-pointer mb-2 w-full">
                                                            <input type="checkbox" name="export_scope[]" value="puskesmas" x-model="exportScope.puskesmas" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500 cursor-pointer">
                                                            <span class="ml-2 block text-xs font-bold" :class="exportScope.puskesmas ? 'text-slate-800' : 'text-slate-500'">Top N Per Puskesmas</span>
                                                        </label>
                                                        <input type="number" name="top_n_puskesmas" x-model.number="exportTopNPuskesmas" min="1" max="20" :disabled="!exportScope.puskesmas" :class="exportScope.puskesmas ? 'bg-white text-slate-900 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'" class="w-full rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 transition-colors">
                                                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">Ranking di tiap faskes</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-6">
                                                <h4 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs">3</span> Filter Wilayah</h4>
                                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                    <div class="rounded-xl border border-slate-200 bg-white p-5 transition-opacity" :class="!exportScope.kecamatan ? 'opacity-50' : ''">
                                                        <h5 class="text-sm font-bold text-slate-800 mb-4">Top N Per Kecamatan</h5>

                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <label class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all"
                                                                :class="kecamatanFilterMode === 'all' ? 'border-red-500 bg-red-50/50' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50'">
                                                                <input type="radio" value="all" x-model="kecamatanFilterMode" :disabled="!exportScope.kecamatan" class="mt-1 text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-bold text-slate-800">Semua Kecamatan</div>
                                                                    <div class="text-[11px] text-slate-500 mt-0.5">Seluruh kecamatan diikutkan.</div>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all"
                                                                :class="kecamatanFilterMode === 'selected' ? 'border-red-500 bg-red-50/50' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50'">
                                                                <input type="radio" value="selected" x-model="kecamatanFilterMode" :disabled="!exportScope.kecamatan" class="mt-1 text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-bold text-slate-800">Kecamatan Tertentu</div>
                                                                    <div class="text-[11px] text-slate-500 mt-0.5">Pilih spesifik kecamatan.</div>
                                                                </div>
                                                            </label>
                                                        </div>

                                                        <div x-show="exportScope.kecamatan && kecamatanFilterMode === 'selected'" x-cloak x-transition class="mt-5 space-y-4">
                                                            <div class="flex flex-col gap-3">
                                                                <input type="text" x-model="kecamatanSearch" placeholder="🔍 Cari kecamatan..." class="w-full border-slate-200 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-4 py-2.5 bg-slate-50">
                                                                
                                                                <div class="flex items-center justify-between px-1">
                                                                    <span class="text-xs font-semibold text-slate-500" x-text="selectedKecamatan.length + ' dipilih'"></span>
                                                                    <div class="flex gap-2">
                                                                        <button type="button" @click="selectAllFiltered('kecamatan')" class="text-[11px] font-bold text-red-600 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-md transition-colors">Pilih Semua</button>
                                                                        <button type="button" @click="clearSelections('kecamatan')" class="text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-2.5 py-1.5 rounded-md transition-colors">Kosongkan</button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="h-64 overflow-y-auto rounded-lg border border-slate-100 bg-white shadow-sm ring-1 ring-slate-900/5 divide-y divide-slate-50">
                                                                <template x-for="option in filteredKecamatanOptions" :key="'kec-option-' + option.code">
                                                                    <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors">
                                                                        <input type="checkbox" :checked="selectedKecamatan.includes(option.code)" @change="toggleSelection('kecamatan', option.code)" class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                                        <div>
                                                                            <div class="text-sm font-semibold text-slate-700" x-text="option.name"></div>
                                                                            <div class="text-[10px] text-slate-400" x-text="option.code"></div>
                                                                        </div>
                                                                    </label>
                                                                </template>
                                                                <div x-show="filteredKecamatanOptions.length === 0" x-cloak class="px-4 py-6 text-center text-xs text-slate-500">Kecamatan tidak ditemukan.</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="rounded-xl border border-slate-200 bg-white p-5 transition-opacity" :class="!exportScope.puskesmas ? 'opacity-50' : ''">
                                                        <h5 class="text-sm font-bold text-slate-800 mb-4">Top N Per Puskesmas</h5>

                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <label class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all"
                                                                :class="puskesmasFilterMode === 'all' ? 'border-red-500 bg-red-50/50' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50'">
                                                                <input type="radio" value="all" x-model="puskesmasFilterMode" :disabled="!exportScope.puskesmas" class="mt-1 text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-bold text-slate-800">Semua Puskesmas</div>
                                                                    <div class="text-[11px] text-slate-500 mt-0.5">Seluruh puskesmas diikutkan.</div>
                                                                </div>
                                                            </label>
                                                            <label class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 cursor-pointer transition-all"
                                                                :class="puskesmasFilterMode === 'selected' ? 'border-red-500 bg-red-50/50' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50'">
                                                                <input type="radio" value="selected" x-model="puskesmasFilterMode" :disabled="!exportScope.puskesmas" class="mt-1 text-red-600 border-slate-300 focus:ring-red-500">
                                                                <div>
                                                                    <div class="text-sm font-bold text-slate-800">Puskesmas Tertentu</div>
                                                                    <div class="text-[11px] text-slate-500 mt-0.5">Pilih spesifik puskesmas.</div>
                                                                </div>
                                                            </label>
                                                        </div>

                                                        <div x-show="exportScope.puskesmas && puskesmasFilterMode === 'selected'" x-cloak x-transition class="mt-5 space-y-4">
                                                            <div class="flex flex-col md:flex-row gap-3">
                                                                <div x-data="{ openKecFilter: false }" class="relative w-full md:w-1/3">
                                                                    <button type="button" @click="openKecFilter = !openKecFilter" class="w-full border border-slate-200 rounded-lg px-4 py-2.5 bg-slate-50 flex justify-between items-center gap-2 text-sm text-slate-600 shadow-sm hover:bg-slate-100 transition-colors">
                                                                        <span class="truncate font-medium" x-text="puskesmasKecamatanCodes.length > 0 ? puskesmasKecamatanCodes.length + ' Kecamatan Filter' : 'Semua Kecamatan...'"></span>
                                                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                    </button>
                                                                    <div x-show="openKecFilter" @click.outside="openKecFilter = false" x-transition class="absolute z-10 mt-2 w-full md:w-64 bg-white border border-slate-100 rounded-xl shadow-xl p-2 max-h-60 overflow-y-auto ring-1 ring-black/5">
                                                                        <div class="flex justify-end mb-2 border-b border-slate-50 pb-2 px-1">
                                                                            <button type="button" @click="clearSelections('puskesmas-kecamatan')" class="text-[10px] font-bold text-slate-400 hover:text-slate-600 uppercase tracking-wider">Reset</button>
                                                                        </div>
                                                                        <template x-for="option in kecamatanOptions" :key="'dropdown-pusk-kec-' + option.code">
                                                                            <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors">
                                                                                <input type="checkbox" :checked="puskesmasKecamatanCodes.includes(option.code)" @change="togglePuskesmasKecamatan(option.code)" class="rounded border-slate-300 text-red-600 focus:ring-red-500 h-4 w-4">
                                                                                <span class="text-sm font-medium text-slate-700" x-text="option.name"></span>
                                                                            </label>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                                
                                                                <input type="text" x-model="puskesmasSearch" placeholder="🔍 Cari puskesmas..." class="w-full md:w-2/3 border-slate-200 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-4 py-2.5 bg-slate-50">
                                                            </div>

                                                            <div class="flex items-center justify-between px-1">
                                                                <span class="text-xs font-semibold text-slate-500" x-text="selectedPuskesmas.length + ' dipilih'"></span>
                                                                <div class="flex gap-2">
                                                                    <button type="button" @click="selectAllFiltered('puskesmas')" class="text-[11px] font-bold text-red-600 bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded-md transition-colors">Pilih Semua</button>
                                                                    <button type="button" @click="clearSelections('puskesmas')" class="text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-2.5 py-1.5 rounded-md transition-colors">Kosongkan</button>
                                                                </div>
                                                            </div>

                                                            <div class="h-64 overflow-y-auto rounded-lg border border-slate-100 bg-white shadow-sm ring-1 ring-slate-900/5 divide-y divide-slate-50">
                                                                <template x-for="option in filteredPuskesmasOptions" :key="'pusk-option-' + option.code">
                                                                    <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors">
                                                                        <input type="checkbox" :checked="selectedPuskesmas.includes(option.code)" @change="toggleSelection('puskesmas', option.code)" class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                                        <div>
                                                                            <div class="text-sm font-semibold text-slate-700" x-text="option.name"></div>
                                                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                                                <span x-text="option.code"></span> &bull; <span x-text="option.kecamatan_name"></span>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                </template>
                                                                <div x-show="filteredPuskesmasOptions.length === 0" x-cloak class="px-4 py-6 text-center text-xs text-slate-500">Puskesmas tidak ditemukan.</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h4 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs">4</span> Filter Periode & Kode Penyakit</h4>
                                                <div class="bg-slate-50 p-5 border border-slate-100 rounded-2xl space-y-5">
                                                    {{-- Periode Export --}}
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-500 mb-1">Periode Export</label>
                                                        <select name="period_type" x-model="exportPeriodType" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 cursor-pointer bg-slate-50">
                                                            <option value="year">Per Tahun</option>
                                                            <option value="semester">Per Semester</option>
                                                            <option value="quarter">Per Triwulan</option>
                                                            <option value="month">Per Bulan</option>
                                                            <option value="custom_date">Rentang Tanggal</option>
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="exportPeriodType === 'custom_date'" x-cloak>
                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Mulai</label>
                                                            <input type="date" name="start_date" x-model="exportStartDate" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 mb-1">Tanggal Selesai</label>
                                                            <input type="date" name="end_date" x-model="exportEndDate" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="exportPeriodType !== 'custom_date' && exportPeriodType !== 'year'" x-cloak>
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
                                                        <div>
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
                                                    </div>
                                                    <div x-show="exportPeriodType === 'year'" x-cloak>
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

                                                    {{-- Divider --}}
                                                    <div class="border-t border-slate-200 pt-4">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Filter Kode Penyakit</label>
                                                            <div class="relative" @click.outside="showFilterTypeMenu = false" x-show="!hasIncludeFilter || !hasExcludeFilter">
                                                                <button type="button" @click="showFilterTypeMenu = !showFilterTypeMenu"
                                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-slate-700 transition-colors">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                                    Tambah Filter
                                                                </button>
                                                                <div x-show="showFilterTypeMenu" x-transition
                                                                    class="absolute right-0 z-50 mt-2 w-56 rounded-xl bg-white border border-slate-200 shadow-xl ring-1 ring-black/5 overflow-hidden"
                                                                    style="display: none;">
                                                                    <div class="py-1.5">
                                                                        <button type="button" @click="addFilter('include')" x-show="!hasIncludeFilter"
                                                                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-emerald-50 transition-colors group">
                                                                            <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                                                                            <div>
                                                                                <div class="text-sm font-bold text-slate-800 group-hover:text-emerald-700">Include</div>
                                                                                <div class="text-[10px] text-slate-400 leading-tight">Hanya tampilkan kode yang dipilih</div>
                                                                            </div>
                                                                        </button>
                                                                        <button type="button" @click="addFilter('exclude')" x-show="!hasExcludeFilter"
                                                                            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-red-50 transition-colors group">
                                                                            <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-red-200"></span>
                                                                            <div>
                                                                                <div class="text-sm font-bold text-slate-800 group-hover:text-red-700">Exclude</div>
                                                                                <div class="text-[10px] text-slate-400 leading-tight">Keluarkan kode dari hasil export</div>
                                                                            </div>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Info box when no filters --}}
                                                        <div x-show="exportFilters.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-center">
                                                            <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                                            <p class="text-xs text-slate-500 leading-relaxed">Belum ada filter kode penyakit.<br>Klik <span class="font-bold text-slate-700">"Tambah Filter"</span> untuk memfilter hasil export berdasarkan kode ICD.</p>
                                                        </div>

                                                        {{-- Dynamic filter cards --}}
                                                        <div class="space-y-3">
                                                            <template x-for="(filter, filterIdx) in exportFilters" :key="filterIdx">
                                                                <div class="rounded-xl border-2 p-4 transition-all duration-200"
                                                                    :class="getFilterTypeColor(filter.type).bg + ' ' + getFilterTypeColor(filter.type).border">
                                                                    {{-- Filter card header --}}
                                                                    <div class="flex items-center justify-between mb-3">
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold border"
                                                                                :class="getFilterTypeColor(filter.type).badge">
                                                                                <span class="w-1.5 h-1.5 rounded-full"
                                                                                    :class="filter.type === 'include' ? 'bg-emerald-500' : (filter.type === 'exclude' ? 'bg-red-500' : 'bg-amber-500')"></span>
                                                                                <span x-text="getFilterTypeLabel(filter.type)"></span>
                                                                            </span>
                                                                            <span class="text-[10px] text-slate-400 font-medium" x-text="'#' + (filterIdx + 1)"></span>
                                                                        </div>
                                                                        <button type="button" @click="removeFilter(filterIdx)"
                                                                            class="text-slate-400 hover:text-red-500 p-1 rounded-lg hover:bg-white/80 transition-colors" title="Hapus filter ini">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                                        </button>
                                                                    </div>
                                                                    {{-- Prefix selector --}}
                                                                    <div class="space-y-4">
                                                                        {{-- Baris untuk Awalan Kode --}}
                                                                        <div class="grid grid-cols-1 gap-4">
                                                                            {{-- Awalan Kode --}}
                                                                            <div>
                                                                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Awalan Kode</label>
                                                                                <div class="relative w-full" @click.outside="filter.isPrefixOpen = false">
                                                                                    <div @click="filter.isPrefixOpen = !filter.isPrefixOpen"
                                                                                        class="w-full border rounded-lg px-3 py-2 cursor-pointer bg-white min-h-[38px] flex items-center justify-between shadow-sm transition-all hover:shadow-md"
                                                                                        :class="filter.isPrefixOpen ? 'ring-2 border-transparent ' + getFilterTypeColor(filter.type).ring : 'border-slate-200'">
                                                                                        <span x-show="filter.selectedPrefixes.length === 0" class="text-slate-400 text-sm">Pilih awalan kode ICD...</span>
                                                                                        <div x-show="filter.selectedPrefixes.length > 0" class="flex flex-wrap gap-1">
                                                                                            <template x-for="prefix in filter.selectedPrefixes" :key="'fp-'+filterIdx+'-'+prefix">
                                                                                                <span class="font-bold px-1.5 py-0.5 rounded text-[10px]"
                                                                                                    :class="getFilterTypeColor(filter.type).badge" x-text="prefix"></span>
                                                                                            </template>
                                                                                        </div>
                                                                                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1 transition-transform" :class="filter.isPrefixOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                                    </div>
                                                                                    <div x-show="filter.isPrefixOpen" x-transition class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-3" style="display: none;">
                                                                                        <div class="mb-2">
                                                                                            <button type="button" @click.stop="toggleAllPrefixes(filterIdx)" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors shadow-sm" x-text="filter.selectedPrefixes.length === letters.length ? 'Hapus Semua' : 'Pilih Semua'"></button>
                                                                                        </div>
                                                                                        <div class="grid grid-cols-7 sm:grid-cols-9 gap-1.5">
                                                                                            <template x-for="prefix in letters" :key="'pl-'+filterIdx+'-'+prefix">
                                                                                                <button type="button" @click.stop="toggleFilterPrefix(filterIdx, prefix)"
                                                                                                    :class="filter.selectedPrefixes.includes(prefix) ? getFilterTypeColor(filter.type).activePrefixBg + ' text-white shadow-inner' : 'bg-white text-slate-600 border-slate-200 ' + getFilterTypeColor(filter.type).hoverBorder"
                                                                                                    class="rounded-lg border py-1.5 text-xs font-bold transition-all shadow-sm" x-text="prefix"></button>
                                                                                            </template>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div></div>

                                                                        {{-- Kode Spesifik (Lebar Penuh) --}}
                                                                        <div>
                                                                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Kode Spesifik</label>
                                                                            <input
                                                                                type="text"
                                                                                x-model="filter.codeSearch"
                                                                                @input.debounce.300ms="searchFilterIcd(filterIdx)"
                                                                                placeholder="Cari kode atau nama penyakit, mis. A01"
                                                                                class="w-full border-slate-200 rounded-lg shadow-sm focus:ring-2 text-sm px-3 py-2 bg-white"
                                                                                :class="'focus:' + getFilterTypeColor(filter.type).ring"
                                                                            >
                                                                            <p class="text-[10px] text-slate-400 mt-1">Ketik minimal 2 karakter untuk mencari kode ICD.</p>
                                                                            <div x-show="filter.codeLoading" class="text-xs text-slate-400 mt-2 flex items-center gap-1.5">
                                                                                <div class="w-3 h-3 border-2 border-slate-200 border-t-slate-500 rounded-full animate-spin"></div>
                                                                                Mencari kode ICD...
                                                                            </div>
                                                                            <div x-show="filter.codeOptions.length > 0" class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-white divide-y divide-slate-100 shadow-sm">
                                                                                <template x-for="option in filter.codeOptions" :key="'co-'+filterIdx+'-'+option.code">
                                                                                    <button type="button" @click="addFilterCode(filterIdx, option)" class="w-full px-3 py-2 text-left hover:bg-slate-50 transition-colors">
                                                                                        <div class="text-sm font-semibold text-slate-800" x-text="option.code"></div>
                                                                                        <div class="text-xs text-slate-500" x-text="option.name"></div>
                                                                                    </button>
                                                                                </template>
                                                                            </div>
                                                                            <div x-show="filter.selectedCodes.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                                                                <template x-for="item in filter.selectedCodes" :key="'sc-'+filterIdx+'-'+item.code">
                                                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold border"
                                                                                        :class="getFilterTypeColor(filter.type).badge">
                                                                                        <span x-text="item.code"></span>
                                                                                        <button type="button" @click="removeFilterCode(filterIdx, item.code)" class="opacity-60 hover:opacity-100 transition-opacity">&times;</button>
                                                                                    </span>
                                                                                </template>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
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

                        <!-- Manual Aggregation Modal -->
                        <div x-show="openAggregateModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div x-show="openAggregateModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openAggregateModal = false"></div>

                            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="openAggregateModal" 
                                    x-transition:enter="ease-out duration-300" 
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                                    x-transition:leave="ease-in duration-200" 
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                    class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-md">
                                    
                                    <div x-show="showAggregateProgress" class="p-12 text-center flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl">
                                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-red-600 mb-4"></div>
                                        <h4 class="text-base font-bold text-slate-800">Memproses Agregasi Data</h4>
                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Sistem sedang menyusun rekap harian untuk bulan terpilih secara asinkron di server.</p>
                                        <div class="mt-4 px-3 py-1.5 bg-slate-100 rounded-full text-[10px] font-bold text-slate-600 uppercase tracking-widest" x-text="'Status: ' + aggregateJobStatus"></div>
                                    </div>

                                    <div x-show="!showAggregateProgress">
                                        <div x-show="aggregateErrorMsg" class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold" x-text="aggregateErrorMsg"></div>
                                        
                                        <div class="bg-white px-6 py-4 border-b border-slate-100 flex justify-between items-center rounded-t-2xl">
                                            <div>
                                                <h3 class="text-lg font-bold text-slate-800">Agregasi Manual Harian</h3>
                                                <p class="text-xs text-slate-500 mt-0.5">Membangun ulang data agregat harian dari log mentah.</p>
                                            </div>
                                        </div>

                                        <div class="px-6 py-5 bg-slate-50/50">
                                            <div class="mb-4">
                                                <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Bulan Agregasi</label>
                                                <input type="month" x-model="aggregateMonth" class="w-full border-slate-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-3 py-2 bg-white">
                                            </div>
                                        </div>

                                        <div class="bg-white px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 rounded-b-2xl border-t border-slate-100">
                                            <button type="button" @click="openAggregateModal = false" class="inline-flex justify-center items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                                                Batal
                                            </button>
                                            <button type="button" @click="submitAggregation()" class="inline-flex justify-center items-center rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-750 focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
                                                Mulai Proses
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Floating Export Progress Widget (Pojok Kanan Atas) -->
                    <div x-show="showExportProgress || exportJobStatus === 'failed'" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed top-6 right-6 z-[9999] w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden" 
                         style="display: none;"
                         x-cloak>
                         
                        {{-- Proses (Pending/Processing) --}}
                        <div x-show="exportJobStatus === 'pending' || exportJobStatus === 'processing'" class="p-4 flex items-center gap-4">
                            <div class="flex-shrink-0 animate-spin rounded-full h-8 w-8 border-3 border-slate-100 border-t-red-600"></div>
                            <div class="flex-grow">
                                <h4 class="text-xs font-bold text-slate-800">Menyiapkan Laporan</h4>
                                <p class="text-[10px] text-slate-500 leading-tight">Proses ekspor sedang berjalan...</p>
                            </div>
                            <div class="px-2 py-0.5 bg-slate-100 rounded-full text-[9px] font-bold text-slate-600 uppercase" x-text="exportJobStatus"></div>
                        </div>

                        {{-- Sukses (Done) --}}
                        <div x-show="exportJobStatus === 'done'" class="p-4 flex items-center gap-4 bg-emerald-50/50">
                            <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="flex-grow">
                                <h4 class="text-xs font-bold text-emerald-900">Ekspor Selesai</h4>
                                <p class="text-[10px] text-emerald-700 leading-tight">Mengunduh file laporan...</p>
                            </div>
                        </div>

                        {{-- Gagal (Failed) --}}
                        <div x-show="exportJobStatus === 'failed'" class="p-4 bg-red-50/50">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div class="flex-grow">
                                    <h4 class="text-xs font-bold text-red-900">Ekspor Gagal</h4>
                                    <p class="text-[10px] text-red-700 leading-tight mt-0.5" x-text="exportErrorMsg || 'Terjadi kesalahan sistem'"></p>
                                </div>
                                <button type="button" @click="showExportProgress = false; exportJobStatus = null" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@push('scripts')
<script>
    const registerRecapDashboard = () => {
        if (window.Alpine && !window.recapDashboardRegistered) {
            window.recapDashboardRegistered = true;
            window.Alpine.data('recapDashboard', () => ({
                activeFilter: 'puskesmas', 
                    search: '',
                    openExportModal: false,
                    exportFormat: 'pdf',
                    exportTopNUmum: 10,
                    exportTopNKecamatan: 10,
                    exportTopNPuskesmas: 10,
                    exportFilters: [],
                    showFilterTypeMenu: false,
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

                    icdSearchUrl: '{{ route('recap.icd.search') }}',
                    letters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
                    currentPageSemua: 1,
                    perPageSemua: 5,
                    currentPagePuskesmas: 1,
                    perPagePuskesmas: 9,
                    
                    // New Async Aggregation and Export Properties
                    openAggregateModal: false,
                    aggregateMonth: '{{ date('Y-m') }}',
                    aggregateJobId: null,
                    aggregateJobStatus: null,
                    aggregateJobInterval: null,
                    showAggregateProgress: false,
                    aggregateErrorMsg: '',
                    
                    exportJobId: null,
                    exportJobStatus: null,
                    exportJobInterval: null,
                    showExportProgress: false,
                    exportErrorMsg: '',
                    exportPollCount: 0,
                    get filterPreviewText() {
                        if (this.exportFilters.length === 0) {
                            return 'Akan export semua penyakit tanpa filter khusus.';
                        }
                        
                        let text = 'Akan export';
                        const includes = this.exportFilters.filter(f => f.type === 'include');
                        const excludes = this.exportFilters.filter(f => f.type === 'exclude');

                        if (includes.length > 0) {
                            const prefixes = [];
                            const codes = [];
                            includes.forEach(f => {
                                prefixes.push(...f.selectedPrefixes);
                                codes.push(...f.selectedCodes.map(c => c.code));
                            });
                            
                            let incText = '';
                            if (prefixes.length > 0) {
                                incText += ' penyakit berawalan ' + prefixes.join(', ');
                            }
                            if (codes.length > 0) {
                                if (incText) incText += ' serta';
                                incText += ' kode ' + codes.join(', ');
                            }
                            text += incText;
                        } else {
                            text += ' semua penyakit';
                        }

                        if (excludes.length > 0) {
                            const prefixes = [];
                            const codes = [];
                            excludes.forEach(f => {
                                prefixes.push(...f.selectedPrefixes);
                                codes.push(...f.selectedCodes.map(c => c.code));
                            });
                            
                            let excText = '';
                            if (prefixes.length > 0) {
                                excText += ' awalan ' + prefixes.join(', ');
                            }
                            if (codes.length > 0) {
                                if (excText) excText += ' serta';
                                excText += ' kode ' + codes.join(', ');
                            }
                            text += ', kecuali ' + excText;
                        }

                        return text + '.';
                    },
                    
                    init() {
                        this.$watch('search', value => {
                            this.currentPageSemua = 1;
                            this.currentPagePuskesmas = 1;
                        });
                        this.$watch('activeFilter', value => {
                            this.currentPageSemua = 1;
                            this.currentPagePuskesmas = 1;
                        });
                    },
                    get filteredKecamatans() {
                        const q = this.search.trim().toLowerCase();
                        const list = @js($kecamatanDataList);
                        const keys = Object.keys(list);
                        if (q === '') return keys;
                        return keys.filter(kecName => {
                            const kecData = list[kecName];
                            const matchKec = kecName.toLowerCase().includes(q);
                            const matchPusk = kecData.list_puskesmas.some(p => p.toLowerCase().includes(q));
                            return matchKec || matchPusk;
                        });
                    },
                    get filteredPuskesmas() {
                        const q = this.search.trim().toLowerCase();
                        const puskNames = @js($puskesmasNames);
                        const mapping = @js($mapping);
                        const keys = Object.keys(@js($groupedByPusk->toArray()));
                        if (q === '') return keys;
                        return keys.filter(puskCode => {
                            const name = (puskNames[puskCode] || puskCode).toLowerCase();
                            const kecName = (mapping[puskCode] || '').toLowerCase();
                            const code = puskCode.toLowerCase();
                            return name.includes(q) || kecName.includes(q) || code.includes(q);
                        });
                    },
                    isKecamatanVisible(kecName) {
                        const idx = this.filteredKecamatans.indexOf(kecName);
                        if (idx === -1) return false;
                        const start = (this.currentPageSemua - 1) * this.perPageSemua;
                        const end = start + this.perPageSemua;
                        return idx >= start && idx < end;
                    },
                    isPuskesmasVisible(puskCode) {
                        const idx = this.filteredPuskesmas.indexOf(puskCode);
                        if (idx === -1) return false;
                        const start = (this.currentPagePuskesmas - 1) * this.perPagePuskesmas;
                        const end = start + this.perPagePuskesmas;
                        return idx >= start && idx < end;
                    },
                    getPages(totalPages, currentPage) {
                        const range = [];
                        const delta = 1;
                        for (let i = 1; i <= totalPages; i++) {
                            if (i === 1 || i === totalPages || (i >= currentPage - delta && i <= currentPage + delta)) {
                                range.push(i);
                            } else if (range[range.length - 1] !== '...') {
                                range.push('...');
                            }
                        }
                        return range;
                    },
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
                        if (this.activeFilter === 'semua') {
                            return this.filteredKecamatans.length > 0;
                        } else if (this.activeFilter === 'kecamatan') {
                            return this.filteredKecamatans.length > 0;
                        } else {
                            return this.filteredPuskesmas.length > 0;
                        }
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
                    addFilter(type) {
                        this.exportFilters.push({
                            type: type,
                            selectedPrefixes: [],
                            selectedCodes: [],
                            codeSearch: '',
                            codeOptions: [],
                            codeLoading: false,
                            isPrefixOpen: false
                        });
                        this.showFilterTypeMenu = false;
                    },
                    removeFilter(index) {
                        this.exportFilters.splice(index, 1);
                    },
                    toggleFilterPrefix(filterIndex, prefix) {
                        const filter = this.exportFilters[filterIndex];
                        if (filter.selectedPrefixes.includes(prefix)) {
                            filter.selectedPrefixes = filter.selectedPrefixes.filter(item => item !== prefix);
                        } else {
                            filter.selectedPrefixes = [...filter.selectedPrefixes, prefix];
                        }
                    },
                    toggleAllPrefixes(filterIndex) {
                        const filter = this.exportFilters[filterIndex];
                        if (filter.selectedPrefixes.length === this.letters.length) {
                            filter.selectedPrefixes = [];
                        } else {
                            filter.selectedPrefixes = [...this.letters];
                        }
                    },
                    removeFilterCode(filterIndex, code) {
                        const filter = this.exportFilters[filterIndex];
                        filter.selectedCodes = filter.selectedCodes.filter(item => item.code !== code);
                    },
                    async searchFilterIcd(filterIndex) {
                        const filter = this.exportFilters[filterIndex];
                        const query = filter.codeSearch.trim();

                        if (query.length < 2) {
                            filter.codeOptions = [];
                            return;
                        }

                        filter.codeLoading = true;

                        try {
                            const response = await fetch(`${this.icdSearchUrl}?q=${encodeURIComponent(query)}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const payload = await response.json();
                            const selected = new Set(filter.selectedCodes.map(item => item.code));
                            filter.codeOptions = (payload.data || []).filter(item => !selected.has(item.code));
                        } catch (error) {
                            filter.codeOptions = [];
                        } finally {
                            filter.codeLoading = false;
                        }
                    },
                    addFilterCode(filterIndex, option) {
                        const filter = this.exportFilters[filterIndex];
                        if (filter.selectedCodes.some(item => item.code === option.code)) {
                            return;
                        }
                        filter.selectedCodes = [...filter.selectedCodes, option];
                        filter.codeSearch = '';
                        filter.codeOptions = [];
                    },
                    getFilterTypeLabel(type) {
                        if (type === 'include') return 'Include';
                        if (type === 'exclude') return 'Exclude';
                        return type;
                    },
                    getFilterTypeColor(type) {
                        if (type === 'include') return { bg: 'bg-emerald-50', border: 'border-emerald-300', text: 'text-emerald-700', badge: 'bg-emerald-100 text-emerald-700 border-emerald-200', activePrefixBg: 'bg-emerald-600', ring: 'ring-emerald-500', hoverBorder: 'hover:border-emerald-300 hover:bg-emerald-50' };
                        if (type === 'exclude') return { bg: 'bg-red-50', border: 'border-red-300', text: 'text-red-700', badge: 'bg-red-100 text-red-700 border-red-200', activePrefixBg: 'bg-red-600', ring: 'ring-red-500', hoverBorder: 'hover:border-red-300 hover:bg-red-50' };
                        return { bg: 'bg-amber-50', border: 'border-amber-300', text: 'text-amber-700', badge: 'bg-amber-100 text-amber-700 border-amber-200', activePrefixBg: 'bg-amber-600', ring: 'ring-amber-500', hoverBorder: 'hover:border-amber-300 hover:bg-amber-50' };
                    },
                    get hasIncludeFilter() {
                        return this.exportFilters.some(f => f.type === 'include');
                    },
                    get hasExcludeFilter() {
                        return this.exportFilters.some(f => f.type === 'exclude');
                    },

                    async submitAggregation() {
                        this.aggregateErrorMsg = '';
                        this.showAggregateProgress = true;
                        this.aggregateJobStatus = 'pending';
                        
                        try {
                            const response = await fetch('/rekap/aggregate', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    bulan: this.aggregateMonth
                                })
                            });
                            
                            if (!response.ok) {
                                throw new Error('Gagal mengirimkan request agregasi.');
                            }
                            
                            const data = await response.json();
                            this.aggregateJobId = data.job_id;
                            
                            this.aggregateJobInterval = setInterval(() => {
                                this.checkAggregateStatus();
                            }, 1500);
                        } catch (err) {
                            this.aggregateErrorMsg = err.message;
                            this.aggregateJobStatus = 'failed';
                            this.showAggregateProgress = false;
                        }
                    },

                    async checkAggregateStatus() {
                        try {
                            const response = await fetch(`/rekap/aggregate/status/${this.aggregateJobId}`);
                            const data = await response.json();
                            this.aggregateJobStatus = data.status;
                            
                            if (data.status === 'done') {
                                clearInterval(this.aggregateJobInterval);
                                this.showAggregateProgress = false;
                                this.openAggregateModal = false;
                                alert('Agregasi selesai! Halaman akan memuat ulang.');
                                window.location.reload();
                            } else if (data.status === 'failed') {
                                clearInterval(this.aggregateJobInterval);
                                this.aggregateErrorMsg = 'Agregasi gagal di server.';
                                this.showAggregateProgress = false;
                            }
                        } catch (err) {
                            clearInterval(this.aggregateJobInterval);
                            this.aggregateErrorMsg = 'Koneksi error.';
                            this.showAggregateProgress = false;
                            this.aggregateJobStatus = 'failed';
                        }
                    },

                    async submitExport() {
                        this.openExportModal = false; // Tutup modal konfigurasi ekspor segera
                        this.exportErrorMsg = '';
                        this.showExportProgress = true;
                        this.exportJobStatus = 'pending';
                        this.exportPollCount = 0;
                        
                        const scopes = [];
                        if (this.exportScope.umum) scopes.push('umum');
                        if (this.exportScope.kecamatan) scopes.push('kecamatan');
                        if (this.exportScope.puskesmas) scopes.push('puskesmas');

                        if (scopes.length === 0) {
                            alert('Pilih minimal satu cakupan laporan untuk diekspor.');
                            this.showExportProgress = false;
                            this.exportJobStatus = null;
                            return;
                        }

                        const topNUmum = this.exportTopNUmum || 10;
                        const topNKecamatan = this.exportTopNKecamatan || 10;
                        const topNPuskesmas = this.exportTopNPuskesmas || 10;
                        
                        let from = '';
                        let to = '';
                        const periodType = this.exportPeriodType;
                        const year = this.exportYear;
                        
                        if (periodType === 'year') {
                            from = `${year}-01-01`;
                            to = `${year}-12-31`;
                        } else if (periodType === 'semester') {
                            const sem = this.exportSemester;
                            from = sem === '1' ? `${year}-01-01` : `${year}-07-01`;
                            to = sem === '1' ? `${year}-06-30` : `${year}-12-31`;
                        } else if (periodType === 'quarter') {
                            const q = this.exportQuarter;
                            if (q === '1') { from = `${year}-01-01`; to = `${year}-03-31`; }
                            else if (q === '2') { from = `${year}-04-01`; to = `${year}-06-30`; }
                            else if (q === '3') { from = `${year}-07-01`; to = `${year}-09-30`; }
                            else if (q === '4') { from = `${year}-10-01`; to = `${year}-12-31`; }
                        } else if (periodType === 'month') {
                            const m = String(this.exportMonth).padStart(2, '0');
                            const lastDay = new Date(year, this.exportMonth, 0).getDate();
                            from = `${year}-${m}-01`;
                            to = `${year}-${m}-${lastDay}`;
                        } else if (periodType === 'custom_date') {
                            from = this.exportStartDate;
                            to = this.exportEndDate;
                        }
                        
                        // Build filters from the unified exportFilters array
                        const includePrefixes = [];
                        const excludePrefixes = [];
                        const includeCodes = [];
                        const excludeCodes = [];
                        this.exportFilters.forEach(f => {
                            if (f.type === 'include') {
                                includePrefixes.push(...f.selectedPrefixes);
                                includeCodes.push(...f.selectedCodes.map(c => c.code));
                            } else if (f.type === 'exclude') {
                                excludePrefixes.push(...f.selectedPrefixes);
                                excludeCodes.push(...f.selectedCodes.map(c => c.code));
                            }
                        });

                        const payload = {
                            from: from,
                            to: to,
                            scopes: scopes,
                            top_n_umum: topNUmum,
                            top_n_kecamatan: topNKecamatan,
                            top_n_puskesmas: topNPuskesmas,
                            kecamatan_filter_mode: this.kecamatanFilterMode,
                            selected_kecamatan: this.selectedKecamatan,
                            puskesmas_filter_mode: this.puskesmasFilterMode,
                            selected_puskesmas: this.selectedPuskesmas,
                            format: this.exportFormat,
                            filters: {
                                include_prefixes: includePrefixes,
                                exclude_prefixes: excludePrefixes,
                                include_codes: includeCodes,
                                exclude_codes: excludeCodes
                            }
                        };
                        
                        try {
                            const response = await fetch('/rekap/export/dispatch', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            });
                            
                            if (!response.ok) {
                                throw new Error('Gagal memproses request export.');
                            }
                            
                            const data = await response.json();
                            this.exportJobId = data.job_id;
                            
                            this.exportJobInterval = setInterval(() => {
                                this.checkExportStatus();
                            }, 1500);
                            
                        } catch (err) {
                            this.exportErrorMsg = err.message;
                            this.exportJobStatus = 'failed';
                        }
                    },

                    async checkExportStatus() {
                        this.exportPollCount++;
                        if (this.exportPollCount > 120) {
                            clearInterval(this.exportJobInterval);
                            this.exportErrorMsg = 'Proses ekspor melebihi batas waktu (timeout 3 menit).';
                            this.exportJobStatus = 'failed';
                            return;
                        }

                        try {
                            const response = await fetch(`/rekap/export/status/${this.exportJobId}`);
                            if (!response.ok) {
                                throw new Error('Koneksi error saat memeriksa status.');
                            }
                            const data = await response.json();
                            this.exportJobStatus = data.status;
                            
                            if (data.status === 'done') {
                                clearInterval(this.exportJobInterval);
                                this.exportJobStatus = 'done';
                                window.location.href = `/rekap/export/download/${this.exportJobId}`;
                                setTimeout(() => {
                                    this.showExportProgress = false;
                                    this.exportJobStatus = null;
                                }, 3000);
                            } else if (data.status === 'failed') {
                                clearInterval(this.exportJobInterval);
                                this.exportJobStatus = 'failed';
                                this.exportErrorMsg = 'Proses ekspor gagal di server.';
                            }
                        } catch (err) {
                            clearInterval(this.exportJobInterval);
                            this.exportErrorMsg = err.message || 'Koneksi error saat memeriksa status.';
                            this.exportJobStatus = 'failed';
                        }
                    }
            }));
        }
    };

    if (window.Alpine) {
        registerRecapDashboard();
    } else {
        document.addEventListener('alpine:init', registerRecapDashboard);
    }
</script>
@endpush
</x-app-layout>
