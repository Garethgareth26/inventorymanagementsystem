{{--
    Barang Jadi (Finished Goods) Index — Skeleton (Sprint 2.1.5)
    ────────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Finished Goods"
    pageSubtitle="Gudang Barang Jadi — Pantau stok dan komposisi produk siap jual"
>
    <!-- Page Header Actions -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->hasCapability('finished-good.manage') || !auth()->user()->isOwner())
            <a href="{{ route('barang_jadi.create') }}" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Finished Good
            </a>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari barang jadi..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Status BOM</option>
                    <option value="1">BOM Terdefinisi</option>
                    <option value="0">Belum Ada BOM</option>
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
            :headers="['Kode', 'Nama Barang Jadi', 'Satuan', 'Stok Saat Ini', 'BOM Terdefinisi?', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">FG-001</td>
                <td class="px-lg py-md font-semibold text-text-primary">Sabun Cuci Piring Lavender 450ml</td>
                <td class="px-lg py-md text-text-secondary">pcs</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">350.00</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Terdefinisi</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Edit">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-negative-bg text-danger-red transition-all" title="Hapus">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">FG-002</td>
                <td class="px-lg py-md font-semibold text-text-primary">Detergen Cair Organic Rose 1L</td>
                <td class="px-lg py-md text-text-secondary">pcs</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">0.00</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Belum Ada</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Edit">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-negative-bg text-danger-red transition-all" title="Hapus">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
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
