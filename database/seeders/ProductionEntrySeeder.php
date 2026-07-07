<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\FinishedGood;
use App\Models\User;
use App\Services\StockMutationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds production entries and their associated stock mutations.
 *
 * Creates ~24 production runs spread over 12 months (2 per month, cycling
 * through finished goods). Each production entry triggers StockMutationService
 * for full atomicity — BOM explosion creates N keluar mutations for
 * bahan_baku and 1 masuk mutation for the finished good.
 *
 * Runs AFTER MutasiStokSeeder so there is sufficient raw material stock.
 */
class ProductionEntrySeeder extends Seeder
{
    public function __construct(private readonly StockMutationService $stockService) {}

    public function run(): void
    {
        $karyawan = User::whereHas('role', fn ($q) => $q->where('slug', 'karyawan'))->first();
        if (! $karyawan) {
            $this->command->warn('ProductionEntrySeeder: No karyawan user found, skipping.');

            return;
        }

        $finishedGoods = FinishedGood::with('bomLines')->get();
        if ($finishedGoods->isEmpty()) {
            $this->command->warn('ProductionEntrySeeder: No finished goods found, skipping.');

            return;
        }

        // Filter to only FGs that have BOMs defined
        $finishedGoodsWithBom = $finishedGoods->filter(fn ($fg) => $fg->bomLines->isNotEmpty());
        if ($finishedGoodsWithBom->isEmpty()) {
            $this->command->warn('ProductionEntrySeeder: No finished goods with BOM, skipping.');

            return;
        }

        $fgList = $finishedGoodsWithBom->values();
        $entryIndex = 0;

        // 2 production runs per month over 12 months = 24 entries
        for ($month = 11; $month >= 0; $month--) {
            for ($run = 0; $run < 2; $run++) {
                $fg = $fgList[$entryIndex % $fgList->count()];
                $jumlahDiproduksi = fake()->randomFloat(2, 20, 100);

                $tanggal = fake()->dateTimeBetween(
                    Carbon::now()->subMonths($month)->startOfMonth(),
                    Carbon::now()->subMonths($month)->endOfMonth()
                )->format('Y-m-d');

                try {
                    $this->stockService->recordProduction(
                        fg: $fg,
                        jumlahDiproduksi: $jumlahDiproduksi,
                        tanggal: $tanggal,
                        actor: $karyawan,
                        keterangan: "Produksi seeder — {$fg->nama}"
                    );
                } catch (\Throwable $e) {
                    // Insufficient stock during seeding is acceptable — log and continue
                    $this->command->warn("ProductionEntrySeeder: Skipped {$fg->nama} ({$tanggal}): {$e->getMessage()}");
                }

                $entryIndex++;
            }
        }
    }
}
