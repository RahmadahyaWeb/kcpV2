<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailIntransit extends Component
{
    public $target = '';

    public $delivery_note;
    public $selectedItems = [];

    public function mount($delivery_note)
    {
        $this->delivery_note = $delivery_note;
    }

    public function updatedSelectedItems($value)
    {
        $selectedItems[] = $value;
    }

    public function save()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_details')
            ->whereIn('id', $this->selectedItems)
            ->get();

        dd($items);
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_details')
            ->where('no_sp_aop', $this->delivery_note)
            ->where('status', 'I')
            ->get();

        return view('livewire.intransit.detail-intransit', compact('items'));
    }
}
