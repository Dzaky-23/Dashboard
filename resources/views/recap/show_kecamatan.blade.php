<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('recap.index', ['kecamatan' => $kecamatan]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Daftar Puskesmas
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-200">
                <!-- Header Component -->
                <div class="bg-red-600 px-8 py-6 border-b border-red-700 flex justify-between items-center bg-gradient-to-r from-red-600 to-red-700">
                    <div>
                        <h3 class="text-2xl font-bold text-white flex items-center">
                            Kecamatan {{ $kecamatan }}
                        </h3>
                        <p class="text-sm text-red-100 mt-1 font-medium flex items-center">
                            Meliputi {{ $totalPuskesmas }} Puskesmas   
                        </p>
                    </div>
                    <div class="text-right bg-black/10 px-4 py-2 rounded-xl backdrop-blur-sm">
                        <span class="block text-3xl font-bold text-white leading-none">{{ number_format($totalKasus) }}</span>
                        <span class="block text-xs text-red-100 mt-1 uppercase tracking-wider font-semibold">Total Kasus</span>
                    </div>
                </div>

                <!-- Alert Warning Limit -->
                @if(isset($warningLimit) && $warningLimit && !$isNotFinished)
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-2 mx-8 mt-6 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-700">
                                    {{ $warningLimit }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Filter Bar Utama -->
                <div class="px-8 py-4 border-b border-slate-200 bg-white flex justify-between items-center flex-wrap gap-4">
                    <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filter Rekapitulasi:
                    </h4>
                    <!-- Form Filter Waktu & Limit N -->
                    <form action="{{ route('recap.kecamatan.show', $kecamatan) }}" method="GET" class="flex flex-wrap items-center justify-end gap-2" x-data="{ pType: '{{ $periodType }}' }">
                        <select name="period_type" x-model="pType" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-slate-50 shadow-sm cursor-pointer">
                            <option value="all">Semua Waktu</option>
                            <option value="year">Per Tahun</option>
                            <option value="semester">Per Semester</option>
                            <option value="quarter">Per Triwulan</option>
                            <option value="month">Per Bulan</option>
                        </select>
                        
                        <template x-if="pType !== 'all'">
                            <select name="year" class="w-24 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
                                @for($y = 2024; $y <= date('Y') + 1; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </template>

                        <template x-if="pType === 'semester'">
                            <select name="semester" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
                                <option value="1" {{ $semester == 1 ? 'selected' : '' }}>Semester 1</option>
                                <option value="2" {{ $semester == 2 ? 'selected' : '' }}>Semester 2</option>
                            </select>
                        </template>

                        <template x-if="pType === 'quarter'">
                            <select name="quarter" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
                                <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Q1 (Jan-Mar)</option>
                                <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Q2 (Apr-Jun)</option>
                                <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Q3 (Jul-Sep)</option>
                                <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Q4 (Okt-Des)</option>
                            </select>
                        </template>

                        <template x-if="pType === 'month'">
                            <select name="month" class="w-32 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm cursor-pointer">
                                @php
                                    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                @endphp
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </template>

                        <div class="flex items-center ml-2 pl-3 space-x-2 border-l border-slate-200">
                            <label for="limit" class="text-xs font-semibold text-slate-500">N:</label>
                            <input type="number" name="limit" id="limit" value="{{ $limit }}" min="1" class="w-16 text-xs font-bold border-slate-300 rounded-md py-1.5 px-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1.5 px-4 rounded-md shadow-sm transition-colors ml-1">Terapkan</button>
                        </div>
                    </form>
                </div>

                @if($isNotFinished)
                    <!-- UI Block: Not Finished -->
                    <div class="p-10 my-8 text-center bg-slate-50 border border-dashed border-slate-300 mx-8 rounded-xl flex flex-col items-center justify-center min-h-[400px]">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 mb-5">
                            <svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Hasil Rekap Belum Tersedia</h3>
                        <p class="text-sm text-slate-500 mt-2 max-w-sm">Periode rekapitulasi waktu yang Anda pilih belum selesai masa perhitungannya atau berada pada rentang masa depan.</p>
                        <a href="{{ route('recap.kecamatan.show', $kecamatan) }}" class="mt-6 px-4 py-2 bg-white border border-slate-300 rounded-md text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">Kembalikan ke Semua Waktu</a>
                    </div>
                @else
                <!-- Section Grafik & Analitik Ringkas -->
                <div class="px-8 py-8 border-b border-slate-200 bg-slate-50/50">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
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
                                                $widthPercentage = ($item->count / $maxChartWidth) * 100;
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

                        <!-- Col 3: Snapshot Statistik Sederhana -->
                        <div class="flex flex-col gap-4">
                            <!-- Card Total Unik Diagnosa -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0 text-red-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Diagnosa Varian</span>
                                    <h4 class="text-2xl font-black text-slate-800">{{ number_format($totalDiagnosaUnik) }}</h4>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">Jenis penyakit tercatat</p>
                                </div>
                            </div>
                            
                            <!-- Card Total Puskesmas -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex-grow flex flex-col justify-center relative overflow-hidden group">
                                <span class="block text-xs font-bold text-red-500 uppercase tracking-widest mb-2 flex items-center gap-1.5 relative z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    Fasilitas Unit
                                </span>
                                <h4 class="text-4xl font-black text-slate-800 tracking-tight leading-none mb-2 relative z-10">{{ number_format($totalPuskesmas) }}</h4>
                                <div class="flex items-end gap-2 relative z-10 mt-1">
                                    <span class="text-xs font-semibold text-slate-500 mb-1">Total Unit Puskesmas Beroperasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Daftar Puskesmas -->
                <div class="px-8 flex-col mb-4">
                    <h4 class="text-lg font-bold text-slate-700 mt-6 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Daftar Fasilitas Puskesmas
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($puskesmasStats as $pstat)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition-all duration-300 group relative flex flex-col justify-between">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h5 class="font-bold text-slate-800 text-base flex items-center gap-1.5">
                                        Puskesmas {{ $pstat->nama }}
                                    </h5>
                                    @if(isset($pstat->top_penyakit))
                                    <p class="text-[11px] font-semibold text-red-600 mt-1 uppercase tracking-wider bg-red-50 inline-block px-2 py-0.5 rounded">
                                        Top: {{ $pstat->top_penyakit->kode_penyakit }}
                                    </p>
                                    @endif
                                </div>
                                <div class="bg-slate-50 w-10 h-10 rounded-full flex items-center justify-center border border-slate-100 group-hover:bg-red-50 transition-colors">
                                    <svg class="w-5 h-5 text-slate-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                            </div>
                            <div class="flex items-end justify-between mt-auto">
                                <div>
                                    <span class="block text-2xl font-black text-slate-700 leading-none">{{ number_format($pstat->total_kasus) }}</span>
                                    <span class="text-xs font-semibold text-slate-400">Total Kasus Riwayat</span>
                                </div>
                                <a href="{{ route('recap.show', $pstat->nama) }}" class="text-xs font-bold text-red-600 hover:text-red-800 flex items-center group-hover:translate-x-1 transition-transform">
                                    Lihat Data <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Section Tabel Data Penyakit -->
                <div class="p-0 mt-8 border-t border-slate-200 pt-2" x-data="{
                    searchQuery: '',
                    filterMode: 'include',
                    tempFilterMode: 'include',
                    selectedLetters: [],
                    tempSelectedLetters: [],
                    sortMode: 'highest',
                    letters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
                    isFilterOpen: false,
                    rawData: {{ $rekapData->map(function($item, $index) { return ['kode_penyakit' => $item->kode_penyakit, 'count' => $item->count, 'is_top' => $index === 0]; })->toJson() }},
                    get filteredData() {
                        let result = this.rawData;
                        
                        // 1. Terapkan Pencarian Global (Search)
                        if (this.searchQuery.trim() !== '') {
                            const sq = this.searchQuery.toLowerCase();
                            result = result.filter(i => i.kode_penyakit.toLowerCase().includes(sq));
                        }
                        
                        // 2. Terapkan Filter Kategori (Include/Exclude huruf awal)
                        if (this.selectedLetters.length > 0) {
                            result = result.filter(i => {
                                const firstLetter = i.kode_penyakit.charAt(0).toUpperCase();
                                const match = this.selectedLetters.includes(firstLetter);
                                return this.filterMode === 'include' ? match : !match;
                            });
                        }
                        
                        return result;
                    },
                    get sortedAndFilteredData() {
                        let result = [...this.filteredData];
                        
                        // 3. Terapkan Pengurutan (Sorting)
                        result.sort((a, b) => {
                            if (this.sortMode === 'highest') {
                                return b.count - a.count;
                            } else if (this.sortMode === 'lowest') {
                                return a.count - b.count;
                            } else if (this.sortMode === 'a_z') {
                                return a.kode_penyakit.localeCompare(b.kode_penyakit);
                            } else if (this.sortMode === 'z_a') {
                                return b.kode_penyakit.localeCompare(a.kode_penyakit);
                            }
                            return 0;
                        });

                        return result;
                    },
                    toggleLetter(l) {
                        if (this.tempSelectedLetters.includes(l)) {
                            this.tempSelectedLetters = this.tempSelectedLetters.filter(x => x !== l);
                        } else {
                            this.tempSelectedLetters.push(l);
                        }
                    },
                    applyFilter() {
                        this.selectedLetters = [...this.tempSelectedLetters];
                        this.filterMode = this.tempFilterMode;
                        this.isFilterOpen = false;
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('id-ID').format(num);
                    }
                }">
                    <div class="px-8 flex flex-col mt-6 mb-4 gap-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <h4 class="text-lg font-bold text-slate-700 flex items-center mb-0">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                Rekapitulasi Total Penyakit
                            </h4>
                            
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Kotak Search Umum -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <input type="text" x-model="searchQuery" placeholder="Cari Kode ICD X..." class="pl-9 text-xs font-bold border-slate-300 rounded-md py-1.5 px-3 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm w-48 transition-all focus:w-64">
                                </div>
                                
                                <div class="h-6 w-px bg-slate-200 hidden md:block"></div>
                                
                                <!-- Dropdown Filter Alfabet -->
                                <div class="relative">
                                    <button @click="isFilterOpen = !isFilterOpen; if(isFilterOpen) { tempSelectedLetters = [...selectedLetters]; tempFilterMode = filterMode; }" class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-300 rounded-md shadow-sm text-xs font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-colors">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                        Filter Kategori (A-Z)
                                        <span x-show="selectedLetters.length > 0" class="flex h-4 w-4 items-center justify-center rounded-full bg-red-100 text-[10px] text-red-600 font-black" x-text="selectedLetters.length" style="display: none;"></span>
                                    </button>

                                    <!-- Area Latar Belakang (Backdrop) untuk click outside -> close + cancel -->
                                    <div x-show="isFilterOpen" @click="isFilterOpen = false" class="fixed inset-0 z-40" style="display: none;"></div>

                                    <!-- Dropdown Menu -->
                                    <div x-show="isFilterOpen" x-transition class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-slate-200 z-50 p-4" style="display: none;">
                                        <div class="mb-3">
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Mode Filter:</label>
                                            <div class="flex rounded-md shadow-sm">
                                                <button @click.stop="tempFilterMode = 'include'" :class="tempFilterMode === 'include' ? 'bg-red-50 border-red-200 text-red-700 z-10' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50'" class="flex-1 border py-1.5 text-xs font-bold rounded-l-md transition-colors relative">Include</button>
                                                <button @click.stop="tempFilterMode = 'exclude'" :class="tempFilterMode === 'exclude' ? 'bg-slate-700 border-slate-700 text-white z-10' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50 -ml-px'" class="flex-1 border py-1.5 text-xs font-bold rounded-r-md transition-colors relative">Exclude</button>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex justify-between items-center">
                                                <span>Pilih Kategori (Awalan ICD-X):</span>
                                                <button @click.stop="tempSelectedLetters = []" x-show="tempSelectedLetters.length > 0" class="text-[10px] text-red-600 hover:underline" style="display: none;">Bersihkan Pilihan</button>
                                            </label>
                                            <div class="grid grid-cols-6 gap-1.5">
                                                <template x-for="l in letters" :key="l">
                                                    <button @click.stop="toggleLetter(l)" 
                                                            :class="tempSelectedLetters.includes(l) ? (tempFilterMode === 'include' ? 'bg-red-500 text-white border-red-500 shadow-inner' : 'bg-slate-700 text-white border-slate-700 shadow-inner') : 'bg-white border-slate-200 text-slate-600 hover:border-red-300 hover:bg-red-50'"
                                                            class="border rounded text-xs font-bold py-1 text-center transition-colors hover:scale-105 active:scale-95" x-text="l"></button>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <!-- Tombol Aksi Bawah -->
                                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                            <button @click.stop="isFilterOpen = false" class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-md transition-colors">Batal</button>
                                            <button @click.stop="applyFilter()" class="px-3 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors flex items-center gap-1.5">
                                                Terapkan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="h-6 w-px bg-slate-200 hidden md:block"></div>

                                <!-- Dropdown Sort -->
                                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                    <select x-model="sortMode" class="text-[11px] font-bold border-none bg-transparent py-0.5 pl-0 pr-6 focus:ring-0 cursor-pointer text-slate-700">
                                        <option value="highest">Kasus Terbanyak</option>
                                        <option value="lowest">Kasus Tersedikit</option>
                                        <option value="a_z">Abjad Penyakit (A-Z)</option>
                                        <option value="z_a">Abjad Penyakit (Z-A)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Indikator Badge Status Filter Aktif -->
                        <div x-show="selectedLetters.length > 0" x-transition class="flex flex-wrap items-center gap-2 mt-1" style="display: none;">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-red-50 border border-red-100 text-xs font-bold text-slate-700 shadow-sm leading-none">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Filter diterapkan: 
                                <span class="text-slate-500 font-medium ml-1">Kategori</span>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="l in selectedLetters" :key="l">
                                        <span class="bg-white px-1.5 py-0.5 rounded border shadow-sm text-[11px]" :class="filterMode === 'include' ? 'border-red-200 text-red-700' : 'border-slate-300 text-slate-700'" x-text="(filterMode === 'include' ? '+' : '-') + l"></span>
                                    </template>
                                </div>
                                <span class="text-slate-500 font-medium ml-1 text-[11px] italic" x-text="filterMode === 'include' ? '(ditampilkan)' : '(dikecualikan)'"></span>
                            </span>
                            <button @click="selectedLetters = []" class="text-xs font-bold text-red-500 hover:text-red-700 hover:underline">Hapus Filter</button>
                        </div>
                    </div>

                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-y border-slate-200">
                            <tr>
                                <th scope="col" class="px-8 py-4 font-semibold w-16 text-center">No</th>
                                <th scope="col" class="px-8 py-4 font-semibold">Kode Penyakit (ICD X)</th>
                                <th scope="col" class="px-8 py-4 text-right font-semibold w-40">Jumlah Kasus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in sortedAndFilteredData" :key="item.kode_penyakit">
                                <tr class="bg-white border-b border-slate-100/50 hover:bg-slate-50 transition-colors">
                                    <td class="px-8 py-4 text-center text-slate-400 font-medium" x-text="index + 1"></td>
                                    <td class="px-8 py-4 font-bold text-slate-800">
                                        <span x-text="item.kode_penyakit"></span>
                                        <template x-if="item.is_top">
                                            <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200 shadow-sm">
                                                ★ Terbanyak
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <span :class="{'text-white bg-red-600 shadow-sm shadow-red-200': item.is_top, 'text-slate-700 bg-slate-100': !item.is_top}" class="inline-flex items-center justify-center px-3 py-1 text-sm font-bold leading-none rounded-full min-w-[3rem]" x-text="formatNumber(item.count)">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Template Jika Tidak Ditemukan -->
                    <div x-show="sortedAndFilteredData.length === 0" class="py-12 px-8 text-center" style="display: none;">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <p class="text-slate-500 font-medium">Berdasarkan nama, tahun kategori, & properti filter ICD-X yang dimasukkan tidak dapat ditemukan data penyakit.</p>
                        <button @click="searchQuery = ''; selectedLetters = []" class="mt-3 text-red-500 hover:text-red-700 font-bold text-sm tracking-wide">
                            Reset Penelusuran Pencarian dan Urutkan
                        </button>
                    </div>

                    <!-- Footer Data -->
                    <div class="px-8 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center h-14 rounded-b-xl">
                        <span class="text-xs font-bold text-slate-500 flex items-center">
                            Memperlihatkan <span class="bg-white rounded border border-slate-200 px-1.5 mx-1.5 py-0.5 shadow-sm text-slate-700" x-text="sortedAndFilteredData.length"></span> penyakit dari total <span class="ml-1 text-slate-700" x-text="rawData.length"></span>
                        </span>
                        
                        <div x-show="searchQuery !== '' || selectedLetters.length > 0" class="text-xs font-bold text-red-500 hover:text-red-700 cursor-pointer" @click="searchQuery = ''; selectedLetters = []" style="display: none;">
                            Bersihkan Filter
                        </div>
                    </div>
                </div>
                <!-- End Blok Else NotFinished -->
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
