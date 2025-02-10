<?php

namespace App\Exports\Pajak;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PajakKeluaranDetailSheet implements FromCollection, WithTitle, WithEvents, WithMapping, WithCustomStartCell, WithColumnFormatting
{
    private $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function title(): string
    {
        return "DetailFaktur";
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function collection()
    {
        $details = collect($this->details);

        return $details->map(function ($row) {
            return $row;
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // SET HEADER TITLE
                $sheet->setCellValue('A1', "Baris");
                $sheet->setCellValue('B1', "Barang/Jasa");
                $sheet->setCellValue('C1', "Kode Barang Jasa");
                $sheet->setCellValue('D1', "Nama Barang/Jasa");
                $sheet->setCellValue('E1', "Nama Satuan Ukur");
                $sheet->setCellValue('F1', "Harga Satuan");
                $sheet->setCellValue('G1', "Jumlah Barang Jasa");
                $sheet->setCellValue('H1', "Total Diskon");
                $sheet->setCellValue('I1', "DPP");
                $sheet->setCellValue('J1', "DPP Nilai Lain");
                $sheet->setCellValue('K1', "Tarif PPn");
                $sheet->setCellValue('L1', "PPN");
                $sheet->setCellValue('M1', "Tarif PPnBM");
                $sheet->setCellValue('N1', "PPnBM");

                // GET TOTAL ROWS WITH DATA
                $totalRows = count($event->sheet->getDelegate()->toArray());

                // ADD "END" AT THE BOTTOM OF COLUMN A
                $sheet->setCellValue('A' . ($totalRows + 1), "END");
            }
        ];
    }

    public function map($row): array
    {

        // BARIS
        $baris = $row->baris;

        // BARANG / JASA
        $barang_jasa = "A";

        // KODE BARANG JASA
        $kode_barang_jasa = "000000";

        // NAMA BARANG JASA
        $nama_barang_jasa = $row->nama_barang;

        // NAMA SATUAN UKUR
        $nama_satuan_ukur = "UM.0021";

        // HARGA SATUAN
        $harga_satuan = $row->harga_satuan / config('tax.ppn_factor');

        // JUMLAH BARANG JASA
        $jumlah_barang_jasa = (int) $row->jumlah_barang;

        // TOTAL DISKON
        $total_diskon = $row->nominal_disc / config('tax.ppn_factor');

        // DPP
        $dpp = ($harga_satuan * $jumlah_barang_jasa) - $total_diskon;

        // DPP NILAI LAIN
        $dpp_lain = 11 / 12 * $dpp;

        // TARIF PPN
        $tarif_ppn = 12;

        // PPN
        $ppn = $dpp_lain * $tarif_ppn / 100;

        // TARIF PPNBM
        $tarif_ppnbm = "0";

        // PPNBM
        $ppnbm = "0";

        $data =  [
            $baris,
            $barang_jasa,
            $kode_barang_jasa,
            $nama_barang_jasa,
            $nama_satuan_ukur,
            $harga_satuan,
            $jumlah_barang_jasa,
            $total_diskon,
            $dpp,
            $dpp_lain,
            $tarif_ppn,
            $ppn,
            $tarif_ppnbm,
            $ppnbm
        ];

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00, // Tarif PPnBM
            'H' => NumberFormat::FORMAT_NUMBER_00, // Tarif PPnBM
            'I' => NumberFormat::FORMAT_NUMBER_00, // Tarif PPnBM
            'J' => NumberFormat::FORMAT_NUMBER_00, // Tarif PPnBM
            'L' => NumberFormat::FORMAT_NUMBER_00, // Tarif PPnBM

            'M' => NumberFormat::FORMAT_NUMBER, // Tarif PPnBM
            'N' => NumberFormat::FORMAT_NUMBER, // PPnBM
        ];
    }
}
