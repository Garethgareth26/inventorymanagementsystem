<div class="grid grid-cols-1 xl:grid-cols-3 gap-md items-start">
    
    <!-- Column 1 & 2: Main Configuration Forms -->
    <div class="xl:col-span-2 flex flex-col gap-md">
        
        <!-- Card 1: Calculation Parameters -->
        <x-ui.analytics-card title="Parameter Kalkulasi Optimasi" subtitle="Mengatur nilai dasar system-wide untuk simulasi EOQ, SS, dan ROP">
            <form wire:submit.prevent="saveCalculationParameters" class="flex flex-col gap-lg mt-4">
                {{-- Z-Factor Default --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="z_factor">Default Z-Factor (Service Level)</label>
                    <input 
                        id="z_factor"
                        type="number" 
                        step="0.01"
                        wire:model="z_factor"
                        placeholder="1.65"
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        @if(!auth()->user()?->isOwner()) disabled @endif
                    />
                    <x-input-error :messages="$errors->get('z_factor')" />
                    <span class="text-xs text-text-secondary pl-sm">Nilai default 1.65 mewakili target service level 95%</span>
                </div>

                {{-- ABC Thresholds --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="abc_threshold_a">Ambang Batas Kelas A (%)</label>
                        <input 
                            id="abc_threshold_a"
                            type="number" 
                            wire:model="abc_threshold_a"
                            placeholder="80"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                        <x-input-error :messages="$errors->get('abc_threshold_a')" />
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="abc_threshold_b">Ambang Batas Kelas B (%)</label>
                        <input 
                            id="abc_threshold_b"
                            type="number" 
                            wire:model="abc_threshold_b"
                            placeholder="95"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                        <x-input-error :messages="$errors->get('abc_threshold_b')" />
                    </div>
                </div>

                {{-- Historical Window & Ordering Costs --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="historical_window">Periode Historis Default (Bulan)</label>
                        <input 
                            id="historical_window"
                            type="number" 
                            wire:model="historical_window"
                            placeholder="12"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                        <x-input-error :messages="$errors->get('historical_window')" />
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="biaya_pesan">Biaya Pesan Default (S - Rupiah)</label>
                        <input 
                            id="biaya_pesan"
                            type="number" 
                            wire:model="biaya_pesan"
                            placeholder="75000"
                            class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                            @if(!auth()->user()?->isOwner()) disabled @endif
                        />
                        <x-input-error :messages="$errors->get('biaya_pesan')" />
                    </div>
                </div>

                {{-- Holding Cost Pct --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="biaya_simpan_pct">Biaya Simpan Default (H - % per Tahun)</label>
                    <input 
                        id="biaya_simpan_pct"
                        type="number" 
                        wire:model="biaya_simpan_pct"
                        placeholder="20"
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        @if(!auth()->user()?->isOwner()) disabled @endif
                    />
                    <x-input-error :messages="$errors->get('biaya_simpan_pct')" />
                </div>

                @if(auth()->user()?->isOwner())
                    <div class="flex gap-md pt-md border-t border-border-divider justify-end">
                        <button type="button" wire:click="resetCalculationParameters" class="flex items-center justify-center gap-2 rounded-full border border-border-divider text-text-secondary font-label-sm text-label-sm px-xl py-md hover:bg-surface-container-high transition-all">
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
            <form wire:submit.prevent="saveCompanyProfile" class="flex flex-col gap-lg mt-4">
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
                        wire:model="company_name"
                        placeholder="Contoh: CV. Akuna"
                        class="w-full rounded-full border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        @if(!auth()->user()?->isOwner()) disabled @endif
                    />
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>

                {{-- Alamat Perusahaan --}}
                <div class="flex flex-col gap-xs">
                    <label class="font-label-sm text-label-sm text-text-secondary pl-sm" for="company_address">Alamat Operasional</label>
                    <textarea 
                        id="company_address"
                        rows="4"
                        wire:model="company_address"
                        class="w-full rounded-md border border-border-divider bg-transparent py-sm px-md font-body-md text-text-primary focus:outline-none focus:border-surface-tint focus:ring-1 focus:ring-surface-tint transition-all"
                        @if(!auth()->user()?->isOwner()) disabled @endif
                    ></textarea>
                    <x-input-error :messages="$errors->get('company_address')" />
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
