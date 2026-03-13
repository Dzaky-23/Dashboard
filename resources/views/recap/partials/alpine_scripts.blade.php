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
        }));
    });
</script>
@endpush
