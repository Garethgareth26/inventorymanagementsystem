{{--
    Inventory Movements (Stock Mutation) Index — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Stock Mutation"
    pageSubtitle="Buku Besar Inventori — Audit ledger mutasi masuk dan keluar dari seluruh item"
>
    <!-- Page Header Actions (Only Stock Adjustment action is available here for Employee) -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->hasCapability('stock.adjust') || !auth()->user()->isOwner())
            <button class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined text-[18px]">tune</span>
                Stock Adjustment
            </button>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari item / kode..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Tipe Item</option>
                    <option value="bahan_baku">Bahan Baku</option>
                    <option value="barang_jadi">Barang Jadi</option>
                </select>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Jenis Mutasi</option>
                    <option value="masuk">Masuk</option>
                    <option value="keluar">Keluar</option>
                </select>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Sumber</option>
                    <option value="manual">Manual</option>
                    <option value="po_penerimaan">PO Penerimaan</option>
                    <option value="produksi">Produksi</option>
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
            :headers="['Tanggal', 'Item', 'Jenis', 'Jumlah', 'Sumber', 'Referensi', 'Dicatat Oleh']"
            :items="[1, 2, 3]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md text-text-secondary font-tabular-nums">05 Jul 2026 10:00</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Sabun Cuci Piring Lavender 450ml</span>
                        <span class="text-xs text-text-secondary">FG-001 (Barang Jadi)</span>
                    </div>
                </td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Masuk</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">100.00 pcs</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Produksi</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">PRD-2026-0001</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md text-text-secondary font-tabular-nums">05 Jul 2026 10:00</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Methyl Ester Sulfonate (MES)</span>
                        <span class="text-xs text-text-secondary">BB-001 (Bahan Baku)</span>
                    </div>
                </td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Keluar</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">25.00 kg</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Produksi</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">PRD-2026-0001</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
            </tr>

            <!-- Row 3 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md text-text-secondary font-tabular-nums">05 Jul 2026 09:15</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Linear Alkylbenzene Sulfonic Acid (LAS)</span>
                        <span class="text-xs text-text-secondary">BB-002 (Bahan Baku)</span>
                    </div>
                </td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Masuk</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">500.00 kg</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">PO Penerimaan</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">PO-2026-0002</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
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
