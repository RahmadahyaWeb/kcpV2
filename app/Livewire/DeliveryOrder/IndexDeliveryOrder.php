<?php

namespace App\Livewire\DeliveryOrder;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexDeliveryOrder extends Component
{
    use WithPagination;

    public $no_lkh;
    public $target = 'no_lkh, status';
    public $status = 'KCP';

    public static function cek_status($no_lkh)
    {
        $headerCount = DB::table('do_bosnet')
            ->where('no_lkh', $no_lkh)
            ->where('status_bosnet', 'BOSNET')
            ->count();

        $detailCount = DB::connection('kcpinformation')
            ->table('trns_lkh_details')
            ->where('no_lkh', $no_lkh)
            ->count();

        if ($headerCount == $detailCount) {
            return 'BOSNET';
        } else {
            return 'KCP';
        }
    }

    public function render()
    {
        if ($this->status == 'KCP') {
            $items = DB::connection('kcpinformation')
                ->table('trns_lkh_header')
                ->where('status', 'C')
                ->where('terima_ar', 'N')
                ->where('flag_batal', 'N')
                ->where('no_lkh', 'like', '%' . $this->no_lkh . '%')
                ->orderBy('crea_date', 'desc')
                ->paginate(20);
        } else {
            $items = DB::table('do_bosnet')
                ->paginate(20);
        }

        return view('livewire.delivery-order.index-delivery-order', compact(
            'items'
        ));
    }
}
