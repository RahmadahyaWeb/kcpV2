<?php

namespace App\Exports;

use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class FrekuensiSheet implements WithTitle, WithEvents, WithColumnFormatting, WithCustomStartCell, WithHeadings, FromCollection, WithMapping
{
    protected $sales;
    protected $fromDate;
    protected $toDate;

    public function __construct($sales, $fromDate, $toDate)
    {
        $this->sales = $sales;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $this->setHeader($sheet);
                $this->autoSizeColumns($sheet);
            }
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

    public function setHeader($sheet)
    {
        $sheet->setCellValue('A1', 'KODE TOKO');
        $sheet->setCellValue('B1', 'TOKO');
        $sheet->setCellValue('C1', 'KAB/KOTA');
        $sheet->setCellValue('D1', 'PROVINSI');
        $sheet->setCellValue('E1', 'SALES');
        $sheet->setCellValue('F1', 'MINIMAL KUNJUNGAN');
        $sheet->setCellValue('G1', 'REALISASI KUNJUNGAN');
        $sheet->setCellValue('H1', 'PUNISHMENT');

        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => ['bold' => true],
        ];

        $sheet->getDelegate()->getStyle('A1:H1')->applyFromArray($styleArray);

        // Mengatur alignment untuk kolom A
        $sheet->getDelegate()->getStyle('A:A')->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    public function collection()
    {
        return DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->leftJoin('mst_areatoko', 'mst_areatoko.area_group', '=', 'mst_outlet.area_group_2w')
            ->leftJoin('mst_area', 'mst_area.kode_kab', '=', 'mst_outlet.kode_kab')
            ->leftJoin('mst_provinsi', 'mst_provinsi.kode_prp', '=', 'mst_outlet.kode_prp')
            ->where('mst_outlet.status', 'Y')
            ->whereIn('mst_areatoko.user_sales', $this->sales)
            ->orderBy('user_sales', 'asc')
            ->get();
    }

    public function map($row): array
    {
        $kd_toko = $row->kd_outlet;

        $nama_toko = $row->nm_outlet;

        $alamat_toko = $row->nm_area;
        $nama_provinsi = $row->provinsi;
        $nama_sales = $row->user_sales;
        $minimal_kunjungan = $row->frekuensi ?? 0;

        // REALISASI KUNJUNGAN
        if ($row->kd_outlet == 'TQ') {
            $realisasi_kunjungan_tq = DB::table('trans_dks')
                ->where('user_sales', $row->user_sales)
                ->whereBetween('tgl_kunjungan', [$this->fromDate, $this->toDate])
                ->where('type', 'in')
                ->whereIn('kd_toko', ['TQ', 'TQ2'])
                ->count();

            $realisasi_kunjungan = (string) $realisasi_kunjungan_tq;
        } else {
            $realisasi_kunjungan_data = DB::table('trans_dks')
                ->where('user_sales', $row->user_sales)
                ->whereBetween('tgl_kunjungan', [$this->fromDate, $this->toDate])
                ->where('type', 'in')
                ->where('kd_toko', $kd_toko)
                ->count();

            $realisasi_kunjungan = (string) $realisasi_kunjungan_data;
        }

        if ($minimal_kunjungan > $realisasi_kunjungan) {
            $punishment = 1;
        } else {
            $punishment = 0;
        }

        return [
            $kd_toko,
            $nama_toko,
            $alamat_toko,
            $nama_provinsi,
            $nama_sales,
            $minimal_kunjungan,
            $realisasi_kunjungan,
            $punishment,
        ];
    }

    private function autoSizeColumns($sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }

    public function title(): string
    {
        return 'Minimal Kunjungan';
    }

    public function columnFormats(): array
    {
        return [];
    }
}
