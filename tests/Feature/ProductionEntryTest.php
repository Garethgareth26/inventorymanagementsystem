<?php

namespace Tests\Feature;

use App\Livewire\Production\CreateProduction;
use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\ProductionEntry;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    private BahanBaku $bahanBaku;

    private FinishedGood $finishedGood;

    protected function setUp(): void
    {
        parent::setUp();

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

        $supplier = Supplier::create([
            'kode' => 'SUP-001',
            'nama' => 'Supplier Test',
            'alamat' => 'Alamat',
            'kontak' => '0812',
            'is_active' => true,
        ]);

        $this->bahanBaku = BahanBaku::create([
            'kode' => 'BB-001',
            'nama' => 'Tepung Terigu',
            'satuan' => 'kg',
            'stok_saat_ini' => 10.0,
            'supplier_id' => $supplier->id,
            'harga_satuan' => 10000.0,
            'lead_time_hari' => 1,
        ]);

        $this->finishedGood = FinishedGood::create([
            'kode' => 'FG-001',
            'nama' => 'Roti Manis',
            'satuan' => 'pcs',
            'stok_saat_ini' => 5.0,
        ]);

        Bom::create([
            'finished_goods_id' => $this->finishedGood->id,
            'bahan_baku_id' => $this->bahanBaku->id,
            'qty_per_unit' => 0.05, // 0.05 kg per 1 Roti Manis
            'satuan' => 'kg',
        ]);
    }

    /** @test */
    public function karyawan_can_record_successful_production()
    {
        $this->actingAs($this->karyawan);

        // Produce 100 Roti Manis -> demands 100 * 0.05 = 5 kg Tepung Terigu. Available: 10 kg
        Livewire::test(CreateProduction::class)
            ->set('finished_goods_id', $this->finishedGood->id)
            ->set('jumlah_diproduksi', 100.0)
            ->set('tanggal_produksi', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('production.index'));

        // Assert stock updates
        $this->assertEquals(5.0, $this->bahanBaku->fresh()->stok_saat_ini);
        $this->assertEquals(105.0, $this->finishedGood->fresh()->stok_saat_ini);

        // Assert ProductionEntry row created
        $this->assertDatabaseHas('production_entries', [
            'finished_goods_id' => $this->finishedGood->id,
            'jumlah_diproduksi' => 100.0,
        ]);

        // Assert mutations created
        $this->assertDatabaseHas('mutasi_stok', [
            'bahan_baku_id' => $this->bahanBaku->id,
            'jenis_mutasi' => MutasiStok::JENIS_KELUAR,
            'jumlah' => 5.0,
            'sumber' => MutasiStok::SUMBER_PRODUKSI,
        ]);

        $this->assertDatabaseHas('mutasi_stok', [
            'finished_goods_id' => $this->finishedGood->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 100.0,
            'sumber' => MutasiStok::SUMBER_PRODUKSI,
        ]);
    }

    /** @test */
    public function production_is_blocked_if_any_ingredient_stock_is_insufficient()
    {
        $this->actingAs($this->karyawan);

        // Produce 300 Roti Manis -> demands 300 * 0.05 = 15 kg Tepung Terigu. Available: 10 kg
        Livewire::test(CreateProduction::class)
            ->set('finished_goods_id', $this->finishedGood->id)
            ->set('jumlah_diproduksi', 300.0)
            ->set('tanggal_produksi', now()->toDateString())
            ->call('save')
            ->assertHasErrors('jumlah_diproduksi');

        // Stock unchanged
        $this->assertEquals(10.0, $this->bahanBaku->fresh()->stok_saat_ini);
        $this->assertEquals(5.0, $this->finishedGood->fresh()->stok_saat_ini);
    }

    /** @test */
    public function owner_cannot_record_production()
    {
        $this->actingAs($this->owner);

        $this->get(route('production.create'))->assertForbidden();
    }
}
