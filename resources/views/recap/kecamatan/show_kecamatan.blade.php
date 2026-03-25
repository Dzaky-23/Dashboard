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
                    @include('recap.partials.filter_waktu_limit', ['actionUrl' => route('recap.kecamatan.show', $kecamatan)])
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
                        @include('recap.partials.chart_top_penyakit')

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
                                </div>
                            </div>
                            <div class="flex items-end justify-between mt-auto">
                                <a href="{{ route('recap.show', $pstat->nama) }}" class="text-xs font-bold text-red-600 hover:text-red-800 flex items-center group-hover:translate-x-1 transition-transform">
                                    Lihat Data <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div x-data="{ 
                    isModalOpen: false, 
                    periodType: 'month', 
                    periodValue: '{{ date('n') }}', 
                    year: '{{ date('Y') }}',
                    get options() {
                        if (this.periodType === 'month') {
                            return [
                                { v: 1, l: 'Januari' }, { v: 2, l: 'Februari' }, { v: 3, l: 'Maret' },
                                { v: 4, l: 'April' }, { v: 5, l: 'Mei' }, { v: 6, l: 'Juni' },
                                { v: 7, l: 'Juli' }, { v: 8, l: 'Agustus' }, { v: 9, l: 'September' },
                                { v: 10, l: 'Oktober' }, { v: 11, l: 'November' }, { v: 12, l: 'Desember' }
                            ];
                        } else if (this.periodType === 'quarter') {
                            return [
                                { v: 1, l: 'Triwulan 1 (Jan-Mar)' },
                                { v: 2, l: 'Triwulan 2 (Apr-Jun)' },
                                { v: 3, l: 'Triwulan 3 (Jul-Sep)' },
                                { v: 4, l: 'Triwulan 4 (Okt-Des)' }
                            ];
                        } else if (this.periodType === 'semester') {
                            return [
                                { v: 1, l: 'Semester 1 (Jan-Jun)' },
                                { v: 2, l: 'Semester 2 (Jul-Des)' }
                            ];
                        }
                        return [];
                    }
                }" class="px-8 mt-4 pb-8 flex justify-center">
                    <!-- Trigger Button -->
                    <button @click="isModalOpen = true" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-slate-800 rounded-xl shadow-md hover:bg-slate-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-all w-full md:w-auto">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Lihat Daftar Lengkap Penyakit
                    </button>

                    <!-- Modal Backdrop -->
                    <div x-show="isModalOpen" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
                         style="display: none;">
                        
                        <!-- Modal Content -->
                        <div @click.away="isModalOpen = false" 
                             x-show="isModalOpen"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden">
                            
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                    Filter Daftar Penyakit
                                </h3>
                                <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <form action="{{ route('recap.kecamatan.full_list', $kecamatan) }}" method="GET" class="p-6 space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Tipe Periode</label>
                                    <select name="period_type" x-model="periodType" class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                        <option value="month">Per Bulan</option>
                                        <option value="quarter">Per Triwulan</option>
                                        <option value="semester">Per Semester</option>
                                        <option value="year">Per Tahun</option>
                                    </select>
                                </div>

                                <div x-show="periodType !== 'year'">
                                    <label class="block text-sm font-bold text-slate-700 mb-2" x-text="periodType === 'month' ? 'Pilih Bulan' : (periodType === 'quarter' ? 'Pilih Triwulan' : 'Pilih Semester')"></label>
                                    <select name="period_value" x-model="periodValue" class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                        <template x-for="opt in options" :key="opt.v">
                                            <option :value="opt.v" x-text="opt.l"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Tahun</label>
                                    <input type="number" name="year" x-model="year" class="w-full rounded-xl border-slate-300 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                                </div>

                                <div class="pt-4 flex justify-end gap-3">
                                    <button type="button" @click="isModalOpen = false" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-xl transition-all">
                                        Batal
                                    </button>
                                    <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                        Tampilkan Data
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Blok Else NotFinished -->
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
