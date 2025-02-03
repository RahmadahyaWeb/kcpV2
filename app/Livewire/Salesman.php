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
        $this->periode = date('Y-m');
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
            ->leftJoin('trns_retur_header as retur_header', function ($join) use ($periode) {
                $join->on('retur_header.noinv', '=', 'invoice.noinv')
                    ->where('retur_header.flag_nota', '=', 'Y')
                    ->whereRaw("SUBSTR(retur_header.flag_nota_date, 1, 7) = ?", [$periode]);
            })
            ->leftJoin('trns_retur_details as retur_details', 'retur_header.noretur', '=', 'retur_details.noretur')
            ->where('salesman.role', 'SALESMAN')
            ->where('salesman.status', 'Y')
            ->select(
                'salesman.username as user_sales',
                'salesman.fullname',
                DB::raw('COALESCE(SUM(invoice.amount_total), 0) as total_amount'),
                DB::raw('COALESCE(SUM(CASE WHEN retur_header.noinv IS NOT NULL THEN retur_details.nominal_total ELSE 0 END), 0) as total_retur')
            )
            ->groupBy('salesman.username', 'salesman.fullname')
            ->orderBy('total_amount', 'desc')
            ->get();

        return $invoice;
    }


    public function render()
    {
        $salesmanData = $this->fetch_invoice_salesman();

        $this->data_salesman = [
            'labels' => $salesmanData->pluck('fullname'),
            'amount' => $salesmanData->pluck('total_amount'),
        ];

        return view('livewire.salesman', compact('salesmanData'));
    }
}
