<?php

use App\Livewire\Reports\ReportGenerator;
use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getOwner(): User
{
    $role = Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);

    return User::factory()->create(['role_id' => $role->id]);
}

function getEmployee(): User
{
    $role = Role::firstOrCreate(['slug' => 'karyawan'], ['name' => 'Karyawan']);

    return User::factory()->create(['role_id' => $role->id]);
}

// ─── Authorization Tests ──────────────────────────────────────────────────────

describe('Reports Authorization', function () {
    it('redirects guest to login', function () {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    });

    it('allows Karyawan to access report page', function () {
        $this->actingAs(getEmployee())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeLivewire(ReportGenerator::class);
    });

    it('allows Owner to access report page', function () {
        $this->actingAs(getOwner())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeLivewire(ReportGenerator::class);
    });
});

// ─── Calculation Tests ────────────────────────────────────────────────────────

describe('Reports Calculations', function () {
    it('calculates historical stock level correctly by reversing mutations', function () {
        $bb = BahanBaku::factory()->create(['stok_saat_ini' => 100]);
        $service = app(ReportService::class);

        // Current stock = 100
        // Record mutations after 2026-07-01:
        // - Masuk: 20 on 2026-07-02
        // - Keluar: 15 on 2026-07-03
        // So stock as of 2026-07-01 should be: 100 - 20 (masuk) + 15 (keluar) = 95
        MutasiStok::factory()->create([
            'bahan_baku_id' => $bb->id,
            'jenis_mutasi' => 'masuk',
            'jumlah' => 20,
            'tanggal' => '2026-07-02',
            'sumber' => 'manual',
            'dicatat_oleh' => getEmployee()->id,
        ]);
        MutasiStok::factory()->create([
            'bahan_baku_id' => $bb->id,
            'jenis_mutasi' => 'keluar',
            'jumlah' => 15,
            'tanggal' => '2026-07-03',
            'sumber' => 'manual',
            'dicatat_oleh' => getEmployee()->id,
        ]);

        $calculated = $service->calculateStockAsOf($bb->id, 'bahan_baku', '2026-07-01');
        expect($calculated)->toBe(95.0);
    });

    it('compiles Valuasi Aset details and Grand Total', function () {
        $bb1 = BahanBaku::factory()->create(['stok_saat_ini' => 10, 'harga_satuan' => 1000]);
        $bb2 = BahanBaku::factory()->create(['stok_saat_ini' => 50, 'harga_satuan' => 200]);

        $fg = FinishedGood::factory()->create(['stok_saat_ini' => 5]);

        // Recipe: 2 units of bb1 per fg
        Bom::factory()->create([
            'finished_goods_id' => $fg->id,
            'bahan_baku_id' => $bb1->id,
            'qty_per_unit' => 2.0,
        ]);

        $service = app(ReportService::class);
        $data = $service->generateValuasiAset('2026-07-07');

        // Total raw: (10 * 1000) + (50 * 200) = 10000 + 10000 = 20000
        // Total FG: 5 * (2 * 1000) = 10000
        // Grand total: 30000
        expect($data['total_materials'])->toBe(20000.0)
            ->and($data['total_finished_goods'])->toBe(10000.0)
            ->and($data['grand_total'])->toBe(30000.0);
    });

    it('compiles Supplier Performance metrics', function () {
        $supplier = Supplier::factory()->create(['nama' => 'Test Supplier']);
        $bb = BahanBaku::factory()->create(['supplier_id' => $supplier->id]);

        $karyawan = getEmployee();

        // PO 1: Received, on-time
        PesananPembelian::create([
            'kode_po' => 'PO-001',
            'bahan_baku_id' => $bb->id,
            'supplier_id' => $supplier->id,
            'jumlah' => 100,
            'harga_satuan' => 1000,
            'status' => PesananPembelian::STATUS_DITERIMA,
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'tanggal_pesan' => '2026-06-01',
            'tanggal_terima' => '2026-06-05',
            'estimasi_tiba' => '2026-06-06',
            'dicatat_oleh' => $karyawan->id,
        ]);

        // PO 2: Received, late
        PesananPembelian::create([
            'kode_po' => 'PO-002',
            'bahan_baku_id' => $bb->id,
            'supplier_id' => $supplier->id,
            'jumlah' => 50,
            'harga_satuan' => 1000,
            'status' => PesananPembelian::STATUS_DITERIMA,
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'tanggal_pesan' => '2026-06-02',
            'tanggal_terima' => '2026-06-10', // 8 days LT
            'estimasi_tiba' => '2026-06-08',
            'dicatat_oleh' => $karyawan->id,
        ]);

        $service = app(ReportService::class);
        $result = $service->generatePerformaSupplier('2026-06-01', '2026-06-15');

        $row = collect($result['suppliers'])->firstWhere('nama', 'Test Supplier');
        expect($row)->not->toBeNull()
            ->and($row['total_po'])->toBe(2)
            ->and($row['po_diterima'])->toBe(2)
            ->and($row['po_tepat_waktu'])->toBe(1)
            ->and($row['ontime_rate'])->toBe(50.0)
            ->and($row['avg_lead_time'])->toBe(6.0) // (4 days + 8 days) / 2 = 6 days
            ->and($row['total_nilai'])->toBe(150000.0);
    });

    it('compiles Monthly Stock Mutation counts correctly', function () {
        $bb = BahanBaku::factory()->create(['stok_saat_ini' => 100]);
        $karyawan = getEmployee();

        // Record some mutations
        // initial = 100
        // on 2026-06-05: masuk 30
        // on 2026-06-10: keluar 20
        // range: 2026-06-01 to 2026-06-30
        // start stock = 100 - 30 (masuk after start) + 20 (keluar after start) = 90
        MutasiStok::factory()->create([
            'bahan_baku_id' => $bb->id,
            'jenis_mutasi' => 'masuk',
            'jumlah' => 30,
            'tanggal' => '2026-06-05',
            'sumber' => 'manual',
            'dicatat_oleh' => $karyawan->id,
        ]);
        MutasiStok::factory()->create([
            'bahan_baku_id' => $bb->id,
            'jenis_mutasi' => 'keluar',
            'jumlah' => 20,
            'tanggal' => '2026-06-10',
            'sumber' => 'manual',
            'dicatat_oleh' => $karyawan->id,
        ]);

        $service = app(ReportService::class);
        $result = $service->generateMutasiBulanan('2026-06-01', '2026-06-30');

        $row = collect($result['materials'])->firstWhere('kode', $bb->kode);
        expect($row)->not->toBeNull()
            ->and($row['stok_awal'])->toBe(90.0)
            ->and($row['masuk'])->toBe(30.0)
            ->and($row['keluar'])->toBe(20.0)
            ->and($row['stok_akhir'])->toBe(100.0);
    });

    it('returns empty lists for empty datasets gracefully', function () {
        $service = app(ReportService::class);
        $valuation = $service->generateValuasiAset('2026-07-07');
        expect($valuation['materials'])->toBeEmpty()
            ->and($valuation['finished_goods'])->toBeEmpty()
            ->and($valuation['grand_total'])->toBe(0.0);
    });
});

// ─── Direct Download Tests ───────────────────────────────────────────────────

describe('Reports Dynamic Export', function () {
    it('downloads the generated report as streamed PDF', function () {
        $employee = getEmployee();
        $bb = BahanBaku::factory()->create(['stok_saat_ini' => 10, 'harga_satuan' => 1000]);

        Livewire::actingAs($employee)
            ->test(ReportGenerator::class)
            ->set('reportType', 'valuasi_aset')
            ->set('startDate', '2026-07-01')
            ->set('endDate', '2026-07-07')
            ->call('generate')
            ->assertFileDownloaded('laporan_Valuasi_Aset_2026-07-01_to_2026-07-07.pdf')
            ->assertDispatched('notify', message: 'Laporan berhasil dibuat.', type: 'success');
    });

    it('denies generating with invalid date range where end date is before start date', function () {
        $employee = getEmployee();

        Livewire::actingAs($employee)
            ->test(ReportGenerator::class)
            ->set('reportType', 'valuasi_aset')
            ->set('startDate', '2026-07-07')
            ->set('endDate', '2026-07-01')
            ->call('generate')
            ->assertHasErrors(['endDate']);
    });
});
