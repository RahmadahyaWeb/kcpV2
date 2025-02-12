<?php

namespace App\Livewire\StockPart;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexStockPart extends Component
{
    use WithPagination;

    public $target = 'search';
    public $search;

    use WithPagination;

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('stock_part as stock')
            ->join('mst_part as part', 'part.part_no', '=', 'stock.part_no')
            ->where('part.status', 'Y')
            ->where(function ($query) {
                $query->where('part.part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('part.nm_part', 'like', '%' . $this->search . '%');
            })
            ->orderBy('part.nm_part')
            ->paginate();

        // dd($items);

        return view('livewire.stock-part.index-stock-part', compact('items'));
    }
}
