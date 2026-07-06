<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryParameter>
 *
 * Bare scaffold only — required so App\Models\InventoryParameter's
 * HasFactory trait resolves. Realistic sample data is M-2.3 scope.
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
            'biaya_pesan' => 75000,
            'biaya_simpan_persen' => 0.20,
            'eoq' => null,
            'safety_stock' => null,
            'reorder_point' => null,
            'z_factor' => 1.65,
            'historical_window_months' => 12,
            'last_applied_by' => null,
            'last_applied_at' => null,
        ];
    }
}
