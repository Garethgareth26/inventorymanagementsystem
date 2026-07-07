<?php

namespace Tests\Feature;

use App\Livewire\Inventory\StockAdjustment;
use App\Models\BahanBaku;
use App\Models\MutasiStok;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    private BahanBaku $bahanBaku;

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
            'nama' => 'Bahan Baku Test',
            'satuan' => 'kg',
            'stok_saat_ini' => 10.0,
            'supplier_id' => $supplier->id,
            'harga_satuan' => 10000.0,
            'lead_time_hari' => 1,
        ]);
    }

    /** @test */
    public function guests_and_owner_are_blocked_from_stock_adjustment()
    {
        $this->get(route('stock_adjustment.create'))->assertRedirect(route('login'));

        $this->actingAs($this->owner)
            ->get(route('stock_adjustment.create'))
            ->assertForbidden();
    }

    /** @test */
    public function karyawan_can_access_adjustment_form_and_save_positive_adjustment()
    {
        $this->actingAs($this->karyawan);

        $this->get(route('stock_adjustment.create'))->assertOk();

        Livewire::test(StockAdjustment::class)
            ->set('item_type', 'bahan_baku')
            ->set('item_id', $this->bahanBaku->id)
            ->set('jenis_mutasi', MutasiStok::JENIS_MASUK)
            ->set('jumlah', 5.0)
            ->set('keterangan', 'Koreksi stok masuk')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(15.0, $this->bahanBaku->fresh()->stok_saat_ini);

        $this->assertDatabaseHas('mutasi_stok', [
            'bahan_baku_id' => $this->bahanBaku->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 5.0,
            'sumber' => MutasiStok::SUMBER_MANUAL,
        ]);
    }

    /** @test */
    public function stock_adjustment_prevents_negative_stock_on_keluar()
    {
        $this->actingAs($this->karyawan);

        // Attempting to deduct 15 kg when only 10 kg exists is blocked
        Livewire::test(StockAdjustment::class)
            ->set('item_type', 'bahan_baku')
            ->set('item_id', $this->bahanBaku->id)
            ->set('jenis_mutasi', MutasiStok::JENIS_KELUAR)
            ->set('jumlah', 15.0)
            ->set('keterangan', 'Koreksi stok keluar berlebih')
            ->call('save')
            ->assertHasErrors('jumlah');

        // Stock unchanged
        $this->assertEquals(10.0, $this->bahanBaku->fresh()->stok_saat_ini);
    }

    /** @test */
    public function stock_adjustment_shows_advisory_alert_if_quantity_exceeds_threshold()
    {
        $this->actingAs($this->karyawan);

        // Seed some minor historical movement so average monthly is positive but very small
        MutasiStok::create([
            'bahan_baku_id' => $this->bahanBaku->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 1.2, // total annual = 1.2 -> avg monthly = 0.1
            'tanggal' => now()->toDateString(),
            'sumber' => MutasiStok::SUMBER_MANUAL,
            'dicatat_oleh' => $this->karyawan->id,
        ]);

        // Attempting to adjust 5.0 (which is > 3 * 0.1 = 0.3) triggers warning flag
        Livewire::test(StockAdjustment::class)
            ->set('item_type', 'bahan_baku')
            ->set('item_id', $this->bahanBaku->id)
            ->set('jenis_mutasi', MutasiStok::JENIS_MASUK)
            ->set('jumlah', 5.0)
            ->set('keterangan', 'Koreksi penyesuaian besar')
            ->assertSet('showAdvisoryWarning', true)
            // Saving without checking confirmation box returns validation error
            ->call('save')
            ->assertHasErrors('jumlah');
    }
}
