<?php

namespace App\Exports;

use DateTime;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PajakMasukanExport implements FromCollection, WithTitle, WithMapping, WithHeadings, WithColumnFormatting, WithEvents
{
    private $from_date, $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    public function title(): string
    {
        return "FakturPajakMasukan" . $this->from_date . "-" . $this->to_date;
    }

    public function map($row): array
    {
        $invoice_aop = $row->invoiceAop;

        $fm = "FM";
        $kd_jenis_transaksi = "1";
        $fg_pengganti = "0";
        $nomor_faktur = $row->fakturPajak;
        $tanggal_faktur = $row->billingDocumentDate;
        $masa_pajak = date('m', strtotime($tanggal_faktur));
        $tahun_pajak = date('Y', strtotime($tanggal_faktur));
        $tanggal_faktur_excel = Date::dateTimeToExcel(new DateTime($tanggal_faktur));
        $npwp = "013452438054000";
        $nama = "PT ASTRA OTOPARTS TBK.";
        $alamat_lengkap = "";

        // EXTRA PLAFON DISCOUNT
        $extra_plafon_discount = DB::table('program_aop')
            ->where('invoiceAop', $invoice_aop)
            ->sum('potonganProgram');

        // AMOUNT
        $amount = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $invoice_aop)
            ->sum('amount');

        // NET SALES
        $net_sales = $amount - $extra_plafon_discount;

        // TAX
        $tax = intval($net_sales * config('tax.ppn_percentage'));

        $jumlah_dpp = $net_sales;
        $jumlah_ppn = $tax;

        $jumlah_ppnb = "0";
        $is_creditable = "1";

        return [
            $fm,
            $kd_jenis_transaksi,
            $fg_pengganti,
            $nomor_faktur,
            $masa_pajak,
            $tahun_pajak,
            $tanggal_faktur_excel,
            $npwp,
            $nama,
            $alamat_lengkap,
            $jumlah_dpp,
            $jumlah_ppn,
            $jumlah_ppnb,
            $is_creditable
        ];
    }

    public function headings(): array
    {
        return [
            'FM',
            'KD_JENIS_TRANSAKSI',
            'FG_PENGGANTI',
            'NOMOR_FAKTUR',
            'MASA_PAJAK',
            'TAHUN_PAJAK',
            'TANGGAL_FAKTUR',
            'NPWP',
            'NAMA',
            'ALAMAT_LENGKAP',
            'JUMLAH_DPP',
            'JUMLAH_PPN',
            'JUMLAH_PPNB',
            'IS_CREDITABLE'
        ];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->getDelegate()->setCellValueExplicit('C' . $row, $sheet->getCell('C' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('D' . $row, $sheet->getCell('D' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('H' . $row, $sheet->getCell('H' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('M' . $row, $sheet->getCell('M' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getDelegate()->setCellValueExplicit('N' . $row, $sheet->getCell('N' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        ];
    }

    public function collection()
    {
        return DB::table('invoice_aop_header as header')
            // ->join('invoice_aop_detail as detail', 'detail.invoiceAop', '=', 'header.invoiceAop')
            ->whereBetween('header.billingDocumentDate', [$this->from_date, $this->to_date])
            ->select([
                'invoiceAop',
                'fakturPajak',
                'billingDocumentDate',
                'netSales',
                'tax'
            ])
            ->get();
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
