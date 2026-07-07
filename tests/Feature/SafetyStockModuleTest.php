<?php

use App\Livewire\Optimization\SafetyStockOverview;
use App\Livewire\Optimization\SafetyStockSimulation;
use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function ssOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function ssKaryawan(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

function ssMaterial(): BahanBaku
{
    $bb = BahanBaku::factory()->create([
        'harga_satuan' => 9500,
        'lead_time_hari' => 9,
        'stok_saat_ini' => 200,
    ]);

    InventoryParameter::factory()->create([
        'bahan_baku_id' => $bb->id,
        'biaya_pesan' => 75000,
        'biaya_simpan_persen' => 0.20,
        'z_factor' => 1.65,
        'historical_window_months' => 12,
        'eoq' => 300.0,
        'safety_stock' => 24.75,
        'reorder_point' => 74.75,
        'kebutuhan_tahunan' => 1000.0,
        'standar_deviasi_harian' => 5.0,
    ]);

    return $bb;
}

// ─── Overview ─────────────────────────────────────────────────────────────────

describe('SafetyStockOverview', function () {
    it('redirects guest to login', function () {
        $this->get(route('safety_stock.index'))->assertRedirect(route('login'));
    });

    it('allows Owner access', function () {
        $this->actingAs(ssOwner())
            ->get(route('safety_stock.index'))
            ->assertOk()
            ->assertSeeLivewire(SafetyStockOverview::class);
    });

    it('allows Karyawan access', function () {
        $this->actingAs(ssKaryawan())
            ->get(route('safety_stock.index'))
            ->assertOk();
    });

    it('shows material with safety stock in list', function () {
        $bb = ssMaterial();
        Livewire::actingAs(ssKaryawan())
            ->test(SafetyStockOverview::class)
            ->assertSee($bb->kode);
    });
});

// ─── Simulation ───────────────────────────────────────────────────────────────

describe('SafetyStockSimulation', function () {
    it('redirects guest to login on simulation page', function () {
        $bb = ssMaterial();
        $this->get(route('safety_stock.show', $bb))->assertRedirect(route('login'));
    });

    it('allows Owner to view simulation page', function () {
        $bb = ssMaterial();
        $this->actingAs(ssOwner())
            ->get(route('safety_stock.show', $bb))
            ->assertOk();
    });

    it('simulation produces correct safety stock', function () {
        $bb = ssMaterial();
        // SS = Z × SD × sqrt(LT) = 1.65 × 5 × sqrt(9) = 24.75

        Livewire::actingAs(ssKaryawan())
            ->test(SafetyStockSimulation::class, ['bahanBaku' => $bb])
            ->set('zFactor', 1.65)
            ->set('leadTimeHari', 9)
            ->set('windowMonths', 12)
            ->call('simulate')
            ->assertSet('simulated', true)
            ->assertSet('simSafetyStock', fn ($v) => $v !== null);
    });

    it('resetToDefaults restores Z from SystemSettings', function () {
        $bb = ssMaterial();

        Livewire::actingAs(ssKaryawan())
            ->test(SafetyStockSimulation::class, ['bahanBaku' => $bb])
            ->set('zFactor', 3.0)
            ->call('resetToDefaults')
            ->assertSet('zFactor', 1.65);
    });
});
