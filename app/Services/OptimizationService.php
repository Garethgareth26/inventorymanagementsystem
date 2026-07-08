<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service encapsulating all inventory optimisation operations.
 *
 * ARCHITECTURE RULES:
 *   - Only this service queries historical mutations for SD/demand computation.
 *   - Only this service writes to inventory_parameters on Apply.
 *   - All CalculationEngine calls live here — never in Livewire components.
 *   - AuditLogger and DashboardQueryService::invalidateCache() are called here.
 *
 * Livewire components may only call the public methods of this service.
 */
final class OptimizationService
{
    public function __construct(
        private readonly CalculationEngine $engine,
        private readonly DashboardQueryService $dashboardQueryService,
    ) {}

    // ─── Data Loading ─────────────────────────────────────────────────────────

    /**
     * Load all parameter data for a BahanBaku, computing historical demand
     * statistics from the mutation ledger.
     *
     * @return array{
     *   bahan_baku: BahanBaku,
     *   param: InventoryParameter|null,
     *   annual_demand: float,
     *   sd_harian: float,
     *   window_months: int,
     *   mutations_count: int,
     *   holding_cost: float,
     * }
     */
    public function getParameterData(BahanBaku $bahanBaku): array
    {
        $param = $bahanBaku->inventoryParameter;
        $windowMonths = $param ? (int) $param->historical_window_months : SystemSettings::getInt('historical_window', 12);

        $mutations = $this->loadKeluar($bahanBaku->id, $windowMonths);

        $annualDemand = $this->engine->computeAnnualDemand($mutations, $windowMonths);
        if ($annualDemand == 0.0 && $param && $param->kebutuhan_tahunan > 0) {
            $annualDemand = (float) $param->kebutuhan_tahunan;
        }

        $sdHarian = $this->engine->computeDailyStdDev($mutations, $windowMonths);
        if ($sdHarian == 0.0 && $param && $param->standar_deviasi_harian > 0) {
            $sdHarian = (float) $param->standar_deviasi_harian;
        }

        $biayaSimpanPersen = $param ? (float) $param->biaya_simpan_persen : (SystemSettings::getFloat('biaya_simpan', 20.0) / 100.0);
        $holdingCost = $this->engine->computeHoldingCost((float) $bahanBaku->harga_satuan, $biayaSimpanPersen);

        return [
            'bahan_baku' => $bahanBaku,
            'param' => $param,
            'annual_demand' => $annualDemand,
            'sd_harian' => $sdHarian,
            'window_months' => $windowMonths,
            'mutations_count' => count($mutations),
            'holding_cost' => $holdingCost,
        ];
    }

    /**
     * Compute SD Harian for a given BahanBaku and historical window (months).
     * Used when the user changes the historical window on the simulation screen.
     */
    public function computeSdForWindow(BahanBaku $bahanBaku, int $windowMonths): float
    {
        $mutations = $this->loadKeluar($bahanBaku->id, max(1, $windowMonths));

        $sdHarian = $this->engine->computeDailyStdDev($mutations, $windowMonths);
        
        if ($sdHarian == 0.0) {
            $param = $bahanBaku->inventoryParameter;
            if ($param && $param->standar_deviasi_harian > 0) {
                $sdHarian = (float) $param->standar_deviasi_harian;
            }
        }

        return $sdHarian;
    }

    // ─── Simulation ───────────────────────────────────────────────────────────

