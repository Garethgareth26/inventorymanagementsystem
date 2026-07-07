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
                @can('create', App\Models\Supplier::class)
                    <x-ui.primary-button wire:click="openCreateModal" class="cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Supplier
                    </x-ui.primary-button>
                @endcan
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Kode', 'Nama Supplier', 'Alamat', 'Kontak', 'Status', 'Aksi']"
        :items="$suppliers->items()"
    >
        @foreach($suppliers as $item)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="sup-{{ $item->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $item->kode }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $item->nama }}</td>
                <td class="px-lg py-md text-body-md text-text-secondary max-w-xs truncate">{{ $item->alamat }}</td>
                <td class="px-lg py-md text-body-md text-text-secondary">{{ $item->kontak }}</td>
                <td class="px-lg py-md text-body-md">
                    <x-feedback.status-badge status="{{ $item->is_active ? 'success' : 'warning' }}">
                        {{ $item->is_active ? 'Aktif' : 'Non-aktif' }}
                    </x-feedback.status-badge>
                </td>
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
                icon="group"
                title="Supplier tidak ditemukan"
                description="Coba gunakan kata kunci pencarian lain atau tambahkan supplier baru."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$suppliers" />
        </x-slot:pagination>
    </x-tables.data-table>

    {{-- ── Create / Edit Modal ───────────────────────────────────────── --}}
    <x-modal name="supplier-form-modal" :show="$isModalOpen" maxWidth="md">
        <form wire:submit.prevent="save" class="p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none">
                {{ $supplierId ? 'Edit Supplier' : 'Tambah Supplier Baru' }}
            </h3>

            <div class="flex flex-col gap-md mb-lg">
                <!-- Kode -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="kode" value="Kode Supplier" />
                    <x-text-input 
                        id="kode" 
                        type="text" 
                        wire:model="kode" 
                        class="w-full" 
                        placeholder="Contoh: SUP-001" 
                    />
                    <x-input-error :messages="$errors->get('kode')" />
                </div>

                <!-- Nama -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="nama" value="Nama Supplier" />
                    <x-text-input 
                        id="nama" 
                        type="text" 
                        wire:model="nama" 
                        class="w-full" 
                        placeholder="Nama PT atau Toko" 
                    />
                    <x-input-error :messages="$errors->get('nama')" />
                </div>

                <!-- Alamat -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="alamat" value="Alamat" />
                    <textarea 
                        id="alamat" 
                        wire:model="alamat" 
                        class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent min-h-[80px] w-full"
                        placeholder="Alamat lengkap supplier"
                    ></textarea>
                    <x-input-error :messages="$errors->get('alamat')" />
                </div>

                <!-- Kontak -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="kontak" value="Kontak / Telepon" />
                    <x-text-input 
                        id="kontak" 
                        type="text" 
                        wire:model="kontak" 
                        class="w-full" 
                        placeholder="Nama perwakilan atau nomor telepon" 
                    />
                    <x-input-error :messages="$errors->get('kontak')" />
                </div>

                <!-- Status Aktif (Hanya muncul saat edit) -->
                @if($supplierId)
                    <div class="flex items-center gap-md mt-sm">
                        <input 
                            id="is_active" 
                            type="checkbox" 
                            wire:model="is_active" 
                            class="rounded border-border-divider text-primary focus:ring-primary h-4 w-4"
                        />
                        <x-input-label for="is_active" value="Supplier Aktif" />
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
        title="Hapus Supplier" 
        type="danger"
    >
        Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.

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
