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
                @if(isset($warningLimit) && $warningLimit)
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
                                <!-- Form Config N -->
                                <form action="{{ route('recap.show', $puskesmas) }}" method="GET" class="flex items-center space-x-2">
                                    <label for="limit" class="text-xs font-semibold text-slate-500">Jumlah Data (N):</label>
                                    <input type="number" name="limit" id="limit" value="{{ $limit }}" min="1" class="w-20 text-xs font-bold border-slate-300 rounded-md py-1 px-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm">
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1 px-3 rounded-md shadow-sm transition-colors">Terapkan</button>
                                </form>
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
                                                        <div class="bg-rose-600 hover:bg-indigo-400 h-full rounded-md transition-all duration-1000 ease-out" style="width: {{ max($widthPercentage, 1) }}%"></div>
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
                                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0 text-orange-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Diagnosa Varian</span>
                                    <h4 class="text-2xl font-black text-slate-800">{{ number_format($totalDiagnosaUnik) }}</h4>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">Jenis penyakit tercatat</p>
                                </div>
                            </div>
                            
                            <!-- Card Penyakit Dominan #1 -> Diubah jadi Distribusi Rata-Rata -->
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
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-0">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-8 py-4 font-semibold w-16 text-center">No</th>
                                <th scope="col" class="px-8 py-4 font-semibold">Kode Penyakit (ICD X)</th>
                                <th scope="col" class="px-8 py-4 text-right font-semibold w-40">Jumlah Kasus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapData as $index => $item)
                                <tr class="bg-white border-b border-slate-100/50 last:border-0 hover:bg-slate-50 transition-colors">
                                    <td class="px-8 py-4 text-center text-slate-400 font-medium">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-8 py-4 font-bold text-slate-800">
                                        {{ $item->kode_penyakit }}
                                        @if($index === 0)
                                            <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200 shadow-sm">
                                                ★ Terbanyak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <span class="inline-flex items-center justify-center px-3 py-1 text-sm font-bold leading-none {{ $index === 0 ? 'text-white bg-red-600 shadow-sm shadow-red-200' : 'text-slate-700 bg-slate-100' }} rounded-full min-w-[3rem]">
                                            {{ $item->count }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-8 py-12 text-center text-slate-500 italic">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4 text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        </div>
                                        <p class="text-base font-medium text-slate-600">Belum Ada Data</p>
                                        <p class="text-sm mt-1">Belum ada data penyakit yang dilaporkan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
