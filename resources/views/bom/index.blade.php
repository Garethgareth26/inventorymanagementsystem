{{--
    Bill of Materials (BOM) Index — Skeleton (Sprint 2.1.5)
    ────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Bill of Materials"
    pageSubtitle="Resep & Komposisi — Kelola formulasi bahan baku untuk setiap produk jadi"
>
    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari barang jadi..." />
            </x-slot:search>
            <x-slot:filters>
                <label class="inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" class="rounded-full border-border-divider text-primary focus:ring-surface-tint focus:ring-offset-0 focus:ring-1" />
                    <span class="ms-2 font-body-md text-text-secondary">Tampilkan Hanya yang Belum Ada BOM</span>
                </label>
            </x-slot:filters>
        </x-forms.filter-bar>
    </div>

    <!-- Data Table -->
    <div class="mb-lg">
        <x-tables.data-table
            :headers="['Produk Jadi', 'Satuan', 'Jumlah Bahan Baku', 'Terakhir Diubah', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="font-semibold text-text-primary">Sabun Cuci Piring Lavender 450ml</span>
                        <span class="text-xs text-text-secondary">FG-001</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">pcs</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary text-center">5</td>
                <td class="px-lg py-md text-text-secondary">05 Jul 2026 14:32</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-md py-sm hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[16px]">edit_note</span>
                        Open Editor
                    </button>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="font-semibold text-text-primary">Detergen Cair Organic Rose 1L</span>
                        <span class="text-xs text-text-secondary">FG-002</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">pcs</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary text-center">0</td>
                <td class="px-lg py-md text-text-secondary">—</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-md py-sm hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">add_circle</span>
                        Create BOM
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
