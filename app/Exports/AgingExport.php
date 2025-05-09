<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class AgingExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    private $items;

    public function __construct($items)
    {
        $this->items = $items;
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
            'KODE TOKO',
            'NAMA TOKO',
            'LIMIT KREDIT',
            'SISA LIMIT KREDIT',
            'BELUM OVERDUE',
            'OVERDUE 1-7',
            'OVERDUE 8-20',
            'OVERDUE 21-50',
            'OVERDUE > 50',
            'RETUR',
            'TOTAL PIUTANG'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // GET TOTAL ROWS WITH DATA
                $totalRows = count($event->sheet->getDelegate()->toArray());

                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getDelegate()->setCellValueExplicit('A' . $row, $sheet->getCell('A' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('C' . $row, $sheet->getCell('C' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('D' . $row, $sheet->getCell('D' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('E' . $row, $sheet->getCell('E' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('F' . $row, $sheet->getCell('F' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('G' . $row, $sheet->getCell('G' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('H' . $row, $sheet->getCell('H' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('I' . $row, $sheet->getCell('I' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('J' . $row, $sheet->getCell('J' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getDelegate()->setCellValueExplicit('K' . $row, $sheet->getCell('K' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                }
            }
        ];
    }

    public function map($row): array
    {
        return [
            $row['kd_outlet'],
            $row['nm_outlet'],
            $row['limit_kredit'],
            $row['sisa_limit_kredit'],
            $row['not_overdue']['total_amount'],
            $row['overdue_1_7']['total_amount'],
            $row['overdue_8_20']['total_amount'],
            $row['overdue_21_50']['total_amount'],
            $row['overdue_over_50']['total_amount'],
            $row['retur']['total_amount'],
            $row['total_piutang']
        ];
    }
}
