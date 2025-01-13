<?php

namespace App\Livewire\StoreRak;

use App\Exports\StoreRakExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class IndexStoreRak extends Component
{
    use WithPagination;

    public $part_number;
    public $kd_rak;
    public $from_date;
    public $to_date;

    public function save()
    {
        $this->validate([
            'part_number' => 'required',
            'kd_rak'      => 'required'
        ]);

        try {
            $kcpapplication = DB::connection('mysql');
            $kcpapplication->beginTransaction();

            $part_number = preg_replace('/\s+/', '', $this->part_number);
            $kd_rak = preg_replace('/\s+/', '', $this->kd_rak);

            // CARI NAMA PART
            $nama_part = DB::connection('kcpinformation')
                ->table('mst_part')
                ->where('part_no', $part_number)
                ->value('nm_part');

            if ($nama_part) {
                DB::table('trans_store_rak')
                    ->insert([
                        'part_number' => $part_number,
                        'nama_part'   => $nama_part,
                        'kd_rak'      => $kd_rak,
                        'user_id'     => Auth::user()->username,
                        'created_at'  => now()
                    ]);

                $kcpapplication->commit();

                $this->dispatch('saved');
                $this->reset();
            } else {
                throw new \Exception('Part number tidak ditemukan.');
            }
        } catch (\Exception $e) {
            $kcpapplication->rollBack();

            $this->dispatch('saved');
            $this->reset();
        }
    }

    public function update_status()
    {
        $update = DB::table('trans_store_rak')
            ->where('status', 'unfinished')
            ->update([
                'status' => 'finished'
            ]);

        if ($update > 0) {
            session()->flash('success', "Berhasil update status.");
        } else {
            session()->flash('error', "Tidak ada data yang diupdate.");
        }
    }

    public function export()
    {
        $from_date = Carbon::parse($this->from_date);
        $to_date = Carbon::parse($this->to_date);

        $filename = 'store_rak_result_' . $from_date . '_' . $to_date . '.xlsx';

        return Excel::download(new StoreRakExport($from_date, $to_date), $filename);
    }

    public function render()
    {
        $user = Auth::user();

        if ($user->hasRole('inventory')) {
            $status = 'finished';
        } else {
            $status = 'unfinished';
        }

        $items = DB::table('trans_store_rak')
            ->orderBy('created_at', 'desc')
            ->where('status', $status);

        $from_date = $this->from_date;
        $to_date = $this->to_date;

        if (!empty($from_date) && !empty($to_date)) {
            $items = $items->whereBetween('created_at', [$from_date, $to_date]);
        }

        $items = $items->paginate(50);

        return view('livewire.store-rak.index-store-rak', compact('items'));
    }
}
