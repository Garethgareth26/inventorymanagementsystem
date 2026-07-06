<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanBaku>
 *
 * Bare scaffold only — required so App\Models\BahanBaku's HasFactory trait
 * resolves. Realistic, internally-consistent sample data (ABC-class
 * distribution, seeded stock/mutation history) is M-2.3 scope.
 */
class BahanBakuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('BB-###'),
            'nama' => fake()->words(2, true),
            'satuan' => fake()->randomElement(['kg', 'liter', 'pcs']),
            'stok_saat_ini' => 0,
            'supplier_id' => null,
            'harga_satuan' => fake()->randomFloat(2, 1000, 500000),
            'lead_time_hari' => fake()->numberBetween(1, 30),
        ];
    }
}
