<div>
    {{-- ── Filter & Search Bar ────────────────────────────────────────── --}}
    <div class="mb-lg">
        <x-forms.filter-bar>
            <x-slot:search>
                <x-forms.search-input 
                    placeholder="Cari nama atau email..." 
                    wire:model.live.debounce.300ms="search" 
                />
            </x-slot:search>

            <x-slot:actions>
                <x-ui.primary-button wire:click="openCreateModal" class="cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tambah Pengguna
                </x-ui.primary-button>
            </x-slot:actions>
        </x-forms.filter-bar>
    </div>

    {{-- ── Data Table ────────────────────────────────────────────────── --}}
    <x-tables.data-table
        :headers="['Nama', 'Email', 'Peran', 'Status', 'Login Terakhir', 'Aksi']"
        :items="$users->items()"
    >
        @foreach($users as $user)
            <tr class="hover:bg-surface-container-lowest transition-colors border-b border-border-divider" wire:key="user-{{ $user->id }}">
                <td class="px-lg py-md text-body-md font-semibold text-text-primary">{{ $user->name }}</td>
                <td class="px-lg py-md text-body-md text-text-primary">{{ $user->email }}</td>
                <td class="px-lg py-md text-body-md">
                    <x-feedback.status-badge type="{{ $user->isOwner() ? 'success' : 'neutral' }}">
                        {{ $user->role->name }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-body-md">
                    <x-feedback.status-badge type="{{ $user->is_active ? 'success' : 'danger' }}">
                        {{ $user->is_active ? 'Aktif' : 'Non-aktif' }}
                    </x-feedback.status-badge>
                </td>
                <td class="px-lg py-md text-body-md text-text-secondary">
                    {{ $user->last_login_at ? $user->last_login_at->translatedFormat('d M Y H:i') : 'Belum pernah' }}
                </td>
                <td class="px-lg py-md">
                    <div class="flex items-center gap-sm">
                        {{-- Reset Password --}}
                        <x-ui.icon-button 
                            icon="lock_reset" 
                            wire:click="openResetModal({{ $user->id }})" 
                            title="Reset Password"
                            class="text-text-secondary hover:text-primary cursor-pointer"
                        />

                        {{-- Edit --}}
                        <x-ui.icon-button 
                            icon="edit" 
                            wire:click="openEditModal({{ $user->id }})" 
                            title="Edit"
                            class="text-text-secondary hover:text-primary cursor-pointer"
                        />

                        {{-- Toggle Status (Don't toggle self) --}}
                        @if($user->id !== auth()->id())
                            <x-ui.icon-button 
                                icon="{{ $user->is_active ? 'toggle_on' : 'toggle_off' }}" 
                                wire:click="toggleStatus({{ $user->id }})" 
                                title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                class="{{ $user->is_active ? 'text-primary' : 'text-text-secondary' }} hover:text-primary cursor-pointer"
                            />
                        @endif

                        {{-- Delete (Don't delete self) --}}
                        @if($user->id !== auth()->id())
                            <x-ui.icon-button 
                                icon="delete" 
                                wire:click="confirmDelete({{ $user->id }})" 
                                title="Hapus"
                                class="text-text-secondary hover:text-negative-rose cursor-pointer"
                            />
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-tables.empty-state
                icon="group"
                title="Pengguna tidak ditemukan"
                description="Coba gunakan kata kunci pencarian lain."
            />
        </x-slot:empty>

        <x-slot:pagination>
            <x-tables.pagination :paginator="$users" />
        </x-slot:pagination>
    </x-tables.data-table>

    {{-- ── Create / Edit Form Modal ────────────────────────────────────── --}}
    <x-modal name="user-form-modal" :show="$isFormModalOpen" maxWidth="md">
        <form wire:submit.prevent="save" class="p-lg">
            <h3 class="font-headline-md text-headline-md text-text-primary mb-lg select-none">
                {{ $userId ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
            </h3>

            <div class="flex flex-col gap-md mb-lg">
                <!-- Nama -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="name" value="Nama Lengkap" />
                    <x-text-input 
                        id="name" 
                        type="text" 
                        wire:model="name" 
                        class="w-full" 
                        placeholder="Nama Lengkap Pengguna" 
                    />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <!-- Email -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="email" value="Alamat Email" />
                    <x-text-input 
                        id="email" 
                        type="email" 
                        wire:model="email" 
                        class="w-full" 
                        placeholder="email@domain.com" 
                    />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <!-- Peran -->
                <div class="flex flex-col gap-xs">
                    <x-input-label for="role_id" value="Peran / Role" />
                    <select 
                        id="role_id" 
                        wire:model="role_id" 
                        class="bg-card-surface border border-border-divider rounded-DEFAULT p-3 outline-none text-body-md focus:ring-2 focus:ring-surface-tint focus:border-transparent w-full"
                    >
                        <option value="">Pilih Peran</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" />
                </div>

                <!-- Password (Only on Create) -->
                @if(! $userId)
                    <div class="flex flex-col gap-xs">
                        <x-input-label for="password" value="Password" />
                        <x-text-input 
                            id="password" 
                            type="password" 
                            wire:model="password" 
                            class="w-full" 
                            placeholder="Minimal 8 karakter" 
                        />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>

                    <div class="flex flex-col gap-xs">
                        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                        <x-text-input 
                            id="password_confirmation" 
                            type="password" 
                            wire:model="password_confirmation" 
                            class="w-full" 
                            placeholder="Ulangi password" 
                        />
                        <x-input-error :messages="$errors->get('password_confirmation')" />
                    </div>
                @endif

                <!-- Status Aktif -->
                <div class="flex items-center gap-md mt-sm">
                    <input 
                        id="is_active" 
                        type="checkbox" 
                        wire:model="is_active" 
                        class="rounded border-border-divider text-primary focus:ring-primary h-4 w-4"
                    />
                    <x-input-label for="is_active" value="Pengguna Aktif" />
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-md">
                <x-ui.secondary-button type="button" wire:click="$set('isFormModalOpen', false)" class="cursor-pointer">
                    Batal
                </x-ui.secondary-button>

                <x-ui.primary-button type="submit" class="cursor-pointer">
                    Simpan
                </x-ui.primary-button>
            </div>
        </form>
    </x-modal>

    {{-- ── Reset Password Modal ────────────────────────────────────────── --}}
    <x-feedback.confirmation-modal 
        name="reset-password-confirm" 
        title="Reset Password Pengguna" 
        type="warning"
    >
        <div class="w-full text-left flex flex-col gap-md">
            <p>Masukkan password baru untuk <strong>{{ $resettingName }}</strong>:</p>
            
            <div class="flex flex-col gap-xs">
                <x-input-label for="newPassword" value="Password Baru" />
                <x-text-input 
                    id="newPassword" 
                    type="password" 
                    wire:model="newPassword" 
                    class="w-full" 
                    placeholder="Minimal 8 karakter" 
                />
                <x-input-error :messages="$errors->get('newPassword')" />
            </div>

            <div class="flex flex-col gap-xs">
                <x-input-label for="newPassword_confirmation" value="Konfirmasi Password Baru" />
                <x-text-input 
                    id="newPassword_confirmation" 
                    type="password" 
                    wire:model="newPassword_confirmation" 
                    class="w-full" 
                    placeholder="Ulangi password baru" 
                />
                <x-input-error :messages="$errors->get('newPassword_confirmation')" />
            </div>
        </div>

        <x-slot:cancel>
            <x-ui.secondary-button type="button" wire:click="$dispatch('toggle-modal', {name: 'reset-password-confirm', show: false})" class="cursor-pointer">
                Batal
            </x-ui.secondary-button>
        </x-slot:cancel>

        <x-slot:confirm>
            <x-ui.primary-button type="button" wire:click="submitResetPassword" class="cursor-pointer">
                Reset Password
            </x-ui.primary-button>
        </x-slot:confirm>
    </x-feedback.confirmation-modal>

    {{-- ── Delete Confirmation Modal ──────────────────────────────────── --}}
    <x-feedback.confirmation-modal 
        name="delete-user-confirm" 
        title="Hapus Pengguna" 
        type="danger"
    >
        Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.

        <x-slot:cancel>
            <x-ui.secondary-button type="button" wire:click="$dispatch('toggle-modal', {name: 'delete-user-confirm', show: false})" class="cursor-pointer">
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
