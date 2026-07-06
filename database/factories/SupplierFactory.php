<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 *
 * Bare scaffold only — required so App\Models\Supplier's HasFactory trait
 * resolves. Realistic, internally-consistent sample data is M-2.3 scope
 * (Domain Seeders & Factories), not M-2.2.
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('SUP-###'),
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'kontak' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
