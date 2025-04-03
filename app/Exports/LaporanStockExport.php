<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use DateTime;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanStockExport implements FromCollection, WithMapping, WithTitle, WithColumnFormatting, WithCustomStartCell
{
    private $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function title(): string
    {
        return "LAPORAN STOCK";
    }

    public function collection()
    {
        $items = collect($this->items);

        return $items->map(function ($row) {
            return $row;
        });
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

                // SET HEADER TITLE
                $sheet->setCellValue('A1', "PART NO");
                $sheet->setCellValue('B1', "NAMA PART");
                $sheet->setCellValue('C1', "SUPPLIER");
                $sheet->setCellValue('D1', "PRODUK PART");

                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');

                $sheet->setCellValue('E1', "STOCK KS");
                $sheet->setCellValue('E2', "OH");
                $sheet->setCellValue('F2', "INT");
                $sheet->mergeCells('E1:F1');

                $sheet->setCellValue('G1', "STOCK KT");
                $sheet->setCellValue('G2', "OH");
                $sheet->setCellValue('H2', "INT");
                $sheet->mergeCells('G1:H1');
            }
        ];
    }

    public function map($row): array
    {
        return [
            $row->part_no,
            $row->nm_part
        ];
    }

    public function columnFormats(): array
    {
        return [];
    }
}
