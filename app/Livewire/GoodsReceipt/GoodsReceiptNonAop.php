<?php

namespace App\Livewire\GoodsReceipt;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoodsReceiptNonAop extends Component
{
    public $target = 'invoiceNon, keterangan';
    public $invoiceNon;
    public $keterangan = 'BELUM SELESAI';

    public function render()
    {
        $items = DB::table('invoice_non_header')
            ->select([
                'invoice_non_header.*',
                DB::raw('(SELECT COUNT(*) FROM invoice_non_detail WHERE invoice_non_detail.invoiceNon = invoice_non_header.invoiceNon AND invoice_non_detail.status = "BOSNET") as total_items_terkirim'),
                DB::raw('(SELECT COUNT(*) FROM invoice_non_detail WHERE invoice_non_detail.invoiceNon = invoice_non_header.invoiceNon) as total_items'),
                DB::raw('CASE
                WHEN
                    (SELECT COUNT(*) FROM invoice_non_detail WHERE invoice_non_detail.invoiceNon = invoice_non_header.invoiceNon AND invoice_non_detail.status = "BOSNET") =
                    (SELECT COUNT(*) FROM invoice_non_detail WHERE invoice_non_detail.invoiceNon = invoice_non_header.invoiceNon)
                THEN "SELESAI"
                ELSE "BELUM SELESAI"
             END as keterangan')
            ])
            ->where('status', 'BOSNET')
            ->where('invoiceNon', 'like', '%' . $this->invoiceNon . '%');

        if (!empty($this->keterangan)) {
            $items->having('keterangan', $this->keterangan);
        }

        $items = $items->paginate(20);

        return view('livewire.goods-receipt.goods-receipt-non-aop', compact(
            'items'
        ));
    }
}
