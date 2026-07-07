<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionEntryConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $karyawan;

    private BahanBaku $materialA;

    private BahanBaku $materialB;

    private FinishedGood $finishedGood;

    protected function setUp(): void
    {
        parent::setUp();

        $karyawanRole = Role::create(['name' => 'Karyawan', 'slug' => 'karyawan']);

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

        // Create materials with specific IDs or order
        $this->materialA = BahanBaku::create([
            'kode' => 'BB-100',
            'nama' => 'Material 100',
            'satuan' => 'kg',
            'stok_saat_ini' => 100.0,
            'supplier_id' => $supplier->id,
            'harga_satuan' => 5000.0,
            'lead_time_hari' => 1,
        ]);

        $this->materialB = BahanBaku::create([
            'kode' => 'BB-200',
            'nama' => 'Material 200',
            'satuan' => 'kg',
            'stok_saat_ini' => 100.0,
            'supplier_id' => $supplier->id,
            'harga_satuan' => 5000.0,
            'lead_time_hari' => 1,
        ]);

        $this->finishedGood = FinishedGood::create([
            'kode' => 'FG-100',
            'nama' => 'FG 100',
            'satuan' => 'pcs',
            'stok_saat_ini' => 0.0,
        ]);

        // Add BOM recipe with both ingredients in arbitrary order
        Bom::create([
            'finished_goods_id' => $this->finishedGood->id,
            'bahan_baku_id' => $this->materialB->id,
            'qty_per_unit' => 1.0,
            'satuan' => 'kg',
        ]);

        Bom::create([
            'finished_goods_id' => $this->finishedGood->id,
            'bahan_baku_id' => $this->materialA->id,
            'qty_per_unit' => 1.0,
            'satuan' => 'kg',
        ]);
    }

    /** @test */
    public function production_service_locks_raw_materials_in_ascending_id_order()
    {
        $productionService = app(ProductionService::class);

        // Track SQL queries
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        // Record production run
        $productionService->recordProduction(
            $this->finishedGood,
            2.0,
            $this->karyawan,
            now()->toDateString(),
            'Test Concurrency Lock Order'
        );

        // Find the locking query on the bahan_baku table
        $lockQuery = null;
        foreach ($queries as $sql) {
            // Check if it is selecting from bahan_baku and has an order by clause
            if ((str_contains($sql, 'select * from "bahan_baku"') || str_contains($sql, 'select * from `bahan_baku`'))
                && (str_contains(strtolower($sql), 'order by') || str_contains(strtolower($sql), 'orderby'))
            ) {
                $lockQuery = $sql;
                break;
            }
        }

        $this->assertNotNull($lockQuery, 'Could not find the bahan_baku locking query in: '.implode("\n", $queries));

        // Assert that the locking query orders ascendingly by id
        $hasAscendingOrder = str_contains(strtolower($lockQuery), 'order by "id" asc')
            || str_contains(strtolower($lockQuery), 'order by `id` asc')
            || str_contains(strtolower($lockQuery), 'order by "id"') // Default order is ASC
            || str_contains(strtolower($lockQuery), 'order by `id`')
            || str_contains(strtolower($lockQuery), 'order by "bahan_baku"."id"');

        $this->assertTrue($hasAscendingOrder, 'Locking query does not order by ID: '.$lockQuery);
    }
}
