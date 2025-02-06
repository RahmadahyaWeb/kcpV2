<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InvoiceAopRekapExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DB::table('invoice_aop_detail as detail')
            ->join('invoice_aop_header as header', 'header.invoiceAop', '=', 'detail.invoiceAop')
            ->select([
                'detail.invoiceAop',
                'detail.customerTo',
                'header.billingDocumentDate',
                'header.tanggalJatuhTempo',
                'detail.materialNumber',
                'detail.qty',
                'detail.amount'
            ])
            ->whereBetween('header.billingDocumentDate', [$this->from_date, $this->to_date])
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->invoiceAop,
            $row->customerTo,
            Date::dateTimeToExcel(Carbon::parse($row->billingDocumentDate)),
            Date::dateTimeToExcel(Carbon::parse($row->tanggalJatuhTempo)),
            $row->materialNumber,
            $row->qty,
            $row->amount,
        ];
    }

    public function headings(): array
    {
        return [
            'NO PEMBELIAN',
            'CUSTOMER TO',
            'BILLING DOCUMENT DATE',
            'TANGGAL JATUH TEMPO',
            'PART NO',
            'QTY',
            'AMOUNT'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
