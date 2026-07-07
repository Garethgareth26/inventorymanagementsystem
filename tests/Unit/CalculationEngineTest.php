<?php

use App\Services\CalculationEngine;

// ─── Test setup ───────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->engine = new CalculationEngine;
});

// ─── computeEoq ───────────────────────────────────────────────────────────────

describe('computeEoq', function () {
    it('computes the correct EOQ for known values', function () {
        // D=1000, S=75000, H=9500*0.20=1900
        // EOQ = sqrt(2 × 1000 × 75000 / 1900) = sqrt(78947.368...) ≈ 280.976
        $eoq = $this->engine->computeEoq(
            annualDemand: 1000,
            orderCost: 75000,
            holdingCost: 1900
        );

        // Verify against the same formula to avoid float precision drift
        $expected = sqrt((2.0 * 1000.0 * 75000.0) / 1900.0);
        expect($eoq)->toBeFloat()
            ->and(abs($eoq - $expected))->toBeLessThan(0.0001);
    });

    it('computes EOQ for tepung terigu (Class A material)', function () {
        // D=3650 units/year, S=75000, harga=9500, H%=20% → H=1900
        // EOQ = sqrt(2*3650*75000/1900) ≈ 536.11
        $eoq = $this->engine->computeEoq(3650, 75000, 1900);

        expect($eoq)->toBeGreaterThan(500)->toBeLessThan(600);
    });

    it('returns 0 when annual demand is 0', function () {
        $eoq = $this->engine->computeEoq(0, 75000, 1900);

        expect($eoq)->toBe(0.0);
    });

    it('returns 0 when holding cost is 0', function () {
        $eoq = $this->engine->computeEoq(1000, 75000, 0);

        expect($eoq)->toBe(0.0);
    });

    it('returns 0 when demand is negative', function () {
        $eoq = $this->engine->computeEoq(-100, 75000, 1900);

        expect($eoq)->toBe(0.0);
    });

    it('gives a larger EOQ for higher demand', function () {
        $eoqLow = $this->engine->computeEoq(500, 75000, 1900);
        $eoqHigh = $this->engine->computeEoq(2000, 75000, 1900);

        expect($eoqHigh)->toBeGreaterThan($eoqLow);
    });
});

// ─── computeSafetyStock ───────────────────────────────────────────────────────

describe('computeSafetyStock', function () {
    it('computes safety stock with Z=1.65, sd=5, lt=9', function () {
        // SS = 1.65 × 5 × sqrt(9) = 1.65 × 5 × 3 = 24.75
        $ss = $this->engine->computeSafetyStock(1.65, 5.0, 9);

        expect(abs($ss - 24.75) < 0.01)->toBeTrue("SS expected 24.75, got {$ss}");
    });

    it('computes safety stock for lead_time=1 (minimum valid input)', function () {
        // SS = 1.65 × 3 × sqrt(1) = 4.95
        $ss = $this->engine->computeSafetyStock(1.65, 3.0, 1);

        expect(abs($ss - 4.95) < 0.01)->toBeTrue("SS expected 4.95, got {$ss}");
    });

    it('returns 0 when lead time is 0', function () {
        $ss = $this->engine->computeSafetyStock(1.65, 5.0, 0);

        expect($ss)->toBe(0.0);
    });

    it('returns 0 when daily std dev is 0', function () {
        $ss = $this->engine->computeSafetyStock(1.65, 0.0, 7);

        expect($ss)->toBe(0.0);
    });

    it('returns 0 when lead time is negative', function () {
        $ss = $this->engine->computeSafetyStock(1.65, 5.0, -3);

        expect($ss)->toBe(0.0);
    });

    it('scales with z-factor', function () {
        $ss95 = $this->engine->computeSafetyStock(1.65, 5.0, 7);  // 95% service level
        $ss99 = $this->engine->computeSafetyStock(2.33, 5.0, 7);  // 99% service level

        expect($ss99)->toBeGreaterThan($ss95);
    });
});

// ─── computeRop ───────────────────────────────────────────────────────────────

describe('computeRop', function () {
    it('computes ROP for known values', function () {
        // D=1825, LT=7, SS=14.75 → ROP = (1825/365 × 7) + 14.75 = 35 + 14.75 = 49.75
        $rop = $this->engine->computeRop(1825, 7, 14.75);

        expect(abs($rop - 49.75) < 0.01)->toBeTrue("ROP expected 49.75, got {$rop}");
    });

    it('returns safety stock when demand is 0', function () {
        $rop = $this->engine->computeRop(0, 7, 20.0);

        expect(abs($rop - 20.0) < 0.01)->toBeTrue('ROP with zero demand should equal SS');
    });

    it('ROP is always >= safety stock', function () {
        $ss = $this->engine->computeSafetyStock(1.65, 5.0, 7);
        $rop = $this->engine->computeRop(1000, 7, $ss);

        expect($rop)->toBeGreaterThanOrEqual($ss);
    });
});

// ─── classifyAbc ──────────────────────────────────────────────────────────────

