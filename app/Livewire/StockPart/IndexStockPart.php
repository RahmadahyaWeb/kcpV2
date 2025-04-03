<?php

namespace App\Livewire\StockPart;

use Illuminate\Pagination\LengthAwarePaginator;
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

        // Ambil semua data stok part
        $items = DB::table('stock_part as stock')
            ->join('mst_part as part', 'part.part_no', '=', 'stock.part_no')
            ->where('part.status', 'Y')
            ->where(function ($query) {
                $query->where('part.part_no', 'like', '%' . $this->search . '%')
                    ->orWhere('part.nm_part', 'like', '%' . $this->search . '%');
            })
            ->orderBy('part.nm_part')
            ->get();

        // Ambil daftar part_no untuk query kedua
        $partNumbers = $items->pluck('part_no')->toArray();

        // Ambil data intransit stock
        $intransitStock = DB::table('intransit_details as intransit')
            ->join('mst_gudang as gudang', 'intransit.kd_gudang_aop', '=', 'gudang.kd_gudang_aop')
            ->where('intransit.status', 'I')
            ->whereIn('intransit.part_no', $partNumbers)
            ->groupBy('intransit.kd_gudang_aop', 'intransit.part_no')
            ->select(
                'intransit.kd_gudang_aop as gudang_aop',
                'intransit.part_no',
                DB::raw('SUM(intransit.qty) as qty_intransit')
            )
            ->get();

        // Buat mapping untuk qty_intransit
        $intransitMap = $intransitStock->keyBy('part_no');

        // Tambahkan qty_intransit ke hasil utama
        $modifiedItems = $items->map(function ($item) use ($intransitMap) {
            $partNo = $item->part_no;
            $item->qty_intransit = $intransitMap[$partNo]->qty_intransit ?? 0;
            return $item;
        });

        // Pagination manual
        $page = $this->page; // Livewire menangani page otomatis
        $offset = ($page - 1) * $this->perPage;
        $pagedItems = $modifiedItems->slice($offset, $this->perPage)->values();

        $paginatedItems = new LengthAwarePaginator(
            $pagedItems,
            $modifiedItems->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.stock-part.index-stock-part', compact('items'));
    }
}
