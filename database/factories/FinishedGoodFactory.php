<?php

namespace Database\Factories;

use App\Models\FinishedGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinishedGood>
 *
 * Bare scaffold only — required so App\Models\FinishedGood's HasFactory
 * trait resolves. Realistic sample data is M-2.3 scope.
 */
class FinishedGoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('FG-###'),
            'nama' => fake()->words(2, true),
            'satuan' => fake()->randomElement(['pcs', 'box', 'botol']),
            'stok_saat_ini' => 0,
        ];
    }
}
