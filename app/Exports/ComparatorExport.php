<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;

class ComparatorExport implements FromCollection, WithHeadings, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $comparatorItems = DB::connection('mysql')
            ->table('comparator')
            ->get();

        // Ambil data dari database 'kcpinformation'
        $mstParts = DB::connection('kcpinformation')
            ->table('mst_part')
            ->select('part_no', 'nm_part')
            ->get()
            ->keyBy('part_no'); // Indeks data berdasarkan 'part_no'

        // Gabungkan data secara manual dan susun dalam format tertentu
        $items = $comparatorItems->map(function ($item) use ($mstParts) {
            // Cari part di mstParts berdasarkan part_number
            $nmPart = $mstParts->get($item->part_number)->nm_part ?? 'PART NUMBER TIDAK DIKENALI';

            // Susun data sesuai urutan yang diinginkan
            return [
                'PART NUMBER' => $item->part_number,
                'NAMA PART'   => $nmPart,
                'QTY'         => $item->qty,
            ];
        });

        return $items;
    }

    /**
     * Define the headings of the export.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'PART NUMBER',
            'NAMA PART',
            'QTY'
        ];
    }

    /**
     * Register events for after the sheet is loaded.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto size columns A, B, and C
                $event->sheet->getDelegate()->getColumnDimension('A')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('C')->setAutoSize(true);

                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('B1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('C1')->getFont()->setBold(true);
            },
        ];
    }
}
