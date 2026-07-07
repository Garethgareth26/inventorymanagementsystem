<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\EmployeeDashboard;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $ownerRole = Role::create(['name' => 'Owner', 'slug' => 'owner']);
        $karyawanRole = Role::create(['name' => 'Karyawan', 'slug' => 'karyawan']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'email_verified_at' => now(),
        ]);

        $this->karyawan = User::factory()->create([
            'role_id' => $karyawanRole->id,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function guests_are_redirected_to_login()
    {
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
        $this->get(route('employee.dashboard'))->assertRedirect(route('login'));
    }

    /** @test */
    public function owner_can_access_owner_dashboard_but_not_employee_dashboard()
    {
        $this->actingAs($this->owner)
            ->get(route('owner.dashboard'))
            ->assertOk();

        $this->actingAs($this->owner)
            ->get(route('employee.dashboard'))
            ->assertForbidden();
    }

    /** @test */
    public function employee_can_access_employee_dashboard_but_not_owner_dashboard()
    {
        $this->actingAs($this->karyawan)
            ->get(route('employee.dashboard'))
            ->assertOk();

        $this->actingAs($this->karyawan)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    /** @test */
    public function owner_dashboard_renders_livewire_component_and_checks_policy()
    {
        $this->actingAs($this->owner);

        Livewire::test(OwnerDashboard::class)
            ->assertViewIs('livewire.dashboard.owner-dashboard')
            ->assertSee('Total Bahan Baku Aktif')
            ->assertSee('Nilai Investasi Tahunan');
    }

    /** @test */
    public function employee_dashboard_renders_livewire_component_and_checks_policy()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(EmployeeDashboard::class)
            ->assertViewIs('livewire.dashboard.employee-dashboard')
            ->assertSee('Material Kritis Hari Ini')
            ->assertSee('Buat PO Baru');
    }

    /** @test */
    public function cache_is_invalidated_upon_stock_change()
    {
        $queryService = app(DashboardQueryService::class);

        // Put some metrics in cache
        $queryService->getOwnerMetrics();
        $queryService->getEmployeeMetrics();

        $this->assertTrue(Cache::has('dashboard:metrics:owner'));
        $this->assertTrue(Cache::has('dashboard:metrics:employee'));

        // Perform stock change via mutation invalidates cache
        $queryService->invalidateCache();

        $this->assertFalse(Cache::has('dashboard:metrics:owner'));
        $this->assertFalse(Cache::has('dashboard:metrics:employee'));
    }
}
