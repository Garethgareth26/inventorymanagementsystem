<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Bulanan</title>
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
            margin-bottom: 20px;
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
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">CV. Akuna</div>
        <div class="company-address">Sistem Manajemen Inventori &amp; Optimasi Persediaan</div>
    </div>

    <div class="report-title">Laporan Mutasi Stok Bulanan</div>
    <div class="report-meta">
        Periode: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d M Y') }}
    </div>

    <div class="section-title">Bahan Baku</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Kode</th>
                <th width="35%">Nama Bahan Baku</th>
                <th width="12%" class="text-right">Stok Awal</th>
                <th width="12%" class="text-right">Masuk</th>
                <th width="12%" class="text-right">Keluar</th>
                <th width="12%" class="text-right">Stok Akhir</th>
                <th width="8%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $mut)
                <tr>
                    <td>{{ $mut['kode'] }}</td>
                    <td>{{ $mut['nama'] }}</td>
                    <td class="text-right">{{ number_format($mut['stok_awal'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['masuk'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['keluar'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['stok_akhir'], 2, ',', '.') }}</td>
                    <td>{{ $mut['satuan'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Barang Jadi</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Kode</th>
                <th width="35%">Nama Barang Jadi</th>
                <th width="12%" class="text-right">Stok Awal</th>
                <th width="12%" class="text-right">Masuk</th>
                <th width="12%" class="text-right">Keluar</th>
                <th width="12%" class="text-right">Stok Akhir</th>
                <th width="8%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finished_goods as $mut)
                <tr>
                    <td>{{ $mut['kode'] }}</td>
                    <td>{{ $mut['nama'] }}</td>
                    <td class="text-right">{{ number_format($mut['stok_awal'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['masuk'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['keluar'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($mut['stok_akhir'], 2, ',', '.') }}</td>
                    <td>{{ $mut['satuan'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
