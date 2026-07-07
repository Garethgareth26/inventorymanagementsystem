<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryParameter>
 *
 * Realistic inventory parameter factory with SystemSettings defaults.
 * kebutuhan_tahunan, standar_deviasi_harian, eoq, safety_stock, reorder_point
 * are deliberately left nullable (computed from actual mutasi_stok history by
 * CalculationEngine) — seeders populate them explicitly once history is seeded.
 */
class InventoryParameterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bahan_baku_id' => BahanBaku::factory(),
            'kebutuhan_tahunan' => null,
            'standar_deviasi_harian' => null,
            'biaya_pesan' => 75000.00,  // SystemSettings default: biaya_pesan
            'biaya_simpan_persen' => 0.2000,   // SystemSettings default: biaya_simpan 20%
            'eoq' => null,
            'safety_stock' => null,
            'reorder_point' => null,
            'z_factor' => 1.6500,   // SystemSettings default: Z=1.65 (95% service level)
            'historical_window_months' => 12,        // SystemSettings default: 12 months
            'last_applied_by' => null,
            'last_applied_at' => null,
        ];
    }

    /**
     * State: fully computed parameters (after CalculationEngine has run).
     *
     * @return Factory<InventoryParameter>
     */
    public function computed(): static
    {
        return $this->state(function (array $attributes) {
            $d = fake()->randomFloat(2, 500, 5000);     // annual demand
            $s = 75000.0;                                 // order cost
            $hargaSatuan = fake()->randomFloat(2, 5000, 100000);
            $h = $hargaSatuan * 0.20;                     // holding cost = price × 20%
            $eoq = ($d > 0 && $h > 0) ? sqrt((2 * $d * $s) / $h) : 0;
            $sdHarian = fake()->randomFloat(4, 0.5, 15);
            $lt = fake()->numberBetween(3, 14);
            $ss = 1.65 * $sdHarian * sqrt($lt);
            $rop = ($d / 365 * $lt) + $ss;

            return [
                'kebutuhan_tahunan' => $d,
                'standar_deviasi_harian' => $sdHarian,
                'eoq' => round($eoq, 4),
                'safety_stock' => round($ss, 4),
                'reorder_point' => round($rop, 4),
                'last_applied_by' => User::factory(),
                'last_applied_at' => now()->subDays(fake()->numberBetween(1, 90)),
            ];
        });
    }
}
