<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceAopExport implements FromCollection, WithHeadings, WithMapping
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
        return DB::table('invoice_aop_header')
            ->select([
                'tanggalJatuhTempo',
                'invoiceAop',
                'billingDocumentDate',
                'price',
                'addDiscount',
                'extraPlafonDiscount',
                'amount',
                'tax',
                'grandTotal',
                'netSales'
            ])
            ->whereBetween('billingDocumentDate', [$this->from_date, $this->to_date])
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->tanggalJatuhTempo,
            $row->invoiceAop,
            $row->billingDocumentDate,
            $row->price,
            $row->addDiscount,
            $row->extraPlafonDiscount,
            $row->netSales,
            $row->tax,
            $row->grandTotal,
        ];
    }

    public function headings(): array
    {
        return [
            'TANGGAL JATUH TEMPO',
            'NO PEMBELIAN',
            'TANGGAL',
            'HARGA',
            'DISC',
            'EXTRA PLAFON DISC',
            'DPP',
            'TAX',
            'GRAND TOTAL',
        ];
    }
}
