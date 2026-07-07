<?php

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\ProductionEntry;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockMutationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->service = app(StockMutationService::class);
    $this->karyawan = User::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->bb = BahanBaku::factory()->create([
        'stok_saat_ini' => 0,
        'supplier_id' => $this->supplier->id,
    ]);
    $this->fg = FinishedGood::factory()->create([
        'stok_saat_ini' => 0,
    ]);
});

// ─── recordMutation — basic behaviour ─────────────────────────────────────────

describe('StockMutationService::recordMutation', function () {
    it('records a masuk mutation and increments stok_saat_ini', function () {
        $mutation = $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 100.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        expect($mutation)->toBeInstanceOf(MutasiStok::class);
        expect($mutation->jenis_mutasi)->toBe('masuk');
        expect($mutation->jumlah)->toBe('100.00');

        $this->bb->refresh();
        expect((float) $this->bb->stok_saat_ini)->toBe(100.0);
    });

    it('records a keluar mutation and decrements stok_saat_ini', function () {
        // First add stock
        $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 200.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'keluar',
            jumlah: 80.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $this->bb->refresh();
        expect((float) $this->bb->stok_saat_ini)->toBe(120.0);
    });

    it('writes the correct mutation row fields', function () {
        $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 50.0,
            tanggal: '2026-01-15',
            actor: $this->karyawan,
            sumber: 'manual',
            keterangan: 'Test keterangan'
        );

        $this->assertDatabaseHas('mutasi_stok', [
            'bahan_baku_id' => $this->bb->id,
            'finished_goods_id' => null,
            'jenis_mutasi' => 'masuk',
            'jumlah' => 50.00,
            'sumber' => 'manual',
            'po_id' => null,
            'dicatat_oleh' => $this->karyawan->id,
            'keterangan' => 'Test keterangan',
        ]);

        // Verify the date separately (SQLite stores as datetime string)
        $mutation = MutasiStok::where('keterangan', 'Test keterangan')->first();
        expect($mutation->tanggal->format('Y-m-d'))->toBe('2026-01-15');

    });

    it('records mutations for finished goods', function () {
        $mutation = $this->service->recordMutation(
            itemType: 'finished_good',
            itemId: $this->fg->id,
            jenisMutasi: 'masuk',
            jumlah: 20.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $this->fg->refresh();
        expect((float) $this->fg->stok_saat_ini)->toBe(20.0);
        expect($mutation->finished_goods_id)->toBe($this->fg->id);
        expect($mutation->bahan_baku_id)->toBeNull();
    });
});

// ─── Negative stock rejection ─────────────────────────────────────────────────

