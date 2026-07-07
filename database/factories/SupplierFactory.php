<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 *
 * Realistic Indonesian supplier data for CV Akuna's domain.
 * Generates plausible company names, addresses, and contacts.
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $supplierNames = [
            'PT Sumber Alam Lestari',
            'CV Maju Bersama',
            'PT Agro Nusantara',
            'UD Berkah Jaya',
            'PT Prima Bahan',
            'CV Rizky Makmur',
            'PT Bahan Kimia Utama',
            'UD Sari Tani',
            'PT Indo Supplier',
            'CV Karya Mandiri',
            'PT Bumi Subur',
            'UD Aneka Bahan',
        ];

        static $cities = [
            'Surabaya', 'Jakarta', 'Bandung', 'Semarang',
            'Yogyakarta', 'Malang', 'Makassar', 'Medan',
        ];

        $name = fake()->unique()->randomElement($supplierNames);
        $city = fake()->randomElement($cities);

        return [
            'kode' => 'SUP-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'nama' => $name,
            'alamat' => 'Jl. '.fake()->streetName().' No. '.fake()->numberBetween(1, 200).', '.$city,
            'kontak' => '08'.fake()->numerify('##########'),
            'is_active' => true,
        ];
    }

    /**
     * State for inactive supplier.
     *
     * @return Factory<Supplier>
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
