<div>
    <!-- Section 1: Generator -->
    <div class="mb-xl bg-card-surface border border-border-divider p-xl rounded-DEFAULT shadow-sm">
        <h2 class="font-headline-md text-headline-md text-text-primary mb-md select-none">Generate Laporan Baru</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-md mb-lg">
            <!-- Report Type 1: Valuasi Aset -->
            <label class="cursor-pointer group select-none">
                <input type="radio" name="report_type" value="valuasi_aset" wire:model="reportType" class="sr-only peer" />
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
                <input type="radio" name="report_type" value="performa_supplier" wire:model="reportType" class="sr-only peer" />
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
                <input type="radio" name="report_type" value="mutasi_bulanan" wire:model="reportType" class="sr-only peer" />
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
                    <input type="date" wire:model="startDate" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all w-full sm:w-48" required />
                    <x-input-error :messages="$errors->get('startDate')" />
                </div>
                <div class="flex flex-col gap-xs w-full sm:w-auto">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm">Sampai Tanggal</label>
                    <input type="date" wire:model="endDate" class="rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-secondary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all w-full sm:w-48" required />
                    <x-input-error :messages="$errors->get('endDate')" />
                </div>
            </div>
            
            <x-ui.primary-button wire:click="generate" wire:loading.attr="disabled" class="w-full md:w-auto cursor-pointer">
                <span wire:loading.remove class="material-symbols-outlined text-[18px]">build</span>
                <span wire:loading class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                Generate PDF Report
            </x-ui.primary-button>
        </div>
    </div>
</div>
