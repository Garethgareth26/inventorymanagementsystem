<?php

use App\Models\AuditLog;
use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\ProductionEntry;
use App\Models\Supplier;
use App\Models\User;

test('supplier has many bahan baku', function () {
    $supplier = Supplier::factory()->create();
    $material = BahanBaku::factory()->create(['supplier_id' => $supplier->id]);

    expect($supplier->bahanBaku)->toHaveCount(1);
    expect($supplier->bahanBaku->first()->is($material))->toBeTrue();
    expect($material->supplier->is($supplier))->toBeTrue();
});

test('bahan baku has one inventory parameter', function () {
    $material = BahanBaku::factory()->create();
    $parameter = InventoryParameter::factory()->create(['bahan_baku_id' => $material->id]);

    expect($material->fresh()->inventoryParameter->is($parameter))->toBeTrue();
    expect($parameter->bahanBaku->is($material))->toBeTrue();
});

test('bahan baku decimal fields are cast correctly', function () {
    $material = BahanBaku::factory()->create([
        'stok_saat_ini' => '123.45',
        'harga_satuan' => '5000.00',
        'lead_time_hari' => '7',
    ]);

    expect($material->stok_saat_ini)->toEqual('123.45');
    expect($material->harga_satuan)->toEqual('5000.00');
    expect($material->lead_time_hari)->toBeInt();
});

test('finished good has many bom lines linking to bahan baku', function () {
    $finishedGood = FinishedGood::factory()->create();
    $material = BahanBaku::factory()->create();
    $line = Bom::factory()->create([
        'finished_goods_id' => $finishedGood->id,
        'bahan_baku_id' => $material->id,
    ]);

    expect($finishedGood->bomLines)->toHaveCount(1);
    expect($line->finishedGood->is($finishedGood))->toBeTrue();
    expect($line->bahanBaku->is($material))->toBeTrue();
});

test('pesanan pembelian belongs to bahan baku, supplier, and recorder', function () {
    $user = User::factory()->create();
    $material = BahanBaku::factory()->create();
    $supplier = Supplier::factory()->create();

    $po = PesananPembelian::factory()->create([
        'bahan_baku_id' => $material->id,
        'supplier_id' => $supplier->id,
        'dicatat_oleh' => $user->id,
        'status' => PesananPembelian::STATUS_MENUNGGU,
        'jenis' => PesananPembelian::JENIS_RUTIN,
    ]);

    expect($po->bahanBaku->is($material))->toBeTrue();
    expect($po->supplier->is($supplier))->toBeTrue();
    expect($po->dicatatOleh->is($user))->toBeTrue();
    expect($user->fresh()->pesananPembelian)->toHaveCount(1);
});

test('production entry belongs to finished good and recorder', function () {
    $user = User::factory()->create();
    $finishedGood = FinishedGood::factory()->create();

    $entry = ProductionEntry::factory()->create([
        'finished_goods_id' => $finishedGood->id,
        'dicatat_oleh' => $user->id,
    ]);

    expect($entry->finishedGood->is($finishedGood))->toBeTrue();
    expect($entry->dicatatOleh->is($user))->toBeTrue();
    expect($user->fresh()->productionEntries)->toHaveCount(1);
});

test('mutasi stok links exclusively to bahan baku with manual sumber', function () {
    $user = User::factory()->create();
    $material = BahanBaku::factory()->create();

    $mutation = MutasiStok::factory()->create([
        'bahan_baku_id' => $material->id,
        'finished_goods_id' => null,
        'jenis_mutasi' => MutasiStok::JENIS_MASUK,
        'sumber' => MutasiStok::SUMBER_MANUAL,
        'po_id' => null,
        'production_entry_id' => null,
        'dicatat_oleh' => $user->id,
    ]);

    expect($mutation->bahanBaku->is($material))->toBeTrue();
    expect($mutation->finishedGood)->toBeNull();
    expect($material->fresh()->mutasiStok)->toHaveCount(1);
});

test('mutasi stok links exclusively to finished good via production sumber', function () {
    $user = User::factory()->create();
    $finishedGood = FinishedGood::factory()->create();
    $entry = ProductionEntry::factory()->create([
        'finished_goods_id' => $finishedGood->id,
        'dicatat_oleh' => $user->id,
    ]);

    $mutation = MutasiStok::factory()->create([
        'bahan_baku_id' => null,
        'finished_goods_id' => $finishedGood->id,
        'jenis_mutasi' => MutasiStok::JENIS_MASUK,
        'sumber' => MutasiStok::SUMBER_PRODUKSI,
        'po_id' => null,
        'production_entry_id' => $entry->id,
        'dicatat_oleh' => $user->id,
    ]);

    expect($mutation->finishedGood->is($finishedGood))->toBeTrue();
    expect($mutation->bahanBaku)->toBeNull();
    expect($mutation->productionEntry->is($entry))->toBeTrue();
});

test('audit log stores json snapshots and has no updated_at column', function () {
    $user = User::factory()->create();

    $log = AuditLog::factory()->create([
        'user_id' => $user->id,
        'action' => 'stock.mutate',
        'subject_type' => BahanBaku::class,
        'subject_id' => 1,
        'old_values' => ['stok_saat_ini' => '10.00'],
        'new_values' => ['stok_saat_ini' => '15.00'],
    ]);

    expect($log->user->is($user))->toBeTrue();
    expect($log->old_values)->toBe(['stok_saat_ini' => '10.00']);
    expect($log->new_values)->toBe(['stok_saat_ini' => '15.00']);
    expect($log->getUpdatedAtColumn())->toBeNull();
});

test('user relationships to domain models resolve correctly', function () {
    $user = User::factory()->create();

    InventoryParameter::factory()->create(['last_applied_by' => $user->id]);
    AuditLog::factory()->create(['user_id' => $user->id]);

    expect($user->fresh()->appliedInventoryParameters)->toHaveCount(1);
    expect($user->fresh()->auditLogs)->toHaveCount(1);
});
