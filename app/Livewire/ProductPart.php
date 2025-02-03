<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductPart extends Component
{
    public $data_aop, $data_non_aop;
    public $periode;

    public function mount()
    {
        $this->periode = date('Y-m');

        $this->data_aop = [
            'labels' => $this->fetch_product_aop(),
            'amount' => $this->fetch_amount_aop()
        ];

        $this->data_non_aop = [
            'labels' => $this->fetch_product_non_aop(),
            'amount' => $this->fetch_amount_non_aop()
        ];
    }

    private function fetch_product_by_supplier(array $suppliers)
    {
        return DB::connection('kcpinformation')
            ->table('mst_part')
            ->whereIn('supplier', $suppliers)
            ->groupBy('produk_part')
            ->pluck('produk_part')
            ->toArray();
    }

    public function fetch_product_aop()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $product_aop = $this->fetch_product_by_supplier(['ASTRA OTOPART']);

        return $product_aop;
    }

    public function fetch_product_non_aop()
    {
        $product_non_aop = $this->fetch_product_by_supplier(['KMC', 'ABM', 'SSI']);

        return $product_non_aop;
    }

    private function fetch_amount_by_supplier(array $suppliers, $periode)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $amount = $kcpinformation->table('mst_part as part')
            ->select([
                'part.produk_part',
                DB::raw('IFNULL(invoice_data.amount_total, 0) as amount_total')
            ])
            ->whereIn('part.supplier', $suppliers)
            ->groupBy('part.produk_part')
            ->leftJoinSub(
                $kcpinformation->table('trns_inv_header as inv')
                    ->join('trns_inv_details as inv_detail', 'inv.noinv', '=', 'inv_detail.noinv')
                    ->join('mst_part as part_info', 'inv_detail.part_no', '=', 'part_info.part_no')
                    ->select([
                        'part_info.produk_part',
                        DB::raw('SUM(inv_detail.nominal_total) as amount_total')
                    ])
                    ->whereRaw("SUBSTR(inv.crea_date, 1, 7) = ?", [date('Y-m', strtotime($periode))])
                    ->where('inv.flag_batal', 'N')
                    ->groupBy('part_info.produk_part'),
                'invoice_data',
                'part.produk_part',
                '=',
                'invoice_data.produk_part'
            )
            ->pluck('amount_total', 'produk_part')
            ->toArray();

        return $amount;
    }

    public function fetch_amount_aop()
    {
        $amount_aop = $this->fetch_amount_by_supplier(['ASTRA OTOPART'], $this->periode);

        return $amount_aop;
    }

    public function fetch_amount_non_aop()
    {
        $amounts = $this->fetch_amount_by_supplier(['KMC', 'ABM', 'SSI'], $this->periode);
        return $amounts;
    }

    public function updatedPeriode()
    {
        $this->data_aop = [
            'labels' => $this->fetch_product_aop(),
            'amount' => $this->fetch_amount_aop()
        ];

        $this->data_non_aop = [
            'labels' => $this->fetch_product_non_aop(),
            'amount' => $this->fetch_amount_non_aop()
        ];
    }

    public function render()
    {
        return view('livewire.product-part');
    }
}
