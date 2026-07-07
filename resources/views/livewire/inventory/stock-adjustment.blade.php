<div class="max-w-xl mx-auto bg-card-surface border border-border-divider rounded-DEFAULT p-lg shadow-sm">
    <form wire:submit.prevent="save" class="flex flex-col gap-md">
        <h3 class="font-headline-md text-headline-md text-text-primary mb-md select-none">
            Form Penyesuaian Stok Manual
        </h3>

        <!-- Jenis Item -->
        <div class="flex flex-col gap-xs select-none">
            <x-input-label value="Kategori Item" />
            <div class="flex gap-md mt-xs">
                <label class="flex-1 border border-border-divider rounded-DEFAULT p-md flex items-center justify-center gap-sm cursor-pointer hover:bg-surface-container-low transition-colors {{ $item_type === 'bahan_baku' ? 'bg-surface-container-low border-primary ring-2 ring-surface-tint' : '' }}">
                    <input 
                        type="radio" 
                        name="item_type" 
                        value="bahan_baku" 
                        wire:model.live="item_type" 
                        class="sr-only"
                    />
                    <span class="material-symbols-outlined text-[20px] {{ $item_type === 'bahan_baku' ? 'text-primary' : 'text-text-secondary' }}">inventory</span>
                    <span class="text-body-md font-semibold {{ $item_type === 'bahan_baku' ? 'text-text-primary' : 'text-text-secondary' }}">Bahan Baku</span>
                </label>

                <label class="flex-1 border border-border-divider rounded-DEFAULT p-md flex items-center justify-center gap-sm cursor-pointer hover:bg-surface-container-low transition-colors {{ $item_type === 'finished_good' ? 'bg-surface-container-low border-primary ring-2 ring-surface-tint' : '' }}">
                    <input 
                        type="radio" 
                        name="item_type" 
                        value="finished_good" 
                        wire:model.live="item_type" 
                        class="sr-only"
                    />
                    <span class="material-symbols-outlined text-[20px] {{ $item_type === 'finished_good' ? 'text-primary' : 'text-text-secondary' }}">deployed_code</span>
                    <span class="text-body-md font-semibold {{ $item_type === 'finished_good' ? 'text-text-primary' : 'text-text-secondary' }}">Barang Jadi</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('item_type')" />
        </div>

        <!-- Item Picker -->
        <div class="flex flex-col gap-xs">
            <x-input-label for="item_id" value="Pilih Item" />
            <select 
                id="item_id" 
                wire:model.live="item_id" 
                class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
            >
                <option value="">Pilih item yang disesuaikan...</option>
                @foreach($itemsList as $item)
                    <option value="{{ $item->id }}">{{ $item->nama }} ({{ $item->kode }}) - Stok: {{ number_format($item->stok_saat_ini, 2) }} {{ $item->satuan }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('item_id')" />
        </div>

        <!-- Jenis Mutasi (Masuk / Keluar) -->
        <div class="flex flex-col gap-xs select-none">
            <x-input-label value="Jenis Penyesuaian" />
            <div class="flex gap-md mt-xs">
                <label class="flex-1 border border-border-divider rounded-DEFAULT p-md flex items-center justify-center gap-sm cursor-pointer hover:bg-surface-container-low transition-colors {{ $jenis_mutasi === 'masuk' ? 'bg-surface-container-low border-success-green ring-2 ring-success-green/20' : '' }}">
                    <input 
                        type="radio" 
                        name="jenis_mutasi" 
                        value="masuk" 
                        wire:model.live="jenis_mutasi" 
                        class="sr-only"
                    />
                    <span class="material-symbols-outlined text-[20px] {{ $jenis_mutasi === 'masuk' ? 'text-success-green' : 'text-text-secondary' }}">add_circle</span>
                    <span class="text-body-md font-semibold {{ $jenis_mutasi === 'masuk' ? 'text-text-primary' : 'text-text-secondary' }}">Stok Masuk (+)</span>
                </label>

                <label class="flex-1 border border-border-divider rounded-DEFAULT p-md flex items-center justify-center gap-sm cursor-pointer hover:bg-surface-container-low transition-colors {{ $jenis_mutasi === 'keluar' ? 'bg-surface-container-low border-danger-red ring-2 ring-danger-red/20' : '' }}">
                    <input 
                        type="radio" 
                        name="jenis_mutasi" 
                        value="keluar" 
                        wire:model.live="jenis_mutasi" 
                        class="sr-only"
                    />
                    <span class="material-symbols-outlined text-[20px] {{ $jenis_mutasi === 'keluar' ? 'text-danger-red' : 'text-text-secondary' }}">remove_circle</span>
                    <span class="text-body-md font-semibold {{ $jenis_mutasi === 'keluar' ? 'text-text-primary' : 'text-text-secondary' }}">Stok Keluar (-)</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('jenis_mutasi')" />
        </div>

        <!-- Jumlah -->
        <div class="flex flex-col gap-xs">
            <x-input-label for="jumlah" value="Jumlah Unit" />
            <x-text-input 
                id="jumlah" 
                type="number" 
                step="0.01"
                wire:model.live="jumlah" 
                class="w-full" 
                placeholder="Jumlah unit penyesuaian" 
            />
            <x-input-error :messages="$errors->get('jumlah')" />
        </div>

        <!-- Alasan / Keterangan -->
        <div class="flex flex-col gap-xs">
            <x-input-label for="keterangan" value="Alasan Penyesuaian" />
            <textarea 
                id="keterangan" 
                wire:model="keterangan" 
                class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent min-h-[80px] w-full"
                placeholder="Tuliskan alasan penyesuaian (misal: Selisih stock opname, Barang rusak)"
            ></textarea>
            <x-input-error :messages="$errors->get('keterangan')" />
        </div>

        <!-- Advisory warning if quantity > 3x average monthly movement -->
        @if($showAdvisoryWarning)
            <div class="p-md rounded bg-warning-bg border-l-4 border-l-warning-orange text-warning-orange text-body-md flex flex-col gap-sm select-none mt-xs">
                <div class="flex items-center gap-md">
                    <span class="material-symbols-outlined text-[24px]">warning</span>
                    <div>
                        <strong>Peringatan Jumlah Besar:</strong> Penyesuaian ini terdeteksi sangat besar (Rata-rata bulanan: {{ number_format($avgMonthly, 2) }}, Penyesuaian: {{ number_format($jumlah, 2) }}).
                    </div>
                </div>

                <div class="flex items-center gap-sm mt-xs border-t border-warning-orange/15 pt-sm">
                    <input 
                        id="confirm_large_adjustment" 
                        type="checkbox" 
                        wire:model="confirm_large_adjustment" 
                        class="rounded border-warning-orange text-warning-orange focus:ring-warning-orange h-4 w-4 cursor-pointer"
                    />
                    <label for="confirm_large_adjustment" class="text-xs text-text-primary font-semibold cursor-pointer">
                        Saya mengonfirmasi penyesuaian jumlah besar ini.
                    </label>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-end gap-md mt-lg pt-md border-t border-border-divider select-none">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-8 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                Batal
            </a>

            <button type="submit" class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-sm text-label-sm px-8 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer">
                Simpan Penyesuaian
            </button>
        </div>
    </form>
</div>
