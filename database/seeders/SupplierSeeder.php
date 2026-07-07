<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Seeds 10 realistic Indonesian suppliers for CV Akuna.
 * Covers the range of categories used by the bakery: flour/grain,
 * dairy, flavouring, and packaging.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'kode' => 'SUP-001',
                'nama' => 'PT Bogasari Flour Mills',
                'alamat' => 'Jl. Raya Cilincing No. 1, Jakarta Utara',
                'kontak' => '0821-1100-2200',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-002',
                'nama' => 'PT Gulaku Prima',
                'alamat' => 'Jl. Industri Raya No. 45, Bekasi',
                'kontak' => '0812-3344-5566',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-003',
                'nama' => 'CV Minyak Subur',
                'alamat' => 'Jl. Pasar Lama No. 12, Surabaya',
                'kontak' => '0813-7788-9900',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-004',
                'nama' => 'PT Frisian Flag Indonesia',
                'alamat' => 'Jl. Raya Bogor KM 5, Bogor',
                'kontak' => '0822-5566-7788',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-005',
                'nama' => 'UD Coklat Nusantara',
                'alamat' => 'Jl. Kebun Coklat No. 8, Yogyakarta',
                'kontak' => '0851-2233-4455',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-006',
                'nama' => 'PT Salim Ivomas Pratama',
                'alamat' => 'Jl. M.H. Thamrin No. 9, Jakarta Pusat',
                'kontak' => '0819-8877-6655',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-007',
                'nama' => 'CV Bumbu Rempah Utama',
                'alamat' => 'Jl. Pasar Senen Blok III, Jakarta',
                'kontak' => '0856-1122-3344',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-008',
                'nama' => 'PT Sari Tani Agro',
                'alamat' => 'Jl. Raya Malang No. 101, Malang',
                'kontak' => '0817-6655-4433',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-009',
                'nama' => 'UD Bahan Kue Maju',
                'alamat' => 'Jl. Wonokromo No. 23, Surabaya',
                'kontak' => '0811-9988-7766',
                'is_active' => true,
            ],
            [
                'kode' => 'SUP-010',
                'nama' => 'CV Keju Premium',
                'alamat' => 'Jl. Kalimantan No. 67, Bandung',
                'kontak' => '0823-4455-6677',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['kode' => $supplier['kode']], $supplier);
        }
    }
}
