<?php

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Full seed test ───────────────────────────────────────────────────────────

describe('DomainSeeder', function () {
    beforeEach(function () {
        // Run the full seeder stack via Artisan
        $this->artisan('db:seed', ['--class' => 'DomainSeeder'])->assertSuccessful();
    });

    // ─── Volume checks ────────────────────────────────────────────────────────

    it('seeds exactly 10 suppliers', function () {
        expect(Supplier::count())->toBe(10);
    });

    it('seeds exactly 10 bahan_baku records', function () {
        expect(BahanBaku::count())->toBe(10);
    });

    it('seeds exactly 10 finished goods', function () {
        expect(FinishedGood::count())->toBe(10);
    });

    it('seeds at least 20 BOM lines across all finished goods', function () {
        expect(Bom::count())->toBeGreaterThanOrEqual(20);
    });

    it('seeds at least 100 purchase orders', function () {
        // UI Spec requires ~132 POs
        expect(PesananPembelian::count())->toBeGreaterThanOrEqual(100);
    });

    it('seeds 12 months of mutation history', function () {
        $oldestRaw = MutasiStok::min('tanggal');
        $oldest = substr((string) $oldestRaw, 0, 10); // Normalise to 'Y-m-d'
        $cutoff = now()->subMonths(12)->startOfMonth()->toDateString();

        expect($oldestRaw)->not->toBeNull();
        // The oldest mutation should be on or before the 12-month cutoff start
        expect($oldest <= $cutoff)->toBeTrue(
            "Oldest mutation ({$oldest}) should be on or before the 12-month window start ({$cutoff})"
        );
    });

    // ─── FK / constraint checks ───────────────────────────────────────────────

    it('all bahan_baku records have a supplier_id that exists', function () {
        $orphans = BahanBaku::whereNotNull('supplier_id')
            ->whereDoesntHave('supplier')
            ->count();

        expect($orphans)->toBe(0);
    });

    it('all BOM lines have valid finished_goods_id and bahan_baku_id', function () {
        $orphanFg = Bom::whereDoesntHave('finishedGood')->count();
        $orphanBb = Bom::whereDoesntHave('bahanBaku')->count();

        expect($orphanFg)->toBe(0)->and($orphanBb)->toBe(0);
    });

    it('all mutasi_stok rows have exactly one of bahan_baku_id or finished_goods_id set', function () {
        // Both null
        $bothNull = MutasiStok::whereNull('bahan_baku_id')
            ->whereNull('finished_goods_id')
            ->count();

        // Both set
        $bothSet = MutasiStok::whereNotNull('bahan_baku_id')
            ->whereNotNull('finished_goods_id')
            ->count();

        expect($bothNull)->toBe(0)
            ->and($bothSet)->toBe(0);
    });

    it('po_penerimaan mutations all have a valid po_id', function () {
        $invalid = MutasiStok::where('sumber', 'po_penerimaan')
            ->whereNull('po_id')
            ->count();

        expect($invalid)->toBe(0);
    });

    // ─── stok_saat_ini consistency check ─────────────────────────────────────

    it('stok_saat_ini for all bahan_baku is non-negative after seeding', function () {
        $negative = BahanBaku::where('stok_saat_ini', '<', 0)->count();

        expect($negative)->toBe(0, 'No bahan_baku should have negative stock after seeding');
    });

    it('stok_saat_ini for all finished_goods is non-negative after seeding', function () {
        $negative = FinishedGood::where('stok_saat_ini', '<', 0)->count();

        expect($negative)->toBe(0, 'No finished_good should have negative stock after seeding');
    });

    // ─── SystemSettings defaults ──────────────────────────────────────────────

    it('z_factor setting is seeded with value 1.65', function () {
        expect(SystemSettings::getFloat('z_factor'))->toBe(1.65);
    });

    it('abc_threshold_a is seeded with value 80', function () {
        expect(SystemSettings::getInt('abc_threshold_a'))->toBe(80);
    });

    it('abc_threshold_b is seeded with value 95', function () {
        expect(SystemSettings::getInt('abc_threshold_b'))->toBe(95);
    });

    it('historical_window is seeded with value 12', function () {
        expect(SystemSettings::getInt('historical_window'))->toBe(12);
    });

    it('biaya_pesan is seeded with value 75000', function () {
        expect(SystemSettings::getFloat('biaya_pesan'))->toBe(75000.0);
    });

    // ─── ABC class distribution ───────────────────────────────────────────────

    it('inventory parameters are seeded for all 10 bahan_baku', function () {
        expect(InventoryParameter::count())->toBe(10);
    });

    it('all inventory parameters have non-null eoq, safety_stock, reorder_point when demand > 0', function () {
        $withDemand = InventoryParameter::where('kebutuhan_tahunan', '>', 0)->get();

        foreach ($withDemand as $param) {
            expect($param->eoq)->not->toBeNull("EOQ should be computed for param {$param->id}");
            expect($param->safety_stock)->not->toBeNull();
            expect($param->reorder_point)->not->toBeNull();
        }
    });

    // ─── User seeding ─────────────────────────────────────────────────────────

    it('seeds at least one owner user and one karyawan user', function () {
        $owner = User::whereHas('role', fn ($q) => $q->where('slug', 'owner'))->first();
        $karyawan = User::whereHas('role', fn ($q) => $q->where('slug', 'karyawan'))->first();

        expect($owner)->not->toBeNull();
        expect($karyawan)->not->toBeNull();
    });
});
