<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Services\CalculationEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds inventory_parameters with realistic EOQ/SS/ROP values
 * computed by CalculationEngine from the seeded mutation history.
 *
 * This seeder runs AFTER MutasiStokSeeder so it can compute demand
 * statistics from actual history rows.
 *
 * Pre-seeded SystemSettings defaults are used for Z-factor, biaya_pesan,
 * biaya_simpan, and historical_window.
 */
class InventoryParameterSeeder extends Seeder
{
    public function __construct(private readonly CalculationEngine $engine) {}

    public function run(): void
    {
        $zFactor = 1.65;
        $biayaPesan = 75000.0;
        $biayaSimpanPct = 0.20;
        $windowMonths = 12;

        $bahanBakuList = BahanBaku::all();

        foreach ($bahanBakuList as $bb) {
            // Fetch 12-month keluar mutation history for demand calculation
            $mutations = DB::table('mutasi_stok')
                ->where('bahan_baku_id', $bb->id)
                ->where('jenis_mutasi', 'keluar')
                ->where('tanggal', '>=', now()->subMonths($windowMonths)->toDateString())
                ->pluck('jumlah')
                ->toArray();

            $kebutuhanTahunan = $this->engine->computeAnnualDemand($mutations, $windowMonths);
            $sdHarian = $this->engine->computeDailyStdDev($mutations, $windowMonths);
            $holdingCost = $this->engine->computeHoldingCost((float) $bb->harga_satuan, $biayaSimpanPct);

            $eoq = $this->engine->computeEoq($kebutuhanTahunan, $biayaPesan, $holdingCost);
            $safetyStock = $this->engine->computeSafetyStock($zFactor, $sdHarian, (int) $bb->lead_time_hari);
            $rop = $this->engine->computeRop($kebutuhanTahunan, (int) $bb->lead_time_hari, $safetyStock);

            InventoryParameter::updateOrCreate(
                ['bahan_baku_id' => $bb->id],
                [
                    'kebutuhan_tahunan' => $kebutuhanTahunan,
                    'standar_deviasi_harian' => $sdHarian,
                    'biaya_pesan' => $biayaPesan,
                    'biaya_simpan_persen' => $biayaSimpanPct,
                    'eoq' => $eoq,
                    'safety_stock' => $safetyStock,
                    'reorder_point' => $rop,
                    'z_factor' => $zFactor,
                    'historical_window_months' => $windowMonths,
                    'last_applied_by' => null,
                    'last_applied_at' => null,
                ]
            );
        }
    }
}
