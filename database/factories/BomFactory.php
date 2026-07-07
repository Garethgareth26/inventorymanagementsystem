<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 *
 * BOM ingredient line factory. qty_per_unit uses realistic bakery quantities.
 * The unique constraint (finished_goods_id, bahan_baku_id) must be managed
 * by the caller — the factory does not guarantee uniqueness by default.
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
            'qty_per_unit' => fake()->randomFloat(4, 0.05, 2.0),
            'satuan' => fake()->randomElement(['kg', 'liter', 'gram']),
        ];
    }
}
