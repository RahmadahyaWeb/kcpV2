<?php

namespace App\Livewire\Invoice;

use App\Exports\InvoiceBosnetExport;
use App\Http\Controllers\API\InvoiceController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class IndexInvoiceBosnet extends Component
{
    use WithPagination;

    public $target = 'noso, noinv, status, send_inv_to_bosnet, status_invoice';

    public $noso = '';
    public $noinv = '';
    public $status = '';
    public $status_invoice = '';

    public function send_inv_to_bosnet()
    {
        try {
            $controller = new InvoiceController();

            $controller->sendToBosnet();
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new InvoiceBosnetExport, 'rekap_invoice.xlsx');
    }

    public function render()
    {
        $invoices = DB::table('invoice_bosnet')
            ->where('noso', 'like', '%' . $this->noso . '%')
            ->where('noinv', 'like', '%' . $this->noinv . '%')
            ->where('status_bosnet', 'like', '%' . $this->status . '%')
            ->where('status_invoice', 'like', '%' . $this->status_invoice . '%')
            ->orderBy('noinv', 'desc')
            ->paginate(20);

        return view('livewire.invoice.index-invoice-bosnet', compact('invoices'));
    }
}
