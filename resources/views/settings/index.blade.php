{{--
    Settings Index — Skeleton (Sprint 2.1.5)
    ─────────────────────────────────────────
--}}
<x-layout.app
    pageTitle="System Settings"
    pageSubtitle="Pengaturan Sistem — Kelola profile perusahaan, parameter default optimasi persediaan, dan preferensi notifikasi"
>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-md items-start">
        
        <!-- Column 1 & 2: Main Configuration Forms -->
        <div class="xl:col-span-2 flex flex-col gap-md">
            
            <!-- Card 1: Calculation Parameters -->
            <x-ui.analytics-card title="Parameter Kalkulasi Optimasi" subtitle="Mengatur nilai dasar system-wide untuk simulasi EOQ, SS, dan ROP">
                <form class="flex flex-col gap-lg mt-4">
                    {{-- Z-Factor Default --}}
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="z_factor_default">Default Z-Factor (Service Level)</label>
                        <input 
                            id="z_factor_default"
                            type="number" 
                            step="0.01"
                            value="1.65" 
                            placeholder="1.65"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                        <span class="text-xs text-text-secondary pl-sm">Nilai default 1.65 mewakili target service level 95%</span>
                    </div>

                    {{-- ABC Thresholds --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="abc_a">Ambang Batas Kelas A (%)</label>
                            <input 
                                id="abc_a"
                                type="number" 
                                value="80" 
                                placeholder="80"
                                class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                                @if(!auth()->user()?->isOwner()) disabled @endif
                            />
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="abc_b">Ambang Batas Kelas B (%)</label>
                            <input 
                                id="abc_b"
                                type="number" 
                                value="95" 
                                placeholder="95"
                                class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                                @if(!auth()->user()?->isOwner()) disabled @endif
                            />
                        </div>
                    </div>

                    {{-- Historical Window & Ordering Costs --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="window_default">Periode Historis Default (Bulan)</label>
                            <input 
                                id="window_default"
                                type="number" 
                                value="12" 
                                placeholder="12"
                                class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                                @if(!auth()->user()?->isOwner()) disabled @endif
                            />
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="order_cost_default">Biaya Pesan Default (S - Rupiah)</label>
                            <input 
                                id="order_cost_default"
                                type="number" 
                                value="75000" 
                                placeholder="75000"
                                class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                                @if(!auth()->user()?->isOwner()) disabled @endif
                            />
                        </div>
                    </div>

                    {{-- Holding Cost Pct --}}
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="holding_cost_default">Biaya Simpan Default (H - % per Tahun)</label>
                        <input 
                            id="holding_cost_default"
                            type="number" 
                            value="20" 
                            placeholder="20"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                    </div>

                    @if(auth()->user()?->isOwner())
                        <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                            <button type="button" class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-xl py-md hover:bg-surface-container-high transition-all">
                                Reset Defaults
                            </button>
                            <button type="submit" class="flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm px-xl py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                                Save Parameters
                            </button>
                        </div>
                    @endif
                </form>
            </x-ui.analytics-card>
            
            <!-- Card 2: Preferensi Notifikasi -->
            <x-ui.analytics-card title="Notification Preferences" subtitle="Konfigurasi saluran pengiriman alert kritis">
                <form class="flex flex-col gap-lg mt-4 select-none">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm">Informasi Polling Alert</label>
                        <input 
                            type="text" 
                            value="Real-time Polling Interval: 15s (System Default)" 
                            class="w-full rounded-full border border-border-divider bg-surface-container-low py-sm px-md font-body-md text-text-secondary focus:outline-none cursor-not-allowed"
                            readonly
                        />
                    </div>

                    <div class="flex flex-col gap-md pl-sm">
                        <label class="inline-flex items-center opacity-50 cursor-not-allowed">
                            <input type="checkbox" class="rounded-full border-border-divider text-primary focus:ring-surface-tint focus:ring-offset-0 focus:ring-1 cursor-not-allowed" disabled />
                            <span class="ms-2 font-body-md text-text-secondary">WhatsApp Notification Alert (Disabled - Client Decision Pending)</span>
                        </label>

                        <label class="inline-flex items-center opacity-50 cursor-not-allowed">
                            <input type="checkbox" class="rounded-full border-border-divider text-primary focus:ring-surface-tint focus:ring-offset-0 focus:ring-1 cursor-not-allowed" disabled />
                            <span class="ms-2 font-body-md text-text-secondary">Email Notification Alert (Disabled - Client Decision Pending)</span>
                        </label>
                    </div>
                </form>
            </x-ui.analytics-card>

        </div>

        <!-- Column 3: Company Profile -->
        <div class="flex flex-col gap-md">
            <x-ui.analytics-card title="Profile Perusahaan" subtitle="Identitas branding yang tercetak pada kop surat laporan PDF">
                <form class="flex flex-col gap-lg mt-4">
                    {{-- Logo Upload --}}
                    <div class="flex flex-col items-center gap-md">
                        <div class="w-24 h-24 rounded-full border-2 border-dashed border-border-divider flex items-center justify-center text-text-secondary select-none">
                            <span class="material-symbols-outlined text-[32px]">image</span>
                        </div>
                        <button type="button" class="rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-lg py-sm hover:bg-surface-container-high transition-all" @if(!auth()->user()?->isOwner()) disabled @endif>
                            Upload New Logo
                        </button>
                    </div>

                    {{-- Nama Perusahaan --}}
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="company_name">Nama Perusahaan</label>
                        <input 
                            id="company_name"
                            type="text" 
                            value="CV. Akuna Organic Indonesia" 
                            placeholder="Contoh: CV. Akuna"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                    </div>

                    {{-- Alamat Perusahaan --}}
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="company_address">Alamat Operasional</label>
                        <textarea 
                            id="company_address"
                            rows="4"
                            class="w-full rounded-md border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        >Jl. Raya Pembangunan No. 104, Cibinong, Bogor, Jawa Barat</textarea>
                    </div>

                    @if(auth()->user()?->isOwner())
                        <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm py-md hover:bg-surface-tint transition-all duration-150 active:scale-[0.98] shadow-sm cursor-pointer">
                                Save Profile
                            </button>
                        </div>
                    @endif
                </form>
            </x-ui.analytics-card>
        </div>

    </div>
</x-layout.app>
