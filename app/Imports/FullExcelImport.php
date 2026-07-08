<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FullExcelImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Master Bahan Baku' => new MasterBahanBakuSheetImport(),
            'ABC-EOQ-SS-ROP' => new ParameterAbcSheetImport(),
            'Data Pemesanan' => new DataPemesananSheetImport(),
        ];
    }
}
