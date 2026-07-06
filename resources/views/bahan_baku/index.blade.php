{{--
    Bahan Baku (Raw Materials) Index — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Raw Materials"
    pageSubtitle="Gudang Bahan Baku — Kelola stok dan parameter pembelian ulang bahan baku"
>
    <!-- Page Header Actions -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->hasCapability('material.manage') || !auth()->user()->isOwner())
            <a href="{{ route('bahan_baku.create') }}" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Raw Material
            </a>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari bahan baku..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Kelas ABC</option>
                    <option value="A">Kelas A</option>
                    <option value="B">Kelas B</option>
                    <option value="C">Kelas C</option>
                </select>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Supplier</option>
                    <option value="1">CV. Multi Kimia Pratama</option>
                    <option value="2">PT. Global Essence Indonesia</option>
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
            :headers="['Kode', 'Nama Bahan Baku', 'Satuan', 'Stok Saat Ini', 'Kelas ABC', 'Harga Satuan', 'Supplier Rutin', 'Aksi']"
            :items="[1, 2, 3]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">BB-001</td>
                <td class="px-lg py-md font-semibold text-text-primary">Methyl Ester Sulfonate (MES)</td>
                <td class="px-lg py-md text-text-secondary">kg</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">1,250.00</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Kelas A</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 32.000</td>
                <td class="px-lg py-md text-text-secondary">CV. Multi Kimia Pratama</td>
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
                <td class="px-lg py-md font-tabular-nums text-text-primary">BB-002</td>
                <td class="px-lg py-md font-semibold text-text-primary">Linear Alkylbenzene Sulfonic Acid (LAS)</td>
                <td class="px-lg py-md text-text-secondary">kg</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">85.00</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Kelas B</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 45.000</td>
                <td class="px-lg py-md text-text-secondary">CV. Multi Kimia Pratama</td>
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

            <!-- Row 3 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">BB-003</td>
                <td class="px-lg py-md font-semibold text-text-primary">Essential Oil Lavender</td>
                <td class="px-lg py-md text-text-secondary">liter</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">12.00</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Kelas C</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 450.000</td>
                <td class="px-lg py-md text-text-secondary">PT. Global Essence Indonesia</td>
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
