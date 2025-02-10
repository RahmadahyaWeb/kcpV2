<?php

namespace App\Exports\Pajak;

use Carbon\Carbon;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PajakKeluaranHeaderSheet implements FromCollection, WithMapping, WithEvents, WithCustomStartCell, WithTitle, WithColumnFormatting
{
    private $headers;

    public function __construct($headers)
    {
        $this->headers = $headers;
    }

    public function title(): string
    {
        return "Faktur";
    }

    public function collection()
    {
        $headers = collect($this->headers);

        return $headers->map(function ($row) {
            return $row;
        });
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // SET HEADER MERGE CELL
                $sheet->mergeCells('A1:B1');

                // SET HEADER TITLE
                $sheet->setCellValue('A1', "NPWP PENJUAL");
                $sheet->setCellValue('C1', "0018606582731000");

                $sheet->setCellValue('A3', "Baris");
                $sheet->setCellValue('B3', "Tanggal Faktur");
                $sheet->setCellValue('C3', "Jenis Faktur");
                $sheet->setCellValue('D3', "Kode Transaksi");
                $sheet->setCellValue('E3', "Keterangan Tambahan");
                $sheet->setCellValue('F3', "Dokumen Pendukung");
                $sheet->setCellValue('G3', "Referensi");
                $sheet->setCellValue('H3', "Cap Fasilitas");
                $sheet->setCellValue('I3', "ID TKU Penjual");
                $sheet->setCellValue('J3', "NPWP/NIK Pembeli");
                $sheet->setCellValue('K3', "Jenis ID Pembeli");
                $sheet->setCellValue('L3', "Negara Pembeli");
                $sheet->setCellValue('M3', "Nomor Dokumen Pembeli");
                $sheet->setCellValue('N3', "Nama Pembeli");
                $sheet->setCellValue('O3', "Alamat Pembeli");
                $sheet->setCellValue('P3', "Email Pembeli");
                $sheet->setCellValue('Q3', "ID TKU Pembeli");

                // GET TOTAL ROWS WITH DATA
                $totalRows = count($event->sheet->getDelegate()->toArray());

                // ADD "END" AT THE BOTTOM OF COLUMN A
                $sheet->setCellValue('A' . ($totalRows + 1), "END");

                $highestRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    $sheet->getDelegate()->setCellValueExplicit('A' . $row, $sheet->getCell('A' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('J' . $row, $sheet->getCell('J' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('M' . $row, $sheet->getCell('M' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('N' . $row, $sheet->getCell('N' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('Q' . $row, $sheet->getCell('Q' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }

            }
        ];
    }

    public function map($row): array
    {
        // BARIS
        $baris = $row->baris;

        // TANGGAL FAKTUR
        $tanggal_faktur_format = date('Y-m-d', strtotime($row->tanggal_faktur));
        $tanggal_faktur_excel = Date::dateTimeToExcel(new DateTime($tanggal_faktur_format));

        // JENIS FAKTUR
        $jenis_faktur = "Normal";

        // KODE TRANSAKSI
        $kode_transaksi = "04";

        // KETERANGAN TAMBAHAN
        $keterangan_tambahan = "";

        // DOKUMEN PENDUKUNG
        $dokumen_pendukung = "";

        // REFERENSI
        $referensi = $row->referensi;

        // CAP FASILITAS
        $cap_fasilitas = "";

        // ID TKU PENJUAL
        $id_tku_penjual = "0018606582731000000000";

        // NPWP
        $npwp = (!empty($row->npwp) && $row->npwp != "000000000000000") ? $row->npwp  : "000000000000000";

        // JENIS ID PEMBELI
        $jenis_id_pembeli = ($npwp == "000000000000000" ? "National ID" : "TIN");

        // NEGARA PEMBELI
        $negara_pembeli = "IDN";

        // NOMOR DOKUMEN PEMBELI
        $nik = $row->nik;
        $nomor_dokumen_pembeli  = ($jenis_id_pembeli == "TIN") ? "-" : $nik;

        // NAMA PEMBELI
        $nama_pembeli = ($npwp == "000000000000000" ? "$nik" . $row->nama_pembeli : $row->nama_pembeli);

        // ALAMAT PEMBELI
        $alamat_pembeli = $row->alamat_pembeli;

        // EMAIL PEMBELI
        $email_pembeli = $row->email;

        // ID TKU PEMBELI
        $id_tku_pembeli = ($npwp != "000000000000000" ? $npwp . "000000"  : "000000");

        return [
            $baris,
            $tanggal_faktur_excel,
            $jenis_faktur,
            $kode_transaksi,
            $keterangan_tambahan,
            $dokumen_pendukung,
            $referensi,
            $cap_fasilitas,
            $id_tku_penjual,
            $npwp,
            $jenis_id_pembeli,
            $negara_pembeli,
            $nomor_dokumen_pembeli,
            $nama_pembeli,
            $alamat_pembeli,
            $email_pembeli,
            $id_tku_pembeli
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
