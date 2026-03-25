<table class="w-full text-sm text-left text-slate-600" x-show="sortedAndFilteredData.length > 0">
    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-y border-slate-200">
        <tr>
            <th scope="col" class="px-8 py-4 font-semibold w-16 text-center">No</th>
            <th scope="col" class="px-8 py-4 font-semibold">Kode Penyakit (ICD X)</th>
            <th scope="col" class="px-8 py-4 text-right font-semibold w-40">Jumlah Kasus</th>
        </tr>
    </thead>
    <tbody>
        <template x-for="(item, index) in paginatedData" :key="item.kode_penyakit">
            <tr class="bg-white border-b border-slate-100/50 hover:bg-slate-50 transition-colors">
                <td class="px-8 py-4 text-center text-slate-500 font-medium" x-text="(Number(currentPage) - 1) * Number(itemsPerPage) + index + 1"></td>
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

<!-- Footer Data & Navigasi Pagination -->
<div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 rounded-b-xl">
    <!-- Teks Info (Kiri) -->
    <div class="text-sm text-slate-700 leading-5 w-full sm:w-auto text-center sm:text-left">
        Menampilkan
        <span class="font-medium" x-text="(Number(currentPage) - 1) * Number(itemsPerPage) + 1"></span>
        ke
        <span class="font-medium" x-text="Math.min(Number(currentPage) * Number(itemsPerPage), sortedAndFilteredData.length)"></span>
        dari
        <span class="font-medium" x-text="sortedAndFilteredData.length"></span>
        hasil
    </div>

    <!-- Tombol Navigasi (Kanan) -->
    <div x-show="totalPages > 1" class="w-full sm:w-auto text-center sm:text-right">
        <span class="relative z-0 inline-flex shadow-sm rounded-md">
            <!-- Prev Button -->
            <button @click="prevPage()" :disabled="Number(currentPage) === 1" :class="Number(currentPage) === 1 ? 'opacity-50 cursor-not-allowed text-slate-400' : 'text-slate-500 hover:bg-slate-50 focus:z-10'" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-300 bg-white text-sm font-medium focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            
            <!-- Page Numbers -->
            <template x-for="(p, index) in getPaginationElements()" :key="index">
                <button type="button" @click="if(p !== '...') goToPage(p)"
                    :disabled="p === '...'"
                    :class="Number(currentPage) === p ? 'z-10 bg-red-50 border-red-500 text-red-600 font-bold' : (p === '...' ? 'bg-white border-slate-300 text-slate-700 cursor-default px-3' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50 focus:z-10')"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 transition-colors -ml-px" x-text="p">
                </button>
            </template>
            
            <!-- Next Button -->
            <button @click="nextPage()" :disabled="Number(currentPage) === totalPages" :class="Number(currentPage) === totalPages ? 'opacity-50 cursor-not-allowed text-slate-400' : 'text-slate-500 hover:bg-slate-50 focus:z-10'" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-300 bg-white text-sm font-medium focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 transition-colors -ml-px">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </span>
    </div>
</div>
