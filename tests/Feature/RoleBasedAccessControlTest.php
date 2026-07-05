<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Ensure the roles are seeded before each test
    $this->seed(RoleSeeder::class);
});

test('guests are redirected to login when accessing authenticated routes', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('owner is redirected to owner dashboard upon login', function () {
    $ownerRole = Role::where('slug', 'owner')->first();
    $user = User::factory()->create(['role_id' => $ownerRole->id]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('owner.dashboard', absolute: false));
});

test('employee is redirected to employee dashboard upon login', function () {
    $karyawanRole = Role::where('slug', 'karyawan')->first();
    $user = User::factory()->create(['role_id' => $karyawanRole->id]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('employee.dashboard', absolute: false));
});

test('require capability middleware blocks unauthorized users', function () {
    $karyawanRole = Role::where('slug', 'karyawan')->first();
    $user = User::factory()->create(['role_id' => $karyawanRole->id]);

    // Setup a dummy route protected by capability:user.manage
    Route::get('/test-manage-users', function () {
        return 'success';
    })->middleware(['web', 'auth', 'capability:user.manage']);

    $response = $this->actingAs($user)->get('/test-manage-users');

    // Employee doesn't have user.manage capability, so 403 Forbidden is expected
    $response->assertStatus(403);
});

test('require capability middleware allows authorized users', function () {
    $ownerRole = Role::where('slug', 'owner')->first();
    $user = User::factory()->create(['role_id' => $ownerRole->id]);

    // Setup a dummy route protected by capability:user.manage
    Route::get('/test-manage-users-allowed', function () {
        return 'success';
    })->middleware(['web', 'auth', 'capability:user.manage']);

    $response = $this->actingAs($user)->get('/test-manage-users-allowed');

    // Owner has user.manage capability, so 200 OK is expected
    $response->assertStatus(200);
    $response->assertSee('success');
});

test('basic RBAC authorization checks on user model', function () {
    $ownerRole = Role::where('slug', 'owner')->first();
    $karyawanRole = Role::where('slug', 'karyawan')->first();

    $owner = User::factory()->create(['role_id' => $ownerRole->id]);
    $karyawan = User::factory()->create(['role_id' => $karyawanRole->id]);

    // Test owner capabilities
    expect($owner->hasCapability('user.manage'))->toBeTrue();
    expect($owner->hasCapability('report.view'))->toBeTrue();

    // Test employee capabilities
    expect($karyawan->hasCapability('user.manage'))->toBeFalse();
    expect($karyawan->hasCapability('report.view'))->toBeTrue();
});
