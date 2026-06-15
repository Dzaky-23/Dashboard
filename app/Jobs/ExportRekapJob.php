<?php

namespace App\Jobs;

use App\Models\JobStatus;
use App\Services\RekapHarianService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportRekapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;
    public int $backoff = 60;

    protected string $jobId;
    protected string $from;
    protected string $to;
    protected array $scopes;
    protected int $topNUmum;
    protected int $topNKecamatan;
    protected int $topNPuskesmas;
    protected ?array $selectedKecamatan;
    protected ?array $selectedPuskesmas;
    protected string $format;
    protected ?int $userId;
    protected array $filters;

    public function __construct(
        string $jobId,
        string $from,
        string $to,
        array $scopes,
        int $topNUmum,
        int $topNKecamatan,
        int $topNPuskesmas,
        ?array $selectedKecamatan,
        ?array $selectedPuskesmas,
        string $format,
        ?int $userId = null,
        array $filters = []
    ) {
        $this->jobId = $jobId;
        $this->from = $from;
        $this->to = $to;
        $this->scopes = $scopes;
        $this->topNUmum = $topNUmum;
        $this->topNKecamatan = $topNKecamatan;
        $this->topNPuskesmas = $topNPuskesmas;
        $this->selectedKecamatan = $selectedKecamatan;
        $this->selectedPuskesmas = $selectedPuskesmas;
        $this->format = strtolower($format);
        $this->userId = $userId;
        $this->filters = $filters;
    }

    public function handle(RekapHarianService $service): void
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $jobStatus = JobStatus::find($this->jobId);
        if (!$jobStatus) {
            Log::error("JobStatus record not found for export job: " . $this->jobId);
            return;
        }

        $jobStatus->update(['status' => 'processing']);

        try {
            $fromCarbon = Carbon::parse($this->from);
            $toCarbon = Carbon::parse($this->to);

            $includePrefixes = $this->filters['include_prefixes'] ?? [];
            $excludePrefixes = $this->filters['exclude_prefixes'] ?? [];
            $includeCodes = $this->filters['include_codes'] ?? [];
            $excludeCodes = $this->filters['exclude_codes'] ?? [];
            $excludeExceptions = $this->filters['exclude_exceptions'] ?? [];
            if (is_string($excludeExceptions)) {
                $excludeExceptions = array_filter(array_map('trim', explode(',', $excludeExceptions)));
            }
            $exceptionPrefixes = $this->filters['exception_prefixes'] ?? [];
            $exceptionCodes = $this->filters['exception_codes'] ?? [];
            $excludeExceptions = array_values(array_unique(array_merge($excludeExceptions, $exceptionPrefixes, $exceptionCodes)));

            $dataUmum = collect();
            $dataKec = collect();
            $dataPusk = collect();

            if (in_array('umum', $this->scopes)) {
                $dataUmum = $service->queryTopUmum($fromCarbon, $toCarbon, $this->topNUmum, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
            }
            if (in_array('kecamatan', $this->scopes)) {
                $dataKec = $service->queryTopPerKecamatan($fromCarbon, $toCarbon, $this->topNKecamatan, $this->selectedKecamatan, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
            }
            if (in_array('puskesmas', $this->scopes)) {
                $dataPusk = $service->queryTopPerPuskesmas($fromCarbon, $toCarbon, $this->topNPuskesmas, $this->selectedPuskesmas, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
            }

            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0775, true);
            }

            $scopesStr = implode('_', $this->scopes);
            $fileName = "Laporan_Rekap_{$scopesStr}_" . date('Ymd_His') . "_" . substr($this->jobId, 0, 8);
            $filePath = '';

            if ($this->format === 'excel') {
                $fileName .= '.xlsx';
                $filePath = storage_path('app/exports/' . $fileName);
                $this->generateExcel($dataUmum, $dataKec, $dataPusk, $filePath);
            } elseif ($this->format === 'csv') {
                $fileName .= '.csv';
                $filePath = storage_path('app/exports/' . $fileName);
                $this->generateCsv($dataUmum, $dataKec, $dataPusk, $filePath);
            } elseif ($this->format === 'pdf') {
                $fileName .= '.pdf';
                $filePath = storage_path('app/exports/' . $fileName);
                $this->generatePdf($dataUmum, $dataKec, $dataPusk, $filePath, $fromCarbon, $toCarbon);
            } else {
                throw new \Exception("Format export tidak valid: " . $this->format);
            }

            $jobStatus->update([
                'status' => 'done',
                'output_path' => $fileName,
            ]);

        } catch (\Exception $e) {
            Log::error("ExportRekapJob failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $jobStatus->update([
                'status' => 'failed',
                'error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
        }
    }

    private function generateExcel($dataUmum, $dataKec, $dataPusk, string $filePath): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Worksheet');

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(18);

        $currentRow = 1;
        $charts = [];

        $addChart = function ($titleText, $startRow, $endRow, $currentRow) use (&$charts) {
            $xAxisTickValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "'Worksheet'!\$C\${$startRow}:\$C\${$endRow}", null, $endRow - $startRow + 1)
            ];
            $dataSeriesValues = [
                new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "'Worksheet'!\$D\${$startRow}:\$D\${$endRow}", null, $endRow - $startRow + 1)
            ];

            $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_STANDARD,
                range(0, count($dataSeriesValues) - 1),
                [],
                $xAxisTickValues,
                $dataSeriesValues
            );
            $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_BAR);

            $layout = new \PhpOffice\PhpSpreadsheet\Chart\Layout();
            $layout->setShowVal(true);
            $layout->setShowCatName(false);
            $layout->setDLblPos('outEnd');

            $categoryAxis = new \PhpOffice\PhpSpreadsheet\Chart\Axis();
            $categoryAxis->setAxisOptionsProperties(
                \PhpOffice\PhpSpreadsheet\Chart\Properties::AXIS_LABELS_NEXT_TO,
                null,
                null,
                \PhpOffice\PhpSpreadsheet\Chart\Properties::ORIENTATION_REVERSED
            );

            $valueAxis = new \PhpOffice\PhpSpreadsheet\Chart\Axis();

            $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea($layout, [$series]);
            $title = new \PhpOffice\PhpSpreadsheet\Chart\Title($titleText);

            $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
                'chart_' . $startRow,
                $title,
                null,
                $plotArea,
                true,
                0,
                null,
                null,
                $categoryAxis,
                $valueAxis
            );

            $chart->setTopLeftPosition('F' . ($startRow - 1));
            $chart->setBottomRightPosition('Q' . max($endRow, $startRow + 12));
            $charts[] = $chart;
        };

        if (in_array('umum', $this->scopes) && $dataUmum->isNotEmpty()) {
            $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)');
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $currentRow += 2;

            $sheet->setCellValue("A{$currentRow}", 'Peringkat');
            $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
            $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
            $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
            $currentRow++;

            $startRow = $currentRow;
            foreach ($dataUmum as $index => $row) {
                $sheet->setCellValue("A{$currentRow}", $index + 1);
                $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                $sheet->setCellValue("D{$currentRow}", $row->total);
                $currentRow++;
            }
            if ($currentRow > $startRow) {
                $addChart('Top Penyakit Umum', $startRow, $currentRow - 1, $currentRow);
            }
            $currentRow += 2;
        }

        if (in_array('kecamatan', $this->scopes) && $dataKec->isNotEmpty()) {
            $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT PER KECAMATAN');
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $currentRow += 2;

            $kecamatanData = $dataKec->groupBy('kecamatan');

            foreach ($kecamatanData as $kecName => $kecData) {
                $sheet->setCellValue("A{$currentRow}", "Kecamatan: $kecName");
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow += 2;

                $sheet->setCellValue("A{$currentRow}", 'Peringkat');
                $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
                $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
                $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
                $currentRow++;

                $startRow = $currentRow;
                foreach ($kecData as $index => $row) {
                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                    $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                    $sheet->setCellValue("D{$currentRow}", $row->count);
                    $currentRow++;
                }
                if ($currentRow > $startRow) {
                    $addChart("Top Penyakit - $kecName", $startRow, $currentRow - 1, $currentRow);
                }
                $currentRow += 2;
            }
        }

        if (in_array('puskesmas', $this->scopes) && $dataPusk->isNotEmpty()) {
            $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT PER PUSKESMAS');
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $currentRow += 2;

            $puskesmasData = $dataPusk->groupBy('nama_puskesmas');

            foreach ($puskesmasData as $puskName => $puskData) {
                $sheet->setCellValue("A{$currentRow}", "Puskesmas: $puskName");
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow += 2;

                $sheet->setCellValue("A{$currentRow}", 'Peringkat');
                $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
                $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
                $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
                $currentRow++;

                $startRow = $currentRow;
                foreach ($puskData as $index => $row) {
                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                    $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                    $sheet->setCellValue("D{$currentRow}", $row->count);
                    $currentRow++;
                }
                if ($currentRow > $startRow) {
                    $addChart("Top Penyakit - $puskName", $startRow, $currentRow - 1, $currentRow);
                }
                $currentRow += 2;
            }
        }

        foreach ($charts as $chart) {
            $sheet->addChart($chart);
        }

        // Generate Detail Sebaran Penyakit sheet if scope contains 'umum'
        $topDiseaseCodes = $dataUmum->pluck('kode_penyakit')->unique()->toArray();
        if (in_array('umum', $this->scopes) && !empty($topDiseaseCodes)) {
            $fromCarbon = Carbon::parse($this->from);
            $toCarbon = Carbon::parse($this->to);

            $kecBreakdown = \Illuminate\Support\Facades\DB::table('rekap_harian as rh')
                ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                ->join('kecamatan as k', 'p.kode_kc', '=', 'k.kode_kc')
                ->select([
                    'k.kode_kc as kode_kecamatan',
                    'k.kecamatan as nama_kecamatan',
                    'rh.kode_penyakit',
                    \Illuminate\Support\Facades\DB::raw("SUM(rh.jumlah_kasus) as count")
                ])
                ->whereBetween('rh.tanggal', [$fromCarbon->toDateString(), $toCarbon->toDateString()])
                ->whereIn('rh.kode_penyakit', $topDiseaseCodes)
                ->groupBy('k.kode_kc', 'k.kecamatan', 'rh.kode_penyakit')
                ->get();

            $puskBreakdown = \Illuminate\Support\Facades\DB::table('rekap_harian as rh')
                ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                ->select([
                    'p.kode_p as kpusk',
                    'p.nama as nama_puskesmas',
                    'rh.kode_penyakit',
                    \Illuminate\Support\Facades\DB::raw("SUM(rh.jumlah_kasus) as count")
                ])
                ->whereBetween('rh.tanggal', [$fromCarbon->toDateString(), $toCarbon->toDateString()])
                ->whereIn('rh.kode_penyakit', $topDiseaseCodes)
                ->groupBy('p.kode_p', 'p.nama', 'rh.kode_penyakit')
                ->get();

            $kecBreakdownGrouped = $kecBreakdown->groupBy('kode_penyakit');
            $puskBreakdownGrouped = $puskBreakdown->groupBy('kode_penyakit');

            $detailSheet = $spreadsheet->createSheet();
            $detailSheet->setTitle('Detail Sebaran Penyakit');

            $detailSheet->getColumnDimension('A')->setWidth(8);
            $detailSheet->getColumnDimension('B')->setWidth(25);
            $detailSheet->getColumnDimension('C')->setWidth(15);
            $detailSheet->getColumnDimension('D')->setWidth(5);
            $detailSheet->getColumnDimension('E')->setWidth(8);
            $detailSheet->getColumnDimension('F')->setWidth(25);
            $detailSheet->getColumnDimension('G')->setWidth(15);

            $rowNum = 1;
            $puskesmasNames = \App\Services\RecapLogicService::getPuskesmasNames();

            foreach ($dataUmum as $index => $disease) {
                $kode = $disease->kode_penyakit;
                $nama = $disease->nama_penyakit ?? $kode;
                $total = $disease->total;

                $detailSheet->mergeCells("A{$rowNum}:G{$rowNum}");
                $detailSheet->setCellValue("A{$rowNum}", "Peringkat #" . ($index + 1) . ": {$nama} ({$kode}) - Total: {$total} Kasus");
                $detailSheet->getStyle("A{$rowNum}")->getFont()->setBold(true)->setSize(12);
                $detailSheet->getStyle("A{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                $rowNum++;

                $detailSheet->mergeCells("A{$rowNum}:C{$rowNum}");
                $detailSheet->setCellValue("A{$rowNum}", 'Peringkat Sebaran Kecamatan');
                $detailSheet->getStyle("A{$rowNum}")->getFont()->setBold(true);

                $detailSheet->mergeCells("E{$rowNum}:G{$rowNum}");
                $detailSheet->setCellValue("E{$rowNum}", 'Peringkat Sebaran Puskesmas');
                $detailSheet->getStyle("E{$rowNum}")->getFont()->setBold(true);
                $rowNum++;

                $detailSheet->setCellValue("A{$rowNum}", 'Rank');
                $detailSheet->setCellValue("B{$rowNum}", 'Nama Kecamatan');
                $detailSheet->setCellValue("C{$rowNum}", 'Kasus');

                $detailSheet->setCellValue("E{$rowNum}", 'Rank');
                $detailSheet->setCellValue("F{$rowNum}", 'Nama Puskesmas');
                $detailSheet->setCellValue("G{$rowNum}", 'Kasus');

                $detailSheet->getStyle("A{$rowNum}:C{$rowNum}")->getFont()->setItalic(true);
                $detailSheet->getStyle("E{$rowNum}:G{$rowNum}")->getFont()->setItalic(true);
                $rowNum++;

                $diseaseKec = $kecBreakdownGrouped->get($kode, collect())->sortByDesc('count')->values();
                $diseasePusk = $puskBreakdownGrouped->get($kode, collect())->sortByDesc('count')->values();

                $maxRows = max($diseaseKec->count(), $diseasePusk->count(), 1);

                for ($i = 0; $i < $maxRows; $i++) {
                    if ($i < $diseaseKec->count()) {
                        $kecItem = $diseaseKec[$i];
                        $namaKec = strtoupper(trim($kecItem->nama_kecamatan ?? $kecItem->kode_kecamatan));
                        $detailSheet->setCellValue("A{$rowNum}", $i + 1);
                        $detailSheet->setCellValue("B{$rowNum}", $namaKec);
                        $detailSheet->setCellValue("C{$rowNum}", $kecItem->count);
                    } else if ($i == 0 && $diseaseKec->isEmpty()) {
                        $detailSheet->setCellValue("B{$rowNum}", 'Tidak ada data');
                    }

                    if ($i < $diseasePusk->count()) {
                        $puskItem = $diseasePusk[$i];
                        $namaPusk = $puskItem->nama_puskesmas ?? ($puskesmasNames[$puskItem->kpusk] ?? $puskItem->kpusk);
                        $detailSheet->setCellValue("E{$rowNum}", $i + 1);
                        $detailSheet->setCellValue("F{$rowNum}", $namaPusk);
                        $detailSheet->setCellValue("G{$rowNum}", $puskItem->count);
                    } else if ($i == 0 && $diseasePusk->isEmpty()) {
                        $detailSheet->setCellValue("F{$rowNum}", 'Tidak ada data');
                    }
                    $rowNum++;
                }

                $rowNum += 2;
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($filePath);
    }

    private function generateCsv($dataUmum, $dataKec, $dataPusk, string $filePath): void
    {
        $file = fopen($filePath, 'w');

        if (in_array('umum', $this->scopes) && $dataUmum->isNotEmpty()) {
            fputcsv($file, ['SECTION: TOP PENYAKIT UMUM']);
            fputcsv($file, ['Peringkat', 'Kode Penyakit', 'Nama Penyakit', 'Jumlah Kasus']);
            $rank = 1;
            foreach ($dataUmum as $row) {
                fputcsv($file, [$rank++, $row->kode_penyakit, $row->nama_penyakit, $row->total]);
            }
            fputcsv($file, []);
        }

        if (in_array('kecamatan', $this->scopes) && $dataKec->isNotEmpty()) {
            fputcsv($file, ['SECTION: TOP PENYAKIT PER KECAMATAN']);
            fputcsv($file, ['Kecamatan', 'Peringkat', 'Kode Penyakit', 'Nama Penyakit', 'Jumlah Kasus']);
            foreach ($dataKec as $row) {
                fputcsv($file, [$row->kecamatan, $row->rnk, $row->kode_penyakit, $row->nama_penyakit, $row->count]);
            }
            fputcsv($file, []);
        }

        if (in_array('puskesmas', $this->scopes) && $dataPusk->isNotEmpty()) {
            fputcsv($file, ['SECTION: TOP PENYAKIT PER PUSKESMAS']);
            fputcsv($file, ['Puskesmas', 'Peringkat', 'Kode Penyakit', 'Nama Penyakit', 'Jumlah Kasus']);
            foreach ($dataPusk as $row) {
                fputcsv($file, [$row->nama_puskesmas, $row->rnk, $row->kode_penyakit, $row->nama_penyakit, $row->count]);
            }
        }

        fclose($file);
    }

    private function generatePdf($dataUmum, $dataKec, $dataPusk, string $filePath, Carbon $from, Carbon $to): void
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = view('recap.exports.pdf', [
            'scopes' => $this->scopes,
            'dataUmum' => $dataUmum,
            'dataKec' => $dataKec,
            'dataPusk' => $dataPusk,
            'from' => $from,
            'to' => $to,
            'topNUmum' => $this->topNUmum,
            'topNKecamatan' => $this->topNKecamatan,
            'topNPuskesmas' => $this->topNPuskesmas,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($filePath, $dompdf->output());
    }

    public function failed(\Throwable $exception): void
    {
        $jobStatus = JobStatus::find($this->jobId);
        if ($jobStatus) {
            $jobStatus->update([
                'status' => 'failed',
                'error' => $exception->getMessage() . "\n" . $exception->getTraceAsString(),
            ]);
        }
    }
}
