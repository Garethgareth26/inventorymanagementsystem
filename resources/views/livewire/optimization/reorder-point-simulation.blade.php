<div>
    <x-feedback.toast />

    {{-- Material header --}}
    <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg mb-lg flex flex-wrap gap-x-8 gap-y-3">
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Kode</p><p class="font-semibold text-text-primary">{{ $bahanBaku->kode }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Nama</p><p class="font-semibold text-text-primary">{{ $bahanBaku->nama }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Stok Saat Ini</p><p class="font-semibold text-text-primary numeric">{{ number_format((float)$bahanBaku->stok_saat_ini, 2) }} {{ $bahanBaku->satuan }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">ROP Saat Ini</p><p class="font-semibold text-text-primary numeric">{{ $currentParam ? number_format((float)$currentParam->reorder_point, 2) : '—' }}</p></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-lg">
        {{-- Inputs --}}
        <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                Parameter Simulasi
            </h3>

            <div class="mb-md">
                <x-input-label for="dailyDemand" value="Permintaan Harian (unit/hari)" />
                <input id="dailyDemand" wire:model="dailyDemand" type="number" step="0.0001" min="0"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <p class="mt-1 text-xs text-text-secondary">Auto-computed dari histori: D/365 = {{ number_format($annualDemand / 365, 4) }}</p>
                <x-input-error :messages="$errors->get('dailyDemand')" class="mt-1" />
            </div>

            <div class="mb-md">
                <x-input-label for="leadTimeHari" value="Lead Time (hari)" />
                <input id="leadTimeHari" wire:model="leadTimeHari" type="number" min="1"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <x-input-error :messages="$errors->get('leadTimeHari')" class="mt-1" />
            </div>

            <div class="mb-lg">
                <x-input-label for="safetyStock" value="Safety Stock (unit)" />
                <input id="safetyStock" wire:model="safetyStock" type="number" step="0.01" min="0"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <p class="mt-1 text-xs text-text-secondary">ROP = (D_harian × LT) + SS</p>
                <x-input-error :messages="$errors->get('safetyStock')" class="mt-1" />
            </div>

            <div class="flex flex-wrap gap-sm">
                <x-ui.primary-button wire:click="simulate" wire:loading.attr="disabled">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Hitung ROP
                </x-ui.primary-button>
            </div>
        </div>

        {{-- Results --}}
        <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">analytics</span>
                Hasil Simulasi
            </h3>

            @if($simulated)
                <div class="grid grid-cols-2 gap-md mb-lg">
                    <x-ui.kpi-card title="ROP Baru" icon="flag" :hero="true">
                        <x-slot:value>{{ number_format($simRop ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs opacity-70">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="ROP Saat Ini" icon="inventory">
                        <x-slot:value>{{ $currentParam ? number_format((float)$currentParam->reorder_point, 2) : '—' }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                </div>

                <div class="text-sm text-text-secondary mb-lg bg-surface-container-low rounded p-md">
                    <strong>Formula:</strong> ROP = ({{ number_format($dailyDemand, 4) }} × {{ $leadTimeHari }}) + {{ number_format($safetyStock, 2) }} = {{ number_format($simRop ?? 0, 2) }}
                </div>

                @can('apply', \App\Models\InventoryParameter::class)
                    <x-ui.primary-button wire:click="$dispatch('toggle-modal', {name: 'rop-apply', show: true})" wire:loading.attr="disabled">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        Terapkan Parameter
                    </x-ui.primary-button>

                    <x-feedback.confirmation-modal name="rop-apply" title="Terapkan Parameter Reorder Point?" type="warning">
                        Parameter Reorder Point baru akan disimpan ke sistem dan dashboard akan diperbarui.
                        <x-slot:confirm>
                            <x-ui.primary-button wire:click="apply" @click="$dispatch('toggle-modal', {name: 'rop-apply', show: false})" wire:loading.attr="disabled">
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
                    <span class="material-symbols-outlined text-[48px] opacity-30">flag</span>
                    <p class="text-body-md">Klik <strong>Hitung ROP</strong> untuk melihat hasil simulasi.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-md">
        <a href="{{ route('reorder_point.index') }}" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke Reorder Point Overview
        </a>
    </div>
</div>
