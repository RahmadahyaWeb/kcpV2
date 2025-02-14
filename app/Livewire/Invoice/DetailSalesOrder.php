<?php

namespace App\Livewire\Invoice;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailSalesOrder extends Component
{
    public $target = "";
    public $noso;

    public function mount($noso)
    {
        $this->noso = $noso;
    }

    public function fetch_items($kcpinformation)
    {
        return $kcpinformation->table('trns_so_details as details')
            ->where('details.noso', $this->noso)
            ->where('details.status', "C")
            ->orderBy('details.part_no', 'desc')
            ->get();
    }

    public function fetch_header($kcpinformation)
    {
        return $kcpinformation->table('trns_so_header as header')
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'header.kd_outlet')
            ->where('header.noso', $this->noso)
            ->select([
                'header.noso',
                'outlet.kd_outlet',
                'outlet.nm_outlet',
                'outlet.jth_tempo'
            ])
            ->first();
    }

    public function create_invoice($noso)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $noinv = $this->generate_invoice_number($kcpinformation);
        $noinv_formatted = 'INV-' . date("Ym") . '-' . $noinv;

        dd($noinv_formatted);
    }

    private function generate_invoice_number($kcpinformation)
    {
        $item = $kcpinformation->table('trns_inv_header')
            ->select('noinv')
            ->whereRaw("SUBSTR(noinv, 5, 4) = ?", [date("Y")])
            ->whereRaw("SUBSTR(noinv, 1, 3) = 'INV'")
            ->orderBy('noinv', 'desc')
            ->first();

        $noinv = substr($item->noinv, 11, 5);
        $Vnoinv = $noinv + 1;

        $Vnoinv = str_pad($Vnoinv, 5, '0', STR_PAD_LEFT);

        return $Vnoinv;
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $header = $this->fetch_header($kcpinformation);

        $items = $this->fetch_items($kcpinformation);

        return view('livewire.invoice.detail-sales-order', compact(
            'items',
            'header'
        ));
    }
}
