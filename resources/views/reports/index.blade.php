{{--
    Reports Index — Skeleton (Sprint 2.1.5)
    ───────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Reports"
    pageSubtitle="Generator Laporan — Konfigurasi dan unduh laporan PDF inventori, pembelian, dan produksi"
>
    <!-- Section 1: Generator -->
    <div class="mb-xl">
        <h2 class="font-headline-md text-headline-md text-text-primary mb-md select-none">Generate Laporan Baru</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-md mb-lg">
            <!-- Report Type 1: Valuasi Aset -->
            <label class="cursor-pointer group select-none">
                <input type="radio" name="report_type" value="valuasi_aset" class="sr-only peer" checked />
                <div class="h-full bg-card-surface border border-border-divider p-xl rounded-DEFAULT flex flex-col justify-between transition-all group-hover:-translate-y-0.5 peer-checked:border-primary-container peer-checked:ring-2 peer-checked:ring-primary-container/20">
                    <div>
                        <div class="w-10 h-10 rounded-full bg-primary-fixed text-primary flex items-center justify-center mb-md group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                        </div>
                        <h3 class="font-headline-md text-headline-md text-text-primary font-semibold">Valuasi Aset</h3>
                        <p class="text-body-sm text-text-secondary mt-sm">Detail stok saat ini dan total nilai investasi seluruh bahan baku dan barang jadi.</p>
                    </div>
                </div>
            </label>

            <!-- Report Type 2: Performa Supplier -->
            <label class="cursor-pointer group select-none">
                <input type="radio" name="report_type" value="performa_supplier" class="sr-only peer" />
                <div class="h-full bg-card-surface border border-border-divider p-xl rounded-DEFAULT flex flex-col justify-between transition-all group-hover:-translate-y-0.5 peer-checked:border-primary-container peer-checked:ring-2 peer-checked:ring-primary-container/20">
                    <div>
                        <div class="w-10 h-10 rounded-full bg-accent-tan-light text-tertiary-container flex items-center justify-center mb-md group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                        </div>
                        <h3 class="font-headline-md text-headline-md text-text-primary font-semibold">Performa Supplier</h3>
                        <p class="text-body-sm text-text-secondary mt-sm">Analisis kepatuhan waktu pengiriman (lead time) dan ketepatan jumlah pasokan supplier.</p>
                    </div>
                </div>
            </label>

            <!-- Report Type 3: Mutasi Bulanan -->
            <label class="cursor-pointer group select-none">
                <input type="radio" name="report_type" value="mutasi_bulanan" class="sr-only peer" />
                <div class="h-full bg-card-surface border border-border-divider p-xl rounded-DEFAULT flex flex-col justify-between transition-all group-hover:-translate-y-0.5 peer-checked:border-primary-container peer-checked:ring-2 peer-checked:ring-primary-container/20">
                    <div>
                        <div class="w-10 h-10 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant flex items-center justify-center mb-md group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">swap_horiz</span>
                        </div>
                        <h3 class="font-headline-md text-headline-md text-text-primary font-semibold">Mutasi Bulanan</h3>
                        <p class="text-body-sm text-text-secondary mt-sm">Rekapitulasi keluar masuk stok inventori secara mendalam dalam periode bulanan.</p>
                    </div>
                </div>
            </label>
        </div>

        <!-- Date Inputs & Trigger -->
        <div class="bg-card-surface border border-border-divider p-lg rounded-DEFAULT flex flex-col md:flex-row gap-lg items-center justify-between shadow-sm">
            <div class="flex flex-col sm:flex-row gap-md items-center w-full md:w-auto">
                <div class="flex flex-col gap-xs w-full sm:w-auto">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm">Mulai Tanggal</label>
                    <input type="date" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all w-full sm:w-48" required />
                </div>
                <div class="flex flex-col gap-xs w-full sm:w-auto">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm">Sampai Tanggal</label>
                    <input type="date" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all w-full sm:w-48" required />
                </div>
            </div>
            
            <button class="w-full md:w-auto flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer mt-md md:mt-0">
                <span class="material-symbols-outlined text-[18px]">build</span>
                Generate PDF Report
            </button>
        </div>
    </div>

    <!-- Section 2: History -->
    <div>
        <h2 class="font-headline-md text-headline-md text-text-primary mb-md select-none">Riwayat Unduhan Laporan</h2>
        
        <x-tables.data-table
            :headers="['Jenis Laporan', 'Rentang Tanggal', 'Dibuat Oleh', 'Tanggal Dibuat', 'Aksi']"
            :items="[1, 2]"
        >
            <!-- Row 1 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-primary text-[20px]">account_balance_wallet</span>
                        <span class="font-semibold text-text-primary">Laporan Valuasi Aset</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">As of 05 Jul 2026</td>
                <td class="px-lg py-md text-text-secondary">Owner (Admin)</td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">05 Jul 2026 15:45</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-md py-sm hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[16px]">download</span>
                        Unduh PDF
                    </button>
                </td>
            </tr>

            <!-- Row 2 -->
            <tr class="hover:bg-surface-container-low transition-colors duration-150">
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-[#C99B5D] text-[20px]">local_shipping</span>
                        <span class="font-semibold text-text-primary">Laporan Performa Supplier</span>
                    </div>
                </td>
                <td class="px-lg py-md text-text-secondary">01 Jun 2026 - 30 Jun 2026</td>
                <td class="px-lg py-md text-text-secondary">Karyawan Gudang</td>
                <td class="px-lg py-md text-text-secondary font-tabular-nums">01 Jul 2026 09:30</td>
                <td class="px-lg py-md">
                    <button class="flex items-center justify-center gap-2 rounded-full border border-primary-container text-primary font-label-sm text-label-sm px-md py-sm hover:bg-primary-fixed/40 transition-all duration-150 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[16px]">download</span>
                        Unduh PDF
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
