<?php

namespace App\Livewire\StockPart;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexStockPart extends Component
{
    use WithPagination;

    public $target = 'search, export';
    public $search;

    use WithPagination;

    public function export()
    {
        $kcpinformation = DB::connection('kcpinformation');

        // Ambil semua data stok part
        $items = $kcpinformation->table('stock_part as stock')
            ->join('mst_part as part', 'part.part_no', '=', 'stock.part_no')
            ->where('part.status', 'Y')
            ->where('part.part_no', 'FP-231PA-K0J-2700')
            ->orderBy('part.nm_part')
            ->get();

        // Ambil daftar part_no untuk query kedua
        $partNumbers = $items->pluck('part_no')->toArray();

        $intransitStock = $kcpinformation->table('intransit_details as intransit')
            ->join('mst_gudang as gudang', 'intransit.kd_gudang_aop', '=', 'gudang.kd_gudang_aop')
            ->where('intransit.status', 'I')
            ->whereIn('intransit.part_no', $partNumbers)
            ->groupBy('intransit.part_no', 'gudang.kd_gudang') // Group berdasarkan part_no dan kd_gudang
            ->select(
                'intransit.part_no',
                'gudang.kd_gudang', // Gudang
                DB::raw('SUM(intransit.qty) as qty_intransit') // Jumlahkan stok intransit
            )
            ->get();

        // Buat mapping untuk stok intransit per part_no dan kd_gudang
        $intransitMap = $intransitStock->mapWithKeys(function ($intransit) {
            $key = $intransit->part_no . '-' . $intransit->kd_gudang; // Key gabungan part_no dan kd_gudang
            return [$key => $intransit->qty_intransit]; // Menyimpan qty_intransit untuk setiap key
        });

        // Gabungkan data stok part (OH) dengan stok intransit per gudang
        $modifiedItems = $items->map(function ($item) use ($intransitMap) {
            // Ambil qty_on_hand per part_no dan gudang
            $qty_on_hand = $item->stock;

            // Tentukan stok intransit berdasarkan gudang
            $qty_intransit_KS = $intransitMap[$item->part_no . '-GD1'] ?? 0;
            $qty_intransit_KT = $intransitMap[$item->part_no . '-GD2'] ?? 0;

            // Tambahkan stok per gudang
            $item->qty_on_hand_KS = $item->kd_gudang == 'GD1' ? $qty_on_hand : 0;
            $item->qty_on_hand_KT = $item->kd_gudang == 'GD2' ? $qty_on_hand : 0;
            $item->qty_intransit_KS = $qty_intransit_KS;
            $item->qty_intransit_KT = $qty_intransit_KT;

            return $item;
        });

        // Gabungkan menjadi satu entri berdasarkan part_no
        $finalItems = $modifiedItems->groupBy('part_no')->map(function ($group) {
            $firstItem = $group->first(); // Ambil item pertama untuk part_no ini

            // Gabungkan qty_on_hand dan qty_intransit per gudang
            $firstItem->qty_on_hand_KS = $group->where('kd_gudang', 'KS')->sum('qty_on_hand_KS');
            $firstItem->qty_on_hand_KT = $group->where('kd_gudang', 'KT')->sum('qty_on_hand_KT');
            $firstItem->qty_intransit_KS = $group->where('kd_gudang', 'KS')->sum('qty_intransit_KS');
            $firstItem->qty_intransit_KT = $group->where('kd_gudang', 'KT')->sum('qty_intransit_KT');

            return $firstItem;
        })->values();

        dd($finalItems);
    }

    public function fetch()
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

        return $modifiedItems;
    }

    public function render()
    {
        $modifiedItems = $this->fetch();

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
