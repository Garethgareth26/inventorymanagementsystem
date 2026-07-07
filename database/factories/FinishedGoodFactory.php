<?php

namespace Database\Factories;

use App\Models\FinishedGood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinishedGood>
 *
 * Realistic finished goods factory for CV Akuna (bakery/food production domain).
 * Allows many factory calls — kode uses random numeric suffix for uniqueness.
 */
class FinishedGoodFactory extends Factory
{
    /**
     * @var array<int, array{nama: string, satuan: string}>
     */
    private static array $catalogue = [
        ['nama' => 'Kue Coklat Premium',  'satuan' => 'pcs'],
        ['nama' => 'Roti Tawar Lembut',   'satuan' => 'loaf'],
        ['nama' => 'Kue Keju Spesial',    'satuan' => 'pcs'],
        ['nama' => 'Croissant Mentega',   'satuan' => 'pcs'],
        ['nama' => 'Brownies Fudge',      'satuan' => 'box'],
        ['nama' => 'Muffin Stroberi',     'satuan' => 'pcs'],
        ['nama' => 'Donat Gula',          'satuan' => 'pcs'],
        ['nama' => 'Cake Vanilla',        'satuan' => 'loaf'],
        ['nama' => 'Cookie Coklat Chip',  'satuan' => 'box'],
        ['nama' => 'Roti Gandum',         'satuan' => 'loaf'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = fake()->randomElement(self::$catalogue);

        return [
            'kode' => 'FG-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'nama' => $item['nama'].' '.fake()->lexify('??'),
            'satuan' => $item['satuan'],
            'stok_saat_ini' => 0,
        ];
    }
}
