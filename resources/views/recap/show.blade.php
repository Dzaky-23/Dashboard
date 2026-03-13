<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="{{ route('recap.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Dashboard Rekap
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-200">
                <!-- Header Component -->
                <div class="bg-red-600 px-8 py-6 border-b border-red-700 flex justify-between items-center bg-gradient-to-r from-red-600 to-red-700">
                    <div>
                        <h3 class="text-2xl font-bold text-white flex items-center">
                            Puskesmas {{ $puskesmas }}
                        </h3>
                        <p class="text-sm text-red-100 mt-1 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Kecamatan {{ $kecamatan }}
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
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filter Rekapitulasi:
                    </h4>
                    <!-- Form Filter Waktu & Limit N -->
                    @include('recap.partials.filter_waktu_limit', ['actionUrl' => route('recap.show', $puskesmas)])
                </div>

                @if($isNotFinished)
                    <!-- UI Block: Not Finished -->
                    <div class="p-10 my-8 text-center bg-slate-50 border border-dashed border-slate-300 mx-8 rounded-xl flex flex-col items-center justify-center min-h-[400px]">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 mb-5">
                            <svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Hasil Rekap Belum Tersedia</h3>
                        <p class="text-sm text-slate-500 mt-2 max-w-sm">Periode rekapitulasi waktu yang Anda pilih belum selesai masa perhitungannya atau berada pada rentang masa depan.</p>
                        <a href="{{ route('recap.show', $puskesmas) }}" class="mt-6 px-4 py-2 bg-white border border-slate-300 rounded-md text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">Kembalikan ke Semua Waktu</a>
                    </div>
                @else
                <!-- Section Grafik & Analitik Ringkas -->
                <div class="px-8 py-8 border-b border-slate-200 bg-slate-50/50">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        @include('recap.partials.chart_top_penyakit')

                        <!-- Col 3: Snapshot Statistik Sederhana -->
                        <div class="flex flex-col gap-4">
                            <!-- Card Total Unik Diagnosa -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0 text-orange-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Diagnosa Varian</span>
                                    <h4 class="text-2xl font-black text-slate-800">{{ number_format($totalDiagnosaUnik) }}</h4>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">Jenis penyakit tercatat</p>
                                </div>
                            </div>
                            
                            <!-- Distribusi Rata-Rata -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex-grow flex flex-col justify-center relative overflow-hidden group">
                                <span class="block text-xs font-bold text-red-500 uppercase tracking-widest mb-2 flex items-center gap-1.5 relative z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    Distribusi Rata-Rata
                                </span>
                                @php
                                    $avgKasus = $totalDiagnosaUnik > 0 ? $totalKasus / $totalDiagnosaUnik : 0;
                                @endphp
                                <h4 class="text-4xl font-black text-slate-800 tracking-tight leading-none mb-2 relative z-10">{{ number_format($avgKasus, 1) }}</h4>
                                <div class="flex items-end gap-2 relative z-10 mt-1">
                                    <span class="text-xs font-semibold text-slate-500 mb-1">Kasus per jenis diagnosa</span>
                                </div>
                                <div class="mt-3 text-[10px] xl:text-xs text-slate-400 leading-relaxed relative z-10 border-t border-slate-100 pt-3">
                                    Nilai ini mengukur rasio kepadatan penyebaran penyakit, dihitung dari total akumulasi <strong>{{ number_format($totalKasus) }} kasus</strong> dibagi rata kepada <strong>{{ number_format($totalDiagnosaUnik) }} varian</strong> diagnosa ICD-X yang tercatat.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-0 mt-8 border-t border-slate-200 pt-2" x-data="recapTable({{ $rekapData->map(function($item, $index) { return ['kode_penyakit' => $item->kode_penyakit, 'count' => $item->count, 'is_top' => $index === 0]; })->toJson() }})">
                    @include('recap.partials.filter_controls')
                    @include('recap.partials.disease_table')
                </div>
                <!-- End NotFinished IF Block -->
                @endif
            </div>
        </div>
    </div>
    
    @include('recap.partials.alpine_scripts')
</x-app-layout>
