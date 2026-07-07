<?php

use App\Livewire\Optimization\EoqOverview;
use App\Livewire\Optimization\EoqSimulation;
use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function eoqOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function eoqKaryawan(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

function eoqMaterial(): BahanBaku
{
    $bb = BahanBaku::factory()->create([
        'harga_satuan' => 9500,   // H = 9500 × 0.20 = 1900; EOQ = sqrt(2×D×S/H) ≈ 280.976
        'lead_time_hari' => 7,
        'stok_saat_ini' => 100,
    ]);

    InventoryParameter::factory()->create([
        'bahan_baku_id' => $bb->id,
        'biaya_pesan' => 75000,
        'biaya_simpan_persen' => 0.20,
        'z_factor' => 1.65,
        'historical_window_months' => 12,
        'eoq' => 281.0,
        'safety_stock' => 24.75,
        'reorder_point' => 49.75,
        'kebutuhan_tahunan' => 1000.0,
        'standar_deviasi_harian' => 5.0,
    ]);

    return $bb;
}

// ─── EOQ Overview ─────────────────────────────────────────────────────────────

describe('EoqOverview', function () {
    it('redirects guest to login', function () {
        $this->get(route('eoq.index'))->assertRedirect(route('login'));
    });

    it('allows Owner access to EOQ overview', function () {
        $this->actingAs(eoqOwner())
            ->get(route('eoq.index'))
            ->assertOk();
    });

    it('allows Karyawan access to EOQ overview', function () {
        $this->actingAs(eoqKaryawan())
            ->get(route('eoq.index'))
            ->assertOk();
    });

    it('renders Livewire component on overview route', function () {
        $this->actingAs(eoqOwner())
            ->get(route('eoq.index'))
            ->assertSeeLivewire(EoqOverview::class);
    });

    it('shows material in overview list', function () {
        $bb = eoqMaterial();
        Livewire::actingAs(eoqKaryawan())
            ->test(EoqOverview::class)
            ->assertSee($bb->kode)
            ->assertSee($bb->nama);
    });

    it('search filters materials by name', function () {
        eoqMaterial();
        $other = BahanBaku::factory()->create(['nama' => 'XYZXYZ_Material_Unik']);
        InventoryParameter::factory()->create(['bahan_baku_id' => $other->id]);

        Livewire::actingAs(eoqKaryawan())
            ->test(EoqOverview::class)
            ->set('search', 'XYZXYZ')
            ->assertSee('XYZXYZ_Material_Unik');
    });
});

// ─── EOQ Simulation ─────────────────────────────────────────────────────────

describe('EoqSimulation', function () {
    it('redirects guest to simulation page login', function () {
        $bb = eoqMaterial();
        $this->get(route('eoq.show', $bb))->assertRedirect(route('login'));
    });

    it('allows Owner to view EOQ simulation page', function () {
        $bb = eoqMaterial();
        $this->actingAs(eoqOwner())
            ->get(route('eoq.show', $bb))
            ->assertOk();
    });

    it('allows Karyawan to view EOQ simulation page', function () {
        $bb = eoqMaterial();
        $this->actingAs(eoqKaryawan())
            ->get(route('eoq.show', $bb))
            ->assertOk();
    });

    it('simulation produces correct EOQ close to CalculationEngine output', function () {
        $bb = eoqMaterial();

        Livewire::actingAs(eoqKaryawan())
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 1000)
            ->set('biayaPesan', 75000)
            ->set('biayaSimpanPct', 20)
            ->call('simulate')
            ->assertSet('simulated', true)
            ->assertSet('simEoq', fn ($v) => abs($v - 280.976) < 1.0);
    });

    it('resetToDefaults repopulates from SystemSettings', function () {
        $bb = eoqMaterial();

        Livewire::actingAs(eoqKaryawan())
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('biayaPesan', 99999)
            ->call('resetToDefaults')
            ->assertSet('biayaPesan', 75000.0);
    });
});
