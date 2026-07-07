<div class="max-w-2xl mx-auto bg-card-surface border border-border-divider rounded-DEFAULT p-lg shadow-sm">
    <form wire:submit.prevent="save" class="flex flex-col gap-md">
        <h3 class="font-headline-md text-headline-md text-text-primary mb-md select-none">
            Form Pembuatan Purchase Order
        </h3>

        <!-- Form Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
            <!-- Kode PO -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label for="kode_po" value="Kode PO" />
                <x-text-input 
                    id="kode_po" 
                    type="text" 
                    wire:model="kode_po" 
                    class="w-full bg-surface-container-low text-text-secondary cursor-not-allowed" 
                    readonly
                />
                <x-input-error :messages="$errors->get('kode_po')" />
            </div>

            <!-- Jenis PO -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label for="jenis" value="Jenis Pesanan" />
                <select 
                    id="jenis" 
                    wire:model.live="jenis" 
                    class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
                >
                    <option value="Rutin">Rutin (EOQ Pre-fill)</option>
                    <option value="Darurat">Darurat (+20% Surcharge)</option>
                </select>
                <x-input-error :messages="$errors->get('jenis')" />
            </div>

            <!-- Bahan Baku -->
            <div class="flex flex-col gap-xs col-span-2">
                <x-input-label for="bahan_baku_id" value="Bahan Baku" />
                <select 
                    id="bahan_baku_id" 
                    wire:model.live="bahan_baku_id" 
                    class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
                >
                    <option value="">Pilih bahan baku...</option>
                    @foreach($materials as $bb)
                        <option value="{{ $bb->id }}">{{ $bb->nama }} ({{ $bb->kode }}) - Stok: {{ number_format($bb->stok_saat_ini, 2) }} {{ $bb->satuan }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('bahan_baku_id')" />
            </div>

            <!-- Supplier (linked to Bahan Baku) -->
            <div class="flex flex-col gap-xs col-span-2">
                <x-input-label for="supplier_id" value="Supplier" />
                <select 
                    id="supplier_id" 
                    wire:model="supplier_id" 
                    class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
                >
                    <option value="">Pilih Supplier...</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->nama }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('supplier_id')" />
            </div>

            <!-- Jumlah -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label for="jumlah" value="Jumlah" />
                <x-text-input 
                    id="jumlah" 
                    type="number" 
                    step="0.01"
                    wire:model.live="jumlah" 
                    class="w-full" 
                    placeholder="Masukkan jumlah" 
                />
                <x-input-error :messages="$errors->get('jumlah')" />
            </div>

            <!-- Harga Satuan -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label for="harga_satuan" value="Harga Satuan (Rp)" />
                <x-text-input 
                    id="harga_satuan" 
                    type="number" 
                    step="0.01"
                    wire:model.live="harga_satuan" 
                    class="w-full" 
                    placeholder="Harga beli per unit" 
                />
                <x-input-error :messages="$errors->get('harga_satuan')" />
            </div>

            <!-- Tanggal Pesan -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label for="tanggal_pesan" value="Tanggal Pesan" />
                <x-text-input 
                    id="tanggal_pesan" 
                    type="date" 
                    wire:model.live="tanggal_pesan" 
                    class="w-full" 
                />
                <x-input-error :messages="$errors->get('tanggal_pesan')" />
            </div>

            <!-- Estimasi Tiba (computed preview) -->
            <div class="flex flex-col gap-xs col-span-1">
                <x-input-label value="Estimasi Tiba" />
                <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-3 text-body-md font-body-md text-text-secondary h-12 flex items-center select-none font-semibold">
                    {{ !empty($estimasi_tiba) ? \Carbon\Carbon::parse($estimasi_tiba)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>

        <!-- Surcharge Alert for Emergency Order -->
        @if($jenis === 'Darurat' && $bahan_baku_id)
            <div class="p-md rounded bg-negative-bg border-l-4 border-l-danger-red text-negative-rose text-body-md flex items-center gap-md select-none mt-sm">
                <span class="material-symbols-outlined text-[24px]">warning</span>
                <div>
                    <strong>Pesanan Darurat:</strong> Biaya tambahan 20% diterapkan secara otomatis dari harga dasar (Harga Dasar: Rp {{ number_format($harga_dasar, 0, ',', '.') }} menjadi Rp {{ number_format($harga_satuan, 0, ',', '.') }}).
                </div>
            </div>
        @endif

        <!-- Order Summary Preview Card -->
        @if($bahan_baku_id && $jumlah > 0 && $harga_satuan > 0)
            <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-lg mt-sm select-none">
                <h4 class="font-label-md text-label-md text-text-secondary uppercase mb-sm">Ringkasan Pesanan</h4>
                <div class="flex items-center justify-between border-b border-border-divider pb-xs mb-xs text-body-md text-text-primary">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format(($jumlah * $harga_satuan), 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-headline-sm font-semibold text-text-primary">
                    <span>Total Estimasi Harga</span>
                    <span>Rp {{ number_format(($jumlah * $harga_satuan), 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-end gap-md mt-lg pt-md border-t border-border-divider select-none">
            <a href="{{ route('pesanan_pembelian.index') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-8 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                Batal
            </a>

            <button type="submit" class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-sm text-label-sm px-8 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] cursor-pointer">
                Simpan PO
            </button>
        </div>
    </form>
</div>
