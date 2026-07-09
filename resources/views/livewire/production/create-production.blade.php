<div class="max-w-3xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-lg">
    {{-- ── Left: Form Inputs ────────────────────────────────────────── --}}
    <div class="lg:col-span-2 bg-card-surface border border-border-divider rounded-DEFAULT p-lg shadow-sm h-fit">
        <form wire:submit.prevent="save" class="flex flex-col gap-md">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-md select-none">
                Laporan Produksi
            </h3>

            <!-- Finished Good -->
            <div class="flex flex-col gap-xs">
                <x-input-label for="finished_goods_id" value="Barang Jadi" />
                <select 
                    id="finished_goods_id" 
                    wire:model.live="finished_goods_id" 
                    class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
                >
                    <option value="">Pilih barang jadi...</option>
                    @foreach($goods as $fg)
                        <option value="{{ $fg->id }}">{{ $fg->nama }} ({{ $fg->kode }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('finished_goods_id')" />
            </div>

            <!-- Jumlah Diproduksi -->
            <div class="flex flex-col gap-xs">
                <x-input-label for="jumlah_diproduksi" value="Jumlah Diproduksi" />
                <x-text-input 
                    id="jumlah_diproduksi" 
                    type="number" 
                    step="0.01"
                    wire:model.live.debounce.200ms="jumlah_diproduksi" 
                    class="w-full" 
                    placeholder="Contoh: 100" 
                />
                <x-input-error :messages="$errors->get('jumlah_diproduksi')" />
            </div>

            <!-- Tanggal Produksi -->
            <div class="flex flex-col gap-xs">
                <x-input-label for="tanggal_produksi" value="Tanggal Produksi" />
                <x-text-input 
                    id="tanggal_produksi" 
                    type="date" 
                    wire:model="tanggal_produksi" 
                    class="w-full" 
                />
                <x-input-error :messages="$errors->get('tanggal_produksi')" />
            </div>

            <!-- Keterangan -->
            <div class="flex flex-col gap-xs">
                <x-input-label for="keterangan" value="Keterangan / Notes" />
                <textarea 
                    id="keterangan" 
                    wire:model="keterangan" 
                    class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent min-h-[80px] w-full"
                    placeholder="Catatan tambahan hasil produksi"
                ></textarea>
                <x-input-error :messages="$errors->get('keterangan')" />
            </div>

            <!-- Submit Actions -->
            <div class="flex justify-end gap-md mt-md pt-md border-t border-border-divider select-none">
                <a href="{{ route('production.index') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-6 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                    Batal
                </a>

                <button 
                    type="submit" 
                    @if($hasStockShortfall) disabled @endif
                    class="inline-flex items-center gap-2 bg-primary text-text-on-primary hover:bg-primary-hover font-label-sm text-label-sm px-6 py-2.5 rounded-full shadow-sm transition-all active:scale-[0.98] @if($hasStockShortfall) opacity-55 cursor-not-allowed @else cursor-pointer @endif"
                >
                    Simpan
                </button>
            </div>
        </form>
    </div>

    {{-- ── Right: BOM Explosion Preview ──────────────────────────────── --}}
    <div class="lg:col-span-3 bg-card-surface border border-border-divider rounded-DEFAULT p-lg shadow-sm h-fit">
        <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none pb-sm border-b border-border-divider">
            Analisis Komposisi Resep (BOM)
        </h3>

        @if($finished_goods_id)
            <div class="flex flex-col gap-md">
                @forelse($this->ingredientsPreview as $ingredient)
                    <div class="border rounded-DEFAULT p-md flex flex-col sm:flex-row sm:items-center justify-between gap-md transition-colors {{ $ingredient['is_insufficient'] ? 'bg-negative-bg border-danger-red/30' : ($ingredient['is_near_rop'] ? 'bg-warning-bg border-warning-orange/30' : 'bg-surface-container-low border-border-divider') }}" wire:key="ing-{{ $ingredient['id'] }}">
                        <div class="select-none">
                            <span class="text-xs text-text-secondary font-semibold uppercase">{{ $ingredient['kode'] }}</span>
                            <h4 class="font-label-md font-semibold text-text-primary mt-xs">{{ $ingredient['nama'] }}</h4>
                            <div class="flex flex-wrap gap-sm items-center mt-sm text-body-sm text-text-secondary">
                                <span>Stok Gudang: <strong class="text-text-primary">{{ number_format($ingredient['available'], 2) }} {{ $ingredient['satuan'] }}</strong></span>
                                <span>·</span>
                                <span>ROP: <strong class="text-text-primary">{{ number_format($ingredient['rop'], 2) }} {{ $ingredient['satuan'] }}</strong></span>
                            </div>
                        </div>

                        <div class="flex flex-col items-start sm:items-end justify-between select-none">
                            <span class="text-xs text-text-secondary font-semibold uppercase">Kebutuhan</span>
                            <span class="text-body-lg font-bold text-text-primary mt-xs">{{ number_format($ingredient['required'], 2) }} {{ $ingredient['satuan'] }}</span>
                            
                            {{-- Alerts / Status Pill --}}
                            @if($ingredient['is_insufficient'])
                                <span class="inline-flex mt-xs bg-negative-bg text-negative-rose text-[10px] font-bold px-2 py-0.5 rounded border border-danger-red/20 uppercase">Stok Kurang!</span>
                            @elseif($ingredient['is_near_rop'])
                                <span class="inline-flex mt-xs bg-warning-bg text-warning-orange text-[10px] font-bold px-2 py-0.5 rounded border border-warning-orange/20 uppercase" title="Sisa stok setelah produksi akan di bawah ROP">Dekat ROP</span>
                            @else
                                <span class="inline-flex mt-xs bg-success-bg text-success-green text-[10px] font-bold px-2 py-0.5 rounded border border-success-green/20 uppercase">Aman</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-xl select-none">
                        <span class="material-symbols-outlined text-[48px] text-text-secondary mb-md">account_tree</span>
                        <h4 class="font-headline-md text-text-primary">BOM belum dikonfigurasi</h4>
                        <p class="text-body-md text-text-secondary mt-xs">Produk barang jadi ini tidak memiliki resep aktif.</p>
                    </div>
                @endforelse
            </div>

            <!-- General warning banner if stock insufficient -->
            @if($hasStockShortfall)
                <div class="mt-lg p-md rounded bg-negative-bg border-l-4 border-l-danger-red text-negative-rose text-body-md flex items-center gap-md select-none animate-pulse">
                    <span class="material-symbols-outlined text-[24px]">error</span>
                    <div>
                        <strong>Peringatan Stok Kurang:</strong> Beberapa bahan baku memiliki stok yang tidak mencukupi untuk jumlah produksi yang dimasukkan. Tombol simpan dinonaktifkan.
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-xl select-none">
                <span class="material-symbols-outlined text-[48px] text-text-secondary mb-md">precision_manufacturing</span>
                <h4 class="font-headline-md text-text-primary">Pilih Barang Jadi</h4>
                <p class="text-body-md text-text-secondary mt-xs">Pilih salah satu produk barang jadi di kolom kiri untuk me-load visualisasi komposisi resep.</p>
            </div>
        @endif
    </div>
</div>