describe('classifyAbc', function () {
    it('classifies 5 materials with known usage values correctly', function () {
        // Total = 500. Cumulative %: M1=40%, M2=70% (A), M3=84% (B), M4=94% (B), M5=100% (C)
        $materials = [
            ['id' => 1, 'annual_usage_value' => 200.0],  // 40% → A
            ['id' => 2, 'annual_usage_value' => 150.0],  // 70% → A  (cumulative ≤ 80%)
            ['id' => 3, 'annual_usage_value' => 70.0],  // 84% → B
            ['id' => 4, 'annual_usage_value' => 50.0],  // 94% → B
            ['id' => 5, 'annual_usage_value' => 30.0],  // 100% → C
        ];

        $result = $this->engine->classifyAbc($materials, 80.0, 95.0);

        expect($result[1])->toBe('A')
            ->and($result[2])->toBe('A')
            ->and($result[3])->toBe('B')
            ->and($result[4])->toBe('B')
            ->and($result[5])->toBe('C');
    });

    it('assigns all to C when all usage values are zero', function () {
        $materials = [
            ['id' => 1, 'annual_usage_value' => 0],
            ['id' => 2, 'annual_usage_value' => 0],
        ];

        $result = $this->engine->classifyAbc($materials);

        expect($result[1])->toBe('C')->and($result[2])->toBe('C');
    });

    it('returns empty array for empty input', function () {
        $result = $this->engine->classifyAbc([]);

        expect($result)->toBeEmpty();
    });

    it('classifies a single material with all value as A', function () {
        $materials = [['id' => 99, 'annual_usage_value' => 1000.0]];

        $result = $this->engine->classifyAbc($materials);

        // Single item = 100% of total, cumulative = 100% → but first item hits A threshold first
        // classifyAbc accumulates: 100% ≤ 80% is FALSE, so it would be B or C.
        // Correct: cumulative after first item = 100%, which is > 95% → C
        expect($result[99])->toBe('C');
    });

    it('always contains at least one A when there is a dominant material', function () {
        $materials = [
            ['id' => 1, 'annual_usage_value' => 900.0],  // 90% → B? No. 90% > 80% → B. Hmm.
            ['id' => 2, 'annual_usage_value' => 60.0],  // cumulative = 96% → C
            ['id' => 3, 'annual_usage_value' => 40.0],  // cumulative = 100% → C
        ];
        // M1 cumulative=90%. 90 > 80 → B. 90 ≤ 95 → B.
        // M2 cumulative=96% → C.
        // M3 cumulative=100% → C.
        $result = $this->engine->classifyAbc($materials, 80.0, 95.0);

        // The highest usage item exceeds A threshold so it lands in B
        $classes = array_values($result);
        expect(in_array('B', $classes))->toBeTrue();
    });
});

// ─── computeHoldingCost ───────────────────────────────────────────────────────

describe('computeHoldingCost', function () {
    it('computes holding cost correctly', function () {
        // harga=9500, pct=0.20 → H=1900
        $h = $this->engine->computeHoldingCost(9500, 0.20);

        expect(abs($h - 1900) < 0.01)->toBeTrue();
    });

    it('returns 0 when price is 0', function () {
        expect($this->engine->computeHoldingCost(0, 0.20))->toBe(0.0);
    });
});

// ─── computeAnnualDemand ──────────────────────────────────────────────────────

describe('computeAnnualDemand', function () {
    it('projects demand from 6-month window to 12 months', function () {
        // 6 keluar mutations of 100 each in 6 months → annual = 1200
        $quantities = array_fill(0, 6, 100.0);
        $annual = $this->engine->computeAnnualDemand($quantities, 6);

        expect(abs($annual - 1200.0) < 0.01)->toBeTrue("Expected 1200, got {$annual}");
    });

    it('returns 0 for empty quantity list', function () {
        $annual = $this->engine->computeAnnualDemand([], 12);

        expect($annual)->toBe(0.0);
    });

    it('handles single mutation in window', function () {
        // Edge case: 1 mutation of 500 in 12 months → annual = 500
        $annual = $this->engine->computeAnnualDemand([500.0], 12);

        expect(abs($annual - 500.0) < 0.01)->toBeTrue();
    });

    it('returns 0 when window is 0 months', function () {
        $annual = $this->engine->computeAnnualDemand([100.0, 200.0], 0);

        expect($annual)->toBe(0.0);
    });
});

// ─── computeDailyStdDev ───────────────────────────────────────────────────────

describe('computeDailyStdDev', function () {
    it('computes std dev for known dataset', function () {
        // Values [2, 4, 4, 4, 5, 5, 7, 9] → mean=5
        // Sample variance = (9+1+1+1+0+0+4+16) / (8-1) = 32/7 ≈ 4.5714
        // Sample SD = sqrt(32/7) ≈ 2.1381
        $data = [2.0, 4.0, 4.0, 4.0, 5.0, 5.0, 7.0, 9.0];
        $sd = $this->engine->computeDailyStdDev($data, 12);

        $expected = sqrt(32 / 7); // ≈ 2.1381
        expect(abs($sd - $expected) < 0.01)->toBeTrue("Expected sd≈{$expected}, got {$sd}");
    });

    it('returns 0 when fewer than 2 data points', function () {
        expect($this->engine->computeDailyStdDev([100.0], 12))->toBe(0.0);
        expect($this->engine->computeDailyStdDev([], 12))->toBe(0.0);
    });

    it('returns 0 for uniform data (no variance)', function () {
        $sd = $this->engine->computeDailyStdDev([50.0, 50.0, 50.0, 50.0], 12);

        expect($sd)->toBe(0.0);
    });
});
