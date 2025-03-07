<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexIntransit extends Component
{
    use WithPagination;

    public $target = 'delivery_note';
    public $delivery_note;

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_header')
            ->select([
                'delivery_note',
                'kd_gudang_aop',
                'tgl_packingsheet'
            ])
            ->where('status', 'I')
            ->where('delivery_note', 'like', '%' . $this->delivery_note . '%')
            ->whereDate('tgl_packingsheet', '>', '2017-07-01')
            ->groupBy('delivery_note', 'kd_gudang_aop', 'tgl_packingsheet')
            ->get();

        return view('livewire.intransit.index-intransit', compact('items'));
    }
}
