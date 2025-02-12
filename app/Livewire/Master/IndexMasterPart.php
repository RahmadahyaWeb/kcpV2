<?php

namespace App\Livewire\Master;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class IndexMasterPart extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target = "search";
    public $search;

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('mst_part')
            ->where(function ($query) {
                $query->where('part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('nm_part', 'like', '%' . $this->search . '%');
            })
            ->orderBy('kategori_part')
            ->orderBy('group_part')
            ->paginate(20);

        return view('livewire.master.index-master-part', compact('items'));
    }
}
