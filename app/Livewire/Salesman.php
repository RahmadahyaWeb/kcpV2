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
        $this->periode = date('Y-m', strtotime('2024-01'));
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

        return [
            'invoice' => $invoice,
        ];
    }

    public function render()
    {
        $data = $this->fetch_invoice_salesman();

        $this->data_salesman = [
            'labels' => $data['invoice']->pluck('fullname')->toArray(),
            'amount' => $data['invoice']->pluck('total_amount')->toArray()
        ];

        $salesmanData = $data['invoice'];

        return view('livewire.salesman', compact('salesmanData'));
    }
}
