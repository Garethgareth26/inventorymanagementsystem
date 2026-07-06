{{--
    Owner Dashboard — Skeleton (Sprint 2.1.5)
    ──────────────────────────────────────────
    Source: Stitch v2 — owner_dashboard_cv_akuna/code.html
    Role: Owner only (read-only, zero write surface)
    Real data: Sprint 2 (M-2.6)
--}}
<x-layout.app
    pageTitle="Dashboard"
    pageSubtitle="Selamat datang, {{ auth()->user()?->name ?? 'Owner' }}"
>

    {{-- ── Row 1: KPI Cards ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-md mb-lg">

        {{-- KPI 1: Total Bahan Baku Aktif --}}
        <x-ui.kpi-card
            title="Total Bahan Baku Aktif"
            icon="inventory"
            iconBg="bg-primary-fixed text-primary"
        >
            <x-slot:value>
                <span class="text-text-secondary">—</span>
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
                <span class="text-on-primary-container">Rp —</span>
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
                <span class="text-text-secondary">—</span>
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
                <span class="text-text-secondary">Rp —</span>
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
            <x-ui.analytics-card title="Klasifikasi ABC" subtitle="Distribusi nilai pemakaian" height="h-[320px]">
                {{-- Chart placeholder (replaced with ApexCharts in M-2.19) --}}
                <div class="flex flex-col items-center justify-center h-full gap-4">
                    <div class="relative w-40 h-40">
                        {{-- Placeholder donut ring --}}
                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#E7E4DC" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#3e5c48" stroke-width="3"
                                stroke-dasharray="45 55" stroke-linecap="round"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#C99B5D" stroke-width="3"
                                stroke-dasharray="30 70" stroke-dashoffset="-45" stroke-linecap="round"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#8B8880" stroke-width="3"
                                stroke-dasharray="25 75" stroke-dashoffset="-75" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xs text-text-secondary">ABC</span>
                            <span class="text-lg font-bold text-text-primary">—</span>
                        </div>
                    </div>
                    <div class="flex gap-4 text-xs text-text-secondary">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary-container inline-block"></span> Kelas A</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#C99B5D] inline-block"></span> Kelas B</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-text-secondary inline-block"></span> Kelas C</span>
                    </div>
                    <p class="text-xs text-text-secondary text-center">Data tersedia setelah klasifikasi ABC dijalankan</p>
                </div>
            </x-ui.analytics-card>
        </div>

        {{-- Top-5 Cost Bar Chart (60% width) --}}
        <div class="xl:col-span-3">
            <x-ui.analytics-card title="Top 5 Bahan Baku Termahal" subtitle="Berdasarkan nilai pemakaian tahunan" height="h-[320px]">
                <div class="flex flex-col justify-center h-full gap-3 px-2">
                    @foreach(['—','—','—','—','—'] as $i => $item)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-text-secondary w-4 text-right">{{ $i+1 }}</span>
                            <div class="flex-1 bg-border-divider rounded-full h-6 overflow-hidden">
                                <div class="h-full bg-primary-container/30 rounded-full" style="width: {{ (5-$i)*15 }}%"></div>
                            </div>
                            <span class="text-xs text-text-secondary w-20 text-right">Rp —</span>
                        </div>
                    @endforeach
                    <p class="text-xs text-text-secondary text-center mt-2">Data tersedia setelah analisis dijalankan</p>
                </div>
            </x-ui.analytics-card>
        </div>

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
                :headers="['Kode', 'Nama Bahan Baku', 'Stok Saat Ini', 'ROP', 'Defisit']"
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

    {{-- ── Row 4: Recent Activity + Upcoming Reorders ────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-md">

        {{-- Recent Activity Feed --}}
        <x-ui.analytics-card title="Aktivitas Terbaru" subtitle="15 aktivitas terakhir sistem">
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
                <p class="text-xs text-text-secondary text-center pt-2">Data aktivitas tersedia setelah modul diimplementasi</p>
            </div>
        </x-ui.analytics-card>

        {{-- Upcoming Reorders Panel --}}
        <x-ui.analytics-card title="Segera Dipesan Ulang" subtitle="Material mendekati ROP dalam N hari ke depan">
            <div class="flex flex-col gap-3">
                @foreach(range(1, 4) as $i)
                    <div class="flex items-center justify-between py-2 border-b border-border-divider last:border-0">
                        <div class="flex-1 min-w-0">
                            <div class="h-3 bg-border-divider rounded-full w-1/2 mb-2"></div>
                            <div class="h-2.5 bg-border-divider/60 rounded-full w-1/3"></div>
                        </div>
                        <x-feedback.status-badge status="warning">Segera</x-feedback.status-badge>
                    </div>
                @endforeach
                <p class="text-xs text-text-secondary text-center pt-2">Proyeksi tersedia setelah data pemakaian ada</p>
            </div>
        </x-ui.analytics-card>

    </div>

</x-layout.app>
