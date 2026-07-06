{{--
    Suppliers Create — Skeleton (Sprint 2.1.5)
    ───────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="Add Supplier"
    pageSubtitle="Catat rekanan pemasok baru ke dalam sistem"
>
    <!-- Back Button -->
    <div class="mb-lg">
        <a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to List
        </a>
    </div>

    <!-- Form Container -->
    <div class="max-w-2xl">
        <x-ui.analytics-card title="Informasi Supplier" subtitle="Harap lengkapi data pemasok dengan benar">
            <form class="flex flex-col gap-lg mt-4">
                {{-- Kode --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="kode">Kode Supplier <span class="text-danger-red">*</span></label>
                    <input 
                        id="kode"
                        type="text" 
                        placeholder="Contoh: SUP-001" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                    <span class="text-xs text-text-secondary pl-sm">Digunakan sebagai referensi singkat di seluruh sistem</span>
                </div>

                {{-- Nama --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="nama">Nama Perusahaan <span class="text-danger-red">*</span></label>
                    <input 
                        id="nama"
                        type="text" 
                        placeholder="Contoh: CV. Multi Kimia" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        required
                    />
                </div>

                {{-- Alamat --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="alamat">Alamat</label>
                    <textarea 
                        id="alamat"
                        rows="3"
                        placeholder="Alamat lengkap operasional perusahaan..." 
                        class="w-full rounded-md border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                    ></textarea>
                </div>

                {{-- Kontak --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="kontak">Kontak (Telepon / Email)</label>
                    <input 
                        id="kontak"
                        type="text" 
                        placeholder="Contoh: 0812-3456-7890 / pic@company.com" 
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary placeholder:text-outline-variant focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                    />
                    <span class="text-xs text-text-secondary pl-sm">Nomor telepon atau email PIC</span>
                </div>

                {{-- Actions --}}
                <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                    <a href="{{ route('suppliers.index') }}" class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-xl py-md hover:bg-surface-container-high transition-all duration-150 active:scale-[0.98]">
                        Cancel
                    </a>
                    <button type="submit" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                        Save Supplier
                    </button>
                </div>
            </form>
        </x-ui.analytics-card>
    </div>
</x-layout.app>
