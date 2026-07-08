<?php

namespace App\Imports;

use App\Models\BahanBaku;
use App\Models\PesananPembelian;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;

class DataPemesananSheetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $user = User::first(); // fallback user

        foreach ($rows as $row) {
            if (!isset($row['kode']) || !isset($row['jumlah_pesan'])) {
                continue;
            }

            $kodeBahan = trim($row['kode']);
            $bahanBaku = BahanBaku::where('kode', $kodeBahan)->first();
            
            if (!$bahanBaku) {
                continue;
            }

            // Resolve supplier
            $supplierName = trim($row['supplier'] ?? '');
            $supplier = Supplier::where('nama', $supplierName)->first();
            
            $kota = trim($row['kota'] ?? '');

            if (!$supplier && $supplierName !== '') {
                $supplier = Supplier::create([
                    'kode' => 'SUP-' . strtoupper(Str::random(5)),
                    'nama' => $supplierName,
                    'alamat' => $kota !== '' ? $kota : null,
                    'is_active' => true
                ]);
            } elseif ($supplier && $kota !== '') {
                // Update empty alamat with kota if missing
                if (empty($supplier->alamat)) {
                    $supplier->update(['alamat' => $kota]);
                }
            }

            $jumlah = $this->parseNumber($row['jumlah_pesan']);
            $harga = $this->parseNumber($row['harga_satuan_rp'] ?? 0);
            
            $jenisStr = strtolower(trim($row['jenis_pesanan'] ?? ''));
            $jenis = str_contains($jenisStr, 'darurat') ? PesananPembelian::JENIS_DARURAT : PesananPembelian::JENIS_RUTIN;
            
            // Generate PO code
            $kodePo = 'PO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

            // Dates
            $tanggalPesan = $this->parseDate($row['tanggal_pesan'] ?? null) ?? now();
            $estimasiTiba = $this->parseDate($row['estimasi_tiba'] ?? null) ?? $tanggalPesan->copy()->addDays((int)($row['lead_time_hari'] ?? 3));

            PesananPembelian::create([
                'kode_po' => $kodePo,
                'bahan_baku_id' => $bahanBaku->id,
                'supplier_id' => $supplier ? $supplier->id : $bahanBaku->supplier_id,
                'jumlah' => $jumlah,
                'harga_satuan' => $harga > 0 ? $harga : $bahanBaku->harga_satuan,
                'status' => PesananPembelian::STATUS_MENUNGGU, // Default to menunggu to allow system logic to handle receipt
                'jenis' => $jenis,
                'tanggal_pesan' => $tanggalPesan->toDateString(),
                'estimasi_tiba' => $estimasiTiba->toDateString(),
                'dicatat_oleh' => auth()->id() ?? $user->id,
            ]);
        }
    }

    private function parseNumber($val)
    {
        if (is_numeric($val)) return (float) $val;
        if (is_string($val)) {
            $val = str_replace(['Rp', ',', ' ', '%'], '', $val);
            return (float) $val;
        }
        return 0.0;
    }

    private function parseDate($val)
    {
        if (empty($val)) return null;
        try {
            // Excel dates are sometimes numbers
            if (is_numeric($val)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val));
            }
            return Carbon::parse($val);
        } catch (\Exception $e) {
            return null;
        }
    }
}
