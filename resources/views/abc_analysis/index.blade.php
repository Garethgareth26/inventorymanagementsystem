{{--
    ABC Analysis Index — Skeleton (Sprint 2.1.5)
    ────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="ABC Analysis"
    pageSubtitle="Analisis Klasifikasi Persediaan — Kelompokkan bahan baku berdasarkan kontribusi nilai konsumsi tahunan untuk prioritas kontrol"
>
    <!-- Donut Chart & Summary Cards -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-md mb-lg">
        <!-- Donut Chart -->
        <div class="xl:col-span-2">
            <x-ui.analytics-card title="Distribusi Kelas ABC" subtitle="Persentase jumlah item per kategori">
                <div class="flex flex-col items-center justify-center h-48 gap-4 mt-2">
                    <div class="relative w-36 h-36">
                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#E7E4DC" stroke-width="3.5"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#3e5c48" stroke-width="3.5"
                                stroke-dasharray="40 60" stroke-linecap="round"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#C99B5D" stroke-width="3.5"
                                stroke-dasharray="30 70" stroke-dashoffset="-40" stroke-linecap="round"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#8B8880" stroke-width="3.5"
                                stroke-dasharray="30 70" stroke-dashoffset="-70" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-[10px] text-text-secondary uppercase font-semibold">Total Item</span>
                            <span class="text-xl font-bold text-text-primary">10</span>
                        </div>
                    </div>
                </div>
            </x-ui.analytics-card>
        </div>

        <!-- Class Cards (A, B, C details) -->
        <div class="xl:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-md">
            <!-- Class A -->
            <div class="bg-card-surface border border-border-divider p-md rounded-DEFAULT flex flex-col justify-between">
                <div>
                    <x-feedback.status-badge status="success">Kelas A</x-feedback.status-badge>
                    <p class="text-body-sm text-text-secondary mt-sm">Prioritas Tinggi. Kontrol ketat, pemantauan stok harian.</p>
                </div>
                <div class="mt-lg">
                    <p class="text-xs text-text-secondary uppercase tracking-wider">Nilai Kumulatif</p>
                    <p class="text-headline-md font-bold text-text-primary">80.0%</p>
                </div>
            </div>

            <!-- Class B -->
            <div class="bg-card-surface border border-border-divider p-md rounded-DEFAULT flex flex-col justify-between">
                <div>
                    <x-feedback.status-badge status="warning">Kelas B</x-feedback.status-badge>
                    <p class="text-body-sm text-text-secondary mt-sm">Prioritas Menengah. Kontrol moderat, pemesanan berkala.</p>
                </div>
                <div class="mt-lg">
                    <p class="text-xs text-text-secondary uppercase tracking-wider">Nilai Kumulatif</p>
                    <p class="text-headline-md font-bold text-text-primary">15.0%</p>
                </div>
            </div>

            <!-- Class C -->
            <div class="bg-card-surface border border-border-divider p-md rounded-DEFAULT flex flex-col justify-between">
                <div>
                    <x-feedback.status-badge status="neutral">Kelas C</x-feedback.status-badge>
                    <p class="text-body-sm text-text-secondary mt-sm">Prioritas Rendah. Kontrol longgar, stok pengaman lebih tinggi.</p>
                </div>
                <div class="mt-lg">
                    <p class="text-xs text-text-secondary uppercase tracking-wider">Nilai Kumulatif</p>
                    <p class="text-headline-md font-bold text-text-primary">5.0%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari bahan baku..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Kelas</option>
                    <option value="A">Kelas A</option>
                    <option value="B">Kelas B</option>
                    <option value="C">Kelas C</option>
                </select>
            </x-slot:filters>
            <x-slot:actions>
                <button class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-lg py-md hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export CSV
                </button>
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    <!-- Data Table -->
    <div class="mb-lg">
        <x-tables.data-table
            :headers="['Kode', 'Nama Bahan Baku', 'Nilai Pemakaian / Th', '% Kontribusi', '% Kumulatif', 'Kelas']"
            :items="[1, 2, 3]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">BB-001</td>
                <td class="px-lg py-md font-semibold text-text-primary">Methyl Ester Sulfonate (MES)</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 480.000.000</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">55.2%</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">55.2%</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Kelas A</x-feedback.status-badge>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">BB-002</td>
                <td class="px-lg py-md font-semibold text-text-primary">Linear Alkylbenzene Sulfonic Acid (LAS)</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 243.000.000</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">27.9%</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">83.1%</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Kelas A</x-feedback.status-badge>
                </td>
            </tr>

            <!-- Row 3 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">BB-003</td>
                <td class="px-lg py-md font-semibold text-text-primary">Essential Oil Lavender</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 54.000.000</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">6.2%</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">89.3%</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Kelas B</x-feedback.status-badge>
                </td>
            </tr>

            <x-slot:pagination>
                <div class="flex justify-between items-center w-full">
                    <span class="text-body-md text-text-secondary font-body-md">Showing 1 to 3 of 3 entries</span>
                    <div class="flex gap-sm">
                        <button class="px-md py-sm rounded-full border border-border-divider text-text-secondary hover:bg-surface-container-high text-xs cursor-pointer select-none transition-all disabled:opacity-50" disabled>Previous</button>
                        <button class="px-md py-sm rounded-full bg-primary-container text-on-primary text-xs cursor-pointer select-none transition-all">1</button>
                        <button class="px-md py-sm rounded-full border border-border-divider text-text-secondary hover:bg-surface-container-high text-xs cursor-pointer select-none transition-all disabled:opacity-50" disabled>Next</button>
                    </div>
                </div>
            </x-slot:pagination>
        </x-tables.data-table>
    </div>
</x-layout.app>
