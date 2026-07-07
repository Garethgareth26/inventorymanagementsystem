<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanBaku>
 *
 * Realistic raw material factory for CV Akuna's food/beverage domain.
 * Uses a catalogue of 12 common bakery ingredients but allows the factory
 * to be called more than 12 times by falling back to random data.
 *
 * The seeder uses explicit data — this factory is primarily for tests.
 */
class BahanBakuFactory extends Factory
{
    /**
     * @var array<int, array{nama: string, satuan: string, harga_min: int, harga_max: int, lt_min: int, lt_max: int}>
     */
    private static array $catalogue = [
        ['nama' => 'Tepung Terigu',    'satuan' => 'kg',    'harga_min' => 8000,  'harga_max' => 12000,  'lt_min' => 3, 'lt_max' => 7],
        ['nama' => 'Gula Pasir',       'satuan' => 'kg',    'harga_min' => 13000, 'harga_max' => 17000,  'lt_min' => 2, 'lt_max' => 5],
        ['nama' => 'Minyak Goreng',    'satuan' => 'liter', 'harga_min' => 14000, 'harga_max' => 18000,  'lt_min' => 3, 'lt_max' => 7],
        ['nama' => 'Susu Bubuk',       'satuan' => 'kg',    'harga_min' => 50000, 'harga_max' => 80000,  'lt_min' => 7, 'lt_max' => 14],
        ['nama' => 'Coklat Bubuk',     'satuan' => 'kg',    'harga_min' => 45000, 'harga_max' => 70000,  'lt_min' => 7, 'lt_max' => 14],
        ['nama' => 'Mentega',          'satuan' => 'kg',    'harga_min' => 25000, 'harga_max' => 40000,  'lt_min' => 3, 'lt_max' => 7],
        ['nama' => 'Baking Powder',    'satuan' => 'kg',    'harga_min' => 20000, 'harga_max' => 35000,  'lt_min' => 5, 'lt_max' => 10],
        ['nama' => 'Vanilla Essence',  'satuan' => 'liter', 'harga_min' => 80000, 'harga_max' => 120000, 'lt_min' => 7, 'lt_max' => 14],
        ['nama' => 'Perisa Stroberi',  'satuan' => 'liter', 'harga_min' => 70000, 'harga_max' => 110000, 'lt_min' => 7, 'lt_max' => 14],
        ['nama' => 'Keju Parut',       'satuan' => 'kg',    'harga_min' => 60000, 'harga_max' => 90000,  'lt_min' => 5, 'lt_max' => 10],
        ['nama' => 'Ragi Instan',      'satuan' => 'kg',    'harga_min' => 30000, 'harga_max' => 55000,  'lt_min' => 5, 'lt_max' => 10],
        ['nama' => 'Garam Halus',      'satuan' => 'kg',    'harga_min' => 2000,  'harga_max' => 5000,   'lt_min' => 2, 'lt_max' => 5],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = fake()->randomElement(self::$catalogue);

        return [
            'kode' => 'BB-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'nama' => $item['nama'].' '.fake()->lexify('??'),
            'satuan' => $item['satuan'],
            'stok_saat_ini' => 0,
            'supplier_id' => Supplier::factory(),
            'harga_satuan' => fake()->numberBetween($item['harga_min'], $item['harga_max']),
            'lead_time_hari' => fake()->numberBetween($item['lt_min'], $item['lt_max']),
        ];
    }
}
