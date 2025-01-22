<?php

namespace App\Livewire\ReturInvoice;

use App\Http\Controllers\API\ReturInvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailReturInvoice extends Component
{
    public $target = "sendToBosnet";
    public $no_retur;
    public $header;

    public function mount($no_retur)
    {
        $this->no_retur = $no_retur;

        $this->header = DB::connection('kcpinformation')
            ->table('trns_retur_header')
            ->where([
                ['flag_reject', '=', 'N'],
                ['flag_batal', '=', 'N'],
                ['flag_approve1', '=', 'Y'],
                ['flag_nota', '=', 'Y'],
                ['noretur', $this->no_retur]
            ])
            ->whereDate('crea_date', '>=', '2025-01')
            // ->where(function ($query) {
            //     $query->where('flag_bosnet', '=', 'N')
            //         ->orWhere('flag_bosnet', '=', 'F');
            // })
            ->first();

        if (!$this->header) {
            abort(404);
        }
    }

    public function sendToBosnet()
    {
        try {
            $controller = new ReturInvoiceController();
            $controller->sendToBosnet(new Request(['no_retur' => $this->header->noretur, 'no_invoice' => $this->header->noinv]));

            session()->flash('success', "Data RETUR berhasil diteruskan ke BOSNET");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $items = DB::connection('kcpinformation')
            ->table('trns_retur_details')
            ->where('noretur', $this->no_retur)
            ->get();

        $nominal_total = DB::connection('kcpinformation')
            ->table('trns_retur_details')
            ->where('noretur', $this->no_retur)
            ->sum('nominal_total');

        return view('livewire.retur-invoice.detail-retur-invoice', compact(
            'items',
            'nominal_total'
        ));
    }
}
