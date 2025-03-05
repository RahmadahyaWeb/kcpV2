<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FormUpdateIntransit extends Component
{
    public $target = '';
    public $id;

    public function mount($id)
    {
        $this->id = $id;
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $item = $kcpinformation->table('intransit_details')
            ->where('id', $this->id)
            ->first();

        $list_rak = $kcpinformation->table('mst_rakgudang')
            ->select([
                'kd_rak'
            ])
            ->where('kd_gudang', $item->kd_gudang_aop)
            ->orderBy('kd_rak')
            ->get();

        return view('livewire.intransit.form-update-intransit', compact(
            'item',
            'list_rak'
        ));
    }
}
