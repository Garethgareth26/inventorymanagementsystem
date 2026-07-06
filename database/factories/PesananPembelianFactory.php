<?php

namespace Database\Factories;

use App\Models\BahanBaku;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesananPembelian>
 *
 * Bare scaffold only — required so App\Models\PesananPembelian's
 * HasFactory trait resolves. Realistic sample data (~132 POs across
 * statuses) is M-2.3 scope.
 */
class PesananPembelianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_po' => fake()->unique()->bothify('PO-#####'),
            'bahan_baku_id' => BahanBaku::factory(),
            'supplier_id' => Supplier::factory(),
            'jumlah' => fake()->randomFloat(2, 1, 1000),
            'harga_satuan' => fake()->randomFloat(2, 1000, 500000),
            'status' => PesananPembelian::STATUS_MENUNGGU,
            'jenis' => PesananPembelian::JENIS_RUTIN,
            'tanggal_pesan' => fake()->date(),
            'tanggal_terima' => null,
            'estimasi_tiba' => null,
            'dicatat_oleh' => User::factory(),
        ];
    }
}
