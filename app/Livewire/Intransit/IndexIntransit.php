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

        $header_to_rollback = $kcpinformation->table('intransit_header')
            ->where('crea_by', 'SYSTEM')
            ->whereDate('tgl_packingsheet', '>=', '2025-03-05')
            ->get();

        dd($header_to_rollback);

        $details_to_rollback = $kcpinformation->table('intransit_details')
            ->where('crea_by', 'SYSTEM')
            ->whereDate('crea_date', '>=', '2025-03-05')
            ->delete();

        // $item = $kcpinformation->table('intransit_details')
        //     ->where('no_sp_aop', '8700010672KCP02001')
        //     // ->where('part_no', 'H2-412PA-K18-110H')
        //     ->update([
        //         'no_sp_aop' => "8700010671KCP02001",
        //         'delivery_note' => "8700010671KCP02001"
        //     ]);


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
