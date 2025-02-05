<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Salesman extends Component
{
    public $target = 'periode';
    public $data_salesman;
    public $periode;

    public function mount()
    {
        $this->periode = date('Y-m', strtotime('2025-01'));
    }

    public function fetch_invoice_salesman()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        $invoice = $kcpinformation->table('user as salesman')
            ->leftJoin('trns_inv_header as invoice', function ($join) use ($periode) {
                $join->on('salesman.username', '=', 'invoice.user_sales')
                    ->whereRaw("SUBSTR(invoice.crea_date, 1, 7) = ?", [$periode])
                    ->where('invoice.flag_batal', '<>', 'Y');
            })
            ->where('salesman.role', 'SALESMAN')
            ->where('salesman.status', 'Y')
            ->select(
                'salesman.username as user_sales',
                'salesman.fullname',
                DB::raw('COALESCE(SUM(invoice.amount_total), 0) as total_amount')
            )
            ->groupBy('salesman.username', 'salesman.fullname')
            ->orderBy('total_amount', 'desc')
            ->get();

        $retur = $kcpinformation->table('trns_inv_header')
            ->join('trns_retur_header as retur_header', 'retur_header.noinv', '=', 'trns_inv_header.noinv')
            ->join('trns_retur_details as retur_detail', 'retur_header.noretur', '=', 'retur_detail.noretur')
            ->select('trns_inv_header.user_sales', DB::raw('SUM(retur_detail.nominal_total) as total_retur'))
            ->where(DB::raw('SUBSTR(retur_header.flag_nota_date, 1, 7)'), '=', [$periode])
            ->where('retur_header.flag_nota', '=', 'Y')
            ->where('trns_inv_header.flag_batal', '<>', 'Y')
            ->groupBy('trns_inv_header.user_sales')
            ->get();

        foreach ($invoice as $salesman) {
            // Cari data retur untuk user_sales yang sama (dengan strtolower untuk case-insensitive matching)
            $returnData = $retur->firstWhere(function ($item) use ($salesman) {
                return strtolower($item->user_sales) === strtolower($salesman->user_sales);
            });

            // Jika ada data retur, tambahkan total_retur, jika tidak set ke 0
            $salesman->total_retur = $returnData ? $returnData->total_retur : 0;

            $salesman->total = $salesman->total_amount - $salesman->total_retur;
        }

        return [
            'invoice' => $invoice,
        ];
    }

    public function get_product_parts($noinv_list)
    {
        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_details as details')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereIn('details.noinv', $noinv_list)
            ->groupBy('details.noinv', 'part.produk_part', 'part.supplier')
            ->select([
                'details.noinv',
                'part.produk_part',
                'part.supplier'
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noinv => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier
                ]];
            });
    }

    public function fetch_invoice()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        $invoices = $kcpinformation->table('trns_inv_header as header')
            ->select(['header.noinv'])
            ->join('trns_inv_details as details', 'details.noinv', '=', 'header.noinv')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereRaw("SUBSTR(header.crea_date, 1, 7) = ?", [$periode])
            ->where('flag_batal', '<>', 'Y')
            ->groupBy('header.noinv')
            ->pluck('header.noinv');

        $product_parts = $this->get_product_parts($invoices->toArray());

        $arr_inv = [];

        foreach ($invoices as $noinv) {
            $arr_inv[$noinv] = [
                'noinv'         => $noinv,
                'product_part'  => $product_parts[$noinv]['product_part'] ?? null,
                'supplier'      => $product_parts[$noinv]['supplier']
            ];
        }
    }

    public function render()
    {
        $this->fetch_invoice();
        $data = $this->fetch_invoice_salesman();

        $this->data_salesman = [
            'labels' => $data['invoice']->pluck('fullname')->toArray(),
            'amount' => $data['invoice']->pluck('total')->toArray(),
            'retur'  => $data['invoice']->pluck('total_retur')->toArray()
        ];

        $salesmanData = $data['invoice'];

        return view('livewire.salesman', compact('salesmanData'));
    }
}
