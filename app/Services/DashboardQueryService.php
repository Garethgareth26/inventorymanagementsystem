<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BahanBaku;
use App\Models\FinishedGood;
use App\Models\PesananPembelian;
use App\Models\ProductionEntry;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service providing thin, high-performance query methods for the Dashboard.
 *
 * Implements dashboard metrics caching using Redis with DB fallback,
 * and handles explicit cache invalidation when stock or parameters change.
 */
final class DashboardQueryService
{
    private const CACHE_TTL = 3600; // 1 hour (invalidated explicitly on mutations)

    /**
     * Retrieve Owner Dashboard KPI metrics.
     *
     * @return array{total_bahan_baku: int, annual_investment: float, critical_materials: int, fg_stock_value: float}
     */
    public function getOwnerMetrics(): array
    {
        return Cache::remember('dashboard:metrics:owner', self::CACHE_TTL, function () {
            $totalBahanBaku = BahanBaku::count();

            $annualInvestment = DB::table('bahan_baku')
                ->join('inventory_parameters', 'bahan_baku.id', '=', 'inventory_parameters.bahan_baku_id')
                ->sum(DB::raw('inventory_parameters.kebutuhan_tahunan * bahan_baku.harga_satuan'));

            $criticalMaterials = DB::table('bahan_baku')
                ->join('inventory_parameters', 'bahan_baku.id', '=', 'inventory_parameters.bahan_baku_id')
                ->whereRaw('bahan_baku.stok_saat_ini <= inventory_parameters.reorder_point')
                ->count();

            $finishedGoods = FinishedGood::with('bomLines.bahanBaku')->get();
            $fgStockValue = 0.0;
            foreach ($finishedGoods as $fg) {
                $unitCost = 0.0;
                foreach ($fg->bomLines as $line) {
                    if ($line->bahanBaku) {
                        $unitCost += (float) $line->qty_per_unit * (float) $line->bahanBaku->harga_satuan;
                    }
                }
                $fgStockValue += (float) $fg->stok_saat_ini * $unitCost;
            }

            return [
                'total_bahan_baku' => $totalBahanBaku,
                'annual_investment' => (float) $annualInvestment,
                'critical_materials' => $criticalMaterials,
                'fg_stock_value' => $fgStockValue,
            ];
        });
    }

    /**
     * Retrieve Employee Dashboard KPI metrics.
     *
     * @return array{critical_materials: int, pending_pos: int, production_this_month: int}
     */
    public function getEmployeeMetrics(): array
    {
        return Cache::remember('dashboard:metrics:employee', self::CACHE_TTL, function () {
            $criticalMaterials = DB::table('bahan_baku')
                ->join('inventory_parameters', 'bahan_baku.id', '=', 'inventory_parameters.bahan_baku_id')
                ->whereRaw('bahan_baku.stok_saat_ini <= inventory_parameters.reorder_point')
                ->count();

            $pendingPos = PesananPembelian::whereIn('status', [
                PesananPembelian::STATUS_MENUNGGU,
                PesananPembelian::STATUS_DALAM_PROSES,
            ])->count();

            $productionThisMonth = ProductionEntry::whereMonth('tanggal_produksi', now()->month)
                ->whereYear('tanggal_produksi', now()->year)
                ->count();

            return [
                'critical_materials' => $criticalMaterials,
                'pending_pos' => $pendingPos,
                'production_this_month' => $productionThisMonth,
            ];
        });
    }