    /**
     * Simulate EOQ / Safety Stock / ROP for given inputs without persisting.
     *
     * Note: biaya_simpan_persen is a decimal fraction (e.g. 0.20 for 20%).
     *
     * @param  array{
     *   annual_demand: float,
     *   biaya_pesan: float,
     *   biaya_simpan_persen: float,
     *   z_factor: float,
     *   sd_harian: float,
     *   lead_time_hari: int,
     * }  $inputs
     * @return array{eoq: float, safety_stock: float, reorder_point: float, holding_cost: float}
     */
    public function simulate(BahanBaku $bahanBaku, array $inputs): array
    {
        $holdingCost = $this->engine->computeHoldingCost(
            (float) $bahanBaku->harga_satuan,
            (float) $inputs['biaya_simpan_persen']
        );

        $eoq = $this->engine->computeEoq(
            (float) $inputs['annual_demand'],
            (float) $inputs['biaya_pesan'],
            $holdingCost
        );

        $safetyStock = $this->engine->computeSafetyStock(
            (float) $inputs['z_factor'],
            (float) $inputs['sd_harian'],
            (int) $inputs['lead_time_hari']
        );

        $rop = $this->engine->computeRop(
            (float) $inputs['annual_demand'],
            (int) $inputs['lead_time_hari'],
            $safetyStock
        );

        return [
            'eoq' => $eoq,
            'safety_stock' => $safetyStock,
            'reorder_point' => $rop,
            'holding_cost' => $holdingCost,
        ];
    }

    // ─── Apply ────────────────────────────────────────────────────────────────

    /**
     * Persist simulation results to inventory_parameters, write audit log,
     * and invalidate the dashboard cache.
     *
     * Note: biaya_simpan_persen is a decimal fraction.
     *
     * @param  array{
     *   annual_demand: float,
     *   biaya_pesan: float,
     *   biaya_simpan_persen: float,
     *   z_factor: float,
     *   sd_harian: float,
     *   lead_time_hari: int,
     *   historical_window_months: int,
     * }  $inputs
     *
     * @throws RuntimeException If the actor does not have 'parameter.apply' capability.
     */
    public function apply(BahanBaku $bahanBaku, array $inputs, User $actor): InventoryParameter
    {
        if (! $actor->hasCapability('parameter.apply')) {
            throw new RuntimeException('Anda tidak memiliki izin untuk menerapkan parameter ini.');
        }

        $simulated = $this->simulate($bahanBaku, $inputs);

        return DB::transaction(function () use ($bahanBaku, $inputs, $actor, $simulated): InventoryParameter {
            $old = $bahanBaku->inventoryParameter?->toArray();

            /** @var InventoryParameter $param */
            $param = InventoryParameter::updateOrCreate(
                ['bahan_baku_id' => $bahanBaku->id],
                [
                    'kebutuhan_tahunan' => $inputs['annual_demand'],
                    'standar_deviasi_harian' => $inputs['sd_harian'],
                    'biaya_pesan' => $inputs['biaya_pesan'],
                    'biaya_simpan_persen' => $inputs['biaya_simpan_persen'],
                    'eoq' => $simulated['eoq'],
                    'safety_stock' => $simulated['safety_stock'],
                    'reorder_point' => $simulated['reorder_point'],
                    'z_factor' => $inputs['z_factor'],
                    'historical_window_months' => $inputs['historical_window_months'],
                    'last_applied_by' => $actor->id,
                    'last_applied_at' => now(),
                ]
            );

            AuditLogger::log(
                $actor,
                'parameter.apply',
                $param,
                $old,
                $param->fresh()?->toArray()
            );

            $this->dashboardQueryService->invalidateCache();

            return $param;
        });
    }

    // ─── ABC Analysis ─────────────────────────────────────────────────────────

