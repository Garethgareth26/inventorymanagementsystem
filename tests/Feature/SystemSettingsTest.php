<?php

namespace Tests\Feature;

use App\Livewire\Administration\SystemSettingsManager;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
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

        // Seed default system settings
        SystemSetting::create(['key' => 'z_factor', 'value' => '1.65']);
        SystemSetting::create(['key' => 'abc_threshold_a', 'value' => '80']);
        SystemSetting::create(['key' => 'abc_threshold_b', 'value' => '95']);
        SystemSetting::create(['key' => 'historical_window', 'value' => '12']);
        SystemSetting::create(['key' => 'biaya_pesan', 'value' => '75000']);
        SystemSetting::create(['key' => 'biaya_simpan', 'value' => '20']);
        SystemSetting::create(['key' => 'company_name', 'value' => 'CV Akuna']);
        SystemSetting::create(['key' => 'company_address', 'value' => 'Jl. Industri Bakery No. 1, Surabaya']);

        SystemSettings::flush();
    }

    /** @test */
    public function guests_are_redirected_to_login()
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function karyawan_cannot_access_settings()
    {
        $this->actingAs($this->karyawan)
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    /** @test */
    public function owner_can_access_settings()
    {
        $this->actingAs($this->owner)
            ->get(route('settings.index'))
            ->assertOk();
    }

    /** @test */
    public function settings_manager_loads_initial_values()
    {
        $this->actingAs($this->owner);

        Livewire::test(SystemSettingsManager::class)
            ->assertSet('company_name', 'CV Akuna')
            ->assertSet('company_address', 'Jl. Industri Bakery No. 1, Surabaya')
            ->assertSet('z_factor', 1.65)
            ->assertSet('abc_threshold_a', 80)
            ->assertSet('abc_threshold_b', 95)
            ->assertSet('historical_window', 12)
            ->assertSet('biaya_pesan', 75000.0)
            ->assertSet('biaya_simpan_pct', 20.0);
    }

    /** @test */
    public function owner_can_update_company_profile()
    {
        $this->actingAs($this->owner);

        Livewire::test(SystemSettingsManager::class)
            ->set('company_name', 'CV Akuna Baru')
            ->set('company_address', 'Alamat Baru')
            ->call('saveCompanyProfile')
            ->assertHasNoErrors();

        $this->assertEquals('CV Akuna Baru', SystemSettings::get('company_name'));
        $this->assertEquals('Alamat Baru', SystemSettings::get('company_address'));

        // Assert audit log was written
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'settings.update',
            'subject_type' => SystemSetting::class,
        ]);
    }

    /** @test */
    public function owner_can_update_calculation_parameters()
    {
        $this->actingAs($this->owner);

        // Put some dashboard metrics in cache to verify cache invalidation
        Cache::put('dashboard:metrics:owner', ['some' => 'data'], 3600);

        Livewire::test(SystemSettingsManager::class)
            ->set('z_factor', 1.96)
            ->set('abc_threshold_a', 75)
            ->set('abc_threshold_b', 90)
            ->set('historical_window', 6)
            ->set('biaya_pesan', 50000.0)
            ->set('biaya_simpan_pct', 15.0)
            ->call('saveCalculationParameters')
            ->assertHasNoErrors();

        // Check cache invalidation
        $this->assertFalse(Cache::has('dashboard:metrics:owner'));

        // Verify values
        $this->assertEquals(1.96, SystemSettings::getFloat('z_factor'));
        $this->assertEquals(75, SystemSettings::getInt('abc_threshold_a'));
        $this->assertEquals(90, SystemSettings::getInt('abc_threshold_b'));
        $this->assertEquals(6, SystemSettings::getInt('historical_window'));
        $this->assertEquals(50000.0, SystemSettings::getFloat('biaya_pesan'));
        $this->assertEquals(15.0, SystemSettings::getFloat('biaya_simpan'));
    }

    /** @test */
    public function owner_can_reset_calculation_parameters()
    {
        $this->actingAs($this->owner);

        // Modify parameters first
        SystemSettings::set('z_factor', '2.58');
        SystemSettings::set('abc_threshold_a', '70');

        Livewire::test(SystemSettingsManager::class)
            ->call('resetCalculationParameters')
            ->assertHasNoErrors();

        $this->assertEquals(1.65, SystemSettings::getFloat('z_factor'));
        $this->assertEquals(80, SystemSettings::getInt('abc_threshold_a'));
        $this->assertEquals(95, SystemSettings::getInt('abc_threshold_b'));
    }

    /** @test */
    public function validation_fails_if_abc_threshold_b_is_not_greater_than_a()
    {
        $this->actingAs($this->owner);

        Livewire::test(SystemSettingsManager::class)
            ->set('abc_threshold_a', 85)
            ->set('abc_threshold_b', 80)
            ->call('saveCalculationParameters')
            ->assertHasErrors(['abc_threshold_b']);
    }

    /** @test */
    public function validation_rules_are_enforced()
    {
        $this->actingAs($this->owner);

        Livewire::test(SystemSettingsManager::class)
            ->set('z_factor', -1)
            ->set('abc_threshold_a', 150)
            ->set('historical_window', 30)
            ->set('biaya_pesan', 0)
            ->call('saveCalculationParameters')
            ->assertHasErrors([
                'z_factor',
                'abc_threshold_a',
                'historical_window',
                'biaya_pesan',
            ]);
    }
}
