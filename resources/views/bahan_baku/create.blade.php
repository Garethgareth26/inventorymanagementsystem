{{--
    Bahan Baku (Raw Materials) Create — Skeleton (Sprint 2.1.5)
    ──────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Add Raw Material"
    pageSubtitle="Catat bahan baku baru ke dalam sistem beserta parameter persediaannya"
>
    <!-- Back Button -->
    <div class="mb-lg">
        <a href="{{ route('bahan_baku.index') }}" class="inline-flex items-center gap-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to List
        </a>
    </div>

    <!-- Form Container -->
    <div class="max-w-2xl">
        <x-ui.analytics-card title="Informasi Bahan Baku" subtitle="Harap lengkapi detail dan parameter default supplier">
            <form class="flex flex-col gap-lg mt-4">
                {{-- Kode --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="kode">Kode Bahan Baku <span class="text-danger-red">*</span></label>
                    <input 
                        id="kode"
                        type="text" 
                        placeholder="Contoh: BB01" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Nama --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="nama">Nama Bahan Baku <span class="text-danger-red">*</span></label>
                    <input 
                        id="nama"
                        type="text" 
                        placeholder="Contoh: Methyl Ester Sulfonate (MES)" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Satuan --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="satuan">Satuan <span class="text-danger-red">*</span></label>
                    <select id="satuan" class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all" required>
                        <option value="">Pilih satuan...</option>
                        <option value="kg">kg</option>
                        <option value="liter">liter</option>
                        <option value="gram">gram</option>
                        <option value="ml">ml</option>
                        <option value="pcs">pcs</option>
                    </select>
                </div>

                {{-- Stok Saat Ini (Create-only editable) --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="stok_saat_ini">Stok Awal <span class="text-danger-red">*</span></label>
                    <input 
                        id="stok_saat_ini"
                        type="number" 
                        step="0.01"
                        placeholder="0.00" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                    <span class="text-xs text-text-secondary pl-sm">Setelah dibuat, stok hanya dapat diubah melalui mutasi stok resmi</span>
                </div>

                {{-- Supplier Rutin --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="supplier_id">Supplier Rutin <span class="text-danger-red">*</span></label>
                    <select id="supplier_id" class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all" required>
                        <option value="">Pilih supplier...</option>
                        <option value="1">CV. Multi Kimia Pratama</option>
                        <option value="2">PT. Global Essence Indonesia</option>
                    </select>
                </div>

                {{-- Harga Satuan --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="harga_satuan">Harga Satuan (Rupiah) <span class="text-danger-red">*</span></label>
                    <div class="relative w-full">
                        <span class="absolute left-sm top-1/2 -translate-y-1/2 text-text-secondary font-body-md pointer-events-none select-none pl-sm">Rp</span>
                        <input 
                            id="harga_satuan"
                            type="number" 
                            placeholder="32000" 
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm pl-[40px] pr-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            required
                        />
                    </div>
                </div>

                {{-- Lead Time --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="lead_time">Lead Time (Hari) <span class="text-danger-red">*</span></label>
                    <input 
                        id="lead_time"
                        type="number" 
                        placeholder="4" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Actions --}}
                <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                    <a href="{{ route('bahan_baku.index') }}" class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-xl py-md hover:bg-surface-container-high transition-all duration-150 active:scale-[0.98]">
                        Cancel
                    </a>
                    <button type="submit" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                        Save Material
                    </button>
                </div>
            </form>
        </x-ui.analytics-card>
    </div>
</x-layout.app>
