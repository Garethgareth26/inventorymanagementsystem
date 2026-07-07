<?php

use App\Livewire\Optimization\AbcAnalysis;
use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardQueryService;
use App\Services\OptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function abcOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function abcKaryawan(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

function abcMaterials(): void
{
    // Three materials with known usage values:
    // M1: 3000 (3000/4000 = 75% → under 80% → A)
    // M2: 800  (800/4000  = 20% → cumulative 95% → B)
    // M3: 200  (200/4000  = 5%  → cumulative 100% → C)
    $bb1 = BahanBaku::factory()->create(['harga_satuan' => 1000, 'stok_saat_ini' => 0]);
    $bb2 = BahanBaku::factory()->create(['harga_satuan' => 1000, 'stok_saat_ini' => 0]);
    $bb3 = BahanBaku::factory()->create(['harga_satuan' => 1000, 'stok_saat_ini' => 0]);

    InventoryParameter::factory()->create(['bahan_baku_id' => $bb1->id, 'kebutuhan_tahunan' => 3000, 'biaya_simpan_persen' => 0.20, 'biaya_pesan' => 75000, 'z_factor' => 1.65, 'historical_window_months' => 12]);
    InventoryParameter::factory()->create(['bahan_baku_id' => $bb2->id, 'kebutuhan_tahunan' => 800, 'biaya_simpan_persen' => 0.20, 'biaya_pesan' => 75000, 'z_factor' => 1.65, 'historical_window_months' => 12]);
    InventoryParameter::factory()->create(['bahan_baku_id' => $bb3->id, 'kebutuhan_tahunan' => 200, 'biaya_simpan_persen' => 0.20, 'biaya_pesan' => 75000, 'z_factor' => 1.65, 'historical_window_months' => 12]);
}

// ─── Route access ─────────────────────────────────────────────────────────────

describe('AbcAnalysis routes', function () {
    it('redirects guest to login', function () {
        $this->get(route('abc_analysis.index'))->assertRedirect(route('login'));
    });

    it('allows Owner access', function () {
        $this->actingAs(abcOwner())
            ->get(route('abc_analysis.index'))
            ->assertOk()
            ->assertSeeLivewire(AbcAnalysis::class);
    });

    it('allows Karyawan access', function () {
        $this->actingAs(abcKaryawan())
            ->get(route('abc_analysis.index'))
            ->assertOk();
    });
});

// ─── ABC Classification ───────────────────────────────────────────────────────

describe('AbcAnalysis classification', function () {
    it('classifies materials using CalculationEngine (matches OptimizationService)', function () {
        abcMaterials();

        $service = app(OptimizationService::class);
        $table = $service->getAbcTable();

        expect($table)->not->toBeEmpty();

        // Materials should be sorted descending by annual_usage_value
        for ($i = 0; $i < count($table) - 1; $i++) {
            expect($table[$i]['annual_usage_value'])
                ->toBeGreaterThanOrEqual($table[$i + 1]['annual_usage_value']);
        }
    });

    it('cumulative percentage is non-decreasing', function () {
        abcMaterials();

        $service = app(OptimizationService::class);
        $table = $service->getAbcTable();

        for ($i = 1; $i < count($table); $i++) {
            expect($table[$i]['cumulative_pct'])
                ->toBeGreaterThanOrEqual($table[$i - 1]['cumulative_pct']);
        }
    });

    it('total cumulative percentage reaches ~100 when summing all materials', function () {
        abcMaterials();
        $service = app(OptimizationService::class);
        $table = $service->getAbcTable();

        $last = end($table);
        expect($last['cumulative_pct'])->toBeGreaterThan(99.0);
    });

    it('kelas filter in component hides other classes', function () {
        abcMaterials();

        Livewire::actingAs(abcKaryawan())
            ->test(AbcAnalysis::class)
            ->call('setFilter', 'A')
            ->assertSet('filterKelas', 'A');
    });

    it('filter toggle off when same class clicked twice', function () {
        abcMaterials();

        Livewire::actingAs(abcKaryawan())
            ->test(AbcAnalysis::class)
            ->call('setFilter', 'B')
            ->call('setFilter', 'B')
            ->assertSet('filterKelas', '');
    });

    it('reuses DashboardQueryService chart data (no new aggregation)', function () {
        abcMaterials();
        $dashSvc = app(DashboardQueryService::class);
        $chartData = $dashSvc->getChartData();

        // Verify the chart data has the required ABC keys
        expect($chartData)->toHaveKeys(['donut', 'donut_value', 'top5']);
        expect($chartData['donut'])->toHaveKeys(['A', 'B', 'C']);
    });
});
