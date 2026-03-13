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

                <!-- Area Latar Belakang (Backdrop) -->
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
