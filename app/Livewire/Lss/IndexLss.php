<?php

namespace App\Livewire\Lss;

use App\Exports\LaporanLssSheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexLss extends Component
{
    public $target = 'export';
    public $bulan = '01', $tahun = '2025';
    public $part_no;
    public $resultsPerPart = [];

    public function export()
    {
        $this->generateLaporanFifo();

        usort($this->resultsPerPart, function ($a, $b) {
            return strcmp($a['produk_part'], $b['produk_part']);
        });

        $filename = "LAPORAN_LSS_" . date('Y-m-d') . ".xlsx";

        return Excel::download(new LaporanLssSheet($this->resultsPerPart), $filename);
    }

    public function generateLaporanFifo()
    {
        $this->validate([
            'bulan'     => ['required'],
            'tahun'     => ['required'],
        ]);

        $kcpinformation = DB::connection('kcpinformation');

        $bulan = $this->bulan;
        $tahun = $this->tahun;

        $this->resultsPerPart = [];

        $partNumbers = $kcpinformation->table('mst_part')
            ->where('status', 'Y')
            ->where('supplier', 'NON AOP');
            // ->whereIn('supplier', ['SSI', 'KMC', 'ABM']);
            // ->whereIn('produk_part', ['ASPIRA TUBE 2W']);

        if ($this->part_no) {
            $partNumbers = $partNumbers->where('part_no', $this->part_no);
        }

        $partNumbers = $partNumbers->get();

        foreach ($partNumbers as $part) {
            // Ambil data pembelian (fifo_layers)
            $pembelian = DB::table('fifo_layers')
                ->where('part_no', $part->part_no)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->whereNotIn('source', ['stock_awal', 'carry_over']) // <-- ini ditambahkan
                ->get();

            $totalQtyBeli = $pembelian->sum('qty_awal');
            $historiPembelian = $pembelian->map(function ($row) {
                return [
                    'source_id' => $row->source_id,
                    'tanggal' => $row->tanggal,
                    'qty' => $row->qty_awal,
                    'harga' => $row->harga_per_unit,
                ];
            });

            // Ambil data usages
            $usages = DB::table('fifo_usages')
                ->where('part_no', $part->part_no)
                ->whereMonth('tanggal_penjualan', $bulan)
                ->whereYear('tanggal_penjualan', $tahun)
                ->get();

            // Ambil layer untuk lookup sumber pembelian
            $layerIds = $usages->pluck('layer_id')->unique()->toArray();
            $layers = DB::table('fifo_layers')
                ->whereIn('id', $layerIds)
                ->get()
                ->keyBy('id');

            // Ambil invoice untuk harga jual
            $saleIds = $usages->pluck('sale_id')->unique()->toArray();
            $detailPenjualan = DB::connection('kcpinformation')
                ->table('trns_inv_details')
                ->whereIn('noinv', $saleIds)
                ->where('part_no', $part->part_no)
                ->select('noinv', 'part_no', 'qty', 'nominal_total')
                ->get()
                ->keyBy('noinv');

            $historiPenjualan = [];
            $totalQtyJual = 0;
            $totalModal = 0;
            $totalPenjualan = 0;

            foreach ($usages as $row) {
                $layer = $layers[$row->layer_id] ?? null;
                $detail = $detailPenjualan[$row->sale_id] ?? null;

                if (!$layer || !$detail) continue;

                $hargaJualPerUnit = $detail->qty > 0 ? $detail->nominal_total / $detail->qty : 0;
                $subTotalJual = $row->qty_terpakai * $hargaJualPerUnit;
                $subTotalModal = $row->qty_terpakai * $row->harga_modal;

                $historiPenjualan[] = [
                    'tanggal' => $row->tanggal_penjualan,
                    'noinv' => $row->sale_id,
                    'qty' => $row->qty_terpakai,
                    'harga_modal' => $row->harga_modal,
                    'harga_jual' => $hargaJualPerUnit,
                    'subtotal_modal' => $subTotalModal,
                    'subtotal_jual' => $subTotalJual,
                    'sumber_modal' => [
                        'source' => $layer->source,
                        'source_id' => $layer->source_id,
                        'tanggal_layer' => $layer->tanggal,
                        'harga_per_unit_layer' => $layer->harga_per_unit,
                    ]
                ];

                $totalQtyJual += $row->qty_terpakai;
                $totalModal += $subTotalModal;
                $totalPenjualan += $subTotalJual;
            }

            // Ambil stock awal (stock_awal / carry_over)
            $stockAwal = DB::table('fifo_layers')
                ->where('part_no', $part->part_no)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->whereIn('source', ['stock_awal', 'carry_over'])
                ->get()
                ->map(function ($row) {
                    return [
                        'tanggal' => $row->tanggal,
                        'source' => $row->source,
                        'qty' => $row->qty_awal,
                        'harga' => $row->harga_per_unit,
                    ];
                })
                ->toArray();

            // Simpan hasil per part
            $this->resultsPerPart[] = [
                'part_no' => $part->part_no,
                'produk_part' => $part->produk_part,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_beli' => $totalQtyBeli,
                'total_jual' => $totalQtyJual,
                'total_modal' => $totalModal,
                'total_penjualan' => $totalPenjualan,
                'total_profit' => $totalPenjualan - $totalModal,
                'histori_pembelian' => $historiPembelian,
                'histori_penjualan' => $historiPenjualan,
                'stock_awal' => $stockAwal,
            ];
        }

        $this->reset('part_no');
    }

    public function render()
    {
        return view('livewire.lss.index-lss');
    }
}
