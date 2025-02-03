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
        $salesman = $this->fetch_salesman();

        $kcpinformation = DB::connection('kcpinformation');

        $currentMonth = date('Y-m', strtotime('2025-01'));

        $invoice = $kcpinformation->table('user as u')
            ->leftJoin('trns_inv_header as inv', function ($join) use ($currentMonth) {
                $join->on('u.username', '=', 'inv.user_sales')
                    ->whereRaw("SUBSTR(inv.crea_date, 1, 7) = ?", [$currentMonth]);
            })
            ->where('u.role', 'SALESMAN')
            ->where('u.status', 'Y')
            ->select('u.username as user_sales', DB::raw('COALESCE(SUM(inv.amount_total), 0) as total_amount'))
            ->groupBy('u.username')
            ->get();

        dd($invoice);
    }

    public function render()
    {
        dd($this->fetch_invoice_salesman());
        return view('livewire.salesman');
    }
}
