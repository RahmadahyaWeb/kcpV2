<?php

namespace App\Livewire\GoodsReceipt;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class GoodsReceiptAop extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target = 'invoiceAop, spb, keterangan';
    public $invoiceAop;
    public $spb;
    public $keterangan = 'BELUM SELESAI';

    public function render()
    {
        $items = DB::table('invoice_aop_header')
            ->select([
                'invoice_aop_header.*',
                DB::raw('(SELECT COUNT(*) FROM invoice_aop_detail WHERE invoice_aop_detail.invoiceAop = invoice_aop_header.invoiceAop AND invoice_aop_detail.status = "BOSNET") as total_items_terkirim'),
                DB::raw('(SELECT COUNT(*) FROM invoice_aop_detail WHERE invoice_aop_detail.invoiceAop = invoice_aop_header.invoiceAop) as total_items'),
                DB::raw('CASE
                WHEN
                    (SELECT COUNT(*) FROM invoice_aop_detail WHERE invoice_aop_detail.invoiceAop = invoice_aop_header.invoiceAop AND invoice_aop_detail.status = "BOSNET") =
                    (SELECT COUNT(*) FROM invoice_aop_detail WHERE invoice_aop_detail.invoiceAop = invoice_aop_header.invoiceAop)
                THEN "SELESAI"
                ELSE "BELUM SELESAI"
             END as keterangan')
            ])
            ->where('flag_po', 'Y')
            ->where('invoiceAop', 'like', '%' . $this->invoiceAop . '%')
            ->where('SPB', 'like', '%' . $this->spb . '%');

        if (!empty($this->keterangan)) {
            $items->having('keterangan', $this->keterangan);
        }

        $items = $items->paginate(20);

        return view('livewire.goods-receipt.goods-receipt-aop', compact(
            'items'
        ));
    }
}
