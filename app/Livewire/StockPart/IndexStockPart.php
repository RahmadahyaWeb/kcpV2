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
        $items = $kcpinformation->table('stock_part as stock')
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

        $intransitStock = $kcpinformation->table('intransit_details as intransit')
            ->join('mst_gudang as gudang', 'intransit.kd_gudang_aop', '=', 'gudang.kd_gudang_aop')
            ->where('intransit.status', 'I')
            ->whereIn('intransit.part_no', $partNumbers)
            ->groupBy('intransit.kd_gudang_aop', 'intransit.part_no', 'gudang.kd_gudang') // Tambahkan kd_gudang
            ->select(
                'intransit.kd_gudang_aop as gudang_aop',
                'gudang.kd_gudang', // Tambahkan kd_gudang
                'intransit.part_no',
                DB::raw('SUM(intransit.qty) as qty_intransit')
            )
            ->get();

        $intransitMap = $intransitStock->mapWithKeys(function ($intransit) {
            $key = $intransit->part_no . '-' . $intransit->kd_gudang; // Gunakan key unik
            return [$key => $intransit];
        });

        $modifiedItems = $items->map(function ($item) use ($intransitMap) {
            $key = $item->part_no . '-' . $item->kd_gudang; // Sesuaikan dengan key yang sudah dibuat
            $item->qty_intransit = $intransitMap[$key]->qty_intransit ?? 0;
            return $item;
        });

        // Pagination manual
        $page = $this->getPage(); // Livewire menangani current page otomatis
        $perPage = 10; // Ambil jumlah item per halaman dari property
        $offset = ($page - 1) * $perPage;

        // Potong data sesuai halaman
        $pagedItems = $modifiedItems->slice($offset, $perPage)->values();

        // Buat LengthAwarePaginator manual
        $paginatedItems = new LengthAwarePaginator(
            $pagedItems,
            $modifiedItems->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.stock-part.index-stock-part', compact('paginatedItems'));
    }
}
