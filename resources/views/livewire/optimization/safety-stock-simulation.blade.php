<div>
    <x-feedback.toast />

    {{-- Material header --}}
    <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg mb-lg flex flex-wrap gap-x-8 gap-y-3">
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Kode</p><p class="font-semibold text-text-primary">{{ $bahanBaku->kode }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Nama</p><p class="font-semibold text-text-primary">{{ $bahanBaku->nama }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">SD Harian (terhitung)</p><p class="font-semibold text-text-primary numeric">{{ number_format($sdHarian, 4) }}</p></div>
        <div><p class="text-xs text-text-secondary uppercase tracking-wider">Window Histori</p><p class="font-semibold text-text-primary numeric">{{ $windowMonths }} bulan</p></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-lg">
        {{-- Inputs --}}
        <div class="bg-card-surface border border-border-divider rounded-DEFAULT p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                Parameter Simulasi
            </h3>

            <div class="mb-md">
                <x-input-label for="zFactor" value="Z-Factor (Tingkat Layanan)" />
                <select id="zFactor" wire:model="zFactor"
                        class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="1.28">1.28 — 90% service level</option>
                    <option value="1.645">1.645 — 95% service level</option>
                    <option value="1.65">1.65 — default sistem</option>
                    <option value="2.05">2.05 — 98% service level</option>
                    <option value="2.33">2.33 — 99% service level</option>
                </select>
                <x-input-error :messages="$errors->get('zFactor')" class="mt-1" />
            </div>

            <div class="mb-md">
                <x-input-label for="leadTimeHari" value="Lead Time (hari)" />
                <input id="leadTimeHari" wire:model="leadTimeHari" type="number" min="1"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <x-input-error :messages="$errors->get('leadTimeHari')" class="mt-1" />
            </div>

            <div class="mb-lg">
                <x-input-label for="windowMonths" value="Window Histori (bulan, 1–24)" />
                <input id="windowMonths" wire:model.lazy="windowMonths" type="number" min="1" max="24"
                       class="mt-1 block w-full rounded border border-border-divider bg-surface-container px-3 py-2 text-body-md text-text-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <p class="mt-1 text-xs text-text-secondary">Mengubah window akan menghitung ulang SD Harian dari histori mutasi.</p>
                <x-input-error :messages="$errors->get('windowMonths')" class="mt-1" />
            </div>

            <div class="flex flex-wrap gap-sm">
                <x-ui.primary-button wire:click="simulate" wire:loading.attr="disabled">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Hitung Safety Stock
                </x-ui.primary-button>
                <x-ui.secondary-button wire:click="resetToDefaults">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                    Reset ke Default
                </x-ui.secondary-button>
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
                    <x-ui.kpi-card title="Safety Stock Baru" icon="shield" :hero="true">
                        <x-slot:value>{{ number_format($simSafetyStock ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs opacity-70">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="Safety Stock Saat Ini" icon="inventory">
                        <x-slot:value>{{ $currentParam ? number_format((float)$currentParam->safety_stock, 2) : '—' }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="ROP Baru" icon="warning">
                        <x-slot:value>{{ number_format($simRop ?? 0, 2) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">{{ $bahanBaku->satuan }}</span></x-slot:footer>
                    </x-ui.kpi-card>
                    <x-ui.kpi-card title="SD Harian" icon="show_chart">
                        <x-slot:value>{{ number_format($sdHarian, 4) }}</x-slot:value>
                        <x-slot:footer><span class="text-xs text-text-secondary">Window: {{ $windowMonths }} bln</span></x-slot:footer>
                    </x-ui.kpi-card>
                </div>

                @can('apply', \App\Models\InventoryParameter::class)
                    <x-ui.primary-button wire:click="$dispatch('toggle-modal', {name: 'ss-apply', show: true})" wire:loading.attr="disabled">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        Terapkan Parameter
                    </x-ui.primary-button>

                    <x-feedback.confirmation-modal name="ss-apply" title="Terapkan Parameter Safety Stock?" type="warning">
                        Parameter Safety Stock dan Reorder Point baru akan disimpan dan dashboard akan diperbarui.
                        <x-slot:confirm>
                            <x-ui.primary-button wire:click="apply" @click="$dispatch('toggle-modal', {name: 'ss-apply', show: false})" wire:loading.attr="disabled">
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
                    <span class="material-symbols-outlined text-[48px] opacity-30">shield</span>
                    <p class="text-body-md">Klik <strong>Hitung Safety Stock</strong> untuk melihat hasil simulasi.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-md">
        <a href="{{ route('safety_stock.index') }}" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Kembali ke Daftar Safety Stock
        </a>
    </div>
</div>
