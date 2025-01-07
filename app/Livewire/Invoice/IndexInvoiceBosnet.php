<?php

namespace App\Livewire\Invoice;

use App\Http\Controllers\API\InvoiceController;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexInvoiceBosnet extends Component
{
    use WithPagination;

    public $target = 'noso, noinv, status, send_inv_to_bosnet';

    public $noso = '';
    public $noinv = '';
    public $status = '';

    public function send_inv_to_bosnet()
    {
        try {
            $controller = new InvoiceController();

            $controller->sendToBosnet();
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $invoices = DB::table('invoice_bosnet')
            ->where('noso', 'like', '%' . $this->noso . '%')
            ->where('noinv', 'like', '%' . $this->noinv . '%')
            ->where('status_bosnet', 'like', '%' . $this->status . '%')
            ->orderBy('noinv', 'desc')
            ->paginate(20);

        return view('livewire.invoice.index-invoice-bosnet', compact('invoices'));
    }
}
