<?php

namespace Database\Factories;

use App\Models\FinishedGood;
use App\Models\ProductionEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionEntry>
 *
 * Bare scaffold only — required so App\Models\ProductionEntry's
 * HasFactory trait resolves. Realistic sample data is M-2.3 scope.
 */
class ProductionEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finished_goods_id' => FinishedGood::factory(),
            'jumlah_diproduksi' => fake()->randomFloat(2, 1, 500),
            'tanggal_produksi' => fake()->date(),
            'dicatat_oleh' => User::factory(),
        ];
    }
}
