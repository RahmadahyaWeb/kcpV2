<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanLssSheet implements FromCollection, WithMapping, WithHeadings
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
            'PART NO',
            'PRODUK PART',
            'AWAL QTY',
            'BELI QTY',
            'JUAL QTY',
            'AKHIR QTY',
            'AWAL AMT',
            'BELI AMT',
            'JUAL HPP',
            'JUAL RBP',
            'AKHIR AMT'
        ];
    }

    public function map($row): array
    {
        $part_no = $row['part_no'];
        $produk_part = $row['produk_part'];
        $stock_awal = array_sum(array_column($row['stock_awal'], 'qty'));
        $qty_beli = $row['total_beli'];
        $qty_jual = $row['total_jual'];
        $qty_akhir = ($stock_awal + $qty_beli) - $qty_jual;

        $awal_amount = array_reduce($row['stock_awal'], function ($carry, $item) {
            return $carry + ($item['qty'] * $item['harga']);
        }, 0);

        $beli_amount = $row['histori_pembelian']->sum(function ($item) {
            return $item['qty'] * $item['harga'];
        });

        $jual_hpp = collect($row['histori_penjualan'])->sum(function ($item) {
            return (float) $item['subtotal_modal'];
        });

        $jual_amount_inc_ppn = collect($row['histori_penjualan'])->sum(function ($item) {
            return (float) $item['harga_jual'] * (float) $item['qty'];
        });

        $jual_amount_exc_ppn = $jual_amount_inc_ppn / config('tax.ppn_factor');

        $akhir_amt = ($beli_amount + $awal_amount) - $jual_amount_exc_ppn;

        return [
            $part_no,
            $produk_part,
            $stock_awal,
            $qty_beli,
            $qty_jual,
            $qty_akhir,
            $awal_amount,
            $beli_amount,
            $jual_hpp,
            $jual_amount_exc_ppn
        ];
    }
}
