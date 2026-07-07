<?php

namespace App\Livewire\Administration;

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Livewire controller for User Management.
 *
 * Enforces authorization checks and delegates writes to UserManagementService.
 */
class UserManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    // Create / Edit Form State
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public ?int $role_id = null;

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    // Reset Password Form State
    public ?int $resettingUserId = null;

    public string $resettingName = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // Confirm Deletion State
    public ?int $confirmingDeletionUserId = null;

    // UI Control flags
    public bool $isFormModalOpen = false;

    public bool $isResetModalOpen = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('manage', User::class);

        $query = User::with('role');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        $users = $query->orderBy('name')->paginate(25);
        $roles = Role::orderBy('name')->get();

        return view('livewire.administration.user-management', [
            'users' => $users,
            'roles' => $roles,
        ])->layout('components.layout.app', [
            'pageTitle' => 'Manajemen Pengguna',
            'pageSubtitle' => 'Kelola akun pengguna, hak akses, dan status keaktifan',
        ]);
    }

    // ─── Create User ─────────────────────────────────────────────────────────

    public function openCreateModal(): void
    {
        $this->authorize('manage', User::class);

        $this->resetErrorBag();
        $this->resetForm();

        $this->isFormModalOpen = true;
    }

    // ─── Edit User ───────────────────────────────────────────────────────────

    public function openEditModal(int $id): void
    {
        $this->authorize('manage', User::class);

        $this->resetErrorBag();
        $this->resetForm();

        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->is_active = $user->is_active;

        $this->isFormModalOpen = true;
    }

    // ─── Reset Password Modal ────────────────────────────────────────────────

    public function openResetModal(int $id): void
    {
        $this->authorize('manage', User::class);

        $this->resetErrorBag();
        $this->newPassword = '';
        $this->newPassword_confirmation = '';

        $user = User::findOrFail($id);
        $this->resettingUserId = $user->id;
        $this->resettingName = $user->name;

        $this->isResetModalOpen = true;
        $this->dispatch('toggle-modal', name: 'reset-password-confirm', show: true);
    }

    // ─── Save User ───────────────────────────────────────────────────────────

    public function save(UserManagementService $service): void
    {
        $this->authorize('manage', User::class);

        $rules = [
            'name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ];

        if ($this->userId) {
            $rules['email'] = 'required|email|max:255|unique:users,email,'.$this->userId;
        } else {
            $rules['email'] = 'required|email|max:255|unique:users,email';
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules);

        try {
            if ($this->userId) {
                $user = User::findOrFail($this->userId);
                $service->updateUser($user, [
                    'name' => $this->name,
                    'email' => $this->email,
                    'role_id' => $this->role_id,
                    'is_active' => $this->is_active,
                ], auth()->user());

                $this->dispatch('notify', message: 'Detail pengguna berhasil diperbarui.', type: 'success');
            } else {
                $service->createUser([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password,
                    'role_id' => $this->role_id,
                    'is_active' => $this->is_active,
                ], auth()->user());

                $this->dispatch('notify', message: 'Pengguna baru berhasil dibuat.', type: 'success');
            }

            $this->isFormModalOpen = false;
            $this->resetForm();
        } catch (Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'danger');
        }
    }

    // ─── Reset Password Submit ───────────────────────────────────────────────

    public function submitResetPassword(UserManagementService $service): void
    {
        $this->authorize('manage', User::class);

        $this->validate([
            'newPassword' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = User::findOrFail($this->resettingUserId);
            $service->resetPassword($user, $this->newPassword, auth()->user());

            $this->dispatch('toggle-modal', name: 'reset-password-confirm', show: false);
            $this->isResetModalOpen = false;

            $this->dispatch('notify', message: 'Password pengguna berhasil direset.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'danger');
        }
    }

    // ─── Deletion ────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->authorize('manage', User::class);

        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'Tidak dapat menghapus akun Anda sendiri.', type: 'danger');

            return;
        }

        $this->confirmingDeletionUserId = $id;
        $this->dispatch('toggle-modal', name: 'delete-user-confirm', show: true);
    }

    public function delete(UserManagementService $service): void
    {
        $this->authorize('manage', User::class);

        if (! $this->confirmingDeletionUserId) {
            return;
        }

        try {
            $user = User::findOrFail($this->confirmingDeletionUserId);
            $service->deleteUser($user, auth()->user());

            $this->dispatch('toggle-modal', name: 'delete-user-confirm', show: false);
            $this->dispatch('notify', message: 'Pengguna berhasil dihapus.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toggle-modal', name: 'delete-user-confirm', show: false);
            $this->dispatch('notify', message: $e->getMessage(), type: 'danger');
        } finally {
            $this->confirmingDeletionUserId = null;
        }
    }

    // ─── Quick Toggle Status ─────────────────────────────────────────────────

    public function toggleStatus(int $id, UserManagementService $service): void
    {
        $this->authorize('manage', User::class);

        try {
            $user = User::findOrFail($id);
            $service->updateUser($user, [
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'is_active' => ! $user->is_active,
            ], auth()->user());

            $this->dispatch('notify', message: 'Status pengguna berhasil diubah.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'danger');
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->role_id = Role::where('slug', 'karyawan')->first()?->id;
        $this->is_active = true;
        $this->password = '';
        $this->password_confirmation = '';
    }
}
