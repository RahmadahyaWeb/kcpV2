<?php

namespace App\Livewire\Lss;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IndexLss extends Component
{
    public $target = 'lihat_detail';
    public $items = [];
    public $from_date, $to_date;
    public $from_date_detail, $to_date_detail, $part_no_detail;

    /**
     * IBARATKAN KITA START APRIL, ARTINYA KITA MENGAMBIL DATA DARI 1 MARET - 31 MARET
     * SEHINGGA BELI QTY DAN JUAL QTY ITU 0 (HANYA ADA AWAL QTY DAN AKHIR QTY)
     *
     */

    public function export()
    {
        // TAMBAHKAN VALIDASI TIDAK BOLEH KURANG DARI BULAN MARET

        $this->validate([
            'from_date'     => ['required'],
            'to_date'       => ['required'],
        ]);

        $fromDateFormatted = Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = Carbon::parse($this->to_date)->endOfDay();

        $this->fetch($fromDateFormatted, $toDateFormatted);
    }

    public function lihat_detail()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $this->validate([
            'from_date_detail'     => ['required'],
            'to_date_detail'       => ['required'],
            'part_no_detail'       => ['required']
        ]);

        $fromDateFormatted = Carbon::parse($this->from_date_detail)->startOfDay()->format('Y-m-d H:i:s');
        $toDateFormatted = Carbon::parse($this->to_date_detail)->endOfDay()->format('Y-m-d H:i:s');

        // DATA PART
        $part = $kcpinformation->table('stock_part')
            ->select('kelompok_part', 'produk_part', 'part_no')
            ->where('part_no', '11-GTZ-7VMF')
            ->groupBy('kelompok_part', 'produk_part', 'part_no')
            ->first();


        $cogs_stock_awal = 210000; // anggap lah harga untuk stock awalnya

        $stock_awal = $kcpinformation->table('trns_log_stock')
            ->selectRaw('part_no, stock + kredit AS stock_awal')
            ->where(function ($query) {
                $query->where(DB::raw("REPLACE(REPLACE(part_no, '-', ''), '/', '')"), '=', '131008014SPR40TL')
                    ->orWhere('part_no', '=', '13-100/8014SPR40TL');
            })
            ->whereBetween(DB::raw("DATE_FORMAT(crea_date, '%Y-%m-%d')"), ['2025-01-01', '2025-01-31'])
            ->orderBy('crea_date')
            ->orderBy('id')
            ->first();


        // Ambil data penjualan
        $data_penjualan = $kcpinformation->table('trns_inv_header as header')
            ->join('trns_inv_details as detail', 'detail.noinv', '=', 'header.noinv')
            ->where('detail.part_no', '13-100/8014SPR40TL')
            ->whereBetween(DB::raw("DATE_FORMAT(header.crea_date, '%Y-%m-%d')"), ['2025-01-01', '2025-01-31'])
            ->select([
                'header.noinv as no_dokumen',
                'header.kd_outlet as outlet',
                'header.crea_date as tanggal',
                'detail.part_no',
                'detail.nominal_total as amount',
                'detail.qty',
                DB::raw("'penjualan' as tipe")
            ])
            ->get();

        // Ambil data pembelian
        $data_pembelian = DB::table('invoice_aop_header as header')
            ->join('invoice_aop_detail as detail', 'detail.invoiceAop', '=', 'header.invoiceAop')
            ->select([
                'header.invoiceAop as no_dokumen',
                DB::raw("'' as outlet"), // Tidak ada outlet di pembelian
                'header.billingDocumentDate as tanggal',
                'detail.materialNumber as part_no',
                'detail.amount',
                'detail.qty',
                DB::raw("'pembelian' as tipe")
            ])
            ->where('detail.materialNumber', '13-100/8014SPR40TL')
            ->whereBetween('header.billingDocumentDate', ['2025-01-01', '2025-01-31'])
            ->get();

        // Gabungkan dan tambahkan harga_per_pcs
        $gabungan = $data_penjualan
            ->concat($data_pembelian)
            ->map(function ($item) {
                $item->harga_per_pcs = ($item->qty > 0) ? ($item->amount / $item->qty) : 0;
                return $item;
            })
            ->sortBy('tanggal')
            ->values(); // Reset index

        $cogs_stock_awal = 210000;
        $stock_awal_qty = $stock_awal->stock_awal ?? 0;

        // Siapkan FIFO stock layers
        $fifo_layers = [];

        if ($stock_awal_qty > 0) {
            $fifo_layers[] = [
                'qty' => $stock_awal_qty,
                'harga_per_pcs' => $cogs_stock_awal
            ];
        }

        // Tambahkan pembelian ke fifo_layers
        $data_pembelian_sorted = $data_pembelian->sortBy('tanggal');
        foreach ($data_pembelian_sorted as $item) {
            if ($item->qty > 0) {
                $fifo_layers[] = [
                    'qty' => $item->qty,
                    'harga_per_pcs' => $item->amount / $item->qty
                ];
            }
        }

        // Urutkan gabungan berdasarkan tanggal
        $gabungan = $data_penjualan
            ->concat($data_pembelian)
            ->sortBy('tanggal')
            ->values();

        // Loop untuk proses FIFO profit per transaksi penjualan
        $results = [];

        foreach ($gabungan as $item) {
            if ($item->tipe === 'penjualan') {
                $qty_jual = $item->qty;
                $harga_jual_per_pcs = $item->harga_per_pcs ?? ($item->amount / $item->qty);
                $total_modal = 0;

                while ($qty_jual > 0 && count($fifo_layers) > 0) {
                    $layer = &$fifo_layers[0];

                    if ($layer['qty'] <= 0) {
                        array_shift($fifo_layers); // habis, buang
                        continue;
                    }

                    $ambil_qty = min($qty_jual, $layer['qty']);
                    $total_modal += $ambil_qty * $layer['harga_per_pcs'];

                    // kurangi stock di layer dan qty jual
                    $layer['qty'] -= $ambil_qty;
                    $qty_jual -= $ambil_qty;

                    if ($layer['qty'] == 0) {
                        array_shift($fifo_layers); // habis, buang
                    }
                }

                $profit = $item->amount - $total_modal;

                $results[] = [
                    'tanggal' => $item->tanggal,
                    'no_dokumen' => $item->no_dokumen,
                    'qty_jual' => $item->qty,
                    'harga_jual_per_pcs' => $harga_jual_per_pcs,
                    'total_penjualan' => $item->amount,
                    'total_modal' => $total_modal,
                    'profit' => $profit
                ];
            }
        }


        dd($results);

        $result = [
            'kelompok_part' => $part->kelompok_part ?? null,
            'produk_part'   => $part->produk_part ?? null,
            'part_no'       => $part->part_no ?? null,
            'stock_awal'    => $stock_awal->stock_awal ?? null,
        ];
    }

    public function fetch($from_date, $to_date)
    {
        $kcpinformation = DB::connection('kcpinformation');

        // AMBIL DATA PART
        $stock_part = $kcpinformation->table('stock_part')
            ->selectRaw('kelompok_part, produk_part, part_no, SUM(stock)')
            ->where('part_no', '11-GTZ-7VMF')
            ->groupBy('kelompok_part', 'produk_part', 'part_no')
            ->get();

        dd($stock_part);
    }

    public function render()
    {
        return view('livewire.lss.index-lss');
    }
}
