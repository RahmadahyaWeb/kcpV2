<?php

namespace App\Livewire\StockPart;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexStockPartRak extends Component
{
    use WithPagination;

    public $target = 'search';

    public $search;

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('stock_part')
            ->whereNotNull('kd_rak')
            ->where('kd_rak', '<>', '')
            ->where(function ($query) {
                $query->where('part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('nm_part', 'like', '%' . $this->search . '%');
            })
            ->orderBy('kd_rak', 'asc')
            ->paginate();

        return view('livewire.stock-part.index-stock-part-rak', compact(
            'items'
        ));
    }
}
