<?php

use App\Livewire\Administration\UserManagement;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getOwnerRole(): Role
{
    return Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);
}

function getEmployeeRole(): Role
{
    return Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);
}

function createOwnerUser(string $email = 'owner@test.com', bool $active = true): User
{
    return User::factory()->create([
        'email' => $email,
        'role_id' => getOwnerRole()->id,
        'is_active' => $active,
    ]);
}

function createEmployeeUser(string $email = 'employee@test.com', bool $active = true): User
{
    return User::factory()->create([
        'email' => $email,
        'role_id' => getEmployeeRole()->id,
        'is_active' => $active,
    ]);
}

// ─── RBAC Access ─────────────────────────────────────────────────────────────

describe('User Management Access', function () {
    it('redirects guest to login', function () {
        $this->get(route('user_management.index'))->assertRedirect(route('login'));
    });

    it('denies Employee access (403)', function () {
        $employee = createEmployeeUser();
        $this->actingAs($employee)
            ->get(route('user_management.index'))
            ->assertForbidden();
    });

    it('allows Owner access', function () {
        $owner = createOwnerUser();
        $this->actingAs($owner)
            ->get(route('user_management.index'))
            ->assertOk()
            ->assertSeeLivewire(UserManagement::class);
    });
});

// ─── Search & Pagination ─────────────────────────────────────────────────────

describe('User Management Listing', function () {
    it('searches users by name or email', function () {
        $owner = createOwnerUser();
        $user1 = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        $user2 = User::factory()->create(['name' => 'Ani Wijaya', 'email' => 'ani@test.com']);

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Ani Wijaya')
            ->set('search', 'ani@test.com')
            ->assertSee('Ani Wijaya')
            ->assertDontSee('Budi Santoso');
    });

    it('paginates users listing', function () {
        $owner = createOwnerUser();
        User::factory()->count(30)->create();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->assertViewHas('users', fn ($users) => $users->total() >= 31 && $users->perPage() === 25);
    });
});

// ─── CRUD Operations ─────────────────────────────────────────────────────────

describe('User Management CRUD', function () {
    it('creates a new user and hashes password', function () {
        $owner = createOwnerUser();
        $employeeRole = getEmployeeRole();

        // Seed a fake cache value to verify invalidation
        Cache::put('dashboard:metrics:owner', ['dummy' => true], 3600);

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('name', 'Joko Susilo')
            ->set('email', 'joko@test.com')
            ->set('role_id', $employeeRole->id)
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('is_active', true)
            ->call('save');

        $user = User::where('email', 'joko@test.com')->firstOrFail();
        expect($user->name)->toBe('Joko Susilo')
            ->and($user->role_id)->toBe($employeeRole->id)
            ->and($user->is_active)->toBeTrue()
            ->and(Hash::check('secret123', $user->password))->toBeTrue();

        // Verify Audit Log
        expect(
            AuditLog::where('action', 'user.create')
                ->where('user_id', $owner->id)
                ->where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->exists()
        )->toBeTrue();

        // Verify Cache Invalidation
        expect(Cache::has('dashboard:metrics:owner'))->toBeFalse();
    });

    it('validates user creation input rules', function () {
        $owner = createOwnerUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('name', '')
            ->set('email', 'invalid-email')
            ->set('password', '123')
            ->set('password_confirmation', '1234')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password', 'role_id']);
    });

    it('updates user details', function () {
        $owner = createOwnerUser();
        $employee = createEmployeeUser('change-me@test.com');
        $ownerRole = getOwnerRole();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('userId', $employee->id)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@test.com')
            ->set('role_id', $ownerRole->id)
            ->set('is_active', false)
            ->call('save');

        $employee->refresh();
        expect($employee->name)->toBe('Updated Name')
            ->and($employee->email)->toBe('updated@test.com')
            ->and($employee->role_id)->toBe($ownerRole->id)
            ->and($employee->is_active)->toBeFalse();

        // Verify Audit Log has old and new snapshots
        $log = AuditLog::where('action', 'user.update')
            ->where('subject_id', $employee->id)
            ->firstOrFail();

        expect($log->old_values['email'])->toBe('change-me@test.com')
            ->and($log->new_values['email'])->toBe('updated@test.com');
    });

    it('resets user password with encrypted hash storage', function () {
        $owner = createOwnerUser();
        $employee = createEmployeeUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('openResetModal', $employee->id)
            ->set('newPassword', 'newpass123')
            ->set('newPassword_confirmation', 'newpass123')
            ->call('submitResetPassword');

        $employee->refresh();
        expect(Hash::check('newpass123', $employee->password))->toBeTrue();

        // Verify Audit Log
        expect(
            AuditLog::where('action', 'user.reset_password')
                ->where('subject_id', $employee->id)
                ->exists()
        )->toBeTrue();
    });

    it('deletes an employee user', function () {
        $owner = createOwnerUser();
        $employee = createEmployeeUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('confirmingDeletionUserId', $employee->id)
            ->call('delete');

        expect(User::find($employee->id))->toBeNull();

        // Verify Audit Log
        expect(
            AuditLog::where('action', 'user.delete')
                ->where('subject_id', $employee->id)
                ->exists()
        )->toBeTrue();
    });

    it('toggles user status using quick action', function () {
        $owner = createOwnerUser();
        $employee = createEmployeeUser('toggle@test.com', active: true);

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('toggleStatus', $employee->id);

        $employee->refresh();
        expect($employee->is_active)->toBeFalse();
    });
});

