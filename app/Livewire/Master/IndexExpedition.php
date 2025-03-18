<?php

namespace App\Livewire\Master;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexExpedition extends Component
{
    use WithPagination;

    public $target = '';

    public function render()
    {
        $items = DB::table('mst_expedition')
            ->paginate();

        return view('livewire.master.index-expedition', compact('items'));
    }
}
