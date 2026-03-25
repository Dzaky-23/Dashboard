<?php
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"Laporan_Rekap_Penyakit_" . date('Ymd_His') . ".xls\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .table {
            border-collapse: collapse;
        }
        .section-title {
            color: #FF0000;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }
        .header {
            border: 1px solid #000000;
            background-color: #FFFFFF;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }
        .cell-center {
            border: 1px solid #000000;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: center;
        }
        .cell-left {
            border: 1px solid #000000;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }
    </style>
</head>
<body>
    <table class="table" border="0" cellpadding="2">
        <!-- Section 1 -->
        <tr>
            <td colspan="3" class="section-title">SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)</td>
        </tr>
        <tr>
            <th class="header">Peringkat</th>
            <th class="header">Kode Penyakit (ICD-X)</th>
            <th class="header">Jumlah Kasus</th>
        </tr>
        @foreach($topUmum as $index => $row)
        <tr>
            <td class="cell-center">{{ $index + 1 }}</td>
            <td class="cell-left">{{ $row->kode_penyakit }}</td>
            <td class="cell-center">{{ $row->count }}</td>
        </tr>
        @endforeach
        <tr><td colspan="3"></td></tr>

        <!-- Section 2 -->
        <tr>
            <td colspan="3" class="section-title">SECTION: TOP PENYAKIT PER KECAMATAN</td>
        </tr>
        @foreach($kecamatanData as $kecName => $data)
        <tr>
            <td colspan="3" class="section-title">Kecamatan: {{ strtoupper($kecName) }}</td>
        </tr>
        <tr>
            <th class="header">Peringkat</th>
            <th class="header">Kode Penyakit (ICD-X)</th>
            <th class="header">Jumlah Kasus</th>
        </tr>
        @foreach($data as $index => $row)
        <tr>
            <td class="cell-center">{{ $index + 1 }}</td>
            <td class="cell-left">{{ $row->kode_penyakit }}</td>
            <td class="cell-center">{{ $row->count }}</td>
        </tr>
        @endforeach
        <tr><td colspan="3"></td></tr>
        @endforeach

        <!-- Section 3 -->
        <tr>
            <td colspan="3" class="section-title">SECTION: TOP PENYAKIT PER PUSKESMAS</td>
        </tr>
        @foreach($puskesmasData as $puskName => $data)
        <tr>
            <td colspan="3" class="section-title">Puskesmas: {{ strtoupper($puskName) }}</td>
        </tr>
        <tr>
            <th class="header">Peringkat</th>
            <th class="header">Kode Penyakit (ICD-X)</th>
            <th class="header">Jumlah Kasus</th>
        </tr>
        @foreach($data as $index => $row)
        <tr>
            <td class="cell-center">{{ $index + 1 }}</td>
            <td class="cell-left">{{ $row->kode_penyakit }}</td>
            <td class="cell-center">{{ $row->count }}</td>
        </tr>
        @endforeach
        <tr><td colspan="3"></td></tr>
        @endforeach
    </table>
</body>
</html>
