<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 *
 * Bare scaffold only — required so App\Models\Bom's HasFactory trait
 * resolves. Realistic sample data is M-2.3 scope.
 */
class BomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finished_goods_id' => FinishedGood::factory(),
            'bahan_baku_id' => BahanBaku::factory(),
            'qty_per_unit' => fake()->randomFloat(4, 0.1, 100),
            'satuan' => fake()->randomElement(['kg', 'liter', 'pcs']),
        ];
    }
}
