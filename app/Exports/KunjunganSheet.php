<?php

namespace App\Exports;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class KunjunganSheet implements WithTitle, WithEvents, WithColumnFormatting
{
    protected $sales; // Daftar sales
    protected $fromDate; // Tanggal mulai
    protected $toDate;   // Tanggal akhir

    /**
     * Constructor untuk inisialisasi parameter.
     *
     * @param array $sales Daftar sales.
     * @param string $fromDate Tanggal awal (format Y-m-d).
     * @param string $toDate Tanggal akhir (format Y-m-d).
     */
    public function __construct($sales, $fromDate, $toDate)
    {
        $this->sales = $sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * Register event yang digunakan pada Excel.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $this->setHeader($sheet);
                $dates = $this->getDateRange();
                $daysMap = $this->getDaysMap();
                $this->populateData($sheet, $dates, $daysMap);
                $this->autoSizeColumns($sheet);
            }
        ];
    }

    /**
     * Set header untuk worksheet.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     */
    private function setHeader($sheet)
    {
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->setCellValue('A1', 'Tgl. Kunjungan');
        $sheet->setCellValue('B1', 'Hari');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $sheet->getDelegate()->getStyle('A1:B2')->applyFromArray($styleArray);
    }

    /**
     * Generate daftar tanggal dari $fromDate hingga $toDate.
     *
     * @return array
     */
    private function getDateRange(): array
    {
        $start = new DateTime($this->fromDate);
        $end = new DateTime($this->toDate);
        $dates = [];

        while ($start <= $end) {
            $dates[] = $start->format('Y-m-d');
            $start->modify('+1 day');
        }

        return $dates;
    }

    /**
     * Map nama hari dalam bahasa Inggris ke bahasa Indonesia.
     *
     * @return array
     */
    private function getDaysMap(): array
    {
        return [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu',
        ];
    }

    /**
     * Populate data ke dalam sheet berdasarkan tanggal dan sales.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array $dates
     * @param array $daysMap
     */
    private function populateData($sheet, $dates, $daysMap)
    {
        $startColumn = 3;

        foreach ($this->sales as $salesName) {
            $this->setSalesHeaders($sheet, $startColumn, $salesName);
            $this->fillSalesData($sheet, $dates, $daysMap, $startColumn, $salesName);
            $startColumn++;
        }
    }

    /**
     * Set header untuk setiap sales.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $startColumn
     * @param string $salesName
     */
    private function setSalesHeaders($sheet, $startColumn, $salesName)
    {
        $sheet->setCellValueByColumnAndRow($startColumn, 1, $salesName);
        $sheet->mergeCellsByColumnAndRow($startColumn, 1, $startColumn, 2);
        $this->styleHeader($sheet, $startColumn);
    }

    /**
     * Apply style untuk header sales.
     *
     * @param int $column
     */
    private function styleHeader($sheet, $column)
    {
        $sheet->getStyleByColumnAndRow($column, 1)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyleByColumnAndRow($column, 1)
            ->getFont()
            ->setBold(true);
    }

    /**
     * Isi data kunjungan berdasarkan sales.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array $dates
     * @param array $daysMap
     * @param int $column
     * @param string $salesName
     */
    private function fillSalesData($sheet, $dates, $daysMap, $column, $salesName)
    {
        $excludedStores = config('absen_toko.absen_toko');

        foreach ($dates as $index => $date) {
            $row = $index + 3;

            // Set tanggal kunjungan
            $this->setVisitDate($sheet, $date, $row);

            // Set hari
            $this->setDay($sheet, $date, $row, $daysMap);

            // Ambil total kunjungan
            $totalVisits = DB::table('trans_dks')
                ->where('user_sales', $salesName)
                ->where('tgl_kunjungan', $date)
                ->whereNotIn('kd_toko', $excludedStores)
                ->where('type', 'in')
                ->count();

            // Isi total kunjungan
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $sheet->setCellValue("{$columnLetter}{$row}", $totalVisits);
        }
    }

    /**
     * Set tanggal kunjungan di kolom A.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $date
     * @param int $row
     */
    private function setVisitDate($sheet, $date, $row)
    {
        $excelDate = Date::dateTimeToExcel(Carbon::parse($date));
        $sheet->setCellValue("A{$row}", $excelDate);
        $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }

    /**
     * Set nama hari di kolom B.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $date
     * @param int $row
     * @param array $daysMap
     */
    private function setDay($sheet, $date, $row, $daysMap)
    {
        $dayName = $daysMap[Carbon::parse($date)->format('D')] ?? '';
        $sheet->setCellValue("B{$row}", $dayName);
    }

    /**
     * Set auto-size untuk semua kolom.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     */
    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }

    /**
     * Nama worksheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Kunjungan Harian';
    }

    /**
     * Format kolom dalam worksheet.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
