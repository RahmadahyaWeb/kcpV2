<?php

namespace App\Livewire\Lss;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GenerateLss extends Component
{
    public $target = "seedFifoLayers, prosesPenjualanFifo";
    public $bulan = '01', $tahun = '2025';

    public function seedFifoLayers()
    {
        $this->validate([
            'bulan' => ['required'],
            'tahun' => ['required']
        ]);

        $kcpinformation = DB::connection('kcpinformation');

        $bulan = $this->bulan;
        $tahun = $this->tahun;
        $periode = "$tahun-$bulan";
        $tanggalAwal = Carbon::parse("$periode-01")->startOfMonth();
        $tanggalAkhir = Carbon::parse("$periode-01")->endOfMonth();
        $tanggalLayer = $tanggalAwal->toDateString();

        $partNumbers = $kcpinformation->table('mst_part')
            ->where('status', 'Y')
            ->whereIn('produk_part', ['ASPIRA TUBE 2W'])
            ->pluck('part_no');

        foreach ($partNumbers as $partNo) {
            if ((int) $bulan === 1) {
                $stockAwal = DB::table('stock_awal')
                    ->where('part_no', $partNo)
                    ->first();

                if ($stockAwal) {
                    DB::table('fifo_layers')->updateOrInsert([
                        'part_no' => $partNo,
                        'source' => 'stock_awal',
                        'source_id' => 'stock_awal_' . $tahun,
                    ], [
                        'tanggal' => $tanggalLayer,
                        'qty_awal' => $stockAwal->qty,
                        'qty_sisa' => $stockAwal->qty,
                        'harga_per_unit' => $stockAwal->harga,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $tanggalBulanLalu = $tanggalAwal->copy()->subMonth();

                $carryExist = DB::table('fifo_layers')
                    ->where('part_no', $partNo)
                    ->where('source', 'carry_over')
                    ->whereMonth('tanggal', $bulan)
                    ->whereYear('tanggal', $tahun)
                    ->exists();

                if (!$carryExist) {
                    $layerSebelumnya = DB::table('fifo_layers')
                        ->where('part_no', $partNo)
                        ->whereMonth('tanggal', $tanggalBulanLalu->month)
                        ->whereYear('tanggal', $tanggalBulanLalu->year)
                        ->where('qty_sisa', '>', 0)
                        ->get();

                    foreach ($layerSebelumnya as $prevLayer) {
                        DB::table('fifo_layers')->insert([
                            'part_no' => $prevLayer->part_no,
                            'tanggal' => $tanggalLayer,
                            'source' => 'carry_over',
                            'source_id' => $prevLayer->id,
                            'qty_awal' => $prevLayer->qty_sisa,
                            'qty_sisa' => $prevLayer->qty_sisa,
                            'harga_per_unit' => $prevLayer->harga_per_unit,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $pembelian = DB::table('invoice_aop_header as header')
                ->join('invoice_aop_detail as detail', 'detail.invoiceAop', '=', 'header.invoiceAop')
                ->select([
                    'header.invoiceAop as no_dokumen',
                    'header.billingDocumentDate as tanggal',
                    'detail.materialNumber as part_no',
                    'detail.amount',
                    'detail.qty',
                ])
                ->where('detail.materialNumber', $partNo)
                ->whereBetween('header.billingDocumentDate', [$tanggalAwal, $tanggalAkhir])
                ->get();

            foreach ($pembelian as $row) {
                $harga = $row->qty > 0 ? $row->amount / $row->qty : 0;

                DB::table('fifo_layers')->insert([
                    'part_no' => $row->part_no,
                    'tanggal' => $row->tanggal,
                    'source' => 'pembelian',
                    'source_id' => $row->no_dokumen,
                    'qty_awal' => $row->qty,
                    'qty_sisa' => $row->qty,
                    'harga_per_unit' => $harga,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function prosesPenjualanFifo()
    {
        $this->validate([
            'bulan' => ['required'],
            'tahun' => ['required']
        ]);

        $kcpinformation = DB::connection('kcpinformation');

        $bulan = $this->bulan;
        $tahun = $this->tahun;
        $periode = "$tahun-$bulan";
        $tanggalAwal = Carbon::parse("$periode-01")->startOfMonth();
        $tanggalAkhir = Carbon::parse("$periode-01")->endOfMonth();

        // Ambil part number yang akan diproses
        $partNumbers = $kcpinformation->table('mst_part')
            ->where('status', 'Y')
            ->whereIn('produk_part', ['ASPIRA TUBE 2W'])
            ->pluck('part_no');

        // Ambil kombinasi sale_id-part_no yang sudah diproses sebelumnya
        $existingUsages = DB::table('fifo_usages')
            ->select(DB::raw("CONCAT(sale_id, '-', part_no) as usage_key"))
            ->pluck('usage_key')
            ->toArray();

        foreach ($partNumbers as $partNo) {
            // Ambil penjualan dari tabel header + detail
            $penjualan = $kcpinformation->table('trns_inv_header as header')
                ->join('trns_inv_details as detail', 'header.noinv', '=', 'detail.noinv')
                ->whereBetween('header.crea_date', [$tanggalAwal, $tanggalAkhir])
                ->where('detail.part_no', $partNo)
                ->orderBy('header.crea_date')
                ->select(
                    'header.crea_date as tanggal',
                    'header.noinv',
                    'detail.part_no',
                    'detail.qty'
                )
                ->get();

            foreach ($penjualan as $sale) {
                $usageKey = "{$sale->noinv}-{$sale->part_no}";

                // Cek jika sudah pernah diproses, skip
                if (in_array($usageKey, $existingUsages)) {
                    continue;
                }

                $qtyDibutuhkan = $sale->qty;

                // Ambil layer FIFO yang masih ada qty_sisa
                $layers = DB::table('fifo_layers')
                    ->where('part_no', $sale->part_no)
                    ->where('qty_sisa', '>', 0)
                    ->orderBy('tanggal')
                    ->lockForUpdate()
                    ->get();

                foreach ($layers as $layer) {
                    if ($qtyDibutuhkan <= 0) {
                        break;
                    }

                    $pakai = min($qtyDibutuhkan, $layer->qty_sisa);

                    DB::table('fifo_usages')->insert([
                        'part_no' => $sale->part_no,
                        'tanggal_penjualan' => $sale->tanggal,
                        'sale_id' => $sale->noinv,
                        'layer_id' => $layer->id,
                        'qty_terpakai' => $pakai,
                        'harga_modal' => $layer->harga_per_unit,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('fifo_layers')
                        ->where('id', $layer->id)
                        ->decrement('qty_sisa', $pakai);

                    $qtyDibutuhkan -= $pakai;
                }

                // Tambahkan ke daftar yang sudah diproses agar tidak terulang di loop part selanjutnya
                $existingUsages[] = $usageKey;
            }
        }
    }

    public function render()
    {
        return view('livewire.lss.generate-lss');
    }
}
