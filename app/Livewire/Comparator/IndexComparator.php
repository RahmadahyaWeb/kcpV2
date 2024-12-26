<?php

namespace App\Livewire\Comparator;

use App\Exports\ComparatorExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexComparator extends Component
{
    public $barcode;
    public $number_update;
    public $items = [];

    public $part_number;
    public $qty;
    public $edited_qty;

    public function store()
    {
        if (!$this->barcode) {
            session()->flash('error', 'Barcode error.');
            return;
        }

        try {
            DB::beginTransaction();

            // Periksa apakah part_number sudah ada
            $save_part_number = str_replace(' ', '', trim($this->barcode));
            $existingRecord = DB::table('comparator')
                ->where('part_number', $save_part_number)
                ->lockForUpdate() // Tambahkan lock for update
                ->first();

            if ($existingRecord) {
                // Jika ada, increment qty
                DB::table('comparator')
                    ->where('part_number', $save_part_number)
                    ->increment('qty', 1);

                DB::table('comparator')
                    ->where('part_number', $save_part_number)
                    ->update([
                        'created_at' => now()
                    ]);
            } else {
                // Jika tidak ada, masukkan data baru
                DB::table('comparator')->insert([
                    'part_number' => $save_part_number,
                    'qty'         => 1,
                    'scan_by'     => Auth::user()->username,
                    'created_at'  => now(),
                ]);
            }

            DB::commit(); // Commit transaksi

            // Reset field
            $this->barcode = '';

            session()->flash('success', "Berhasil scan barcode.");

            $this->dispatch('qty-saved');
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi kesalahan
        }
    }

    public function resetComparator()
    {
        DB::table('comparator')
            ->truncate();
    }

    public function export()
    {
        $filename = 'comparator_result_' . date('d-m-Y_H-i') . '.xlsx';

        return Excel::download(new ComparatorExport(), $filename);
    }

    public function updateQty()
    {
        $this->validate([
            'edited_qty' => 'required|numeric'
        ]);

        DB::table('comparator')
            ->where('part_number', $this->part_number)
            ->update([
                'qty' => $this->edited_qty
            ]);

        $this->dispatch('qty-saved');

        $this->reset('part_number', 'qty', 'edited_qty');
    }

    public function edit($part_number)
    {
        $this->part_number = $part_number;

        $item = DB::table('comparator')
            ->where('part_number', $part_number)
            ->first();

        $this->qty = $item->qty;
    }

    public function increment($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->increment('qty', 1);
    }

    public function decrement($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->decrement('qty', 1);
    }

    public function destroy($part_number)
    {
        DB::table('comparator')
            ->where('part_number', $part_number)
            ->delete();

        $this->dispatch('qty-saved');

        session()->flash('success', "Data berhasil dihapus.");
    }

    public function render()
    {
        // Ambil data dari database 'mysql' (default) dan urutkan berdasarkan 'created_at'
        $comparatorItems = DB::connection('mysql')
            ->table('comparator')
            ->orderBy('created_at', 'desc')  // Menambahkan urutan berdasarkan 'created_at'
            ->get();

        // Ambil daftar part_number dari comparatorItems
        $partNumbers = $comparatorItems->pluck('part_number')->unique();

        // Ambil data hanya untuk part_no yang ada di $partNumbers
        $mstParts = DB::connection('kcpinformation')
            ->table('mst_part')
            ->whereIn('part_no', $partNumbers)
            ->select('part_no', 'nm_part')
            ->get()
            ->mapWithKeys(function ($part) {
                return [(string) $part->part_no => $part];
            });

        // Gabungkan data secara manual dan tambahkan 'nm_part' ke setiap item
        $items = $comparatorItems->map(function ($item) use ($mstParts) {
            // Pastikan part_number di-cast ke string untuk konsistensi pencocokan
            $partNumber = (string) $item->part_number;

            // Cari part di mstParts berdasarkan part_number
            $nmPart = $mstParts->get($partNumber)->nm_part ?? 'PART NUMBER TIDAK DIKENALI';

            // Mengubah objek stdClass menjadi array dan menambahkan 'nm_part'
            return (object) array_merge((array) $item, ['nm_part' => $nmPart]);
        });

        $this->items = $items;

        return view('livewire.comparator.index-comparator');
    }
}
