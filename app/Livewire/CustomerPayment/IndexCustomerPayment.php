<?php

namespace App\Livewire\CustomerPayment;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexCustomerPayment extends Component
{
    use WithPagination;

    public $no_piutang;
    public $status_customer_payment = 'O';
    public $target = 'no_piutang, status_customer_payment';

    public function render()
    {
        $kcpapplication = DB::connection('mysql');

        $customer_payment_header = $kcpapplication
            ->table('customer_payment_header')
            ->where('no_piutang', 'like', '%' . $this->no_piutang . '%')
            ->where('status', $this->status_customer_payment)
            ->paginate(20);

        return view('livewire.customer-payment.index-customer-payment', compact(
            'customer_payment_header'
        ));
    }
}
