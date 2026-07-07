<div>
    <x-slot:head>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </x-slot:head>

    {{-- ── Row 1: KPI Cards ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-md mb-lg">

        {{-- KPI 1: Total Bahan Baku Aktif --}}
        <x-ui.kpi-card
            title="Total Bahan Baku Aktif"
            icon="inventory"
            iconBg="bg-primary-fixed text-primary"
        >
            <x-slot:value>
                <span class="font-semibold text-text-primary numeric">{{ $metrics['total_bahan_baku'] }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-text-secondary">Jenis raw material aktif</span>
            </x-slot:footer>
        </x-ui.kpi-card>

        {{-- KPI 2: Total Nilai Investasi (Hero Card) --}}
        <x-ui.kpi-card
            title="Nilai Investasi Tahunan"
            icon="payments"
            :hero="true"
        >
            <x-slot:value>
                <span class="font-semibold text-on-primary-container numeric">Rp {{ number_format($metrics['annual_investment'], 0, ',', '.') }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-on-primary-container/70">Nilai pemakaian per tahun</span>
            </x-slot:footer>
        </x-ui.kpi-card>

        {{-- KPI 3: Material Kritis --}}
        <x-ui.kpi-card
            title="Material Kritis"
            icon="warning"
            iconBg="bg-negative-bg text-negative-rose"
        >
            <x-slot:value>
                <span class="font-semibold text-text-primary numeric">{{ $metrics['critical_materials'] }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-text-secondary">Di bawah ROP saat ini</span>
            </x-slot:footer>
        </x-ui.kpi-card>

        {{-- KPI 4: Nilai Stok Barang Jadi --}}
        <x-ui.kpi-card
            title="Nilai Stok Barang Jadi"
            icon="deployed_code"
            iconBg="bg-accent-tan-light text-tertiary-container"
        >
            <x-slot:value>
                <span class="font-semibold text-text-primary numeric">Rp {{ number_format($metrics['fg_stock_value'], 0, ',', '.') }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-text-secondary">Stok barang jadi saat ini</span>
            </x-slot:footer>
        </x-ui.kpi-card>

    </div>

    {{-- ── Row 2: ABC Donut Chart + Top-5 Cost Bar Chart ────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-md mb-lg">

        {{-- ABC Donut Chart (40% width) --}}
        <div class="xl:col-span-2">
            <x-ui.analytics-card title="Klasifikasi ABC" subtitle="Distribusi nilai pemakaian" height="h-[340px]">
                <div class="flex flex-col items-center justify-center h-full gap-2"
                     x-data="{
                         chart: null,
                         init() {
                             const options = {
                                 chart: {
                                     type: 'donut',
                                     height: 220,
                                     sparkline: { enabled: true }
                                 },
                                 series: [
                                     {{ (float) $chartData['donut_value']['A'] }},
                                     {{ (float) $chartData['donut_value']['B'] }},
                                     {{ (float) $chartData['donut_value']['C'] }}
                                 ],
                                 labels: ['Kelas A', 'Kelas B', 'Kelas C'],
                                 colors: ['#3e5c48', '#C99B5D', '#8B8880'],
                                 legend: { show: false },
                                 plotOptions: {
                                     pie: {
                                         donut: {
                                             size: '70%',
                                             labels: {
                                                 show: true,
                                                 total: {
                                                     show: true,
                                                     label: 'Total Nilai',
                                                     formatter: function () {
                                                         return 'Rp {{ number_format(array_sum($chartData['donut_value']), 0, ',', '.') }}';
                                                     }
                                                 }
                                             }
                                         }
                                     }
                                 }
                             };
                             this.chart = new ApexCharts($refs.donutCanvas, options);
                             this.chart.render();
                             
                             this.$cleanup(() => {
                                 if (this.chart) {
                                     this.chart.destroy();
                                 }
                             });
                         }
                     }">
                    <div class="w-full max-w-[240px] h-[220px]" x-ref="donutCanvas"></div>
                    <div class="flex gap-4 text-xs text-text-secondary mt-1">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span> Kelas A ({{ $chartData['donut']['A'] }} item)</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#C99B5D] inline-block"></span> Kelas B ({{ $chartData['donut']['B'] }} item)</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-text-secondary inline-block"></span> Kelas C ({{ $chartData['donut']['C'] }} item)</span>
                    </div>
                </div>
            </x-ui.analytics-card>
        </div>

        {{-- Top-5 Cost Bar Chart (60% width) --}}
        <div class="xl:col-span-3">
            <x-ui.analytics-card title="Top 5 Bahan Baku Termahal" subtitle="Berdasarkan nilai pemakaian tahunan" height="h-[340px]">
                <div class="flex flex-col justify-center h-full px-2"
                     x-data="{
                         chart: null,
                         init() {
                             const options = {
                                 chart: {
                                     type: 'bar',
                                     height: 240,
                                     toolbar: { show: false }
                                 },
                                 plotOptions: {
                                     bar: {
                                         horizontal: true,
                                         barHeight: '55%',
                                         borderRadius: 4
                                     }
                                 },
                                 colors: ['#3e5c48'],
                                 series: [{
                                     name: 'Nilai Pemakaian',
                                     data: [
                                         @foreach($chartData['top5'] as $item)
                                             {{ (float) $item['value'] }},
                                         @endforeach
                                     ]
                                 }],
                                 xaxis: {
                                     categories: [
                                         @foreach($chartData['top5'] as $item)
                                             '{{ addslashes($item['name']) }}',
                                         @endforeach
                                     ],
                                     labels: {
                                         formatter: function (value) {
                                             return 'Rp ' + (value / 1000).toLocaleString() + 'k';
                                         }
                                     }
                                 },
                                 dataLabels: { enabled: false }
                             };
                             this.chart = new ApexCharts($refs.barCanvas, options);
                             this.chart.render();

                             this.$cleanup(() => {
                                 if (this.chart) {
                                     this.chart.destroy();
                                 }
                             });
                         }
                     }">
                    <div class="w-full h-[240px]" x-ref="barCanvas"></div>
                </div>
            </x-ui.analytics-card>
        </div>

    </div>

    {{-- ── Row 3: Critical Stock Table ───────────────────────────────── --}}
    <div class="mb-lg" wire:poll.15s>
        <x-ui.analytics-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-negative-rose text-[20px]">emergency_heat</span>
                            Live Stock Critical Alert
                        </h3>
                        <p class="text-xs text-text-secondary mt-0.5">Diperbarui setiap 15 detik · <span class="text-negative-rose font-semibold">{{ count($criticalStock) }} material kritis</span></p>
                    </div>
                </div>
            </x-slot:header>

            <x-tables.data-table
                :headers="['Kode', 'Nama Bahan Baku', 'Stok Saat Ini', 'ROP', 'Defisit']"
                :items="$criticalStock"
            >
                @foreach($criticalStock as $item)
                    <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="crit-{{ $item['id'] }}">
                        <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item['kode'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-primary">{{ $item['nama'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-primary numeric">{{ number_format($item['stok_saat_ini'], 2) }} {{ $item['satuan'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-secondary numeric">{{ number_format($item['rop'], 2) }} {{ $item['satuan'] }}</td>
                        <td class="px-lg py-md text-body-md font-semibold text-negative-rose numeric">{{ number_format($item['defisit'], 2) }} {{ $item['satuan'] }}</td>
                    </tr>
                @endforeach

                <x-slot:empty>
                    <x-tables.empty-state
                        icon="check_circle"
                        title="Tidak ada material kritis"
                        description="Semua stok bahan baku berada di atas titik pemesanan ulang (ROP)."
                    />
                </x-slot:empty>
            </x-tables.data-table>
        </x-ui.analytics-card>
    </div>

    {{-- ── Row 4: Recent Activity + Upcoming Reorders ────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-md">

        {{-- Recent Activity Feed --}}
        <x-ui.analytics-card title="Aktivitas Terbaru" subtitle="15 aktivitas terakhir sistem">
            <div class="flex flex-col gap-3 max-h-[400px] overflow-y-auto pr-2 scrollbar-thin">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-3 py-2 border-b border-border-divider last:border-0" wire:key="act-{{ $activity['id'] }}">
                        <div class="w-1 rounded-full bg-primary self-stretch shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-body-md text-text-primary font-semibold">
                                {{ $activity['user']['name'] ?? 'System' }} 
                                <span class="font-normal text-text-secondary">melakukan</span> 
                                {{ $activity['action'] }}
                            </p>
                            <p class="text-xs text-text-secondary">Subject: {{ basename(str_replace('\\', '/', $activity['subject_type'])) }} #{{ $activity['subject_id'] }}</p>
                        </div>
                        <span class="text-xs text-text-secondary shrink-0 numeric">{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-body-md text-text-secondary text-center py-4">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div>
        </x-ui.analytics-card>

        {{-- Upcoming Reorders Panel --}}
        <x-ui.analytics-card title="Segera Dipesan Ulang" subtitle="Material mendekati ROP dalam 7 hari ke depan">
            <div class="flex flex-col gap-3 max-h-[400px] overflow-y-auto pr-2 scrollbar-thin">
                @forelse($upcomingReorders as $item)
                    <div class="flex items-center justify-between py-2 border-b border-border-divider last:border-0" wire:key="up-{{ $item['id'] }}">
                        <div class="flex-1 min-w-0">
                            <p class="text-body-md text-text-primary font-semibold">{{ $item['nama'] }} ({{ $item['kode'] }})</p>
                            <p class="text-xs text-text-secondary">Stok: {{ number_format($item['stok_saat_ini'], 2) }} / ROP: {{ number_format($item['rop'], 2) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-warning-amber font-semibold numeric">{{ $item['days_until_rop'] }} hari lagi</span>
                            <x-feedback.status-badge status="warning">Mendekati ROP</x-feedback.status-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-body-md text-text-secondary text-center py-4">Tidak ada reorder terproyeksi dalam 7 hari ke depan.</p>
                @endforelse
            </div>
        </x-ui.analytics-card>

    </div>
</div>
