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

        // $items = $kcpinformation->table('stock_part as stock')
        //     ->join('mst_part as part', 'part.part_no', '=', 'stock.part_no')
        //     ->where('part.status', 'Y')
        //     ->where(function ($query) {
        //         $query->where('part.part_no', 'like', '%' . $this->search . '%')
        //             ->orWhere('part.nm_part', 'like', '%' . $this->search . '%');
        //     })
        //     ->orderBy('part.nm_part')
        //     ->paginate();

        $items = $kcpinformation->table('stock_part as stock')
            ->join('mst_part as part', 'part.part_no', '=', 'stock.part_no')
            ->leftJoin('intransit_details as intransit', function ($join) {
                $join->on('stock.part_no', '=', 'intransit.part_no')
                    ->join('mst_gudang as gudang', 'intransit.kd_gudang_aop', '=', 'gudang.kd_gudang_aop')
                    ->where('intransit.status', 'I');
            })
            ->where('part.status', 'Y')
            ->where(function ($query) {
                $query->where('part.part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('part.nm_part', 'like', '%' . $this->search . '%');
            })
            ->select(
                'part.part_no',
                'part.nm_part',
                'stock.stock',
                DB::raw("SUM(CASE WHEN gudang.kd_gudang = 'GD1' THEN intransit.qty ELSE 0 END) as qty_intransit_kalsel"),
                DB::raw("SUM(CASE WHEN gudang.kd_gudang = 'GD2' THEN intransit.qty ELSE 0 END) as qty_intransit_kalteng")
            )
            ->groupBy('part.part_no', 'part.nm_part', 'stock.stock')
            ->orderBy('part.nm_part')
            ->paginate();
        dd($items);

        return view('livewire.stock-part.index-stock-part', compact('items'));
    }
}
