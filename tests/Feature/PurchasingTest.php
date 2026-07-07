<?php

namespace Tests\Feature;

use App\Livewire\Purchasing\CreatePurchaseOrder;
use App\Livewire\Purchasing\PurchaseOrderDetail;
use App\Models\BahanBaku;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $karyawan;

    private Supplier $supplier;

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

        $this->supplier = Supplier::create([
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
            'supplier_id' => $this->supplier->id,
            'harga_satuan' => 10000.0,
            'lead_time_hari' => 2,
        ]);
    }

    /** @test */
    public function guests_are_redirected_to_login_on_procurement()
    {
        $this->get(route('pesanan_pembelian.index'))->assertRedirect(route('login'));
        $this->get(route('pesanan_pembelian.create'))->assertRedirect(route('login'));
    }

    /** @test */
    public function owner_can_view_but_cannot_create_po()
    {
        $this->actingAs($this->owner);

        $this->get(route('pesanan_pembelian.index'))->assertOk();
        $this->get(route('pesanan_pembelian.create'))->assertForbidden();
    }

    /** @test */
    public function karyawan_can_access_create_po_and_save_rutin_po()
    {
        $this->actingAs($this->karyawan);

        $this->get(route('pesanan_pembelian.create'))->assertOk();

        Livewire::test(CreatePurchaseOrder::class)
            ->set('bahan_baku_id', $this->bahanBaku->id)
            ->set('jenis', PesananPembelian::JENIS_RUTIN)
            ->set('jumlah', 50.0)
            ->set('harga_satuan', 10000.0)
            ->set('tanggal_pesan', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('pesanan_pembelian.index'));

        $this->assertDatabaseHas('pesanan_pembelian', [
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'jumlah' => 50.0,
            'harga_satuan' => 10000.0,
            'status' => PesananPembelian::STATUS_MENUNGGU,
        ]);
    }

    /** @test */
    public function darurat_po_calculates_emergency_surcharge()
    {
        $this->actingAs($this->karyawan);

        Livewire::test(CreatePurchaseOrder::class)
            ->set('bahan_baku_id', $this->bahanBaku->id)
            ->set('jenis', PesananPembelian::JENIS_DARURAT)
            ->set('jumlah', 10.0)
            ->set('harga_satuan', 10000.0) // 10000 dasar -> expected 12000 after +20%
            ->set('tanggal_pesan', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pesanan_pembelian', [
            'jenis' => PesananPembelian::JENIS_DARURAT,
            'harga_satuan' => 12000.0,
        ]);
    }

    /** @test */
    public function po_status_workflow_transitions_correctly()
    {
        $this->actingAs($this->karyawan);

        $po = PesananPembelian::create([
            'kode_po' => 'PO-00001',
            'bahan_baku_id' => $this->bahanBaku->id,
            'supplier_id' => $this->supplier->id,
            'jumlah' => 15.0,
            'harga_satuan' => 10000.0,
            'status' => PesananPembelian::STATUS_MENUNGGU,
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'tanggal_pesan' => now()->toDateString(),
            'estimasi_tiba' => now()->addDays(2)->toDateString(),
            'dicatat_oleh' => $this->karyawan->id,
        ]);

        // 1. Transition Menunggu -> Dalam Proses
        Livewire::test(PurchaseOrderDetail::class, ['po' => $po])
            ->call('processOrder')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals(PesananPembelian::STATUS_DALAM_PROSES, $po->status);

        // 2. Transition Dalam Proses -> Diterima (finalizes receipt, increments stock)
        $this->assertEquals(10.0, $this->bahanBaku->fresh()->stok_saat_ini);

        Livewire::test(PurchaseOrderDetail::class, ['po' => $po])
            ->set('tanggal_terima', now()->toDateString())
            ->call('receiveOrder')
            ->assertHasNoErrors();

        $po->refresh();
        $this->assertEquals(PesananPembelian::STATUS_DITERIMA, $po->status);
        $this->assertEquals(25.0, $this->bahanBaku->fresh()->stok_saat_ini);

        // Assert mutation was created
        $this->assertDatabaseHas('mutasi_stok', [
            'bahan_baku_id' => $this->bahanBaku->id,
            'jenis_mutasi' => MutasiStok::JENIS_MASUK,
            'jumlah' => 15.0,
            'sumber' => MutasiStok::SUMBER_PO_PENERIMAAN,
            'po_id' => $po->id,
        ]);
    }

    /** @test */
    public function cannot_advance_status_backward_or_cancel_processed_po()
    {
        $this->actingAs($this->karyawan);

        $po = PesananPembelian::create([
            'kode_po' => 'PO-00002',
            'bahan_baku_id' => $this->bahanBaku->id,
            'supplier_id' => $this->supplier->id,
            'jumlah' => 15.0,
            'harga_satuan' => 10000.0,
            'status' => PesananPembelian::STATUS_DALAM_PROSES,
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'tanggal_pesan' => now()->toDateString(),
            'estimasi_tiba' => now()->addDays(2)->toDateString(),
            'dicatat_oleh' => $this->karyawan->id,
        ]);

        // Attempting to cancel PO in Dalam Proses state is blocked
        Livewire::test(PurchaseOrderDetail::class, ['po' => $po])
            ->call('cancelOrder')
            ->assertDispatched('notify', function ($name, $detail) {
                return str_contains($detail['message'], 'Hanya pesanan berstatus Menunggu');
            });

        $this->assertEquals(PesananPembelian::STATUS_DALAM_PROSES, $po->fresh()->status);
    }
}
