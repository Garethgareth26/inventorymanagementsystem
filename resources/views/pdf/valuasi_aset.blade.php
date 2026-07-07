<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Valuasi Aset Gudang</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-address {
            font-size: 9px;
            color: #666666;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .report-meta {
            font-size: 9px;
            color: #666666;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border: 1px solid #dddddd;
            padding: 6px;
            text-align: left;
        }
        td {
            border: 1px solid #dddddd;
            padding: 6px;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .total-row {
            font-weight: bold;
            background-color: #fcfcfc;
        }
        .summary-box {
            float: right;
            width: 250px;
            border: 1px solid #dddddd;
            padding: 8px;
            margin-top: 15px;
            background-color: #fafafa;
        }
        .summary-row {
            margin-bottom: 5px;
        }
        .summary-label {
            float: left;
        }
        .summary-value {
            float: right;
            font-weight: bold;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">CV. Akuna</div>
        <div class="company-address">Sistem Manajemen Inventori &amp; Optimasi Persediaan</div>
    </div>

    <div class="report-title">Laporan Valuasi Aset Gudang</div>
    <div class="report-meta">As of Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</div>

    <div class="section-title">Aset Bahan Baku</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Kode</th>
                <th width="35%">Bahan Baku</th>
                <th width="12%" class="text-right">Stok</th>
                <th width="8%">Satuan</th>
                <th width="15%" class="text-right">Harga Satuan</th>
                <th width="15%" class="text-right">Nilai Aset</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $bb)
                <tr>
                    <td>{{ $bb['kode'] }}</td>
                    <td>{{ $bb['nama'] }}</td>
                    <td class="text-right">{{ number_format($bb['stok'], 2, ',', '.') }}</td>
                    <td>{{ $bb['satuan'] }}</td>
                    <td class="text-right">Rp {{ number_format($bb['harga'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($bb['nilai'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Aset Bahan Baku:</td>
                <td class="text-right">Rp {{ number_format($total_materials, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Aset Barang Jadi</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Kode</th>
                <th width="35%">Barang Jadi</th>
                <th width="12%" class="text-right">Stok</th>
                <th width="8%">Satuan</th>
                <th width="15%" class="text-right">Biaya Produksi/Unit</th>
                <th width="15%" class="text-right">Nilai Aset</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finished_goods as $fg)
                <tr>
                    <td>{{ $fg['kode'] }}</td>
                    <td>{{ $fg['nama'] }}</td>
                    <td class="text-right">{{ number_format($fg['stok'], 2, ',', '.') }}</td>
                    <td>{{ $fg['satuan'] }}</td>
                    <td class="text-right">Rp {{ number_format($fg['harga'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($fg['nilai'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="text-right">Total Aset Barang Jadi:</td>
                <td class="text-right">Rp {{ number_format($total_finished_goods, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row">
            <span class="summary-label">Total Bahan Baku:</span>
            <span class="summary-value">Rp {{ number_format($total_materials, 0, ',', '.') }}</span>
            <div class="clear"></div>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Barang Jadi:</span>
            <span class="summary-value">Rp {{ number_format($total_finished_goods, 0, ',', '.') }}</span>
            <div class="clear"></div>
        </div>
        <div class="summary-row" style="border-top: 1px solid #dddddd; padding-top: 5px; margin-top: 5px;">
            <span class="summary-label">Nilai Aset Keseluruhan:</span>
            <span class="summary-value">Rp {{ number_format($grand_total, 0, ',', '.') }}</span>
            <div class="clear"></div>
        </div>
    </div>
    <div class="clear"></div>

</body>
</html>
