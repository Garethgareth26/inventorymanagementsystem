<div>
    <x-slot:head>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </x-slot:head>

    {{-- ── Filter bar ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-sm mb-lg">
        <x-forms.search-input wire:model.live.debounce.300ms="search" placeholder="Cari bahan baku..." />
        <div class="flex gap-2">
            @foreach(['A', 'B', 'C'] as $k)
                <button wire:click="setFilter('{{ $k }}')"
                        class="px-4 py-2 rounded text-sm font-semibold transition-colors
                               {{ $filterKelas === $k
                                    ? 'bg-primary text-on-primary'
                                    : 'bg-surface-container text-text-secondary hover:bg-surface-container-high' }}">
                    Kelas {{ $k }}
                </button>
            @endforeach
            @if($filterKelas)
                <button wire:click="setFilter('')" class="px-3 py-2 rounded bg-surface-container text-text-secondary hover:bg-surface-container-high text-sm transition-colors">
                    ✕ Reset
                </button>
            @endif
        </div>
    </div>

    {{-- ── Chart row ────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-md mb-lg">
        {{-- ABC Donut (2/5) --}}
        <div class="xl:col-span-2">
            <x-ui.analytics-card title="Distribusi Kelas ABC" subtitle="Berdasarkan nilai pemakaian tahunan" height="h-[340px]">
                <div class="flex flex-col items-center justify-center h-full gap-2"
                     x-data="{
                         chart: null,
                         init() {
                             const options = {
                                 chart: { type: 'donut', height: 220, sparkline: { enabled: true } },
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
                                                     formatter: () => 'Rp {{ number_format(array_sum($chartData['donut_value']), 0, ',', '.') }}'
                                                 }
                                             }
                                         }
                                     }
                                 },
                                 dataLabels: { enabled: false }
                             };
                             this.chart = new ApexCharts($refs.donutCanvas, options);
                             this.chart.render();
                             this.$cleanup(() => { if (this.chart) { this.chart.destroy(); } });
                         }
                     }">
                    <div class="w-full max-w-[240px] h-[220px]" x-ref="donutCanvas"></div>
                    <div class="flex gap-4 text-xs text-text-secondary mt-1">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span> Kelas A ({{ $chartData['donut']['A'] }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#C99B5D] inline-block"></span> Kelas B ({{ $chartData['donut']['B'] }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-text-secondary inline-block"></span> Kelas C ({{ $chartData['donut']['C'] }})</span>
                    </div>
                </div>
            </x-ui.analytics-card>
        </div>

        {{-- Top 5 Bar (3/5) --}}
        <div class="xl:col-span-3">
            <x-ui.analytics-card title="Top 5 Nilai Pemakaian" subtitle="Bahan baku dengan nilai pemakaian tahunan tertinggi" height="h-[340px]">
                <div class="flex flex-col justify-center h-full px-2"
                     x-data="{
                         chart: null,
                         init() {
                             const options = {
                                 chart: { type: 'bar', height: 240, toolbar: { show: false } },
                                 plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4 } },
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
                                         formatter: (v) => 'Rp ' + (v / 1000).toLocaleString() + 'k'
                                     }
                                 },
                                 dataLabels: { enabled: false }
                             };
                             this.chart = new ApexCharts($refs.barCanvas, options);
                             this.chart.render();
                             this.$cleanup(() => { if (this.chart) { this.chart.destroy(); } });
                         }
                     }">
                    <div class="w-full h-[240px]" x-ref="barCanvas"></div>
                </div>
            </x-ui.analytics-card>
        </div>
    </div>

    {{-- ── Detail Table ─────────────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Bahan Baku', 'Nilai Pemakaian Tahunan', 'Kontribusi (%)', 'Kumulatif (%)', 'Kelas']"
        :items="$table"
    >
        @foreach($table as $row)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider"
                wire:key="abc-{{ $row['id'] }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $row['kode'] }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $row['nama'] }}</td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    Rp {{ number_format($row['annual_usage_value'], 0, ',', '.') }}
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ number_format($row['individual_pct'], 2) }}%
                </td>
                <td class="px-lg py-md text-body-md numeric text-text-secondary">
                    {{ number_format($row['cumulative_pct'], 2) }}%
                </td>
                <td class="px-lg py-md">
                    @php
                        $type = match($row['kelas']) { 'A' => 'success', 'B' => 'warning', default => 'neutral' };
                    @endphp
                    <x-feedback.status-badge :type="$type">{{ $row['kelas'] }}</x-feedback.status-badge>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="analytics"
                title="Tidak ada data"
                description="Tidak ada bahan baku yang cocok dengan filter ini."
            />
        </x-slot:empty>
    </x-tables.data-table>
</div>
