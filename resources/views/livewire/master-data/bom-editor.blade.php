<div>
    {{-- ── Finished Good Info Card ────────────────────────────────────── --}}
    <div class="mb-lg bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-md select-none">
            <div>
                <span class="text-xs text-text-secondary uppercase font-semibold">Produk Barang Jadi</span>
                <h2 class="font-headline-md text-headline-md text-text-primary mt-xs">{{ $finishedGood->nama }}</h2>
                <p class="text-body-md text-text-secondary mt-1">Kode: <span class="font-semibold text-text-primary">{{ $finishedGood->kode }}</span> · Satuan: <span class="font-semibold text-text-primary">{{ $finishedGood->satuan }}</span></p>
            </div>
            
            <div class="flex items-center gap-sm">
                <a href="{{ route('barang_jadi.index') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-6 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    {{-- ── BOM Composition Editor Card ───────────────────────────────── --}}
    <div class="bg-card-surface rounded-DEFAULT border border-border-divider p-lg shadow-sm">
        <div class="flex items-center justify-between mb-lg select-none border-b border-border-divider pb-md">
            <div>
                <h3 class="font-headline-md text-headline-md text-text-primary">Komposisi Bahan Baku (Resep)</h3>
                <p class="text-xs text-text-secondary mt-0.5">Tentukan daftar bahan baku dan jumlah pemakaian untuk memproduksi 1 unit {{ $finishedGood->nama }}</p>
            </div>

            @can('create', App\Models\Bom::class)
                <x-ui.secondary-button wire:click="addLine" class="cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tambah Baris
                </x-ui.secondary-button>
            @endcan
        </div>

        {{-- General Error Banner for duplicate check or save exceptions --}}
        @error('lines')
            <div class="mb-lg p-md rounded bg-negative-bg border-l-4 border-l-danger-red text-negative-rose text-body-md">
                {{ $message }}
            </div>
        @enderror

        <div class="flex flex-col gap-md">
            {{-- Table header simulation --}}
            <div class="hidden md:flex items-center gap-md px-md py-sm select-none bg-surface-container-low border border-border-divider rounded-DEFAULT font-label-sm text-label-sm text-text-secondary uppercase">
                <div class="flex-1">Bahan Baku</div>
                <div class="w-48">Jumlah Pemakaian</div>
                <div class="w-32">Satuan</div>
                @can('delete', App\Models\Bom::class)
                    <div class="w-16 text-center">Hapus</div>
                @endcan
            </div>

            {{-- Recipe lines loop --}}
            @forelse($lines as $index => $line)
                <div class="flex flex-col md:flex-row md:items-center gap-md border border-border-divider rounded-DEFAULT p-md hover:bg-surface-container-lowest transition-colors" wire:key="line-{{ $index }}">
                    <!-- Material Picker -->
                    <div class="flex-1 flex flex-col gap-xs">
                        <span class="md:hidden text-xs text-text-secondary uppercase font-semibold">Bahan Baku</span>
                        @can('update', App\Models\Bom::class)
                            <select 
                                wire:model.live="lines.{{ $index }}.bahan_baku_id"
                                class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full h-12 cursor-pointer"
                            >
                                <option value="">Pilih bahan baku...</option>
                                @foreach($materials as $bb)
                                    <option value="{{ $bb->id }}">{{ $bb->nama }} ({{ $bb->kode }})</option>
                                @endforeach
                            </select>
                        @else
                            {{-- Read-only display --}}
                            @php
                                $selectedBb = $materials->firstWhere('id', $line['bahan_baku_id']);
                            @endphp
                            <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-3 text-body-md text-text-secondary h-12 flex items-center select-none">
                                {{ $selectedBb ? $selectedBb->nama . ' (' . $selectedBb->kode . ')' : '—' }}
                            </div>
                        @endcan
                        @error("lines.{$index}.bahan_baku_id")
                            <span class="text-xs text-negative-rose">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Quantity Input -->
                    <div class="w-full md:w-48 flex flex-col gap-xs">
                        <span class="md:hidden text-xs text-text-secondary uppercase font-semibold">Jumlah</span>
                        @can('update', App\Models\Bom::class)
                            <x-text-input 
                                type="number" 
                                step="0.0001"
                                wire:model="lines.{{ $index }}.qty_per_unit"
                                class="w-full"
                                placeholder="0.00"
                            />
                        @else
                            <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-3 text-body-md text-text-secondary h-12 flex items-center select-none">
                                {{ number_format($line['qty_per_unit'], 4) }}
                            </div>
                        @endcan
                        @error("lines.{$index}.qty_per_unit")
                            <span class="text-xs text-negative-rose">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Unit Text (read-only) -->
                    <div class="w-full md:w-32 flex flex-col gap-xs">
                        <span class="md:hidden text-xs text-text-secondary uppercase font-semibold">Satuan</span>
                        <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-3 text-body-md text-text-secondary h-12 flex items-center select-none font-semibold">
                            {{ $line['satuan'] }}
                        </div>
                    </div>

                    <!-- Delete Row Button -->
                    @can('delete', App\Models\Bom::class)
                        <div class="w-full md:w-16 flex items-center justify-center pt-md md:pt-0">
                            <x-ui.icon-button 
                                icon="delete" 
                                wire:click="removeLine({{ $index }})" 
                                title="Hapus Baris"
                                class="text-text-secondary hover:text-negative-rose cursor-pointer border border-border-divider rounded-full hover:bg-negative-bg"
                            />
                        </div>
                    @endcan
                </div>
            @empty
                <div class="text-center py-xl select-none">
                    <span class="material-symbols-outlined text-[48px] text-text-secondary mb-md">account_tree</span>
                    <h4 class="font-headline-md text-text-primary">BOM belum didefinisikan</h4>
                    <p class="text-body-md text-text-secondary mt-xs">Resep komposisi bahan baku untuk produk ini masih kosong.</p>
                </div>
            @endforelse
        </div>

        {{-- Footer Action Buttons --}}
        @can('update', App\Models\Bom::class)
            <div class="flex justify-end gap-md mt-xl border-t border-border-divider pt-lg select-none">
                <a href="{{ route('barang_jadi.index') }}" class="inline-flex items-center gap-2 border border-border-divider text-text-secondary bg-transparent font-label-sm text-label-sm px-8 py-2.5 rounded-full hover:bg-surface-container-low transition-all active:scale-[0.98] cursor-pointer">
                    Batal
                </a>
                
                <x-ui.primary-button wire:click="save" class="cursor-pointer px-8">
                    Simpan Resep
                </x-ui.primary-button>
            </div>
        @else
            <div class="flex justify-end gap-md mt-xl border-t border-border-divider pt-lg select-none">
                <span class="text-body-md text-text-secondary italic">Akses Read-Only: Hanya Employee yang dapat mengedit resep BOM.</span>
            </div>
        @endcan
    </div>
</div>
