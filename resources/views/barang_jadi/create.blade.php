{{--
    Barang Jadi (Finished Goods) Create — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Add Finished Good"
    pageSubtitle="Catat barang jadi baru ke dalam sistem"
>
    <!-- Back Button -->
    <div class="mb-lg">
        <a href="{{ route('barang_jadi.index') }}" class="inline-flex items-center gap-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to List
        </a>
    </div>

    <!-- Form Container -->
    <div class="max-w-2xl">
        <x-ui.analytics-card title="Informasi Barang Jadi" subtitle="Lengkapi data dasar produk jadi">
            <form class="flex flex-col gap-lg mt-4">
                {{-- Kode --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="kode">Kode Produk <span class="text-danger-red">*</span></label>
                    <input 
                        id="kode"
                        type="text" 
                        placeholder="Contoh: FG01" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Nama --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="nama">Nama Produk <span class="text-danger-red">*</span></label>
                    <input 
                        id="nama"
                        type="text" 
                        placeholder="Contoh: Sabun Batang Lavender" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Satuan --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="satuan">Satuan <span class="text-danger-red">*</span></label>
                    <select id="satuan" class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all" required>
                        <option value="">Pilih satuan...</option>
                        <option value="pcs">pcs</option>
                        <option value="botol">botol</option>
                        <option value="box">box</option>
                    </select>
                </div>

                {{-- Stok Saat Ini (Create-only editable) --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="stok_saat_ini">Stok Awal <span class="text-danger-red">*</span></label>
                    <input 
                        id="stok_saat_ini"
                        type="number" 
                        placeholder="0" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                    <span class="text-xs text-text-secondary pl-sm">Setelah dibuat, stok hanya dapat diubah melalui mutasi stok resmi (produksi / penyesuaian)</span>
                </div>

                {{-- Actions --}}
                <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                    <a href="{{ route('barang_jadi.index') }}" class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-xl py-md hover:bg-surface-container-high transition-all duration-150 active:scale-[0.98]">
                        Cancel
                    </a>
                    <button type="submit" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                        Save Product
                    </button>
                </div>
            </form>
        </x-ui.analytics-card>
    </div>
</x-layout.app>
