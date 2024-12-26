<?php

namespace App\Livewire\StockMovement;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexStockMovement extends Component
{
    use WithPagination;

    public $target, $part_number;

    public function mount()
    {
        $this->target = 'part_number';
    }

    public function render()
    {
        $kcpapplication = DB::connection('mysql');

        $log_stock = $kcpapplication->table('trans_log_stock')
            ->where('part_no', 'like', '%' . $this->part_number . '%')
            ->paginate(20);

        return view(
            'livewire.stock-movement.index-stock-movement',
            compact('log_stock')
        );
    }
}
