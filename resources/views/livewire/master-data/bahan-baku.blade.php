<div>
    {{-- ── Filter & Search Bar ────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input 
                    placeholder="Cari kode atau nama..." 
                    wire:model.live.debounce.300ms="search" 
                />
            </x-slot:search>

            <x-slot:filters>
                <select 
                    wire:model.live="filterAbc" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer"
                >
                    <option value="">Semua Kelas ABC</option>
                    <option value="A">Kelas A</option>
                    <option value="B">Kelas B</option>
                    <option value="C">Kelas C</option>
                </select>

                <select 
                    wire:model.live="filterSupplier" 
                    class="bg-card-surface border border-border-divider rounded-full px-4 py-1.5 text-body-md font-body-md text-text-primary focus:ring-2 focus:ring-surface-tint focus:border-transparent outline-none h-12 cursor-pointer max-w-xs"
                >
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->nama }}</option>
                    @endforeach
                </select>
            </x-slot:filters>

            <x-slot:actions>
                @can('create', App\Models\BahanBaku::class)
                    <div class="flex items-center gap-sm">
                        <x-ui.primary-button wire:click="openCreateModal" class="cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Tambah Bahan Baku
                        </x-ui.primary-button>
                    </div>
                @endcan
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Bahan Baku', 'Satuan', 'Stok Saat Ini', 'Kelas ABC', 'Harga Satuan', 'Supplier Rutin', 'Aksi']"
        :items="$materials->items()"
    >
        @foreach($materials as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="mat-{{ $item->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item->kode }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $item->nama }}</td>
                <td class="px-lg py-md text-body-md text-text-secondary">{{ $item->satuan }}</td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">{{ number_format($item->stok_saat_ini, 2) }}</td>
                <td class="px-lg py-md text-body-md">
                    <x-feedback.status-badge status="{{ $item->abc_class === 'A' ? 'danger' : ($item->abc_class === 'B' ? 'warning' : 'success') }}">
                        Kelas {{ $item->abc_class }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                <td class="px-lg py-md text-body-md text-text-secondary">{{ $item->supplier->nama ?? '—' }}</td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        @can('update', $item)
                            <x-ui.icon-button 
                                icon="edit" 
                                wire:click="openEditModal({{ $item->id }})" 
                                title="Edit"
                                class="text-text-secondary hover:text-primary cursor-pointer"
                            />
                        @else
                            <span class="text-xs text-text-secondary italic">Read-only</span>
                        @endcan

                        @can('delete', $item)
                            <x-ui.icon-button 
                                icon="delete" 
                                wire:click="confirmDelete({{ $item->id }})" 
                                title="Hapus"
                                class="text-text-secondary hover:text-negative-rose cursor-pointer"
                            />
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="inventory"
                title="Bahan baku tidak ditemukan"
                description="Coba pilih filter lain atau tambahkan bahan baku baru."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$materials" />
        </x-slot:pagination>
    </x-tables.data-table>

    {{-- ── Create / Edit Modal ───────────────────────────────────────── --}}
    <x-modal name="material-form-modal" :show="$isModalOpen" maxWidth="md">
        <form wire:submit.prevent="save" class="p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none">
                {{ $materialId ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru' }}
            </h3>

            <div class="flex flex-col gap-md mb-lg">
                <!-- Kode -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="kode" value="Kode Bahan Baku" />
                    <x-text-input 
                        id="kode" 
                        type="text" 
                        wire:model="kode" 
                        class="w-full" 
                        placeholder="Contoh: BB-001" 
                    />
                    <x-input-error :messages="$errors->get('kode')" />
                </div>

                <!-- Nama -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="nama" value="Nama Bahan Baku" />
                    <x-text-input 
                        id="nama" 
                        type="text" 
                        wire:model="nama" 
                        class="w-full" 
                        placeholder="Contoh: Tepung Terigu" 
                    />
                    <x-input-error :messages="$errors->get('nama')" />
                </div>

                <!-- Satuan -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="satuan" value="Satuan" />
                    <x-text-input 
                        id="satuan" 
                        type="text" 
                        wire:model="satuan" 
                        class="w-full" 
                        placeholder="Contoh: kg, liter, pcs" 
                    />
                    <x-input-error :messages="$errors->get('satuan')" />
                </div>

                <!-- Supplier -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="supplier_id" value="Supplier Rutin" />
                    <select 
                        id="supplier_id" 
                        wire:model="supplier_id" 
                        class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full cursor-pointer h-12"
                    >
                        <option value="">Pilih Supplier...</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('supplier_id')" />
                </div>

                <!-- Harga Satuan -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="harga_satuan" value="Harga Satuan (Rp)" />
                    <x-text-input 
                        id="harga_satuan" 
                        type="number" 
                        step="0.01"
                        wire:model="harga_satuan" 
                        class="w-full" 
                        placeholder="Contoh: 12000" 
                    />
                    <x-input-error :messages="$errors->get('harga_satuan')" />
                </div>

                <!-- Lead Time -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="lead_time_hari" value="Lead Time (Hari)" />
                    <x-text-input 
                        id="lead_time_hari" 
                        type="number" 
                        wire:model="lead_time_hari" 
                        class="w-full" 
                        placeholder="Waktu kirim dalam hari" 
                    />
                    <x-input-error :messages="$errors->get('lead_time_hari')" />
                </div>

                <!-- Stok Saat Ini (Hanya Create) -->
                @if(! $materialId)
                    <div class="flex flex-col gap-xs">
                        <x-input-label for="stok_saat_ini" value="Stok Awal" />
                        <x-text-input 
                            id="stok_saat_ini" 
                            type="number" 
                            step="0.01"
                            wire:model="stok_saat_ini" 
                            class="w-full" 
                            placeholder="Stok fisik awal" 
                        />
                        <x-input-error :messages="$errors->get('stok_saat_ini')" />
                    </div>
                @else
                    <!-- Read-only stok display with help text during edit -->
                    <div class="flex flex-col gap-xs">
                        <x-input-label value="Stok Saat Ini" />
                        <div class="bg-surface-container-low border border-border-divider rounded-DEFAULT p-3 text-body-md font-body-md text-text-secondary select-none">
                            {{ number_format($stok_saat_ini, 2) }} {{ $satuan }}
                        </div>
                        <p class="text-xs text-text-secondary select-none">Perubahan stok harus dicatat melalui Penyesuaian Stok atau Penerimaan PO.</p>
                    </div>
                @endif
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-md">
                <x-ui.secondary-button type="button" wire:click="closeModal" class="cursor-pointer">
                    Batal
                </x-ui.secondary-button>

                <x-ui.primary-button type="submit" class="cursor-pointer">
                    Simpan
                </x-ui.primary-button>
            </div>
        </form>
    </x-modal>

    {{-- ── Delete Confirmation Modal ──────────────────────────────────── --}}
    <x-feedback.confirmation-modal 
        name="delete-confirm" 
        title="Hapus Bahan Baku" 
        type="danger"
    >
        Apakah Anda yakin ingin menghapus bahan baku ini? Tindakan ini tidak dapat dibatalkan.

        <x-slot:cancel>
            <x-ui.secondary-button type="button" wire:click="$dispatch('toggle-modal', {name: 'delete-confirm', show: false})" class="cursor-pointer">
                Batal
            </x-ui.secondary-button>
        </x-slot:cancel>

        <x-slot:confirm>
            <x-ui.primary-button type="button" wire:click="delete" class="cursor-pointer bg-negative-rose hover:bg-negative-rose/90 border-transparent">
                Hapus
            </x-ui.primary-button>
        </x-slot:confirm>
    </x-feedback.confirmation-modal>
</div>
