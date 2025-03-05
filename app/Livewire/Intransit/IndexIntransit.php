<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexIntransit extends Component
{
    use WithPagination;

    public $target = '';

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_header')
            ->select([
                'delivery_note'
            ])
            ->where('status', 'I')
            ->whereDate('tgl_packingsheet', '>', '2017-07-01')
            ->groupBy('delivery_note')
            ->get();

        return view('livewire.intransit.index-intransit', compact('items'));
    }
}
