<?php

namespace Tests\Feature;

use App\Livewire\MasterData\FinishedGoods;
use App\Livewire\MasterData\Suppliers;
use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BomService;
use App\Services\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    private Supplier $supplier;

    private BahanBaku $bahanBaku;

    private FinishedGood $finishedGood;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & system settings
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

        $this->supplier = Supplier::create([
            'kode' => 'SUP-TEST-1',
            'nama' => 'Supplier Test PT',
            'alamat' => 'Alamat PT 1',
            'kontak' => 'Kontak 1',
            'is_active' => true,
        ]);

        $this->bahanBaku = BahanBaku::create([
            'kode' => 'BB-TEST-1',
            'nama' => 'Bahan Baku Test',
            'satuan' => 'kg',
            'stok_saat_ini' => 10.0,
            'supplier_id' => $this->supplier->id,
            'harga_satuan' => 15000.0,
            'lead_time_hari' => 3,
        ]);

        $this->finishedGood = FinishedGood::create([
            'kode' => 'FG-TEST-1',
            'nama' => 'Barang Jadi Test',
            'satuan' => 'pcs',
            'stok_saat_ini' => 5.0,
        ]);

        // Seed settings
        SystemSettings::set('z_factor', '1.65');
        SystemSettings::set('biaya_pesan', '75000');
        SystemSettings::set('biaya_simpan_persen', '0.20');
        SystemSettings::set('historical_window_months', '12');
    }

    // ─── Supplier CRUD Tests ───────────────────────────────────────────────

    /** @test */
    public function karyawan_can_create_and_edit_supplier()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(Suppliers::class)
            ->call('openCreateModal')
            ->set('kode', 'SUP-NEW')
            ->set('nama', 'PT Supplier Baru')
            ->set('alamat', 'Jakarta')
            ->set('kontak', '0812')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('suppliers', ['kode' => 'SUP-NEW', 'nama' => 'PT Supplier Baru']);
    }

    /** @test */
    public function owner_cannot_create_or_delete_supplier()
    {
        $this->actingAs($this->owner);

        Livewire::test(Suppliers::class)
            ->call('openCreateModal')
            ->assertStatus(403);
    }

    /** @test */
    public function cannot_delete_supplier_if_referenced_in_bahan_baku()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(Suppliers::class)
            ->set('confirmingDeletionId', $this->supplier->id)
            ->call('delete')
            ->assertDispatched('notify', function ($name, $detail) {
                return str_contains($detail['message'], 'Gagal menghapus');
            });

        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id]);
    }

    // ─── BahanBaku CRUD Tests ──────────────────────────────────────────────

    /** @test */
    public function creating_bahan_baku_initializes_parameters_and_stock_mutation()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(\App\Livewire\MasterData\BahanBaku::class)
            ->call('openCreateModal')
            ->set('kode', 'BB-NEW')
            ->set('nama', 'Bahan Baku Baru')
            ->set('satuan', 'pcs')
            ->set('supplier_id', $this->supplier->id)
            ->set('harga_satuan', 5000.0)
            ->set('lead_time_hari', 3)
            ->set('stok_saat_ini', 20.0) // Initial stock
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $bb = BahanBaku::where('kode', 'BB-NEW')->first();
        $this->assertNotNull($bb);

        // Parameters auto-initialized
        $this->assertDatabaseHas('inventory_parameters', [
            'bahan_baku_id' => $bb->id,
            'biaya_pesan' => 75000.0,
            'biaya_simpan_persen' => 0.20,
            'z_factor' => 1.65,
        ]);

        // Stock initial mutation seeded
        $this->assertDatabaseHas('mutasi_stok', [
            'bahan_baku_id' => $bb->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 20.0,
            'sumber' => MutasiStok::SUMBER_MANUAL,
        ]);
    }

    /** @test */
    public function cannot_delete_bahan_baku_referenced_in_bom()
    {
        $this->actingAs($this->karyawan);

        // Define a BOM linking finished good to our raw material
        Bom::create([
            'finished_goods_id' => $this->finishedGood->id,
            'bahan_baku_id' => $this->bahanBaku->id,
            'qty_per_unit' => 2.0,
            'satuan' => $this->bahanBaku->satuan,
        ]);

        Livewire::test(\App\Livewire\MasterData\BahanBaku::class)
            ->set('confirmingDeletionId', $this->bahanBaku->id)
            ->call('delete')
            ->assertDispatched('notify', function ($name, $detail) {
                return str_contains($detail['message'], 'Gagal menghapus');
            });

        $this->assertDatabaseHas('bahan_baku', ['id' => $this->bahanBaku->id]);
    }

    // ─── Finished Goods CRUD Tests ────────────────────────────────────────

    /** @test */
    public function creating_finished_good_seeds_stock_mutation()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(FinishedGoods::class)
            ->call('openCreateModal')
            ->set('kode', 'FG-NEW')
            ->set('nama', 'Finished Good Baru')
            ->set('satuan', 'box')
            ->set('stok_saat_ini', 15.0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $fg = FinishedGood::where('kode', 'FG-NEW')->first();
        $this->assertNotNull($fg);

        // Stock mutation initial seed
        $this->assertDatabaseHas('mutasi_stok', [
            'finished_goods_id' => $fg->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 15.0,
            'sumber' => MutasiStok::SUMBER_MANUAL,
        ]);
    }

    // ─── BOM Editor Tests ──────────────────────────────────────────────────

    /** @test */
    public function bom_service_transactional_saves_recipes_successfully()
    {
        $actor = $this->karyawan;

        $bomService = app(BomService::class);
        $ingredients = [
            ['bahan_baku_id' => $this->bahanBaku->id, 'qty_per_unit' => 2.5],
        ];

        $bomService->saveBom($this->finishedGood, $ingredients, $actor);

        $this->assertDatabaseHas('bom', [
            'finished_goods_id' => $this->finishedGood->id,
            'bahan_baku_id' => $this->bahanBaku->id,
            'qty_per_unit' => 2.5,
        ]);
    }

    /** @test */
    public function bom_service_rejects_duplicate_ingredients()
    {
        $this->expectException(\InvalidArgumentException::class);

        $bomService = app(BomService::class);
        $ingredients = [
            ['bahan_baku_id' => $this->bahanBaku->id, 'qty_per_unit' => 2.5],
            ['bahan_baku_id' => $this->bahanBaku->id, 'qty_per_unit' => 1.0], // Duplicate!
        ];

        $bomService->saveBom($this->finishedGood, $ingredients, $this->karyawan);
    }
}
