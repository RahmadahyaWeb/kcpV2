<?php

namespace App\Livewire\CustomerPayment;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexCustomerPayment extends Component
{
    use WithPagination;

    public $no_piutang;
    public $search_toko;
    public $status_customer_payment = 'O';
    public $pembayaran_via;
    public $target = 'no_piutang, status_customer_payment, search_toko, pembayaran_via, batal';

    public function batal($no_piutang)
    {
        $kcpapplication = DB::connection('mysql');

        try {
            $kcpapplication->beginTransaction();

            $kcpapplication->table('customer_payment_header')
                ->where('no_piutang', $no_piutang)
                ->update([
                    'status' => 'F'
                ]);

            $kcpapplication->commit();

            session()->flash('success', "Berhasil batal customer payment: $no_piutang");
        } catch (\Exception $e) {
            $kcpapplication->rollBack();

            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $kcpapplication = DB::connection('mysql');

        $customer_payment_header = $kcpapplication
            ->table('customer_payment_header')
            ->where(function ($query) {
                $query->where('kd_outlet', 'like', '%' . $this->search_toko . '%')
                    ->orWhere('nm_outlet', 'like', '%' . $this->search_toko . '%');
            })
            ->where('no_piutang', 'like', '%' . $this->no_piutang . '%')
            ->where('pembayaran_via', 'like', '%' . $this->pembayaran_via . '%')
            ->where('status', $this->status_customer_payment)
            ->orderBy('crea_date', 'desc')
            ->paginate(20);

        return view('livewire.customer-payment.index-customer-payment', compact(
            'customer_payment_header'
        ));
    }
}
