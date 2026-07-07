<?php

namespace Database\Seeders;

use App\Models\FinishedGood;
use Illuminate\Database\Seeder;

/**
 * Seeds 10 realistic finished goods (barang jadi) for CV Akuna's bakery.
 *
 * stok_saat_ini starts at 0 — MutasiStokSeeder and ProductionEntrySeeder
 * will populate stock history via StockMutationService.
 */
class FinishedGoodSeeder extends Seeder
{
    public function run(): void
    {
        $goods = [
            ['FG-001', 'Kue Coklat Premium',  'pcs'],
            ['FG-002', 'Roti Tawar Lembut',   'loaf'],
            ['FG-003', 'Kue Keju Spesial',    'pcs'],
            ['FG-004', 'Croissant Mentega',   'pcs'],
            ['FG-005', 'Brownies Fudge',      'box'],
            ['FG-006', 'Muffin Stroberi',     'pcs'],
            ['FG-007', 'Donat Gula',          'pcs'],
            ['FG-008', 'Cake Vanilla',        'loaf'],
            ['FG-009', 'Cookie Coklat Chip',  'box'],
            ['FG-010', 'Roti Gandum',         'loaf'],
        ];

        foreach ($goods as [$kode, $nama, $satuan]) {
            FinishedGood::firstOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'satuan' => $satuan,
                    'stok_saat_ini' => 0,
                ]
            );
        }
    }
}
