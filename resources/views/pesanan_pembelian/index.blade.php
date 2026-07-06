{{--
    Purchase Orders Index — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Purchase Orders"
    pageSubtitle="Logistik & Pembelian — Pantau pengadaan bahan baku rutin dan darurat"
>
    <!-- Page Header Actions -->
    <div class="flex justify-end mb-lg">
        @if(auth()->user()->hasCapability('procurement.manage') || !auth()->user()->isOwner())
            <div class="flex gap-sm">
                <button class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Create PO
                </button>
            </div>
        @endif
    </div>

    <!-- Filter & Toolbar -->
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input placeholder="Cari PO / bahan baku..." />
            </x-slot:search>
            <x-slot:filters>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Status</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Dalam Proses">Dalam Proses</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Dibatalkan">Dibatalkan</option>
                </select>
                <select class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all">
                    <option value="">Semua Jenis</option>
                    <option value="Rutin">Rutin</option>
                    <option value="Darurat">Darurat</option>
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
            :headers="['Kode PO', 'Bahan Baku', 'Supplier', 'Jenis', 'Jumlah', 'Status', 'Tanggal Pesan', 'Estimasi Tiba', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">PO-2026-0001</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Methyl Ester Sulfonate (MES)</span>
                        <span class="text-xs text-text-secondary">BB-001</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">CV. Multi Kimia Pratama</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Rutin</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">1,250.00 kg</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Diterima</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary">01 Jul 2026</td>
                <td class="px-lg py-md text-text-secondary">05 Jul 2026</td>
                <td class="px-lg py-md">
                    <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Detail">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary font-semibold">PO-2026-0002</td>
                <td class="px-lg py-md">
                    <div class="flex flex-col">
                        <span class="text-text-primary font-semibold">Linear Alkylbenzene Sulfonic Acid (LAS)</span>
                        <span class="text-xs text-text-secondary">BB-002</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">CV. Multi Kimia Pratama</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Darurat</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">500.00 kg</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Dalam Proses</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-text-secondary">05 Jul 2026</td>
                <td class="px-lg py-md text-text-secondary">09 Jul 2026</td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-surface-container-high text-primary transition-all" title="Detail">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                        @if(auth()->user()->hasCapability('procurement.manage') || !auth()->user()->isOwner())
                            <button class="rounded-full w-8 h-8 flex items-center justify-center hover:bg-primary-fixed/40 text-primary transition-all" title="Receive PO">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            </button>
                        @endif
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
