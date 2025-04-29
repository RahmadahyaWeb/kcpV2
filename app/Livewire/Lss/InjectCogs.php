<?php

namespace App\Livewire\Lss;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class InjectCogs extends Component
{
    use WithPagination;

    public $target = "inject";
    public $file_cogs;

    public function inject()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $parts = $kcpinformation->table('mst_part')
            ->where('status', 'Y')
            ->whereIn('produk_part', ['ASPIRA TUBE 2W'])
            ->get();

        $harga_cogs = DB::table('lss_cogs_temp')->get();

        // Ubah $harga_cogs menjadi associative array berdasarkan part_no
        $cogsByPartNo = $harga_cogs->keyBy('part_no');

        // Gabungkan
        $merged = $parts->map(function ($part) use ($cogsByPartNo) {
            $part_no = $part->part_no;

            return (object) [
                'part_no' => $part_no,
                'nama_part' => $part->nm_part ?? null,
                'produk_part' => $part->produk_part ?? null,
                'cogs' => $cogsByPartNo[$part_no]->cogs ?? null, // ambil cogs jika ada
            ];
        });

        $totalParts = $parts->count();
        $successCount = 0;
        $failedCount = 0;

        foreach ($merged as $part) {
            $partNo = $part->part_no;
            $hargaPcs = $part->cogs;

            $cleanedPartNo = str_replace(['-', '/'], '', $partNo);

            $qty = 0;

            try {
                $stock_awal = $kcpinformation->table('trns_log_stock')
                    ->selectRaw("
                        part_no,
                        kd_gudang,
                        status,
                        keterangan,
                        qty,
                        debet,
                        kredit,
                        stock,
                        stock - kredit AS stock_awal,
                        crea_date,
                        crea_by
                    ")
                    ->whereRaw("REPLACE(REPLACE(part_no, '-', ''), '/', '') = ?", [$cleanedPartNo])
                    ->where('kd_gudang', 'GD1')
                    ->whereBetween(DB::raw("DATE_FORMAT(crea_date, '%Y-%m-%d')"), ['2025-01-01', '2025-01-31'])
                    ->orderBy('crea_date')
                    ->orderBy('id')
                    ->first();

                if ($stock_awal) {
                    if (stripos($stock_awal->keterangan, 'TERIMA MUTASI') !== false) {
                        $qty = $stock_awal->stock - $stock_awal->qty;
                    } elseif (
                        stripos($stock_awal->keterangan, 'PENJUALAN') !== false ||
                        stripos($stock_awal->keterangan, 'PENERIMAAN') !== false
                    ) {
                        $qty = $stock_awal->stock + $stock_awal->qty;
                    } else {
                        $qty = $stock_awal->stock;
                    }

                    $successCount++;
                    Log::info("Inject berhasil dari trns_log_stock: $partNo");
                } else {
                    // Cek ke stock_part
                    $stockPart = $kcpinformation->table('stock_part')
                        ->select('stock')
                        ->whereRaw("REPLACE(REPLACE(part_no, '-', ''), '/', '') = ?", [$cleanedPartNo])
                        ->where('kd_gudang', 'GD1')
                        ->first();

                    if ($stockPart) {
                        $qty = $stockPart->stock;
                        $successCount++;
                        Log::info("Inject berhasil dari stock_part: $partNo");
                    } else {
                        $qty = 0;
                        $failedCount++;
                        Log::warning("Data tidak ditemukan di trns_log_stock & stock_part: $partNo");
                    }
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Gagal inject: $partNo | Error: " . $e->getMessage());
            }

            // Simpan data meskipun gagal atau log tidak ditemukan
            DB::table('stock_awal')->insert([
                'part_no' => $partNo,
                'qty' => $qty,
                'harga' => $hargaPcs,
                'tahun' => 2025,
                'bulan' => 1,
            ]);
        }

        $summary = "$successCount/$totalParts part berhasil di-inject.";
        Log::info("Ringkasan inject stock awal: $summary");
    }

    public function render()
    {
        $items = DB::table('lss_cogs_temp')
            ->paginate();

        return view('livewire.lss.inject-cogs', compact('items'));
    }
}
