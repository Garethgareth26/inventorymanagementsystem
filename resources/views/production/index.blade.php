{{--
    Production Index — Skeleton (Sprint 2.1.5)
    ──────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Production Runs"
    pageSubtitle="Produksi & Manufaktur — Pantau riwayat aktivitas produksi barang jadi"
>
    <!-- Page Header Actions -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->hasCapability('production.record') || !auth()->user()->isOwner())
            <button class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Create Production Entry
            </button>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari barang jadi..." />
            </x-slot:search>
            <x-slot:filters>
                <input type="date" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all" placeholder="Mulai Tanggal" />
                <input type="date" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all" placeholder="Sampai Tanggal" />
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
            :headers="['Tanggal', 'Barang Jadi', 'Jumlah Diproduksi', 'Dicatat Oleh', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md text-text-secondary font-tabular-nums">05 Jul 2026 10:00</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Sabun Cuci Piring Lavender 450ml</span>
                        <span class="text-xs text-text-secondary">FG-001</span>
                    </div>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">100.00 pcs</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
                <td class="px-lg py-md">
                    <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Lihat Detail Mutasi">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md text-text-secondary font-tabular-nums">03 Jul 2026 11:30</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Sabun Cuci Piring Lavender 450ml</span>
                        <span class="text-xs text-text-secondary">FG-001</span>
                    </div>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">250.00 pcs</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
                <td class="px-lg py-md">
                    <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Lihat Detail Mutasi">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </td>
            </tr>

            <x-slot:pagination>
                <div class="flex justify-between items-center w-full">
                    <span class="text-body-md text-text-secondary font-body-md">Showing 1 to 2 of 2 entries</span>
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
