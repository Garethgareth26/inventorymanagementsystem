<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\PesananPembelian;
use App\Models\User;
use App\Services\StockMutationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds 12 months of stock mutation history for all raw materials.
 *
 * Strategy:
 * 1. Initial stock (masuk, manual) for each material at the beginning of history.
 * 2. PO receipt mutations (masuk, po_penerimaan) for all "Diterima" POs.
 * 3. Monthly keluar mutations simulating production/sales consumption.
 *
 * All writes go through StockMutationService to ensure stok_saat_ini is
 * updated atomically and consistently with mutasi_stok rows.
 *
 * Target volumes per UI Spec: 12 months of mutation history.
 */
class MutasiStokSeeder extends Seeder
{
    public function __construct(private readonly StockMutationService $stockService) {}

    public function run(): void
    {
        $karyawan = User::whereHas('role', fn ($q) => $q->where('slug', 'karyawan'))->first();
        if (! $karyawan) {
            $this->command->warn('MutasiStokSeeder: No karyawan user found, skipping.');

            return;
        }

        $materials = BahanBaku::all();
        if ($materials->isEmpty()) {
            $this->command->warn('MutasiStokSeeder: No bahan_baku found, skipping.');

            return;
        }

        // 1. Seed initial stock for each material (beginning of history window)
        $historyStart = Carbon::now()->subMonths(12)->startOfMonth()->toDateString();

        foreach ($materials as $bb) {
            // Initial opening stock: 3–6 months worth of estimated demand
            $initialStock = fake()->randomFloat(2, 500, 2000);

            $this->stockService->recordMutation(
                itemType: 'bahan_baku',
                itemId: $bb->id,
                jenisMutasi: 'masuk',
                jumlah: $initialStock,
                tanggal: $historyStart,
                actor: $karyawan,
                sumber: 'manual',
                keterangan: 'Stok awal — pembukaan periode'
            );
        }

        // 2. PO receipt mutations for all Diterima POs
        $diterimaPOs = PesananPembelian::where('status', PesananPembelian::STATUS_DITERIMA)
            ->whereNotNull('tanggal_terima')
            ->get();

        foreach ($diterimaPOs as $po) {
            $this->stockService->recordMutation(
                itemType: 'bahan_baku',
                itemId: $po->bahan_baku_id,
                jenisMutasi: 'masuk',
                jumlah: (float) $po->jumlah,
                tanggal: $po->tanggal_terima->format('Y-m-d'),
                actor: $karyawan,
                sumber: 'po_penerimaan',
                poId: $po->id,
                keterangan: "Penerimaan PO #{$po->kode_po}"
            );
        }

        // 3. Monthly keluar mutations simulating consumption over 12 months
        // Each material gets 4–8 outgoing mutations per month
        for ($month = 11; $month >= 1; $month--) {
            $monthStart = Carbon::now()->subMonths($month)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($month)->endOfMonth();

            foreach ($materials as $bb) {
                $mutationsThisMonth = fake()->numberBetween(3, 6);

                for ($k = 0; $k < $mutationsThisMonth; $k++) {
                    $tanggal = fake()->dateTimeBetween($monthStart, $monthEnd)->format('Y-m-d');
                    $jumlah = fake()->randomFloat(2, 20, 200);

                    try {
                        $this->stockService->recordMutation(
                            itemType: 'bahan_baku',
                            itemId: $bb->id,
                            jenisMutasi: 'keluar',
                            jumlah: $jumlah,
                            tanggal: $tanggal,
                            actor: $karyawan,
                            sumber: 'manual',
                            keterangan: 'Pemakaian produksi — seeder'
                        );
                    } catch (\Throwable $e) {
                        // Negative stock rejection is acceptable during seeding — add replenishment
                        $this->stockService->recordMutation(
                            itemType: 'bahan_baku',
                            itemId: $bb->id,
                            jenisMutasi: 'masuk',
                            jumlah: $jumlah * 3,
                            tanggal: $tanggal,
                            actor: $karyawan,
                            sumber: 'manual',
                            keterangan: 'Pengisian stok — seeder recovery'
                        );

                        // Retry the keluar after replenishment
                        try {
                            $this->stockService->recordMutation(
                                itemType: 'bahan_baku',
                                itemId: $bb->id,
                                jenisMutasi: 'keluar',
                                jumlah: $jumlah,
                                tanggal: $tanggal,
                                actor: $karyawan,
                                sumber: 'manual',
                                keterangan: 'Pemakaian produksi — seeder (retry)'
                            );
                        } catch (\Throwable) {
                            // Swallow if still failing after replenishment
                        }
                    }
                }
            }
        }
    }
}
