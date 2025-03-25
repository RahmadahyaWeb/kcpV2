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
        $rows = DB::table('invoice_aop_header as header')
            ->where('flag_final', 'Y')
            ->whereBetween('header.billingDocumentDate', [$this->from_date, $this->to_date])
            ->get();

        return $rows;
    }

    public function map($row): array
    {
        $invoice_aop = $row->invoiceAop;
        $tanggal_jatuh_tempo = $row->tanggalJatuhTempo;
        $billing_document_date = $row->billingDocumentDate;

        // PRICE
        $price = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $invoice_aop)
            ->sum('price');

        // ADD DISC
        $add_discount = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $invoice_aop)
            ->sum('addDiscount');

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

        // GRAND TOTAL
        $grand_total = $net_sales + $tax;

        return [
            $tanggal_jatuh_tempo,
            $invoice_aop,
            $billing_document_date,
            $price,
            $add_discount,
            $extra_plafon_discount,
            $net_sales,
            $tax,
            $grand_total,
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
