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
            $qty = $part->qty;


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