// ─── Owner Protections ─────────────────────────────────────────────────────────

describe('User Management Owner Protections', function () {
    it('prevents user from deleting their own account', function () {
        $owner = createOwnerUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->call('confirmDelete', $owner->id)
            ->assertNotDispatched('toggle-modal', name: 'delete-user-confirm', show: true)
            ->assertDispatched('notify', message: 'Tidak dapat menghapus akun Anda sendiri.', type: 'danger');
    });

    it('prevents deleting the last Owner', function () {
        $owner = createOwnerUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('confirmingDeletionUserId', $owner->id)
            ->call('delete')
            ->assertDispatched('notify', message: 'Tidak dapat menghapus akun Anda sendiri.', type: 'danger');

        // Let's test via direct service call to bypass the livewire self-delete check and target the db last owner check
        $anotherOwner = createOwnerUser('another-owner@test.com');
        // Now there are 2 owners in DB. Delete $anotherOwner first.
        app(UserManagementService::class)->deleteUser($anotherOwner, $owner);
        expect(User::find($anotherOwner->id))->toBeNull();

        // Now only $owner is left. Trying to delete $owner (via a fake actor to bypass self-delete) should throw Exception
        $fakeActor = User::factory()->create();
        expect(fn () => app(UserManagementService::class)->deleteUser($owner, $fakeActor))
            ->toThrow(RuntimeException::class, 'Tidak dapat menghapus satu-satunya Owner di sistem.');
    });

    it('prevents deactivating the last active Owner', function () {
        $owner = createOwnerUser();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('userId', $owner->id)
            ->set('name', $owner->name)
            ->set('email', $owner->email)
            ->set('role_id', $owner->role_id)
            ->set('is_active', false)
            ->call('save')
            ->assertDispatched('notify', message: 'Tidak dapat menonaktifkan satu-satunya Owner aktif.', type: 'danger');

        expect($owner->fresh()->is_active)->toBeTrue();
    });

    it('prevents demoting the last active Owner to Employee', function () {
        $owner = createOwnerUser();
        $employeeRole = getEmployeeRole();

        Livewire::actingAs($owner)
            ->test(UserManagement::class)
            ->set('userId', $owner->id)
            ->set('name', $owner->name)
            ->set('email', $owner->email)
            ->set('role_id', $employeeRole->id)
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('notify', message: 'Tidak dapat mengubah peran satu-satunya Owner aktif.', type: 'danger');

        expect($owner->fresh()->role_id)->toBe(getOwnerRole()->id);
    });

    it('allows managing another Owner when multiple Owners exist', function () {
        $owner1 = createOwnerUser('owner1@test.com');
        $owner2 = createOwnerUser('owner2@test.com');

        // owner1 can deactivate owner2 since owner1 is still active
        Livewire::actingAs($owner1)
            ->test(UserManagement::class)
            ->set('userId', $owner2->id)
            ->set('name', $owner2->name)
            ->set('email', $owner2->email)
            ->set('role_id', $owner2->role_id)
            ->set('is_active', false)
            ->call('save')
            ->assertDispatched('notify', message: 'Detail pengguna berhasil diperbarui.', type: 'success');

        expect($owner2->fresh()->is_active)->toBeFalse();

        // owner1 can delete owner2 since owner1 is still there
        Livewire::actingAs($owner1)
            ->test(UserManagement::class)
            ->set('confirmingDeletionUserId', $owner2->id)
            ->call('delete')
            ->assertDispatched('notify', message: 'Pengguna berhasil dihapus.', type: 'success');

        expect(User::find($owner2->id))->toBeNull();
    });
});
