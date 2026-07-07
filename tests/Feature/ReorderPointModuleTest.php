<?php

use App\Livewire\Optimization\ReorderPointOverview;
use App\Livewire\Optimization\ReorderPointSimulation;
use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\Role;
use App\Models\User;
use App\Services\OptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function ropOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function ropKaryawan(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

function ropMaterial(float $stok, float $rop): BahanBaku
{
    $bb = BahanBaku::factory()->create([
        'stok_saat_ini' => $stok,
        'lead_time_hari' => 7,
        'harga_satuan' => 5000,
    ]);

    InventoryParameter::factory()->create([
        'bahan_baku_id' => $bb->id,
        'reorder_point' => $rop,
        'safety_stock' => 10.0,
        'eoq' => 100.0,
        'kebutuhan_tahunan' => 1000.0,
        'standar_deviasi_harian' => 3.0,
        'biaya_pesan' => 75000,
        'biaya_simpan_persen' => 0.20,
        'z_factor' => 1.65,
        'historical_window_months' => 12,
    ]);

    return $bb;
}

// ─── Overview ─────────────────────────────────────────────────────────────────

describe('ReorderPointOverview', function () {
    it('redirects guest to login', function () {
        $this->get(route('reorder_point.index'))->assertRedirect(route('login'));
    });

    it('allows Owner access', function () {
        $this->actingAs(ropOwner())
            ->get(route('reorder_point.index'))
            ->assertOk()
            ->assertSeeLivewire(ReorderPointOverview::class);
    });

    it('allows Karyawan access', function () {
        $this->actingAs(ropKaryawan())
            ->get(route('reorder_point.index'))
            ->assertOk();
    });

    it('classifies a material as critical when stok <= ROP', function () {
        // stok=5, rop=20 → critical
        ropMaterial(stok: 5.0, rop: 20.0);

        $service = app(OptimizationService::class);
        $statuses = $service->getReorderPointStatus();

        $critical = array_filter($statuses, fn ($i) => $i['status'] === 'critical');
        expect($critical)->not->toBeEmpty();
    });

    it('classifies a material as near when stok <= 1.2 * ROP', function () {
        // stok=22, rop=20 → near (22 <= 24 = 1.2*20)
        ropMaterial(stok: 22.0, rop: 20.0);

        $service = app(OptimizationService::class);
        $statuses = $service->getReorderPointStatus();

        $near = array_filter($statuses, fn ($i) => $i['status'] === 'near');
        expect($near)->not->toBeEmpty();
    });

    it('classifies a material as OK when stok > 1.2 * ROP', function () {
        // stok=100, rop=20 → ok (100 > 24)
        ropMaterial(stok: 100.0, rop: 20.0);

        $service = app(OptimizationService::class);
        $statuses = $service->getReorderPointStatus();

        $ok = array_filter($statuses, fn ($i) => $i['status'] === 'ok');
        expect($ok)->not->toBeEmpty();
    });

    it('classifies exact boundary: stok == ROP as critical', function () {
        ropMaterial(stok: 20.0, rop: 20.0);

        $service = app(OptimizationService::class);
        $statuses = $service->getReorderPointStatus();

        $critical = array_filter($statuses, fn ($i) => $i['status'] === 'critical');
        expect($critical)->not->toBeEmpty();
    });

    it('classifies exact boundary: stok == 1.2 * ROP as near', function () {
        ropMaterial(stok: 24.0, rop: 20.0); // 1.2*20=24, stok=24 → near
        $service = app(OptimizationService::class);
        $statuses = $service->getReorderPointStatus();

        $near = array_filter($statuses, fn ($i) => $i['status'] === 'near');
        expect($near)->not->toBeEmpty();
    });

    it('shows "Buat PO Darurat" in component for critical material', function () {
        ropMaterial(stok: 5.0, rop: 100.0); // definitely critical

        Livewire::actingAs(ropKaryawan())
            ->test(ReorderPointOverview::class)
            ->assertSee('Buat PO Darurat');
    });
});

// ─── Simulation ───────────────────────────────────────────────────────────────

describe('ReorderPointSimulation', function () {
    it('redirects guest to login on simulation page', function () {
        $bb = ropMaterial(50.0, 20.0);
        $this->get(route('reorder_point.show', $bb))->assertRedirect(route('login'));
    });

    it('allows Karyawan to view simulation page', function () {
        $bb = ropMaterial(50.0, 20.0);
        $this->actingAs(ropKaryawan())
            ->get(route('reorder_point.show', $bb))
            ->assertOk();
    });

    it('simulation produces ROP matching formula', function () {
        $bb = ropMaterial(50.0, 20.0);
        // ROP = (dailyDemand × leadTime) + SS = (5 × 7) + 10 = 45

        Livewire::actingAs(ropKaryawan())
            ->test(ReorderPointSimulation::class, ['bahanBaku' => $bb])
            ->set('dailyDemand', 5.0)
            ->set('leadTimeHari', 7)
            ->set('safetyStock', 10.0)
            ->call('simulate')
            ->assertSet('simulated', true)
            ->assertSet('simRop', fn ($v) => abs($v - 45.0) < 0.01);
    });
});
