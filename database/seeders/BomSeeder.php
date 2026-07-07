<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Bom;
use App\Models\FinishedGood;
use Illuminate\Database\Seeder;

/**
 * Seeds Bill of Materials for all 10 finished goods.
 *
 * Each finished good gets 2–4 ingredient lines using materials from BahanBakuSeeder.
 * The BOM lines are internally consistent: e.g., Kue Coklat uses tepung + coklat + gula.
 * qty_per_unit represents the raw material needed to produce 1 unit of finished good.
 */
class BomSeeder extends Seeder
{
    public function run(): void
    {
        // [finished_good_kode => [[bahan_baku_kode, qty_per_unit, satuan], ...]]
        $boms = [
            'FG-001' => [ // Kue Coklat Premium
                ['BB-001', 0.2500, 'kg'],  // Tepung Terigu
                ['BB-002', 0.1500, 'kg'],  // Gula Pasir
                ['BB-005', 0.0800, 'kg'],  // Coklat Bubuk
                ['BB-006', 0.0500, 'kg'],  // Mentega
            ],
            'FG-002' => [ // Roti Tawar Lembut
                ['BB-001', 0.4000, 'kg'],  // Tepung Terigu
                ['BB-011', 0.0100, 'kg'],  // Ragi Instan
                ['BB-012', 0.0050, 'kg'],  // Garam Halus
                ['BB-006', 0.0300, 'kg'],  // Mentega
            ],
            'FG-003' => [ // Kue Keju Spesial
                ['BB-001', 0.2000, 'kg'],  // Tepung Terigu
                ['BB-010', 0.1000, 'kg'],  // Keju Parut
                ['BB-006', 0.0800, 'kg'],  // Mentega
                ['BB-002', 0.0500, 'kg'],  // Gula Pasir
            ],
            'FG-004' => [ // Croissant Mentega
                ['BB-001', 0.3000, 'kg'],  // Tepung Terigu
                ['BB-006', 0.1500, 'kg'],  // Mentega
                ['BB-011', 0.0050, 'kg'],  // Ragi Instan
                ['BB-002', 0.0300, 'kg'],  // Gula Pasir
            ],
            'FG-005' => [ // Brownies Fudge (1 box = 12 pieces)
                ['BB-001', 0.3000, 'kg'],  // Tepung Terigu
                ['BB-005', 0.2000, 'kg'],  // Coklat Bubuk
                ['BB-002', 0.2500, 'kg'],  // Gula Pasir
                ['BB-006', 0.1000, 'kg'],  // Mentega
            ],
            'FG-006' => [ // Muffin Stroberi
                ['BB-001', 0.1500, 'kg'],  // Tepung Terigu
                ['BB-009', 0.0200, 'liter'], // Perisa Stroberi
                ['BB-002', 0.1000, 'kg'],  // Gula Pasir
                ['BB-007', 0.0050, 'kg'],  // Baking Powder
            ],
            'FG-007' => [ // Donat Gula
                ['BB-001', 0.2000, 'kg'],  // Tepung Terigu
                ['BB-002', 0.0800, 'kg'],  // Gula Pasir
                ['BB-011', 0.0080, 'kg'],  // Ragi Instan
                ['BB-003', 0.0500, 'liter'], // Minyak Goreng
            ],
            'FG-008' => [ // Cake Vanilla
                ['BB-001', 0.2500, 'kg'],  // Tepung Terigu
                ['BB-008', 0.0100, 'liter'], // Vanilla Essence
                ['BB-002', 0.2000, 'kg'],  // Gula Pasir
                ['BB-004', 0.0500, 'kg'],  // Susu Bubuk
            ],
            'FG-009' => [ // Cookie Coklat Chip (1 box = 20 pieces)
                ['BB-001', 0.2000, 'kg'],  // Tepung Terigu
                ['BB-005', 0.0500, 'kg'],  // Coklat Bubuk
                ['BB-002', 0.1500, 'kg'],  // Gula Pasir
                ['BB-006', 0.1200, 'kg'],  // Mentega
            ],
            'FG-010' => [ // Roti Gandum
                ['BB-001', 0.3500, 'kg'],  // Tepung Terigu
                ['BB-011', 0.0120, 'kg'],  // Ragi Instan
                ['BB-012', 0.0060, 'kg'],  // Garam Halus
            ],
        ];

        foreach ($boms as $fgKode => $lines) {
            $fg = FinishedGood::where('kode', $fgKode)->first();
            if (! $fg) {
                continue;
            }

            foreach ($lines as [$bbKode, $qty, $satuan]) {
                $bb = BahanBaku::where('kode', $bbKode)->first();
                if (! $bb) {
                    continue;
                }

                Bom::firstOrCreate(
                    [
                        'finished_goods_id' => $fg->id,
                        'bahan_baku_id' => $bb->id,
                    ],
                    [
                        'qty_per_unit' => $qty,
                        'satuan' => $satuan,
                    ]
                );
            }
        }
    }
}
