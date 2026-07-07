<?php

namespace Database\Factories;

use App\Models\FinishedGood;
use App\Models\ProductionEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionEntry>
 *
 * Production entry factory covering 12-month history.
 * Note: actual BOM explosion / stock mutation is NOT done here —
 * the factory only creates the header row. Seeder is responsible for
 * creating the associated mutasi_stok rows via StockMutationService.
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
            'jumlah_diproduksi' => fake()->randomFloat(2, 10, 200),
            'tanggal_produksi' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'dicatat_oleh' => User::factory(),
        ];
    }
}
