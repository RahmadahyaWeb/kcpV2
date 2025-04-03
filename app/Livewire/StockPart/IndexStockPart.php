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
            ->leftJoin('kcpinformation.intransit_details as intransit', function ($join) {
                $join->on('part.part_no', '=', 'intransit.part_no')
                    ->where('intransit.status', 'I');
            })
            ->leftJoin('kcpinformation.mst_gudang as gudang', 'intransit.kd_gudang_aop', '=', 'gudang.kd_gudang_aop')
            ->where('part.status', 'Y')
            ->where(function ($query) {
                $query->where('part.part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('part.nm_part', 'like', '%' . $this->search . '%');
            })
            ->where(function ($query) {
                // Menambahkan filter untuk gudang Kalsel dan Kalteng
                $query->where('gudang.kd_gudang', 'GD1')
                    ->orWhere('gudang.kd_gudang', 'GD2');
            })
            ->select(
                'stock.*',
                'part.*',
                'intransit.qty as qty_intransit', // Alias untuk menghindari nama duplikat
                'part.part_no as part_no_part',   // Alias untuk part_no dari tabel part
                'intransit.part_no as part_no_intransit' // Alias untuk part_no dari tabel intransit
            )
            ->groupBy('part.part_no')
            ->orderBy('part.nm_part')
            ->paginate();

        dd($items);

        return view('livewire.stock-part.index-stock-part', compact('items'));
    }
}
