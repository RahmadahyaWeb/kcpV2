<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Salesman extends Component
{
    public function fetch_salesman()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $salesman = $kcpinformation->table('user')
            ->where('role', 'SALESMAN')
            ->where('status', 'Y')
            ->pluck('username');

        return $salesman;
    }

    public function fetch_invoice_salesman()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $currentMonth = date('Y-m', strtotime('2025-01'));

        $invoice = $kcpinformation->table('user as salesman')
            ->leftJoin('trns_inv_header as invoice', function ($join) use ($currentMonth) {
                $join->on('salesman.username', '=', 'invoice.user_sales')
                    ->whereRaw("SUBSTR(invoice.crea_date, 1, 7) = ?", [$currentMonth]);
            })
            ->where('invoice.flag_batal', '<>', 'Y')
            ->where('salesman.role', 'SALESMAN')
            ->where('salesman.status', 'Y')
            ->select('salesman.fullname as user_sales', DB::raw('COALESCE(SUM(invoice.amount_total), 0) as total_amount'))
            ->groupBy('salesman.username')
            ->get();

        dd($invoice);
    }

    public function render()
    {
        dd($this->fetch_invoice_salesman());
        return view('livewire.salesman');
    }
}
