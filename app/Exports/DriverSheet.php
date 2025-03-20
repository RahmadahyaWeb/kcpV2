<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DriverSheet implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, WithMapping, WithColumnFormatting, WithTitle
{
    protected $user_sales;
    protected $fromDate;
    protected $toDate;
    protected $items;

    public function __construct($user_sales, $fromDate, $toDate, $items)
    {
        $this->user_sales = $user_sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->items = $items;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // SET HEADER MERGE CELL
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells('E1:E2');
                $sheet->mergeCells('F1:F2');
                $sheet->mergeCells('G1:G2');
                $sheet->mergeCells('H1:H2');
                $sheet->mergeCells('I1:I2');

                // SET HEADER TITLE
                $sheet->setCellValue('A1', "Sales");
                $sheet->setCellValue('B1', "Tgl.Kunjungan");
                $sheet->setCellValue('C1', "Kode Toko");
                $sheet->setCellValue('D1', "Toko");
                $sheet->setCellValue('E1', "Check In");
                $sheet->setCellValue('F1', "Check Out");
                $sheet->setCellValue('G1', "Keterangan");
                $sheet->setCellValue('H1', "Durasi Kunjungan");
                $sheet->setCellValue('I1', "Durasi Perjalanan");

                // HEADER STYLE
                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                ];

                $cellRange = 'A1:O2';
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray($styleArray);

                $sheet->getRowDimension(2)->setRowHeight(20);

                // Enable auto width for all columns
                foreach (range('A', 'O') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                // Mengatur alignment untuk kolom C
                $sheet->getDelegate()->getStyle('C:C')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Mengatur alignment untuk kolom E-F
                $sheet->getDelegate()->getStyle('E:F')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Mengatur alignment untuk kolom H-O
                $sheet->getDelegate()->getStyle('H:O')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // FREEZE PANE
                $event->sheet->getDelegate()->freezePane('H1');
            },
        ];
    }


    public function headings(): array
    {
        return [
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
        ];
    }

    public function collection()
    {
        // Ambil $items dari properti yang sudah ada
        $items = collect($this->items);

        // Langsung kembalikan koleksi tanpa flatMap, cukup map untuk memodifikasi setiap row
        return $items->map(function ($row) {
            // Mungkin Anda bisa menambahkan proses lain di sini jika perlu
            return $row; // Pastikan Anda mengembalikan struktur data asli
        });
    }

    public function map($row): array
    {
        $tokoAbsen = config('absen_toko.absen_toko');

        // NAMA LENGKAP SALES
        $nama_lengkap_sales = $row->name;

        // TGL KUNJUNGAN
        $tgl_kunjungan = Date::dateTimeToExcel(Carbon::parse($row->tgl_kunjungan));

        // KODE TOKO
        $kd_toko = $row->kd_toko;

        // NAMA TOKO
        if ($kd_toko && $kd_toko != 'TQ2') {
            if (strpos($kd_toko, 'E_') !== false) {
                $nama_toko = DB::connection('mysql')
                    ->table('mst_expedition')
                    ->select(['kd_expedition', 'nama_expedition', 'latitude', 'longitude'])
                    ->where('kd_expedition', $kd_toko)
                    ->value('nama_expedition');
            } else {
                $nama_toko = DB::connection('kcpinformation')
                    ->table('mst_outlet')
                    ->where('kd_outlet', $kd_toko)
                    ->value('nm_outlet');
            }
        } else if ($kd_toko == 'TQ2') {
            $nama_toko = 'SINAR TAQWA MOTOR 2';
        } else {
            $nama_toko = '';
        }

        // WAKTU CEK IN
        $waktu_cek_in = $row->waktu_cek_in ? Carbon::parse($row->waktu_cek_in)->format('H:i:s') : '';

        // WAKTU CEK OUT
        $waktu_cek_out = $row->waktu_cek_out ? Carbon::parse($row->waktu_cek_out)->format('H:i:s') : '';

        // KETERANGAN
        $keterangan = strtolower($row->keterangan);

        if ($row->lama_kunjungan !== null) {
            $hours = floor($row->lama_kunjungan / 60);
            $minutes = $row->lama_kunjungan % 60;
            $lama_kunjungan = sprintf('%02d:%02d:00', $hours, $minutes);
        } else {
            $lama_kunjungan = '00:00:00';
        }

        $durasi_perjalanan = $row->durasi_perjalanan;

        return [
            $nama_lengkap_sales,
            $tgl_kunjungan,
            $kd_toko,
            $nama_toko,
            $waktu_cek_in,
            $waktu_cek_out,
            $keterangan,
            $lama_kunjungan,
            $durasi_perjalanan,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'N' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function title(): string
    {
        return $this->user_sales;
    }
}
