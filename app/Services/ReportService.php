<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\FinishedGood;
use App\Models\MutasiStok;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use Carbon\Carbon;

/**
 * Service encapsulating reporting business logic and dataset compilation.
 */
final class ReportService
{
    /**
     * Compile Warehouse Asset Valuation Report data as of a given date.
     *
     * @return array{
     *   date: string,
     *   materials: array<int, array{kode: string, nama: string, stok: float, satuan: string, harga: float, nilai: float}>,
     *   finished_goods: array<int, array{kode: string, nama: string, stok: float, satuan: string, harga: float, nilai: float}>,
     *   total_materials: float,
     *   total_finished_goods: float,
     *   grand_total: float,
     * }
     */
    public function generateValuasiAset(string $endDate): array
    {
        $rawMaterials = BahanBaku::orderBy('kode')->get();
        $materialsData = [];
        $totalMaterials = 0.0;

        foreach ($rawMaterials as $bb) {
            $stok = $this->calculateStockAsOf($bb->id, 'bahan_baku', $endDate);
            $nilai = $stok * (float) $bb->harga_satuan;
            $totalMaterials += $nilai;

            $materialsData[] = [
                'kode' => $bb->kode,
                'nama' => $bb->nama,
                'stok' => $stok,
                'satuan' => $bb->satuan,
                'harga' => (float) $bb->harga_satuan,
                'nilai' => $nilai,
            ];
        }

        $finishedGoods = FinishedGood::with('bomLines.bahanBaku')->orderBy('kode')->get();
        $fgData = [];
        $totalFg = 0.0;

        foreach ($finishedGoods as $fg) {
            $stok = $this->calculateStockAsOf($fg->id, 'finished_good', $endDate);

            // Compute Finished Goods unit cost based on BOM ingredients
            $unitCost = 0.0;
            foreach ($fg->bomLines as $line) {
                if ($line->bahanBaku) {
                    $unitCost += (float) $line->qty_per_unit * (float) $line->bahanBaku->harga_satuan;
                }
            }

            $nilai = $stok * $unitCost;
            $totalFg += $nilai;

            $fgData[] = [
                'kode' => $fg->kode,
                'nama' => $fg->nama,
                'stok' => $stok,
                'satuan' => $fg->satuan,
                'harga' => $unitCost,
                'nilai' => $nilai,
            ];
        }

        return [
            'date' => $endDate,
            'materials' => $materialsData,
            'finished_goods' => $fgData,
            'total_materials' => $totalMaterials,
            'total_finished_goods' => $totalFg,
            'grand_total' => $totalMaterials + $totalFg,
        ];
    }

    /**
     * Compile Supplier Performance Report data for a given date range.
     *
     * @return array{
     *   start_date: string,
     *   end_date: string,
     *   suppliers: array<int, array{nama: string, total_po: int, po_diterima: int, po_tepat_waktu: int, ontime_rate: float, avg_lead_time: float, total_nilai: float}>,
     * }
     */
    public function generatePerformaSupplier(string $startDate, string $endDate): array
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $suppliersData = [];

        foreach ($suppliers as $sup) {
            $pos = PesananPembelian::where('supplier_id', $sup->id)
                ->whereBetween('tanggal_pesan', [$startDate, $endDate])
                ->get();

            $totalPo = $pos->count();
            $receivedPos = $pos->where('status', PesananPembelian::STATUS_DITERIMA);
            $poDiterima = $receivedPos->count();

            $poTepatWaktu = 0;
            $totalLeadTimeDays = 0;

            foreach ($receivedPos as $po) {
                $tglTerima = $po->tanggal_terima ? Carbon::parse($po->tanggal_terima) : null;
                $tglPesan = $po->tanggal_pesan ? Carbon::parse($po->tanggal_pesan) : null;
                $estTiba = $po->estimasi_tiba ? Carbon::parse($po->estimasi_tiba) : null;

                if ($tglTerima && $estTiba) {
                    if ($tglTerima->lte($estTiba)) {
                        $poTepatWaktu++;
                    }
                }
                if ($tglTerima && $tglPesan) {
                    $totalLeadTimeDays += $tglPesan->diffInDays($tglTerima);
                }
            }

            $ontimeRate = $poDiterima > 0 ? ($poTepatWaktu / $poDiterima) * 100.0 : 0.0;
            $avgLeadTime = $poDiterima > 0 ? ($totalLeadTimeDays / $poDiterima) : 0.0;
            $totalNilai = 0.0;

            foreach ($pos as $po) {
                $totalNilai += (float) $po->jumlah * (float) $po->harga_satuan;
            }

            $suppliersData[] = [
                'nama' => $sup->nama,
                'total_po' => $totalPo,
                'po_diterima' => $poDiterima,
                'po_tepat_waktu' => $poTepatWaktu,
                'ontime_rate' => $ontimeRate,
                'avg_lead_time' => $avgLeadTime,
                'total_nilai' => $totalNilai,
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'suppliers' => $suppliersData,
        ];
    }

