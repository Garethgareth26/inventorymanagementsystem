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
 * Purchase order factory. Generates realistic PO data:
 * - Rutin or Darurat (80/20 split)
 * - Prices correlated with bahan_baku.harga_satuan (+20% for Darurat)
 * - Historical date range: last 12 months
 */
class PesananPembelianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement([
            PesananPembelian::JENIS_RUTIN,
            PesananPembelian::JENIS_RUTIN,
            PesananPembelian::JENIS_RUTIN,
            PesananPembelian::JENIS_RUTIN,
            PesananPembelian::JENIS_DARURAT,
        ]);

        $status = fake()->randomElement([
            PesananPembelian::STATUS_MENUNGGU,
            PesananPembelian::STATUS_DALAM_PROSES,
            PesananPembelian::STATUS_DITERIMA,
            PesananPembelian::STATUS_DITERIMA,
            PesananPembelian::STATUS_DITERIMA,
        ]);

        $hargaSatuan = fake()->randomFloat(2, 5000, 200000);
        if ($jenis === PesananPembelian::JENIS_DARURAT) {
            $hargaSatuan = round($hargaSatuan * 1.2, 2);
        }

        $tanggalPesan = fake()->dateTimeBetween('-12 months', '-1 week');
        $tanggalTerima = null;

        if ($status === PesananPembelian::STATUS_DITERIMA) {
            $tanggalTerima = fake()->dateTimeBetween($tanggalPesan, 'now');
        }

        return [
            'kode_po' => 'PO-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'bahan_baku_id' => BahanBaku::factory(),
            'supplier_id' => Supplier::factory(),
            'jumlah' => fake()->randomFloat(2, 50, 2000),
            'harga_satuan' => $hargaSatuan,
            'status' => $status,
            'jenis' => $jenis,
            'tanggal_pesan' => $tanggalPesan->format('Y-m-d'),
            'estimasi_tiba' => fake()->dateTimeBetween($tanggalPesan, '+30 days')->format('Y-m-d'),
            'tanggal_terima' => $tanggalTerima ? $tanggalTerima->format('Y-m-d') : null,
            'dicatat_oleh' => User::factory(),
        ];
    }

    /**
     * State: PO with status Diterima (received).
     *
     * @return Factory<PesananPembelian>
     */
    public function diterima(): static
    {
        return $this->state([
            'status' => PesananPembelian::STATUS_DITERIMA,
            'tanggal_terima' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * State: emergency PO.
     *
     * @return Factory<PesananPembelian>
     */
    public function darurat(): static
    {
        return $this->state(['jenis' => PesananPembelian::JENIS_DARURAT]);
    }

    /**
     * State: PO pending (Menunggu).
     *
     * @return Factory<PesananPembelian>
     */
    public function menunggu(): static
    {
        return $this->state([
            'status' => PesananPembelian::STATUS_MENUNGGU,
            'tanggal_terima' => null,
        ]);
    }
}
