<div>
    {{-- ── Row 1: KPI Cards (3 cards) ───────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-md mb-lg">

        {{-- KPI 1: Material Kritis (Hero) --}}
        <x-ui.kpi-card
            title="Material Kritis Hari Ini"
            icon="warning"
            :hero="true"
        >
            <x-slot:value>
                <span class="font-semibold text-on-primary-container numeric">{{ $metrics['critical_materials'] }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-on-primary-container/70">Item di bawah ROP</span>
            </x-slot:footer>
        </x-ui.kpi-card>

        {{-- KPI 2: PO Menunggu --}}
        <x-ui.kpi-card
            title="PO Menunggu"
            icon="shopping_cart"
            iconBg="bg-accent-tan-light text-tertiary-container"
        >
            <x-slot:value>
                <span class="font-semibold text-text-primary numeric">{{ $metrics['pending_pos'] }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-text-secondary">Perlu konfirmasi / penerimaan</span>
            </x-slot:footer>
        </x-ui.kpi-card>

        {{-- KPI 3: Produksi Bulan Ini --}}
        <x-ui.kpi-card
            title="Produksi Bulan Ini"
            icon="precision_manufacturing"
            iconBg="bg-primary-fixed text-primary"
        >
            <x-slot:value>
                <span class="font-semibold text-text-primary numeric">{{ $metrics['production_this_month'] }}</span>
            </x-slot:value>
            <x-slot:footer>
                <span class="text-xs text-text-secondary">Entri produksi tercatat</span>
            </x-slot:footer>
        </x-ui.kpi-card>

    </div>

    {{-- ── Row 2: Quick Actions Bar ──────────────────────────────────── --}}
    <div class="mb-lg">
        <x-ui.analytics-card>
            <div class="flex flex-col sm:flex-row gap-md items-center justify-center py-md">
                <a href="{{ route('pesanan_pembelian.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full bg-primary text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-primary/95 transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                    Buat PO Baru
                </a>
                <a href="{{ route('production.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full border border-primary text-primary bg-transparent font-label-sm text-label-sm px-xl py-md hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98] cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">precision_manufacturing</span>
                    Catat Produksi
                </a>
                <a href="{{ route('mutasi_stok.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full border border-primary text-primary bg-transparent font-label-sm text-label-sm px-xl py-md hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98] cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">tune</span>
                    Sesuaikan Stok
                </a>
            </div>
        </x-ui.analytics-card>
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
                :headers="['Kode', 'Nama Bahan Baku', 'Stok Saat Ini', 'ROP', 'Defisit', 'Aksi']"
                :items="$criticalStock"
            >
                @foreach($criticalStock as $item)
                    <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="crit-{{ $item['id'] }}">
                        <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item['kode'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-primary">{{ $item['nama'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-primary numeric">{{ number_format($item['stok_saat_ini'], 2) }} {{ $item['satuan'] }}</td>
                        <td class="px-lg py-md text-body-md text-text-secondary numeric">{{ number_format($item['rop'], 2) }} {{ $item['satuan'] }}</td>
                        <td class="px-lg py-md text-body-md font-semibold text-negative-rose numeric">{{ number_format($item['defisit'], 2) }} {{ $item['satuan'] }}</td>
                        <td class="px-lg py-md">
                            <a href="{{ route('pesanan_pembelian.index') }}?bahan_baku_id={{ $item['id'] }}&jenis=Darurat"
                               class="inline-flex items-center justify-center rounded-full bg-negative-bg text-negative-rose text-xs px-3 py-1 font-semibold hover:bg-negative-bg/85 transition-all cursor-pointer">
                                Buat PO Darurat
                            </a>
                        </td>
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

    {{-- ── Row 4: Recent Activity Feed ───────────────────────────────── --}}
    <div>
        <x-ui.analytics-card title="Aktivitas Saya Terbaru" subtitle="15 aktivitas terakhir Anda">
            <div class="flex flex-col gap-3 max-h-[400px] overflow-y-auto pr-2 scrollbar-thin">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-3 py-2 border-b border-border-divider last:border-0" wire:key="act-{{ $activity['id'] }}">
                        <div class="w-1 rounded-full bg-primary self-stretch shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-body-md text-text-primary font-semibold">
                                Anda <span class="font-normal text-text-secondary">melakukan</span> {{ $activity['action'] }}
                            </p>
                            <p class="text-xs text-text-secondary">Subject: {{ basename(str_replace('\\', '/', $activity['subject_type'])) }} #{{ $activity['subject_id'] }}</p>
                        </div>
                        <span class="text-xs text-text-secondary shrink-0 numeric">{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-body-md text-text-secondary text-center py-4">Belum ada aktivitas Anda tercatat.</p>
                @endforelse
            </div>
        </x-ui.analytics-card>
    </div>
</div>
