<?php

namespace App\Livewire\Purchase;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class PurchaseNonAop extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target = 'invoiceNon, status';
    public $invoiceNon;
    public $status = 'KCP';

    public function render()
    {
        $query = DB::table('invoice_non_header')
            ->select(['*'])
            ->where('invoiceNon', 'like', '%' . $this->invoiceNon . '%');


        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.purchase.purchase-non-aop', compact(
            'items'
        ));
    }
}