    /**
     * Compile Monthly Stock Mutation Report data for a given date range.
     *
     * @return array{
     *   start_date: string,
     *   end_date: string,
     *   materials: array<int, array{kode: string, nama: string, stok_awal: float, masuk: float, keluar: float, stok_akhir: float, satuan: string}>,
     *   finished_goods: array<int, array{kode: string, nama: string, stok_awal: float, masuk: float, keluar: float, stok_akhir: float, satuan: string}>,
     * }
     */
    public function generateMutasiBulanan(string $startDate, string $endDate): array
    {
        $rawMaterials = BahanBaku::orderBy('kode')->get();
        $materialsData = [];

        foreach ($rawMaterials as $bb) {
            $prevDate = date('Y-m-d', strtotime($startDate.' -1 day'));
            $stokAwal = $this->calculateStockAsOf($bb->id, 'bahan_baku', $prevDate);

            $masuk = (float) MutasiStok::where('bahan_baku_id', $bb->id)
                ->where('jenis_mutasi', 'masuk')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('jumlah');

            $keluar = (float) MutasiStok::where('bahan_baku_id', $bb->id)
                ->where('jenis_mutasi', 'keluar')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('jumlah');

            $stokAkhir = $stokAwal + $masuk - $keluar;

            $materialsData[] = [
                'kode' => $bb->kode,
                'nama' => $bb->nama,
                'stok_awal' => $stokAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stokAkhir,
                'satuan' => $bb->satuan,
            ];
        }

        $finishedGoods = FinishedGood::orderBy('kode')->get();
        $fgData = [];

        foreach ($finishedGoods as $fg) {
            $prevDate = date('Y-m-d', strtotime($startDate.' -1 day'));
            $stokAwal = $this->calculateStockAsOf($fg->id, 'finished_good', $prevDate);

            $masuk = (float) MutasiStok::where('finished_goods_id', $fg->id)
                ->where('jenis_mutasi', 'masuk')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('jumlah');

            $keluar = (float) MutasiStok::where('finished_goods_id', $fg->id)
                ->where('jenis_mutasi', 'keluar')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('jumlah');

            $stokAkhir = $stokAwal + $masuk - $keluar;

            $fgData[] = [
                'kode' => $fg->kode,
                'nama' => $fg->nama,
                'stok_awal' => $stokAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stokAkhir,
                'satuan' => $fg->satuan,
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'materials' => $materialsData,
            'finished_goods' => $fgData,
        ];
    }

    /**
     * Back-calculate inventory stock balance as of a given historical date.
     */
    public function calculateStockAsOf(int $itemId, string $itemType, string $asOfDate): float
    {
        if ($itemType === 'bahan_baku') {
            $item = BahanBaku::findOrFail($itemId);
            $currentStock = (float) $item->stok_saat_ini;
            $after = MutasiStok::where('bahan_baku_id', $itemId)
                ->where('tanggal', '>', $asOfDate)
                ->get();
        } else {
            $item = FinishedGood::findOrFail($itemId);
            $currentStock = (float) $item->stok_saat_ini;
            $after = MutasiStok::where('finished_goods_id', $itemId)
                ->where('tanggal', '>', $asOfDate)
                ->get();
        }

        $stok = $currentStock;
        foreach ($after as $mut) {
            if ($mut->jenis_mutasi === 'masuk') {
                $stok -= (float) $mut->jumlah;
            } else {
                $stok += (float) $mut->jumlah;
            }
        }

        return $stok;
    }
}
