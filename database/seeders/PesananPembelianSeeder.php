<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\PesananPembelian;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds ~132 purchase orders spread over 12 months.
 *
 * Distribution per UI Spec:
 * - ~80% Rutin, ~20% Darurat
 * - Status mix: ~60% Diterima, ~25% Dalam Proses, ~10% Menunggu, ~5% Dibatalkan
 * - Covers all 10 materials, using their default suppliers
 * - POs with status Diterima have tanggal_terima set
 *
 * NOTE: MutasiStokSeeder creates stock receipt mutations for Diterima POs.
 */
class PesananPembelianSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = User::whereHas('role', fn ($q) => $q->where('slug', 'karyawan'))->first();
        if (! $karyawan) {
            $this->command->warn('PesananPembelianSeeder: No karyawan user found, skipping.');

            return;
        }

        $materials = BahanBaku::with('supplier')->get();
        if ($materials->isEmpty()) {
            $this->command->warn('PesananPembelianSeeder: No bahan_baku found, skipping.');

            return;
        }

        $poCounter = 1;
        $poData = [];

        // Distribute ~132 POs over 12 months (11 POs/month)
        for ($month = 11; $month >= 0; $month--) {
            $monthStart = Carbon::now()->subMonths($month)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($month)->endOfMonth();

            // 11 POs per month, cycling through materials
            $posThisMonth = 11;

            for ($i = 0; $i < $posThisMonth; $i++) {
                $material = $materials[$i % $materials->count()];
                $jenis = ($poCounter % 5 === 0) ? PesananPembelian::JENIS_DARURAT : PesananPembelian::JENIS_RUTIN;

                // Status distribution
                $statusRoll = $poCounter % 20;
                if ($statusRoll < 12) {
                    $status = PesananPembelian::STATUS_DITERIMA;
                } elseif ($statusRoll < 17) {
                    $status = PesananPembelian::STATUS_DALAM_PROSES;
                } elseif ($statusRoll < 19) {
                    $status = PesananPembelian::STATUS_MENUNGGU;
                } else {
                    $status = PesananPembelian::STATUS_DIBATALKAN;
                }

                // For current month POs, use pending statuses more
                if ($month === 0) {
                    $status = ($poCounter % 3 === 0)
                        ? PesananPembelian::STATUS_DALAM_PROSES
                        : PesananPembelian::STATUS_MENUNGGU;
                }

                $tanggalPesan = fake()->dateTimeBetween($monthStart, $monthEnd)->format('Y-m-d');
                $hargaSatuan = (float) $material->harga_satuan;
                if ($jenis === PesananPembelian::JENIS_DARURAT) {
                    $hargaSatuan = round($hargaSatuan * 1.2, 2);
                }

                $tanggalTerima = null;
                if ($status === PesananPembelian::STATUS_DITERIMA) {
                    $tanggalTerima = fake()->dateTimeBetween(
                        $tanggalPesan,
                        min(Carbon::parse($tanggalPesan)->addDays($material->lead_time_hari + 5)->toDateString(), now()->toDateString())
                    )->format('Y-m-d');
                }

                $poData[] = [
                    'kode_po' => 'PO-'.str_pad((string) $poCounter, 5, '0', STR_PAD_LEFT),
                    'bahan_baku_id' => $material->id,
                    'supplier_id' => $material->supplier_id,
                    'jumlah' => fake()->randomFloat(2, 100, 2000),
                    'harga_satuan' => $hargaSatuan,
                    'status' => $status,
                    'jenis' => $jenis,
                    'tanggal_pesan' => $tanggalPesan,
                    'estimasi_tiba' => Carbon::parse($tanggalPesan)->addDays($material->lead_time_hari)->toDateString(),
                    'tanggal_terima' => $tanggalTerima,
                    'dicatat_oleh' => $karyawan->id,
                    'created_at' => $tanggalPesan,
                    'updated_at' => $tanggalTerima ?? $tanggalPesan,
                ];

                $poCounter++;
            }
        }

        // Insert in batches for performance
        foreach (array_chunk($poData, 50) as $chunk) {
            PesananPembelian::upsert($chunk, ['kode_po'], [
                'bahan_baku_id', 'supplier_id', 'jumlah', 'harga_satuan',
                'status', 'jenis', 'tanggal_pesan', 'estimasi_tiba',
                'tanggal_terima', 'dicatat_oleh', 'updated_at',
            ]);
        }
    }
}
