<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductPart extends Component
{
    public $data_aop;

    public function fetch_product_aop()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $product_aop = $kcpinformation->table('mst_part')
            ->where('supplier', 'ASTRA OTOPART')
            ->groupBy('produk_part')
            ->pluck('produk_part')
            ->toArray();

        return $product_aop;
    }

    public function fetch_amount_aop()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $amount_aop = $kcpinformation->table('mst_part as part')
            ->select([
                'part.produk_part',
                DB::raw('IFNULL(invoice_data.amount_total, 0) as amount_total')
            ])
            ->where('part.supplier', 'ASTRA OTOPART')
            ->groupBy('part.produk_part')
            ->leftJoinSub(
                $kcpinformation->table('trns_inv_header as inv')
                    ->join('trns_inv_details as inv_detail', 'inv.noinv', '=', 'inv_detail.noinv')
                    ->join('mst_part as part_info', 'inv_detail.part_no', '=', 'part_info.part_no')
                    ->select([
                        'part_info.produk_part',
                        DB::raw('SUM(inv_detail.nominal_total) as amount_total')
                    ])
                    ->whereRaw("SUBSTR(inv.crea_date, 1, 7) = ?", [date('Y-m')])
                    ->where('inv.flag_batal', 'N')
                    ->groupBy('part_info.produk_part'),
                'invoice_data',
                'part.produk_part',
                '=',
                'invoice_data.produk_part'
            )
            ->pluck('amount_total', 'produk_part')
            ->toArray();

        return $amount_aop;
    }

    public function render()
    {
        $this->data_aop = [
            'labels' => $this->fetch_product_aop(),
            'amount' => $this->fetch_amount_aop()
        ];

        return view('livewire.product-part');
    }
}
