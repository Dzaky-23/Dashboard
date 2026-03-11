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
                                            {{ number_format($item->count) }}
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
