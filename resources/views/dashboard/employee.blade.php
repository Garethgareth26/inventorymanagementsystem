{{--
    Employee Dashboard — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────────
    Source: Stitch v2 — employee_dashboard_cv_akuna/code.html
    Role: Employee (operational cockpit with quick actions)
    Real data: Sprint 2 (M-2.7)
--}}
<x-layout.app
    pageTitle="Dashboard"
    pageSubtitle="Selamat datang, {{ auth()->user()?->name ?? 'Karyawan' }}"
>

    {{-- ── Row 1: KPI Cards (3 cards) ───────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-md mb-lg">

        {{-- KPI 1: Material Kritis (Hero) --}}
        <x-ui.kpi-card
            title="Material Kritis Hari Ini"
            icon="warning"
            :hero="true"
        >
            <x-slot:value>
                <span class="text-on-primary-container">—</span>
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
                <span class="text-text-secondary">—</span>
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
                <span class="text-text-secondary">—</span>
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
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                    Buat PO Baru
                </a>
                <a href="{{ route('production.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-xl py-md hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">precision_manufacturing</span>
                    Catat Produksi
                </a>
                <a href="{{ route('mutasi_stok.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-xl py-md hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">tune</span>
                    Sesuaikan Stok
                </a>
            </div>
        </x-ui.analytics-card>
    </div>

    {{-- ── Row 3: Critical Stock Table ───────────────────────────────── --}}
    <div class="mb-lg">
        <x-ui.analytics-card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-negative-rose text-[20px]">emergency_heat</span>
                            Live Stock Critical Alert
                        </h3>
                        <p class="text-xs text-text-secondary mt-0.5">Diperbarui setiap 15 detik · <span class="text-warning-amber">Data belum tersedia</span></p>
                    </div>
                </div>
            </x-slot:header>

            <x-tables.data-table
                :headers="['Kode', 'Nama Bahan Baku', 'Stok Saat Ini', 'ROP', 'Defisit', 'Aksi']"
                :items="[]"
            >
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
            <div class="flex flex-col gap-3">
                @foreach(range(1, 5) as $i)
                    <div class="flex items-start gap-3 py-2 border-b border-border-divider last:border-0">
                        <div class="w-1 rounded-full bg-primary-container self-stretch shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <div class="h-3 bg-border-divider rounded-full w-3/4 mb-2"></div>
                            <div class="h-2.5 bg-border-divider/60 rounded-full w-1/2"></div>
                        </div>
                        <span class="text-xs text-text-secondary shrink-0">—</span>
                    </div>
                @endforeach
                <p class="text-xs text-text-secondary text-center pt-2">Aktivitas Anda akan muncul di sini setelah modul diimplementasi</p>
            </div>
        </x-ui.analytics-card>
    </div>

</x-layout.app>
