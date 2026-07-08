<?php

namespace App\Imports;

use App\Models\BahanBaku;
use App\Models\InventoryParameter;
use App\Models\MutasiStok;
use App\Models\User;
use App\Services\CalculationEngine;
use App\Services\StockMutationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ParameterAbcSheetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $stockMutationService = app(StockMutationService::class);
        $calculationEngine = app(CalculationEngine::class);
        $user = User::first(); // fallback user for system actions if no auth

        foreach ($rows as $row) {
            if (! isset($row['kode'])) {
                continue;
            }

            $kode = trim($row['kode']);
            $bahanBaku = BahanBaku::where('kode', $kode)->first();

            if (! $bahanBaku) {
                continue;
            }

            // Clean numeric fields using array keys that Maatwebsite generates
            $kebutuhan = $this->parseNumber($row['kebutuhan_tahunan_d'] ?? 0);
            $biayaPesan = $this->parseNumber($row['biaya_pesans_rp'] ?? $row['biaya_pesan_s_rp'] ?? 75000);

            // Biaya simpan might be parsed as 'biaya_simpanh_rpunitth'
            $biayaSimpan = 0;
            foreach ($row as $key => $val) {
                if (str_contains(strtolower($key), 'biaya_simpan')) {
                    $biayaSimpan = $this->parseNumber($val);
                    break;
                }
            }

            // Usually Excel might have nominal instead of percentage. Let's calculate percentage:
            $harga = $bahanBaku->harga_satuan;
            $biayaSimpanPersen = ($harga > 0 && $biayaSimpan > 0) ? ($biayaSimpan / $harga) : 0.2;

            $sdHarian = $this->parseNumber($row['sd_harian'] ?? 0);

            $zFactor = 1.65;
            foreach ($row as $key => $val) {
                if (str_contains(strtolower($key), 'z_')) {
                    $zFactor = $this->parseNumber($val);
                    break;
                }
            }

            // Calculate EOQ, Safety Stock, and ROP
            $holdingCost = $calculationEngine->computeHoldingCost((float) $bahanBaku->harga_satuan, $biayaSimpanPersen);
            $eoq = $calculationEngine->computeEoq($kebutuhan, $biayaPesan, $holdingCost);
            $safetyStock = $calculationEngine->computeSafetyStock($zFactor, $sdHarian, (int) $bahanBaku->lead_time_hari);
            $rop = $calculationEngine->computeRop($kebutuhan, (int) $bahanBaku->lead_time_hari, $safetyStock);

            InventoryParameter::updateOrCreate(
                ['bahan_baku_id' => $bahanBaku->id],
                [
                    'kebutuhan_tahunan' => $kebutuhan,
                    'standar_deviasi_harian' => $sdHarian,
                    'biaya_pesan' => $biayaPesan,
                    'biaya_simpan_persen' => $biayaSimpanPersen,
                    'z_factor' => $zFactor,
                    'eoq' => $eoq,
                    'safety_stock' => $safetyStock,
                    'reorder_point' => $rop,
                ]
            );

            // Handle Stok Awal
            $stokAwal = 0;
            foreach ($row as $key => $val) {
                if (str_contains(strtolower($key), 'asumsi_stok')) {
                    $stokAwal = $this->parseNumber($val);
                    break;
                }
            }

            if ($stokAwal > 0 && $bahanBaku->stok_saat_ini == 0) {
                $stockMutationService->recordMutation(
                    itemType: 'bahan_baku',
                    itemId: $bahanBaku->id,
                    jenisMutasi: MutasiStok::JENIS_MASUK,
                    jumlah: $stokAwal,
                    tanggal: now()->toDateString(),
                    actor: auth()->user() ?? $user,
                    sumber: MutasiStok::SUMBER_MANUAL,
                    keterangan: 'Pencatatan stok awal dari Import Excel'
                );
            }
        }
    }

    private function parseNumber($val)
    {
        if (is_numeric($val)) {
            return (float) $val;
        }
        if (is_string($val)) {
            $val = str_replace(['Rp', ',', ' ', '%'], '', $val);

            return (float) $val;
        }

        return 0.0;
    }
}
