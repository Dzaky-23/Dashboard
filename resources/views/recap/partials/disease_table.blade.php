<table class="w-full text-sm text-left text-slate-600" x-show="sortedAndFilteredData.length > 0">
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