    /**
     * Retrieve ABC donut chart and Top 5 expensive materials metrics.
     *
     * @return array{donut: array<string, int>, donut_value: array<string, float>, top5: array<int, array{name: string, value: float}>}
     */
    public function getChartData(): array
    {
        return Cache::remember('dashboard:metrics:charts', self::CACHE_TTL, function () {
            $materials = BahanBaku::with('inventoryParameter')->get();

            $abcInput = [];
            $materialMap = [];
            foreach ($materials as $bb) {
                $kebutuhanTahunan = (float) ($bb->inventoryParameter->kebutuhan_tahunan ?? 0.0);
                $annualUsageValue = $kebutuhanTahunan * (float) $bb->harga_satuan;

                $abcInput[] = [
                    'id' => $bb->id,
                    'annual_usage_value' => $annualUsageValue,
                ];
                $materialMap[$bb->id] = [
                    'name' => $bb->nama,
                    'value' => $annualUsageValue,
                ];
            }

            $engine = app(CalculationEngine::class);
            $abcMap = $engine->classifyAbc($abcInput);

            $donut = ['A' => 0, 'B' => 0, 'C' => 0];
            $donutValue = ['A' => 0.0, 'B' => 0.0, 'C' => 0.0];
            foreach ($abcMap as $id => $class) {
                if (isset($donut[$class])) {
                    $donut[$class]++;
                    $donutValue[$class] += $materialMap[$id]['value'];
                }
            }

            uasort($materialMap, fn ($a, $b) => $b['value'] <=> $a['value']);
            $top5 = array_slice($materialMap, 0, 5, true);
            $top5Data = [];
            foreach ($top5 as $data) {
                $top5Data[] = [
                    'name' => $data['name'],
                    'value' => $data['value'],
                ];
            }

            return [
                'donut' => $donut,
                'donut_value' => $donutValue,
                'top5' => $top5Data,
            ];
        });
    }

    /**
     * Retrieve currently critical materials (stok_saat_ini <= reorder_point).
     *
     * @return array<int, array{id: int, kode: string, nama: string, stok_saat_ini: float, rop: float, defisit: float, satuan: string}>
     */
    public function getCriticalStockList(): array
    {
        $materials = BahanBaku::with('inventoryParameter')->get();

        $critical = [];
        foreach ($materials as $bb) {
            $rop = (float) ($bb->inventoryParameter->reorder_point ?? 0.0);
            $stok = (float) $bb->stok_saat_ini;
            if ($bb->inventoryParameter && $stok <= $rop) {
                $critical[] = [
                    'id' => $bb->id,
                    'kode' => $bb->kode,
                    'nama' => $bb->nama,
                    'stok_saat_ini' => $stok,
                    'rop' => $rop,
                    'defisit' => max(0.0, $rop - $stok),
                    'satuan' => $bb->satuan,
                ];
            }
        }

        usort($critical, fn ($a, $b) => $b['defisit'] <=> $a['defisit']);

        return $critical;
    }

    /**
     * Retrieve upcoming reorders (projected to cross ROP within next 7 days).
     *
     * @return array<int, array{id: int, kode: string, nama: string, stok_saat_ini: float, rop: float, days_until_rop: float, satuan: string}>
     */
    public function getUpcomingReorders(): array
    {
        $materials = BahanBaku::with('inventoryParameter')->get();

        $upcoming = [];
        foreach ($materials as $bb) {
            $stok = (float) $bb->stok_saat_ini;
            $rop = (float) ($bb->inventoryParameter->reorder_point ?? 0.0);
            $annual = (float) ($bb->inventoryParameter->kebutuhan_tahunan ?? 0.0);
            $dailyDemand = $annual / 365.0;

            if ($stok <= $rop) {
                continue;
            }

            if ($dailyDemand > 0.0) {
                $days = ($stok - $rop) / $dailyDemand;
                if ($days <= 7.0 && $bb->inventoryParameter) {
                    $upcoming[] = [
                        'id' => $bb->id,
                        'kode' => $bb->kode,
                        'nama' => $bb->nama,
                        'stok_saat_ini' => $stok,
                        'rop' => $rop,
                        'days_until_rop' => round($days, 1),
                        'satuan' => $bb->satuan,
                    ];
                }
            }
        }

        usort($upcoming, fn ($a, $b) => $a['days_until_rop'] <=> $b['days_until_rop']);

        return $upcoming;
    }

    /**
     * Retrieve the last 15 audit logs.
     * Filtered by a specific user if provided (Employee cockpit).
     */
    public function getRecentActivities(?User $user = null): array
    {
        $query = AuditLog::with('user');

        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        return $query->latest('id')->limit(15)->get()->toArray();
    }

    /**
     * Invalidate all dashboard metrics and chart caches.
     */
    public function invalidateCache(): void
    {
        Cache::forget('dashboard:metrics:owner');
        Cache::forget('dashboard:metrics:employee');
        Cache::forget('dashboard:metrics:charts');
    }
}
