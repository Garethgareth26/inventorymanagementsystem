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

            <x-slot:actions>
                @can('create', App\Models\FinishedGood::class)
                    <x-ui.primary-button wire:click="openCreateModal" class="cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Barang Jadi
                    </x-ui.primary-button>
                @endcan
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Barang Jadi', 'Satuan', 'Stok Saat Ini', 'BOM Resep', 'Aksi']"
        :items="$goods->items()"
    >
        @foreach($goods as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="fg-{{ $item->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item->kode }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $item->nama }}</td>
                <td class="px-lg py-md text-body-md text-text-secondary">{{ $item->satuan }}</td>
                <td class="px-lg py-md text-body-md text-text-primary numeric">{{ number_format($item->stok_saat_ini, 2) }}</td>
                <td class="px-lg py-md text-body-md">
                    <div class="flex items-center gap-sm">
                        <x-feedback.status-badge status="{{ $item->bom_lines_count > 0 ? 'success' : 'warning' }}">
                            {{ $item->bom_lines_count > 0 ? 'Resep Ada' : 'Belum Ada Resep' }}
                        </x-feedback.status-badge>
                    </div>
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        {{-- Edit BOM button --}}
                        @can('update', $item)
                            <a href="{{ route('bom.edit', $item->id) }}" 
                               class="text-text-secondary hover:text-primary p-1 rounded hover:bg-surface-container-low transition-colors"
                               title="Edit BOM Resep"
                            >
                                <span class="material-symbols-outlined text-[20px]">account_tree</span>
                            </a>
                        @else
                            <a href="{{ route('bom.edit', $item->id) }}" 
                               class="text-text-secondary hover:text-primary p-1 rounded hover:bg-surface-container-low transition-colors"
                               title="Lihat BOM Resep"
                            >
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </a>
                        @endcan

                        {{-- Edit Finished Good metadata --}}
                        @can('update', $item)
                            <x-ui.icon-button 
                                icon="edit" 
                                wire:click="openEditModal({{ $item->id }})" 
                                title="Edit Info"
                                class="text-text-secondary hover:text-primary cursor-pointer"
                            />
                        @else
                            <span class="text-xs text-text-secondary italic">Read-only</span>
                        @endcan

                        {{-- Delete Finished Good --}}
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
                icon="deployed_code"
                title="Barang jadi tidak ditemukan"
                description="Coba gunakan kata kunci pencarian lain atau tambahkan barang jadi baru."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$goods" />
        </x-slot:pagination>
    </x-tables.data-table>

    {{-- ── Create / Edit Modal ───────────────────────────────────────── --}}
    <x-modal name="fg-form-modal" :show="$isModalOpen" maxWidth="md">
        <form wire:submit.prevent="save" class="p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none">
                {{ $fgId ? 'Edit Barang Jadi' : 'Tambah Barang Jadi Baru' }}
            </h3>

            <div class="flex flex-col gap-md mb-lg">
                <!-- Kode -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="kode" value="Kode Barang Jadi" />
                    <x-text-input 
                        id="kode" 
                        type="text" 
                        wire:model="kode" 
                        class="w-full" 
                        placeholder="Contoh: FG-001" 
                    />
                    <x-input-error :messages="$errors->get('kode')" />
                </div>

                <!-- Nama -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="nama" value="Nama Barang Jadi" />
                    <x-text-input 
                        id="nama" 
                        type="text" 
                        wire:model="nama" 
                        class="w-full" 
                        placeholder="Contoh: Sabun Mandi Cair A" 
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
                        placeholder="Contoh: botol, box, pcs" 
                    />
                    <x-input-error :messages="$errors->get('satuan')" />
                </div>

                <!-- Stok Saat Ini (Hanya Create) -->
                @if(! $fgId)
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
                        <p class="text-xs text-text-secondary select-none">Perubahan stok barang jadi harus dicatat melalui pencatatan Hasil Produksi.</p>
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
        title="{{ $forceDelete ? 'Peringatan: Ada Riwayat Produksi' : 'Hapus Barang Jadi' }}" 
        type="danger"
    >
        @if($forceDelete && $linkedProductionCount > 0)
            {{-- Second warning: production entries linked --}}
            <div class="flex flex-col gap-sm">
                <p class="text-body-md text-text-primary font-semibold">
                    Barang jadi ini terhubung dengan <span class="text-negative-rose">{{ $linkedProductionCount }} entri produksi</span>.
                </p>
                <p class="text-body-md text-text-secondary">
                    Menghapus barang jadi ini akan <strong>menghapus seluruh riwayat produksi terkait</strong> secara permanen. Data yang dihapus tidak dapat dipulihkan.
                </p>
                <p class="text-body-md text-text-secondary">
                    Apakah Anda tetap yakin ingin melanjutkan penghapusan?
                </p>
            </div>
        @else
            Apakah Anda yakin ingin menghapus barang jadi ini? Tindakan ini tidak dapat dibatalkan.
        @endif

        <x-slot:cancel>
            <x-ui.secondary-button type="button" wire:click="$dispatch('toggle-modal', {name: 'delete-confirm', show: false})" class="cursor-pointer">
                Batal
            </x-ui.secondary-button>
        </x-slot:cancel>

        <x-slot:confirm>
            <x-ui.primary-button 
                type="button" 
                wire:click="delete" 
                class="cursor-pointer bg-negative-rose hover:bg-negative-rose/90 border-transparent"
            >
                {{ $forceDelete ? 'Ya, Hapus Semua' : 'Hapus' }}
            </x-ui.primary-button>
        </x-slot:confirm>
    </x-feedback.confirmation-modal>
</div>
