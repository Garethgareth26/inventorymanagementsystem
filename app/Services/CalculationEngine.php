<?php

namespace App\Services;

/**
 * Pure, framework-agnostic computation engine for inventory optimisation.
 *
 * Zero Eloquent inside — all methods receive primitive PHP values or
 * arrays and return primitive PHP values. This makes them trivially
 * unit-testable and reusable without any Laravel bootstrap overhead.
 *
 * All formulas documented with their standard operations-research notation:
 *   D  = Annual demand (units/year)
 *   S  = Order cost per order (Rp)
 *   H  = Holding cost per unit per year (Rp)
 *   Z  = Service-level Z-factor (e.g. 1.65 = 95%)
 *   σd = Daily standard deviation of demand (units/day)
 *   LT = Lead time (days)
 *   SS = Safety Stock
 *   ROP = Reorder Point
 */
final class CalculationEngine
{
    // ─── EOQ ──────────────────────────────────────────────────────────────────

    /**
     * Compute Economic Order Quantity.
     *
     * Formula: EOQ = sqrt(2 × D × S / H)
     *
     * Returns 0.0 when D ≤ 0 or H ≤ 0 (degenerate case — ordering nothing
     * is optimal if there is no demand or no holding cost).
     *
     * @param  float  $annualDemand  D — annual demand (units/year)
     * @param  float  $orderCost  S — order cost per order (Rp)
     * @param  float  $holdingCost  H — holding cost per unit per year (Rp)
     */
    public function computeEoq(float $annualDemand, float $orderCost, float $holdingCost): float
    {
        if ($annualDemand <= 0 || $holdingCost <= 0) {
            return 0.0;
        }

        return sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
    }

    // ─── Safety Stock ──────────────────────────────────────────────────────────

    /**
     * Compute Safety Stock.
     *
     * Formula: SS = Z × σd × sqrt(LT)
     *
     * Returns 0.0 when leadTimeDays ≤ 0 or sdHarian ≤ 0.
     *
     * @param  float  $zFactor  Z — service-level factor (e.g. 1.65 for 95%)
     * @param  float  $sdHarian  σd — daily standard deviation of demand
     * @param  int  $leadTimeDays  LT — supplier lead time in days
     */
    public function computeSafetyStock(float $zFactor, float $sdHarian, int $leadTimeDays): float
    {
        if ($leadTimeDays <= 0 || $sdHarian <= 0) {
            return 0.0;
        }

        return $zFactor * $sdHarian * sqrt($leadTimeDays);
    }

    // ─── Reorder Point ────────────────────────────────────────────────────────

    /**
     * Compute Reorder Point.
     *
     * Formula: ROP = (D / 365 × LT) + SS
     *
     * Returns safety stock when D ≤ 0 (minimum buffer even with no demand trend).
     *
     * @param  float  $annualDemand  D — annual demand (units/year)
     * @param  int  $leadTimeDays  LT — supplier lead time in days
     * @param  float  $safetyStock  SS — pre-computed safety stock
     */
    public function computeRop(float $annualDemand, int $leadTimeDays, float $safetyStock): float
    {
        $dailyDemand = ($annualDemand > 0) ? $annualDemand / 365.0 : 0.0;

        return ($dailyDemand * $leadTimeDays) + $safetyStock;
    }

    // ─── ABC Classification ───────────────────────────────────────────────────

    /**
     * Classify materials into ABC classes based on cumulative annual usage value.
     *
     * Standard Pareto classification:
     *   Class A: top materials whose cumulative value ≤ $thresholdA% of total
     *   Class B: next tier up to $thresholdB%
     *   Class C: remainder
     *
     * @param  array<int, array{id: int, annual_usage_value: float}>  $materials
     *                                                                            Each element: ['id' => int, 'annual_usage_value' => float (Rp/year)]
     * @param  float  $thresholdA  Cumulative % threshold for Class A (default 80)
     * @param  float  $thresholdB  Cumulative % threshold for Class B (default 95)
     * @return array<int, string> Map of material id → 'A'|'B'|'C'
     */
    public function classifyAbc(
        array $materials,
        float $thresholdA = 80.0,
        float $thresholdB = 95.0
    ): array {
        if (empty($materials)) {
            return [];
        }

        // Sort descending by annual usage value
        usort($materials, fn (array $a, array $b) => $b['annual_usage_value'] <=> $a['annual_usage_value']);

        $total = array_sum(array_column($materials, 'annual_usage_value'));

        if ($total <= 0) {
            // All zero usage — assign all to C
            $result = [];
            foreach ($materials as $m) {
                $result[$m['id']] = 'C';
            }

            return $result;
        }

        $cumulative = 0.0;
        $result = [];

        foreach ($materials as $m) {
            $cumulative += ($m['annual_usage_value'] / $total) * 100.0;

            if ($cumulative <= $thresholdA) {
                $result[$m['id']] = 'A';
            } elseif ($cumulative <= $thresholdB) {
                $result[$m['id']] = 'B';
            } else {
                $result[$m['id']] = 'C';
            }
        }

        return $result;
    }

    // ─── Supporting calculations ───────────────────────────────────────────────

    /**
     * Compute annual holding cost per unit (H) from unit price and holding %.
     *
     * Formula: H = hargaSatuan × holdingPct
     *
     * @param  float  $hargaSatuan  Unit price (Rp)
     * @param  float  $holdingPct  Holding cost as decimal fraction (e.g. 0.20 for 20%)
     */
    public function computeHoldingCost(float $hargaSatuan, float $holdingPct): float
    {
        return $hargaSatuan * $holdingPct;
    }

    /**
     * Compute annualised demand from a list of historical keluar quantities.
     *
     * Projects the observed demand over the historical window to a 12-month
     * annual figure. Returns 0.0 when the mutation list is empty.
     *
     * @param  float[]  $keluarQuantities  Individual keluar mutation quantities (units)
     * @param  int  $windowMonths  Length of history window (months)
     */
    public function computeAnnualDemand(array $keluarQuantities, int $windowMonths): float
    {
        if (empty($keluarQuantities) || $windowMonths <= 0) {
            return 0.0;
        }

        $totalObserved = array_sum($keluarQuantities);

        // Project to 12-month annual figure
        return $totalObserved * (12.0 / $windowMonths);
    }

    /**
     * Compute daily standard deviation of demand from historical keluar quantities.
     *
     * Each mutation amount is treated as a single-day consumption event.
     * Days with no mutation are NOT included (sparse-event model).
     *
     * Returns 0.0 when fewer than 2 data points are available (cannot
     * compute meaningful variance from a single observation).
     *
     * @param  float[]  $keluarQuantities  Individual keluar mutation quantities (units)
     * @param  int  $windowMonths  Length of history window (months, used for normalisation)
     */
    public function computeDailyStdDev(array $keluarQuantities, int $windowMonths): float
    {
        if (count($keluarQuantities) < 2) {
            return 0.0;
        }

        $n = count($keluarQuantities);
        $mean = array_sum($keluarQuantities) / $n;

        $variance = array_sum(
            array_map(fn (float $x) => ($x - $mean) ** 2, $keluarQuantities)
        ) / ($n - 1); // sample variance (Bessel's correction)

        return sqrt($variance);
    }
}
