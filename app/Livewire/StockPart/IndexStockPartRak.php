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
            ->select([
                'stock_part.part_no',
                'stock_part.nm_part',
                'stock_part_rak.kd_rak'
            ])
            ->join('stock_part_rak', 'stock_part_rak.id_stock_part', '=', 'stock_part.id')
            ->where(function ($query) {
                $query->where('stock_part.part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('stock_part.nm_part', 'like', '%' . $this->search . '%');
            })
            ->orderBy('stock_part_rak.kd_rak', 'asc')
            ->paginate();

        return view('livewire.stock-part.index-stock-part-rak', compact(
            'items'
        ));
    }
}