    /**
     * Build the per-material ABC detail table.
     *
     * Computes individual % and cumulative % for every BahanBaku, sorted
     * descending by annual usage value (standard Pareto presentation).
     *
     * The aggregate donut + Top-5 chart data is NOT duplicated here — call
     * DashboardQueryService::getChartData() for that.
     *
     * @return array<int, array{
     *   id: int,
     *   kode: string,
     *   nama: string,
     *   annual_usage_value: float,
     *   individual_pct: float,
     *   cumulative_pct: float,
     *   kelas: string,
     * }>
     */
    public function getAbcTable(): array
    {
        $materials = BahanBaku::with('inventoryParameter')->get();

        $abcInput = [];
        $materialMap = [];

        foreach ($materials as $bb) {
            $param = $bb->inventoryParameter;
            $kebutuhan = $param ? (float) $param->kebutuhan_tahunan : 0.0;
            $annualUsage = $kebutuhan * (float) $bb->harga_satuan;

            $abcInput[] = ['id' => $bb->id, 'annual_usage_value' => $annualUsage];
            $materialMap[$bb->id] = [
                'id' => $bb->id,
                'kode' => $bb->kode,
                'nama' => $bb->nama,
                'annual_usage_value' => $annualUsage,
            ];
        }

        // Use CalculationEngine for consistent classification
        $abcMap = $this->engine->classifyAbc($abcInput);

        // Sort descending for Pareto table presentation
        usort($abcInput, fn (array $a, array $b) => $b['annual_usage_value'] <=> $a['annual_usage_value']);

        $total = array_sum(array_column($abcInput, 'annual_usage_value'));
        $cumulative = 0.0;
        $result = [];

        foreach ($abcInput as $item) {
            $individual = $total > 0.0 ? ($item['annual_usage_value'] / $total) * 100.0 : 0.0;
            $cumulative += $individual;

            $result[] = [
                'id' => $item['id'],
                'kode' => $materialMap[$item['id']]['kode'],
                'nama' => $materialMap[$item['id']]['nama'],
                'annual_usage_value' => $item['annual_usage_value'],
                'individual_pct' => round($individual, 2),
                'cumulative_pct' => round($cumulative, 2),
                'kelas' => $abcMap[$item['id']] ?? 'C',
            ];
        }

        return $result;
    }

    // ─── Reorder Point Status ─────────────────────────────────────────────────

    /**
     * Return all BahanBaku with a 3-tier ROP status badge.
     *
     * Badge rules (as per UI Spec §6.12 & Group 5 roadmap):
     *   critical → stok_saat_ini <= reorder_point
     *   near     → stok_saat_ini <= 1.2 × reorder_point  (and > reorder_point)
     *   ok       → otherwise
     *
     * @return array<int, array{
     *   id: int,
     *   kode: string,
     *   nama: string,
     *   satuan: string,
     *   stok_saat_ini: float,
     *   rop: float,
     *   eoq: float,
     *   safety_stock: float,
     *   lead_time_hari: int,
     *   status: 'critical'|'near'|'ok',
     *   defisit: float,
     *   has_param: bool,
     * }>
     */
    public function getReorderPointStatus(): array
    {
        $materials = BahanBaku::with('inventoryParameter', 'supplier')->orderBy('nama')->get();

        $result = [];

        foreach ($materials as $bb) {
            $stok = (float) $bb->stok_saat_ini;
            $param = $bb->inventoryParameter;
            $rop = $param ? (float) $param->reorder_point : 0.0;

            if ($stok <= $rop) {
                $status = 'critical';
            } elseif ($rop > 0.0 && $stok <= 1.2 * $rop) {
                $status = 'near';
            } else {
                $status = 'ok';
            }

            $result[] = [
                'id' => $bb->id,
                'kode' => $bb->kode,
                'nama' => $bb->nama,
                'satuan' => $bb->satuan,
                'stok_saat_ini' => $stok,
                'rop' => $rop,
                'eoq' => $param ? (float) $param->eoq : 0.0,
                'safety_stock' => $param ? (float) $param->safety_stock : 0.0,
                'lead_time_hari' => (int) $bb->lead_time_hari,
                'status' => $status,
                'defisit' => max(0.0, $rop - $stok),
                'has_param' => $param !== null,
            ];
        }

        return $result;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Load keluar mutation quantities for a raw material within a window.
     *
     * @return float[]
     */
    private function loadKeluar(int $bahanBakuId, int $windowMonths): array
    {
        return MutasiStok::where('bahan_baku_id', $bahanBakuId)
            ->where('jenis_mutasi', MutasiStok::JENIS_KELUAR)
            ->whereDate('tanggal', '>=', now()->subMonths($windowMonths)->toDateString())
            ->pluck('jumlah')
            ->map(fn ($q) => (float) $q)
            ->toArray();
    }
}
