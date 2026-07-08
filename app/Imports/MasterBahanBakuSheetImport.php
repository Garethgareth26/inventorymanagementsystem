<?php

namespace App\Imports;

use App\Models\BahanBaku;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MasterBahanBakuSheetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!isset($row['kode']) || !isset($row['nama_bahan_baku'])) {
                continue;
            }

            // 1. Resolve Supplier
            $supplierName = trim($row['supplier_semarang_rutin'] ?? '');
            $supplierId = null;

            if ($supplierName !== '' && $supplierName !== '-') {
                // Find or create the supplier
                $supplier = Supplier::firstOrCreate(
                    ['nama' => $supplierName],
                    ['kode' => 'SUP-' . strtoupper(Str::random(5)), 'is_active' => true]
                );
                $supplierId = $supplier->id;
            }

            // 2. Resolve or Create Bahan Baku
            $kode = trim($row['kode']);
            
            $harga = $row['harga_rutin_rp'] ?? 0;
            // Clean up currency formatting if any
            if (is_string($harga)) {
                $harga = floatval(str_replace(['Rp', ',', '.', ' '], '', $harga));
            }

            $leadTime = $row['lead_time_rutin_hari'] ?? 0;
            if (is_string($leadTime)) {
                $leadTime = intval(preg_replace('/[^0-9]/', '', $leadTime));
            }

            // In FullExcelImport, we don't insert initial stock here, we insert it when parsing ABC sheet!
            BahanBaku::updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => trim($row['nama_bahan_baku']),
                    'satuan' => trim($row['satuan'] ?? 'pcs'),
                    'supplier_id' => $supplierId,
                    'harga_satuan' => $harga,
                    'lead_time_hari' => $leadTime,
                ]
            );
        }
    }
}
