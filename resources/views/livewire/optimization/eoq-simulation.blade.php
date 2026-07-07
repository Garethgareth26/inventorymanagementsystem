<div>
    <x-feedback.toast />

    {{-- ── Material info header ────────────────────────────────────────────────── --}}
    <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg mb-lg flex flex-wrap gap-x-8 gap-y-3">
        <div>
            <p class="text-xs text-text-secondary uppercase tracking-wider">Kode</p>
            <p class="font-semibold text-text-primary">{{ $bahanBaku->kode }}</p>
        </div>
        <div>
            <p class="text-xs text-text-secondary uppercase tracking-wider">Nama</p>
            <p class="font-semibold text-text-primary">{{ $bahanBaku->nama }}</p>
        </div>
        <div>
            <p class="text-xs text-text-secondary uppercase tracking-wider">Satuan</p>
            <p class="font-semibold text-text-primary">{{ $bahanBaku->satuan }}</p>
        </div>
        <div>
            <p class="text-xs text-text-secondary uppercase tracking-wider">Harga Satuan</p>
            <p class="font-semibold text-text-primary numeric">Rp {{ number_format((float)$bahanBaku->harga_satuan, 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-secondary uppercase tracking-wider">Lead Time</p>
            <p class="font-semibold text-text-primary numeric">{{ $bahanBaku->lead_time_hari }} hari</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-lg">

        {{-- ── Left: Inputs ────────────────────────────────────────────────────── --}}
        <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                Parameter Simulasi
            </h3>

            {{-- Annual Demand --}}
            <div class="mb-md">
                <x-input-label for="annualDemand" value="Kebutuhan Tahunan (D) — auto dari histori" />
                <input id="annualDemand" wire:model="annualDemand" type="number" step="0.01" min="0"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <x-input-error :messages="$errors->get('annualDemand')" class="mt-1" />
            </div>

            {{-- Biaya Pesan --}}
            <div class="mb-md">
                <x-input-label for="biayaPesan" value="Biaya Pesan per Order (S) — Rp" />
                <input id="biayaPesan" wire:model="biayaPesan" type="number" step="0.01" min="0"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <x-input-error :messages="$errors->get('biayaPesan')" class="mt-1" />
            </div>

            {{-- Holding % --}}
            <div class="mb-lg">
                <x-input-label for="biayaSimpanPct" value="Biaya Simpan % per Tahun (H)" />
                <div class="relative mt-1">
                    <input id="biayaSimpanPct" wire:model="biayaSimpanPct" type="number" step="0.01" min="0.01" max="100"
                           class="block w-full rounded border border-border-divider bg-surface-container px-3 py-2 pr-8 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary text-sm">%</span>
                </div>
                <x-input-error :messages="$errors->get('biayaSimpanPct')" class="mt-1" />
            </div>

            <div class="flex flex-wrap gap-sm">
                <x-ui.primary-button wire:click="simulate" wire:loading.attr="disabled">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Hitung EOQ
                </x-ui.primary-button>

                <x-ui.secondary-button wire:click="resetToDefaults">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                    Reset ke Default
                </x-ui.secondary-button>
            </div>
        </div>

        {{-- ── Right: Results ──────────────────────────────────────────────────── --}}
        <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">analytics</span>
                Hasil Simulasi
            </h3>

            @if($simulated)
                <div class="grid grid-cols-2 gap-md mb-lg">
                    <x-ui.kpi-card title="EOQ Baru" icon="shopping_cart" :hero="true">
                        <x-slot:value>{{ number_format($simEoq ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs opacity-70">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="EOQ Saat Ini" icon="inventory">
                        <x-slot:value>{{ $currentParam ? number_format((float)$currentParam->eoq, 2) : '—' }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="Safety Stock" icon="shield">
                        <x-slot:value>{{ number_format($simSafetyStock ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="Reorder Point" icon="warning">
                        <x-slot:value>{{ number_format($simRop ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                </div>

                @can('apply', \App\Models\InventoryParameter::class)
                    <x-ui.primary-button wire:click="$dispatch('toggle-modal', {name: 'eoq-apply', show: true})">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        Terapkan Parameter
                    </x-ui.primary-button>

                    <x-feedback.confirmation-modal name="eoq-apply" title="Terapkan Parameter EOQ?" type="warning">
                        Parameter EOQ, Safety Stock, dan Reorder Point baru akan disimpan ke sistem dan dashboard akan diperbarui.
                        <x-slot:confirm>
                            <x-ui.primary-button wire:click="apply" @click="$dispatch('toggle-modal', {name: 'eoq-apply', show: false})" wire:loading.attr="disabled">
                                Terapkan
                            </x-ui.primary-button>
                        </x-slot:confirm>
                    </x-feedback.confirmation-modal>
                @else
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded bg-surface-container-high text-text-secondary text-sm cursor-not-allowed"
                         title="Hanya Karyawan yang dapat menerapkan parameter.">
                        <span class="material-symbols-outlined text-[16px]">lock</span>
                        Terapkan (Owner: Hanya Simulasi)
                    </div>
                @endcan
            @else
                <div class="flex flex-col items-center justify-center h-48 text-text-secondary gap-3">
                    <span class="material-symbols-outlined text-[48px] opacity-30">functions</span>
                    <p class="text-body-md">Klik <strong>Hitung EOQ</strong> untuk melihat hasil simulasi.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-md">
        <a href="{{ route('eoq.index') }}" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke Daftar EOQ
        </a>
    </div>
</div>
