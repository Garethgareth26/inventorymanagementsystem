<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Service encapsulating all user management business logic and security rules.
 */
final class UserManagementService
{
    public function __construct(
        private readonly DashboardQueryService $dashboardQueryService
    ) {}

    /**
     * Create a new user.
     *
     * @param  array{
     *   name: string,
     *   email: string,
     *   password: string,
     *   role_id: int,
     *   is_active: bool,
     * }  $data
     */
    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);

            AuditLogger::log(
                $actor,
                'user.create',
                $user,
                null,
                $user->toArray()
            );

            $this->dashboardQueryService->invalidateCache();

            return $user;
        });
    }

    /**
     * Update an existing user's details, role, and active status.
     *
     * Enforces Owner deactivation and demotion safeguards.
     *
     * @param  array{
     *   name: string,
     *   email: string,
     *   role_id: int,
     *   is_active: bool,
     * }  $data
     */
    public function updateUser(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $ownerRole = Role::where('slug', 'owner')->first();
            $ownerRoleId = $ownerRole?->id;

            // 1. Last active Owner deactivation safeguard
            if ($user->role_id === $ownerRoleId && $user->is_active && ! $data['is_active']) {
                $activeOwnersCount = User::where('role_id', $ownerRoleId)
                    ->where('is_active', true)
                    ->count();

                if ($activeOwnersCount <= 1) {
                    throw new RuntimeException('Tidak dapat menonaktifkan satu-satunya Owner aktif.');
                }
            }

            // 2. Last active Owner demotion safeguard
            if ($user->role_id === $ownerRoleId && $data['role_id'] !== $ownerRoleId) {
                // If they are currently active, changing role removes them from active Owners
                if ($user->is_active) {
                    $activeOwnersCount = User::where('role_id', $ownerRoleId)
                        ->where('is_active', true)
                        ->count();

                    if ($activeOwnersCount <= 1) {
                        throw new RuntimeException('Tidak dapat mengubah peran satu-satunya Owner aktif.');
                    }
                }
            }

            $oldValues = $user->toArray();
            $user->update($data);

            AuditLogger::log(
                $actor,
                'user.update',
                $user,
                $oldValues,
                $user->toArray()
            );

            $this->dashboardQueryService->invalidateCache();

            return $user;
        });
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(User $user, string $password, User $actor): void
    {
        DB::transaction(function () use ($user, $password, $actor) {
            $oldValues = $user->toArray();

            $user->password = Hash::make($password);
            $user->save();

            AuditLogger::log(
                $actor,
                'user.reset_password',
                $user,
                $oldValues,
                $user->toArray()
            );
        });
    }

    /**
     * Delete a user.
     *
     * Enforces self-deletion and last Owner safeguards.
     */
    public function deleteUser(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            // 1. Self-deletion safeguard
            if ($user->id === $actor->id) {
                throw new RuntimeException('Tidak dapat menghapus akun Anda sendiri.');
            }

            // 2. Last Owner in the database deletion safeguard
            $ownerRole = Role::where('slug', 'owner')->first();
            $ownerRoleId = $ownerRole?->id;

            if ($user->role_id === $ownerRoleId) {
                $ownersCount = User::where('role_id', $ownerRoleId)->count();

                if ($ownersCount <= 1) {
                    throw new RuntimeException('Tidak dapat menghapus satu-satunya Owner di sistem.');
                }
            }

            $oldValues = $user->toArray();
            $user->delete();

            AuditLogger::log(
                $actor,
                'user.delete',
                $user,
                $oldValues,
                null
            );

            $this->dashboardQueryService->invalidateCache();
        });
    }
}
