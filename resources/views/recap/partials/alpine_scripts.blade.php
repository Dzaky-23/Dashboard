@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('recapTable', (initialData) => ({
            searchQuery: '',
            filterMode: 'include',
            tempFilterMode: 'include',
            selectedLetters: [],
            tempSelectedLetters: [],
            sortMode: 'highest',
            letters: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split(''),
            isFilterOpen: false,
            rawData: initialData,
            
            // Paginasi State
            currentPage: 1,
            itemsPerPage: 10,
            
            get filteredData() {
                let result = this.rawData;
                
                // 1. Terapkan Pencarian Global (Search)
                if (this.searchQuery.trim() !== '') {
                    const sq = this.searchQuery.toLowerCase();
                    result = result.filter(i => i.kode_penyakit.toLowerCase().includes(sq));
                }
                
                // Tiap kali filter/search ganti, reset halaman
                this.currentPage = 1;
                
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
            
            // Properti List Data Terhitung yang dipotong per-halaman (Pagination Splitter)
            get paginatedData() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                const end = start + this.itemsPerPage;
                return this.sortedAndFilteredData.slice(start, end);
            },
            
            get totalPages() {
                return Math.ceil(this.sortedAndFilteredData.length / this.itemsPerPage) || 1;
            },
            
            getPaginationElements() {
                let pages = [];
                let current = Number(this.currentPage);
                let total = Number(this.totalPages);
                
                if (total <= 7) {
                    for (let i = 1; i <= total; i++) pages.push(i);
                } else {
                    if (current <= 4) {
                        pages = [1, 2, 3, 4, 5, '...', total];
                    } else if (current >= total - 3) {
                        pages = [1, '...', total - 4, total - 3, total - 2, total - 1, total];
                    } else {
                        pages = [1, '...', current - 1, current, current + 1, '...', total];
                    }
                }
                return pages;
            },
            
            nextPage() {
                if (Number(this.currentPage) < Number(this.totalPages)) this.currentPage = Number(this.currentPage) + 1;
            },
            
            prevPage() {
                if (Number(this.currentPage) > 1) this.currentPage = Number(this.currentPage) - 1;
            },
            
            goToPage(p) {
                if (p >= 1 && p <= Number(this.totalPages)) this.currentPage = Number(p);
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
        }));
    });
</script>
@endpush
