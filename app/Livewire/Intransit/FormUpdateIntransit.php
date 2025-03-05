<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FormUpdateIntransit extends Component
{
    public $target = '';

    public $id;

    public $qty;
    public $qty_terima;
    public $kd_rak;
    public $search_rak;

    public function mount($id)
    {
        $this->id = $id;
    }

    public function updatedSearchRak()
    {
        $this->reset('kd_rak');
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $item = $kcpinformation->table('intransit_details')
            ->where('id', $this->id)
            ->first();

        $this->qty = $item->qty;
        $this->qty_terima = $item->qty_terima;
        $this->kd_rak = $item->kd_rak;

        $kd_gudang = ($item->kd_gudang_aop == 'KCP01001') ? 'GD1' : 'GD2';

        $list_rak = $kcpinformation->table('mst_rakgudang')
            ->select([
                'kd_rak'
            ])
            ->where('kd_gudang', $kd_gudang)
            ->where('kd_rak', 'like', '%' . $this->search_rak . '%')
            ->orderBy('kd_rak')
            ->get();

        return view('livewire.intransit.form-update-intransit', compact(
            'item',
            'list_rak'
        ));
    }
}
