<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class LaporanPenerimaanPiutang implements FromCollection, WithMapping, WithTitle, WithColumnFormatting, WithHeadings, WithEvents
{
    private $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function title(): string
    {
        return "LAPORAN PENERIMAAN PIUTANG";
    }

    public function collection()
    {
        $items = collect($this->items);

        return $items->map(function ($row) {
            return $row;
        });
    }

    public function headings(): array
    {
        return [
            'NO PIUTANG',
            'KODE TOKO',
            'NAMA TOKO',
            'PEMBAYARAN VIA',
            'BANK',
            'TANGGAL PEMOTONGAN',
            'NOMINAL POTONG',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->getDelegate()->setCellValueExplicit('B' . $row, $sheet->getCell('B' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('C' . $row, $sheet->getCell('C' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('D' . $row, $sheet->getCell('D' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('E' . $row, $sheet->getCell('E' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        ];
    }

    public function map($row): array
    {
        $no_piutang = $row['no_piutang'];
        $kode_toko = $row['kd_outlet'];
        $nama_toko = $row['nm_outlet'];
        $pembayaran_via = $row['pembayaran_via'];
        $bank = $row['details'][0]['bank'];
        $tanggal_potong = Date::dateTimeToExcel(new DateTime(isset($row['tanggal_potong']) ? date('Y-m-d', strtotime($row['tanggal_potong'])) : null));
        $nominal_potong = $row['nominal_potong'];

        return [
            $no_piutang,
            $kode_toko,
            $nama_toko,
            $pembayaran_via,
            $bank,
            $tanggal_potong,
            $nominal_potong
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
