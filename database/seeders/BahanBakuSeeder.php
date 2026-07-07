<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Seeds 10 realistic raw materials (bahan baku) for CV Akuna's bakery.
 *
 * Supplier assignments ensure each material has a logical default supplier.
 * stok_saat_ini starts at 0 — MutasiStokSeeder populates stock history
 * via StockMutationService which will update the running balance.
 */
class BahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            // [kode, nama, satuan, harga_satuan, lead_time_hari, supplier_kode]
            ['BB-001', 'Tepung Terigu',    'kg',    9500,   5,  'SUP-001'],
            ['BB-002', 'Gula Pasir',       'kg',   14000,   3,  'SUP-002'],
            ['BB-003', 'Minyak Goreng',    'liter', 15500,   5,  'SUP-003'],
            ['BB-004', 'Susu Bubuk',       'kg',   65000,  10,  'SUP-004'],
            ['BB-005', 'Coklat Bubuk',     'kg',   58000,  10,  'SUP-005'],
            ['BB-006', 'Mentega',          'kg',   32000,   5,  'SUP-006'],
            ['BB-007', 'Baking Powder',    'kg',   28000,   7,  'SUP-007'],
            ['BB-008', 'Vanilla Essence',  'liter', 95000,  10,  'SUP-008'],
            ['BB-009', 'Perisa Stroberi',  'liter', 85000,  10,  'SUP-009'],
            ['BB-010', 'Keju Parut',       'kg',   75000,   7,  'SUP-010'],
        ];

        foreach ($materials as [$kode, $nama, $satuan, $harga, $leadTime, $supplierKode]) {
            $supplier = Supplier::where('kode', $supplierKode)->first();

            BahanBaku::firstOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'satuan' => $satuan,
                    'stok_saat_ini' => 0, // populated by MutasiStokSeeder
                    'supplier_id' => $supplier?->id,
                    'harga_satuan' => $harga,
                    'lead_time_hari' => $leadTime,
                ]
            );
        }
    }
}