describe('Negative stock rejection', function () {
    it('rejects keluar when stock is insufficient — hard server-side block', function () {
        // stok = 0, trying to take 50
        expect(fn () => $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'keluar',
            jumlah: 50.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(RuntimeException::class);
    });

    it('does not write any mutation row when stock is insufficient', function () {
        $initialCount = MutasiStok::count();

        try {
            $this->service->recordMutation(
                itemType: 'bahan_baku',
                itemId: $this->bb->id,
                jenisMutasi: 'keluar',
                jumlah: 999.0,
                tanggal: now()->toDateString(),
                actor: $this->karyawan,
            );
        } catch (Throwable) {
            // Expected
        }

        expect(MutasiStok::count())->toBe($initialCount);
    });

    it('leaves stok_saat_ini unchanged when keluar is rejected', function () {
        $this->bb->update(['stok_saat_ini' => 100]);

        try {
            $this->service->recordMutation(
                itemType: 'bahan_baku',
                itemId: $this->bb->id,
                jenisMutasi: 'keluar',
                jumlah: 150.0,
                tanggal: now()->toDateString(),
                actor: $this->karyawan,
            );
        } catch (Throwable) {
        }

        $this->bb->refresh();
        expect((float) $this->bb->stok_saat_ini)->toBe(100.0);
    });
});

// ─── Transaction atomicity ────────────────────────────────────────────────────

describe('Transaction atomicity', function () {
    it('is all-or-nothing: rejected mutation does not partially update stock', function () {
        // Stock = 50. Attempt keluar of 100 (will fail). Stock must stay at 50.
        $this->bb->update(['stok_saat_ini' => 50]);
        $countBefore = MutasiStok::count();

        try {
            $this->service->recordMutation(
                itemType: 'bahan_baku',
                itemId: $this->bb->id,
                jenisMutasi: 'keluar',
                jumlah: 100.0,
                tanggal: now()->toDateString(),
                actor: $this->karyawan,
            );
        } catch (Throwable) {
        }

        $this->bb->refresh();
        expect((float) $this->bb->stok_saat_ini)->toBe(50.0);
        expect(MutasiStok::count())->toBe($countBefore);
    });
});

// ─── Input validation ─────────────────────────────────────────────────────────

describe('Input validation', function () {
    it('rejects jumlah = 0', function () {
        expect(fn () => $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 0.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects negative jumlah', function () {
        expect(fn () => $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: -10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects unknown itemType', function () {
        expect(fn () => $this->service->recordMutation(
            itemType: 'unknown_type',
            itemId: 1,
            jenisMutasi: 'masuk',
            jumlah: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects manual sumber with po_id set', function () {
        expect(fn () => $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
            sumber: 'manual',
            poId: 999,
        ))->toThrow(InvalidArgumentException::class);
    });

    it('rejects po_penerimaan sumber without po_id', function () {
        expect(fn () => $this->service->recordMutation(
            itemType: 'bahan_baku',
            itemId: $this->bb->id,
            jenisMutasi: 'masuk',
            jumlah: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
            sumber: 'po_penerimaan',
            poId: null,
        ))->toThrow(InvalidArgumentException::class);
    });
});

// ─── recordProduction — BOM explosion ────────────────────────────────────────

describe('StockMutationService::recordProduction', function () {
    beforeEach(function () {
        // Set up BOM: FG-001 requires 0.5 kg BB per unit
        $this->bb->update(['stok_saat_ini' => 1000]);

        Bom::create([
            'finished_goods_id' => $this->fg->id,
            'bahan_baku_id' => $this->bb->id,
            'qty_per_unit' => 0.5,
            'satuan' => 'kg',
        ]);
    });

    it('creates N+1 mutations atomically (N bahan_baku keluar + 1 FG masuk)', function () {
        $countBefore = MutasiStok::count();

        $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 10.0, // 10 units × 0.5 kg = 5 kg keluar
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        // 1 (bb keluar) + 1 (fg masuk) = 2 new mutations
        expect(MutasiStok::count())->toBe($countBefore + 2);
    });

    it('correctly decrements bahan_baku stock', function () {
        $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $this->bb->refresh();
        // 1000 - (10 × 0.5) = 995
        expect((float) $this->bb->stok_saat_ini)->toBe(995.0);
    });

    it('correctly increments finished_good stock', function () {
        $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $this->fg->refresh();
        expect((float) $this->fg->stok_saat_ini)->toBe(10.0);
    });

    it('rejects production when BOM has no lines', function () {
        $fgNoBom = FinishedGood::factory()->create(['stok_saat_ini' => 0]);

        expect(fn () => $this->service->recordProduction(
            fg: $fgNoBom,
            jumlahDiproduksi: 5.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(RuntimeException::class);
    });

    it('rejects production when bahan_baku stock is insufficient', function () {
        $this->bb->update(['stok_saat_ini' => 1]); // only 1 kg, need 5 kg (10 × 0.5)

        expect(fn () => $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 10.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        ))->toThrow(RuntimeException::class);
    });

    it('rolls back ALL mutations when production fails mid-transaction', function () {
        $this->bb->update(['stok_saat_ini' => 3]); // enough for 6 units (6 × 0.5) but not 10

        $mutationsBefore = MutasiStok::count();
        $bbStockBefore = (float) $this->bb->fresh()->stok_saat_ini;
        $fgStockBefore = (float) $this->fg->fresh()->stok_saat_ini;

        try {
            $this->service->recordProduction(
                fg: $this->fg,
                jumlahDiproduksi: 10.0, // would need 5 kg, only 3 available
                tanggal: now()->toDateString(),
                actor: $this->karyawan,
            );
        } catch (Throwable) {
        }

        expect(MutasiStok::count())->toBe($mutationsBefore);
        expect((float) $this->bb->fresh()->stok_saat_ini)->toBe($bbStockBefore);
        expect((float) $this->fg->fresh()->stok_saat_ini)->toBe($fgStockBefore);
    });

    it('creates a ProductionEntry header row', function () {
        $entry = $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 5.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        expect($entry)->toBeInstanceOf(ProductionEntry::class);
        expect($entry->finished_goods_id)->toBe($this->fg->id);
        expect((float) $entry->jumlah_diproduksi)->toBe(5.0);
    });

    it('links all mutation rows to the ProductionEntry via production_entry_id', function () {
        $entry = $this->service->recordProduction(
            fg: $this->fg,
            jumlahDiproduksi: 5.0,
            tanggal: now()->toDateString(),
            actor: $this->karyawan,
        );

        $linkedMutations = MutasiStok::where('production_entry_id', $entry->id)->count();
        expect($linkedMutations)->toBe(2); // 1 keluar + 1 masuk
    });
});
