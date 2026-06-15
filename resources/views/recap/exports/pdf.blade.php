<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Top Penyakit</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0 0 5px 0; }
        .meta { color: #666; font-size: 11px; }
        .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; background-color: #e2e8f0; padding: 6px 10px; margin-top: 25px; margin-bottom: 10px; border-left: 4px solid #ef4444; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .rank { width: 50px; text-align: center; }
        .code { width: 80px; text-align: center; font-family: monospace; font-weight: bold; }
        .cases { width: 80px; text-align: right; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; text-align: center; font-size: 11px; color: #999; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Laporan Rekapitulasi Top Penyakit</h1>
        <div class="meta">
            Periode: {{ $from->format('d-m-Y') }} s.d. {{ $to->format('d-m-Y') }} <br>
            Tanggal Unduh: {{ date('d-m-Y H:i:s') }}
        </div>
    </div>

    @if(in_array('umum', $scopes) && $dataUmum->isNotEmpty())
        <div class="section-title">SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)</div>
        <table>
            <thead>
                <tr>
                    <th class="rank">Peringkat</th>
                    <th class="code">Kode Penyakit</th>
                    <th>Nama Penyakit</th>
                    <th class="cases">Kasus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dataUmum as $index => $row)
                    <tr>
                        <td class="rank">{{ $index + 1 }}</td>
                        <td class="code">{{ $row->kode_penyakit }}</td>
                        <td>{{ $row->nama_penyakit ?? $row->kode_penyakit }}</td>
                        <td class="cases">{{ number_format($row->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(in_array('kecamatan', $scopes) && $dataKec->isNotEmpty())
        <div class="section-title">SECTION: TOP PENYAKIT PER KECAMATAN</div>
        @php
            $kecGroups = $dataKec->groupBy('kecamatan');
        @endphp
        @foreach($kecGroups as $kecName => $kecData)
            <div style="font-weight: bold; margin-top: 10px; margin-bottom: 5px; color: #b91c1c;">Kecamatan: {{ $kecName }}</div>
            <table>
                <thead>
                    <tr>
                        <th class="rank">Peringkat</th>
                        <th class="code">Kode Penyakit</th>
                        <th>Nama Penyakit</th>
                        <th class="cases">Kasus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kecData as $index => $row)
                        <tr>
                            <td class="rank">{{ $index + 1 }}</td>
                            <td class="code">{{ $row->kode_penyakit }}</td>
                            <td>{{ $row->nama_penyakit ?? $row->kode_penyakit }}</td>
                            <td class="cases">{{ number_format($row->count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    @if(in_array('puskesmas', $scopes) && $dataPusk->isNotEmpty())
        <div class="section-title">SECTION: TOP PENYAKIT PER PUSKESMAS</div>
        @php
            $puskGroups = $dataPusk->groupBy('nama_puskesmas');
        @endphp
        @foreach($puskGroups as $puskName => $puskData)
            <div style="font-weight: bold; margin-top: 10px; margin-bottom: 5px; color: #1d4ed8;">Puskesmas: {{ $puskName }}</div>
            <table>
                <thead>
                    <tr>
                        <th class="rank">Peringkat</th>
                        <th class="code">Kode Penyakit</th>
                        <th>Nama Penyakit</th>
                        <th class="cases">Kasus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($puskData as $index => $row)
                        <tr>
                            <td class="rank">{{ $index + 1 }}</td>
                            <td class="code">{{ $row->kode_penyakit }}</td>
                            <td>{{ $row->nama_penyakit ?? $row->kode_penyakit }}</td>
                            <td class="cases">{{ number_format($row->count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        Dihasilkan oleh Sistem Informasi Rekam Medis (Auto-Generated)
    </div>
</body>
</html>
