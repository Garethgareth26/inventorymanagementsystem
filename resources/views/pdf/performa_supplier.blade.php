<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Performa Supplier</title>
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
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-name">CV. Akuna</div>
        <div class="company-address">Sistem Manajemen Inventori &amp; Optimasi Persediaan</div>
    </div>

    <div class="report-title">Laporan Performa Supplier</div>
    <div class="report-meta">
        Periode: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d M Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="30%">Nama Supplier</th>
                <th width="10%" class="text-center">Total PO</th>
                <th width="12%" class="text-center">PO Diterima</th>
                <th width="12%" class="text-center">Tepat Waktu</th>
                <th width="12%" class="text-center">Ketepatan (%)</th>
                <th width="12%" class="text-center">Rata-rata LT</th>
                <th width="12%" class="text-right">Total Transaksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $sup)
                <tr>
                    <td>{{ $sup['nama'] }}</td>
                    <td class="text-center">{{ $sup['total_po'] }}</td>
                    <td class="text-center">{{ $sup['po_diterima'] }}</td>
                    <td class="text-center">{{ $sup['po_tepat_waktu'] }}</td>
                    <td class="text-center">{{ number_format($sup['ontime_rate'], 1, ',', '.') }}%</td>
                    <td class="text-center">{{ number_format($sup['avg_lead_time'], 1, ',', '.') }} hari</td>
                    <td class="text-right">Rp {{ number_format($sup['total_nilai'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
