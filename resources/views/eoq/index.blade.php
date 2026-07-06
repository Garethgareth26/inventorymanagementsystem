{{--
    EOQ Index — Skeleton (Sprint 2.1.5)
    ───────────────────────────────────────
--}}
<x-layout.app
    pageTitle="EOQ Analysis"
    pageSubtitle="Economic Order Quantity — Optimalkan kuantitas pemesanan untuk meminimalkan total biaya persediaan"
>
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
            </x-slot:filters>
        </x-forms.filter-bar>
    </div>

    <!-- Data Table -->
    <div class="mb-lg">
        <x-tables.data-table
            :headers="['Kode', 'Nama Bahan Baku', 'Kelas ABC', 'D (Kebutuhan/Th)', 'S (Biaya Pesan)', 'H (Biaya Simpan)', 'Current EOQ', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">BB-001</td>
                <td class="px-lg py-md font-semibold text-text-primary">Methyl Ester Sulfonate (MES)</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="success">Kelas A</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">15,000.00 kg</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 75.000</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 6.400 / kg / th</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary font-bold">592.00 kg</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-md py-sm hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[16px]">calculate</span>
                        Simulate
                    </button>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md font-tabular-nums text-text-primary">BB-002</td>
                <td class="px-lg py-md font-semibold text-text-primary">Linear Alkylbenzene Sulfonic Acid (LAS)</td>
                <td class="px-lg py-md">
                    <x-feedback.status-badge status="warning">Kelas B</x-feedback.status-badge>
                </td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">5,400.00 kg</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 75.000</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary">Rp 9.000 / kg / th</td>
                <td class="px-lg py-md font-tabular-nums text-text-primary font-bold">300.00 kg</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-md py-sm hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[16px]">calculate</span>
                        Simulate
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
