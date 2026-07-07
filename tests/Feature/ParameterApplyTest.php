<?php

use App\Livewire\Optimization\EoqSimulation;
use App\Livewire\Optimization\SafetyStockSimulation;
use App\Models\AuditLog;
use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function applyOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function applyKaryawan(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

function applyMaterial(): BahanBaku
{
    $bb = BahanBaku::factory()->create([
        'harga_satuan' => 10000,
        'lead_time_hari' => 7,
        'stok_saat_ini' => 100,
    ]);

    InventoryParameter::factory()->create([
        'bahan_baku_id' => $bb->id,
        'biaya_pesan' => 75000,
        'biaya_simpan_persen' => 0.20,
        'z_factor' => 1.65,
        'historical_window_months' => 12,
        'eoq' => 150.0,
        'safety_stock' => 20.0,
        'reorder_point' => 40.0,
        'kebutuhan_tahunan' => 1000.0,
        'standar_deviasi_harian' => 5.0,
        'last_applied_by' => null,
        'last_applied_at' => null,
    ]);

    return $bb;
}

// ─── Owner cannot Apply ───────────────────────────────────────────────────────

describe('Owner cannot Apply', function () {
    it('Owner calling apply on EoqSimulation throws AuthorizationException', function () {
        $owner = applyOwner();
        $bb = applyMaterial();

        Livewire::actingAs($owner)
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 1000)
            ->set('biayaPesan', 75000)
            ->set('biayaSimpanPct', 20)
            ->call('apply')
            ->assertForbidden();
    });

    it('Owner calling apply on SafetyStockSimulation throws AuthorizationException', function () {
        $owner = applyOwner();
        $bb = applyMaterial();

        Livewire::actingAs($owner)
            ->test(SafetyStockSimulation::class, ['bahanBaku' => $bb])
            ->set('zFactor', 1.65)
            ->set('leadTimeHari', 7)
            ->set('windowMonths', 12)
            ->call('apply')
            ->assertForbidden();
    });
});

// ─── Karyawan can Apply ───────────────────────────────────────────────────────

describe('Karyawan Apply flow', function () {
    it('Employee Apply persists InventoryParameter with new values', function () {
        $karyawan = applyKaryawan();
        $bb = applyMaterial();

        Livewire::actingAs($karyawan)
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 1200)
            ->set('biayaPesan', 80000)
            ->set('biayaSimpanPct', 20)
            ->call('apply');

        $param = InventoryParameter::where('bahan_baku_id', $bb->id)->firstOrFail();

        expect((float) $param->kebutuhan_tahunan)->toBe(1200.0)
            ->and((float) $param->biaya_pesan)->toBe(80000.0)
            ->and($param->last_applied_by)->toBe($karyawan->id);
    });

    it('Employee Apply creates AuditLog row with action parameter.apply', function () {
        $karyawan = applyKaryawan();
        $bb = applyMaterial();

        Livewire::actingAs($karyawan)
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 1000)
            ->set('biayaPesan', 75000)
            ->set('biayaSimpanPct', 20)
            ->call('apply');

        expect(
            AuditLog::where('action', 'parameter.apply')
                ->where('user_id', $karyawan->id)
                ->exists()
        )->toBeTrue();
    });

    it('Employee Apply invalidates the dashboard cache', function () {
        $karyawan = applyKaryawan();
        $bb = applyMaterial();

        // Seed a fake cache value
        Cache::put('dashboard:metrics:owner', ['dummy' => true], 3600);
        Cache::put('dashboard:metrics:employee', ['dummy' => true], 3600);
        Cache::put('dashboard:metrics:charts', ['dummy' => true], 3600);

        Livewire::actingAs($karyawan)
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 1000)
            ->set('biayaPesan', 75000)
            ->set('biayaSimpanPct', 20)
            ->call('apply');

        expect(Cache::has('dashboard:metrics:owner'))->toBeFalse()
            ->and(Cache::has('dashboard:metrics:employee'))->toBeFalse()
            ->and(Cache::has('dashboard:metrics:charts'))->toBeFalse();
    });

    it('Apply on Safety Stock persists updated z_factor and window', function () {
        $karyawan = applyKaryawan();
        $bb = applyMaterial();

        Livewire::actingAs($karyawan)
            ->test(SafetyStockSimulation::class, ['bahanBaku' => $bb])
            ->set('zFactor', 2.33)
            ->set('leadTimeHari', 5)
            ->set('windowMonths', 6)
            ->call('apply');

        $param = InventoryParameter::where('bahan_baku_id', $bb->id)->firstOrFail();
        expect((float) $param->z_factor)->toBe(2.33)
            ->and((int) $param->historical_window_months)->toBe(6);
    });

    it('Apply records old and new values in AuditLog', function () {
        $karyawan = applyKaryawan();
        $bb = applyMaterial();

        Livewire::actingAs($karyawan)
            ->test(EoqSimulation::class, ['bahanBaku' => $bb])
            ->set('annualDemand', 999)
            ->set('biayaPesan', 75000)
            ->set('biayaSimpanPct', 20)
            ->call('apply');

        $log = AuditLog::where('action', 'parameter.apply')
            ->where('user_id', $karyawan->id)
            ->latest()
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->old_values)->not->toBeNull()
            ->and($log->new_values)->not->toBeNull();
    });
});
