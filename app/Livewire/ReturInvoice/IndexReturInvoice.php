<?php

namespace App\Livewire\ReturInvoice;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IndexReturInvoice extends Component
{
    public $target = "no_retur";
    public $no_retur;

    public function render()
    {
        $items = DB::connection('kcpinformation')
            ->table('trns_retur_header')
            ->where([
                ['flag_reject', '=', 'N'],
                ['flag_batal', '=', 'N'],
                ['flag_approve1', '=', 'Y'],
                ['flag_nota', '=', 'Y'],
                ['noretur', 'like', '%' . $this->no_retur . '%']
            ])
            ->whereDate('crea_date', '>=', '2025-01')
            ->where(function ($query) {
                $query->where('flag_bosnet', '=', 'N')
                    ->orWhere('flag_bosnet', '=', 'F');
            })
            ->get();

        return view('livewire.retur-invoice.index-retur-invoice', compact(
            'items'
        ));
    }
}
