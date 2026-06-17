<div x-data="analyticsDashboard()" class="mb-8 bg-white border border-slate-200 rounded-3xl shadow-sm overflow-visible relative z-20">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 rounded-t-3xl">
        <div>
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Analisis Penyakit Interaktif
            </h3>
            <p class="text-xs text-slate-500 mt-1">Sesuaikan parameter secara independen untuk tiap grafik</p>
        </div>
    </div>
    
    <!-- Charts Container -->
    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-8 relative z-10">
        
        <!-- ==================== TREND CHART ==================== -->
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-bold text-slate-700">Tren Kasus Penyakit</h4>
                <button @click="downloadChart('trendChart', 'tren_penyakit.png')" class="text-xs flex items-center gap-1 font-semibold text-slate-500 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Unduh Gambar
                </button>
            </div>

            <!-- Controls -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Search -->
                    <div class="relative flex-grow">
                        <input type="text" x-model="trendSearch" @input.debounce.500ms="searchDisease('trend')" placeholder="Tambah penyakit..." class="w-full text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm">
                        <div x-show="trendSearchLoading" class="absolute right-2 top-2">
                            <div class="animate-spin h-4 w-4 border-2 border-red-500 border-t-transparent rounded-full"></div>
                        </div>
                        <div x-show="trendOptions.length > 0 && trendSearch.length > 0" @click.away="trendOptions = []" class="absolute z-50 mt-1 w-full bg-white shadow-lg rounded-lg border border-slate-200 max-h-48 overflow-y-auto">
                            <template x-for="opt in trendOptions" :key="opt.code">
                                <div @click="addTrendDisease(opt)" class="px-3 py-2 hover:bg-red-50 cursor-pointer text-xs border-b border-slate-50">
                                    <span class="font-bold text-slate-700" x-text="opt.code"></span> - <span class="text-slate-600" x-text="opt.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Time Mode -->
                    <select x-model="trendTimeMode" @change="fetchTrendData()" class="text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm">
                        <option value="year">Sepanjang Tahun</option>
                        <option value="last_n">N Bulan Terakhir</option>
                        <option value="last_n_years">N Tahun Terakhir</option>
                        <option value="custom_months">Pilih Bulan</option>
                    </select>

                    <!-- Year Selection -->
                    <div x-show="trendTimeMode === 'year' || trendTimeMode === 'custom_months' || trendTimeMode === 'last_n_years'">
                        <input type="number" x-model.lazy="trendYear" @change="fetchTrendData()" class="w-20 text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm text-center" :title="trendTimeMode === 'last_n_years' ? 'Tahun Akhir' : 'Tahun'">
                    </div>

                    <!-- Time Input -->
                    <div x-show="trendTimeMode === 'last_n' || trendTimeMode === 'last_n_years'">
                        <input type="number" x-model="trendLastN" @change="fetchTrendData()" min="1" max="60" class="w-16 text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm text-center" :placeholder="trendTimeMode === 'last_n' ? 'Bulan' : 'Tahun'">
                    </div>
                    
                    <div x-show="trendTimeMode === 'custom_months'" class="relative">
                        <button @click="showCustomMonths = !showCustomMonths" @click.away="showCustomMonths = false" class="text-xs bg-white border border-slate-300 rounded-md px-3 py-2 shadow-sm w-32 flex justify-between items-center">
                            <span>Bulan <span x-text="'('+trendCustomMonths.length+')'"></span></span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="showCustomMonths" class="absolute right-0 z-50 mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-lg p-2 grid grid-cols-2 gap-2" x-transition>
                            <template x-for="(monthName, idx) in ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des']">
                                <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-slate-50 p-1 rounded">
                                    <input type="checkbox" :value="idx + 1" x-model.number="trendCustomMonths" @change="fetchTrendData()" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span x-text="monthName"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Badges -->
                <div x-show="trendDiseases.length > 0" class="flex flex-wrap gap-1.5">
                    <template x-for="disease in trendDiseases" :key="disease.code">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-red-100 border border-red-200 text-red-800 text-[10px] font-bold">
                            <span x-text="disease.code"></span>
                            <span class="font-medium max-w-[100px] truncate" x-text="disease.name" :title="disease.name"></span>
                            <button @click="removeTrendDisease(disease.code)" class="ml-0.5 text-red-500 hover:text-red-700 focus:outline-none">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <button x-show="trendDiseases.length > 0" @click="trendDiseases = []; fetchTrendData()" class="text-[10px] text-slate-500 hover:underline px-1">Clear</button>
                </div>
            </div>

            <!-- Chart -->
            <div class="relative flex-grow min-h-[300px]">
                <div x-show="trendLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                    <div class="animate-spin h-6 w-6 border-2 border-red-500 border-t-transparent rounded-full"></div>
                </div>
                
                <!-- Empty State Message -->
                <div x-show="!trendLoading && trendDiseases.length === 0 && !trendInitialLoad" class="absolute inset-0 flex flex-col items-center justify-center z-10 bg-white/80 backdrop-blur-sm rounded-xl">
                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <p class="text-sm font-semibold text-slate-500">Silakan input penyakit untuk melihat tren</p>
                    <p class="text-xs text-slate-400">Gunakan kolom pencarian di atas untuk menambahkan penyakit.</p>
                </div>

                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- ==================== PIE CHART ==================== -->
        <div class="flex flex-col gap-4 border-t lg:border-t-0 lg:border-l border-slate-200 pt-6 lg:pt-0 lg:pl-6">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-bold text-slate-700">Komposisi Penyakit</h4>
                <button @click="downloadChart('pieChart', 'komposisi_penyakit.png')" class="text-xs flex items-center gap-1 font-semibold text-slate-500 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </button>
            </div>

            <!-- Controls -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Tampilkan Top:</label>
                    <input type="number" x-model="pieLimit" @change="fetchPieData()" min="1" max="20" class="w-20 text-xs rounded-md border-slate-300 focus:ring-red-500 py-1.5 shadow-sm text-center">
                    <span class="text-xs text-slate-500">Penyakit</span>
                </div>

                <div class="flex flex-col gap-2 border-b border-slate-200 pb-3 mb-1">
                    <div class="flex gap-2">
                        <!-- Time Mode -->
                        <select x-model="pieTimeMode" @change="fetchPieData()" class="w-1/2 text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm">
                            <option value="year">Sepanjang Tahun</option>
                            <option value="last_n">N Bulan Terakhir</option>
                            <option value="last_n_years">N Tahun Terakhir</option>
                            <option value="custom_months">Pilih Bulan</option>
                        </select>

                        <!-- Year Selection -->
                        <div x-show="pieTimeMode === 'year' || pieTimeMode === 'custom_months' || pieTimeMode === 'last_n_years'" class="w-1/2">
                            <input type="number" x-model.lazy="pieYear" @change="fetchPieData()" class="w-full text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm text-center" :placeholder="pieTimeMode === 'last_n_years' ? 'Tahun Akhir' : 'Tahun'">
                        </div>

                        <!-- Time Input -->
                        <div x-show="pieTimeMode === 'last_n' || pieTimeMode === 'last_n_years'" class="w-1/2">
                            <input type="number" x-model="pieLastN" @change="fetchPieData()" min="1" max="60" class="w-full text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm text-center" :placeholder="pieTimeMode === 'last_n' ? 'N Bulan' : 'N Tahun'">
                        </div>
                    </div>
                    
                    <div x-show="pieTimeMode === 'custom_months'" class="relative w-full">
                        <button @click="showPieCustomMonths = !showPieCustomMonths" @click.away="showPieCustomMonths = false" class="text-xs bg-white border border-slate-300 rounded-md px-3 py-2 shadow-sm w-full flex justify-between items-center">
                            <span>Pilih Bulan <span x-text="'('+pieCustomMonths.length+')'"></span></span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="showPieCustomMonths" class="absolute left-0 z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg p-2 grid grid-cols-3 gap-2" x-transition>
                            <template x-for="(monthName, idx) in ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des']">
                                <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-slate-50 p-1 rounded">
                                    <input type="checkbox" :value="idx + 1" x-model.number="pieCustomMonths" @change="fetchPieData()" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span x-text="monthName"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <select x-model="pieScope" @change="pieScopeValue = ''; fetchPieData()" class="w-1/2 text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm">
                        <option value="global">Global</option>
                        <option value="kecamatan">Kecamatan</option>
                        <option value="puskesmas">Puskesmas</option>
                    </select>

                    <div x-show="pieScope !== 'global'" class="w-1/2 relative">
                        <select x-model="pieScopeValue" @change="fetchPieData()" class="w-full text-xs rounded-md border-slate-300 focus:ring-red-500 py-2 shadow-sm">
                            <option value="">-- Pilih --</option>
                            <template x-if="pieScope === 'kecamatan'">
                                <template x-for="kec in kecamatanOptions" :key="kec.code">
                                    <option :value="kec.code" x-text="kec.name"></option>
                                </template>
                            </template>
                            <template x-if="pieScope === 'puskesmas'">
                                <template x-for="pusk in puskesmasOptions" :key="pusk.code">
                                    <option :value="pusk.code" x-text="pusk.name"></option>
                                </template>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="relative flex-grow flex items-center justify-center min-h-[300px]">
                <div x-show="pieLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
                    <div class="animate-spin h-6 w-6 border-2 border-red-500 border-t-transparent rounded-full"></div>
                </div>
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('analyticsDashboard', () => ({
        year: '{{ $yearInput ?? date('Y') }}',
        kecamatanOptions: @json($exportKecamatanOptions ?? []),
        puskesmasOptions: @json($exportPuskesmasOptions ?? []),
        chartColors: ['#ef4444', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#6366f1'],

        // Trend State
        trendLoading: false,
        trendInitialLoad: true,
        trendYear: '{{ $yearInput ?? date('Y') }}',
        trendSearch: '',
        trendSearchLoading: false,
        trendOptions: [],
        trendDiseases: [],
        trendTimeMode: 'year',
        trendLastN: 6,
        trendCustomMonths: [1,2,3,4,5,6,7,8,9,10,11,12],
        showCustomMonths: false,
        trendChartInstance: null,

        // Pie State
        pieLoading: false,
        pieYear: '{{ $yearInput ?? date('Y') }}',
        pieTimeMode: 'year',
        pieLastN: 6,
        pieCustomMonths: [1,2,3,4,5,6,7,8,9,10,11,12],
        showPieCustomMonths: false,
        pieLimit: 5,
        pieScope: 'global',
        pieScopeValue: '',
        pieChartInstance: null,

        init() {
            this.fetchTrendData();
            this.fetchPieData();
        },

        async searchDisease(target) {
            if (target !== 'trend') return;
            const query = this.trendSearch.trim();
            if (query.length < 2) {
                this.trendOptions = [];
                return;
            }
            
            this.trendSearchLoading = true;

            try {
                const res = await fetch(`{{ route('recap.icd.search') }}?q=${encodeURIComponent(query)}`);
                const payload = await res.json();
                
                const selected = this.trendDiseases.map(d => d.code);
                this.trendOptions = (payload.data || []).filter(item => !selected.includes(item.code));
            } catch (err) {
                console.error(err);
            } finally {
                this.trendSearchLoading = false;
            }
        },

        addTrendDisease(opt) {
            this.trendDiseases.push(opt);
            this.trendSearch = '';
            this.trendOptions = [];
            this.fetchTrendData();
        },
        removeTrendDisease(code) {
            this.trendDiseases = this.trendDiseases.filter(d => d.code !== code);
            this.fetchTrendData();
        },

        async fetchTrendData() {
            this.trendLoading = true;
            try {
                const codes = this.trendDiseases.map(d => d.code);
                let url = `{{ route('recap.api.trend_data') }}?year=${this.trendYear}&time_mode=${this.trendTimeMode}&diseases=${encodeURIComponent(JSON.stringify(codes))}`;
                if (this.trendInitialLoad) url += `&is_initial=1`;
                if (this.trendTimeMode === 'last_n' || this.trendTimeMode === 'last_n_years') url += `&last_n=${this.trendLastN}`;
                if (this.trendTimeMode === 'custom_months') url += `&custom_months=${encodeURIComponent(JSON.stringify(this.trendCustomMonths))}`;

                const res = await fetch(url);
                const data = await res.json();
                
                // Set default if empty initially
                if (this.trendInitialLoad && data.trend && data.trend.length > 0) {
                    this.trendDiseases = data.trend.map(item => ({ code: item.kode, name: item.nama }));
                    this.trendInitialLoad = false;
                } else if (this.trendInitialLoad) {
                    this.trendInitialLoad = false;
                }

                this.renderTrendChart(data.trend, data.labels);
            } catch (err) {
                console.error('Error fetching trend data:', err);
            } finally {
                this.trendLoading = false;
            }
        },

        async fetchPieData() {
            // Wait for scope value if kecamatan/puskesmas is selected
            if (this.pieScope !== 'global' && !this.pieScopeValue) {
                this.renderPieChart([]);
                return;
            }

            this.pieLoading = true;
            try {
                let url = `{{ route('recap.api.pie_data') }}?year=${this.pieYear}&limit=${this.pieLimit}&scope=${this.pieScope}&time_mode=${this.pieTimeMode}`;
                if (this.pieScopeValue) url += `&scope_value=${this.pieScopeValue}`;
                if (this.pieTimeMode === 'last_n' || this.pieTimeMode === 'last_n_years') url += `&last_n=${this.pieLastN}`;
                if (this.pieTimeMode === 'custom_months') url += `&custom_months=${encodeURIComponent(JSON.stringify(this.pieCustomMonths))}`;

                const res = await fetch(url);
                const data = await res.json();

                this.renderPieChart(data.pie);
            } catch (err) {
                console.error('Error fetching pie data:', err);
            } finally {
                this.pieLoading = false;
            }
        },

        renderTrendChart(trendData, labels) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (this.trendChartInstance) this.trendChartInstance.destroy();

            const datasets = trendData.map((item, index) => ({
                label: item.nama,
                data: item.data,
                borderColor: this.chartColors[index % this.chartColors.length],
                backgroundColor: this.chartColors[index % this.chartColors.length] + '20',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }));

            this.trendChartInstance = new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 15 } },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false }
                }
            });
        },

        renderPieChart(pieData) {
            const ctx = document.getElementById('pieChart').getContext('2d');
            if (this.pieChartInstance) this.pieChartInstance.destroy();

            if (!pieData || pieData.length === 0) {
                // Render empty chart
                this.pieChartInstance = new Chart(ctx, { type: 'doughnut', data: { datasets: [] }});
                return;
            }

            const dataValues = pieData.map(item => item.count);
            const dataLabels = pieData.map(item => `[${item.kode}] ${item.nama}`);
            const bgColors = pieData.map((_, i) => this.chartColors[i % this.chartColors.length]);

            const customDataLabels = {
                id: 'customDataLabels',
                afterDraw: (chart) => {
                    const ctx = chart.ctx;
                    ctx.save();
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        if (!meta.hidden) {
                            meta.data.forEach((element, index) => {
                                if (dataset.data[index] > 0) {
                                    const label = chart.data.labels[index];
                                    const codeMatch = label.match(/^\[(.*?)\]/);
                                    const code = codeMatch ? codeMatch[1] : '';
                                    const value = dataset.data[index];
                                    if (code) {
                                        const position = element.tooltipPosition();
                                        ctx.fillStyle = '#ffffff';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'middle';
                                        ctx.shadowColor = 'rgba(0, 0, 0, 0.6)';
                                        ctx.shadowBlur = 4;
                                        
                                        // Draw Code
                                        ctx.font = 'bold 12px sans-serif';
                                        ctx.fillText(code, position.x, position.y - 7);
                                        
                                        // Draw Count
                                        ctx.font = 'normal 10px sans-serif';
                                        ctx.fillText(new Intl.NumberFormat('id-ID').format(value), position.x, position.y + 7);
                                    }
                                }
                            });
                        }
                    });
                    ctx.restore();
                }
            };

            this.pieChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dataLabels,
                    datasets: [{
                        data: dataValues, backgroundColor: bgColors,
                        borderWidth: 2, borderColor: '#ffffff', hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '60%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 15 } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed !== null) label += new Intl.NumberFormat('id-ID').format(context.parsed) + ' Kasus';
                                    return label;
                                }
                            }
                        }
                    }
                },
                plugins: [customDataLabels]
            });
        },

        downloadChart(canvasId, filename) {
            const canvas = document.getElementById(canvasId);
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width; tempCanvas.height = canvas.height;
            const ctx = tempCanvas.getContext('2d');
            ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
            ctx.drawImage(canvas, 0, 0);
            const url = tempCanvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = filename; link.href = url; link.click();
        }
    }));
});
</script>
