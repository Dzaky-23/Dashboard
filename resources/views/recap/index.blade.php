<x-app-layout>
    <div class="py-8">
        <div class="max-w-[96%] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 text-slate-900" x-data="{ 
                    activeFilter: 'semua', 
                    search: '',
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
                    }
                }">
                    <!-- Posisi Header dan Filter digeser ke bawah -->
                    @if($groupedByPusk->isEmpty())
                        <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg border border-yellow-200">
                            Masih belum ada data penyebaran penyakit yang tercatat.
                        </div>
                    @else
                        @include('recap.partials.global_chart')

                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-200 pb-4 mb-6 gap-4">
                            <h3 class="text-xl font-bold text-slate-800">Daftar Rekapitulasi Wilayah</h3>
                            
                            <!-- Search Bar -->
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

                        <!-- Filter Tabs -->
                        <div class="flex flex-wrap gap-2 mb-8 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 sm:inline-flex w-full sm:w-auto shadow-inner">
                            <button @click="activeFilter = 'semua'" :class="{'bg-white text-slate-800 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'semua', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'semua'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out">
                                Tampilkan Semua
                            </button>
                            <button @click="activeFilter = 'kecamatan'" :class="{'bg-white text-red-700 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'kecamatan', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'kecamatan'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out flex items-center justify-center gap-2">
                                <span x-show="activeFilter === 'kecamatan'" class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                Hanya Kecamatan
                            </button>
                            <button @click="activeFilter = 'puskesmas'" :class="{'bg-white text-red-700 shadow-sm ring-1 ring-slate-200/60 font-bold': activeFilter === 'puskesmas', 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700 font-medium': activeFilter !== 'puskesmas'}" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg text-sm transition-all duration-200 ease-in-out flex items-center justify-center gap-2">
                                <span x-show="activeFilter === 'puskesmas'" class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                Hanya Puskesmas
                            </button>
                        </div>

                        <div x-show="hasResults" class="min-h-[300px]">
                            @include('recap.partials.tab_semua')
                            @include('recap.partials.tab_kecamatan')
                            @include('recap.partials.tab_puskesmas')
                        </div>

                        <!-- Info Wilayah Kosong -->
                        <div x-show="!hasResults" x-cloak class="p-10 mt-6 text-center bg-slate-50 border border-dashed border-slate-300 rounded-xl">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm border border-slate-200 mb-4">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Puskesmas/Kecamatan Tidak Ditemukan</h3>
                            <p class="text-sm text-slate-500 mt-1">Sistem tidak menemukan fasilitas yang cocok dengan sebutan "<span class="font-semibold text-slate-700" x-text="search"></span>".</p>
                            <button @click="search = ''" class="mt-4 text-sm font-semibold text-red-600 hover:text-red-500 transition-colors">Lihat semua daftar</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
