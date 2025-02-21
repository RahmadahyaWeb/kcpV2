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

    public $target = "save, to_date";
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
            $kcpApplication = DB::connection('mysql');
            $kcpApplication->beginTransaction();

            $partNumber = trim($this->part_number);
            $kdRak = trim($this->kd_rak);

            // CARI NAMA PART
            $namaPart = DB::connection('kcpinformation')
                ->table('mst_part')
                ->where('part_no', $partNumber)
                ->value('nm_part');

            if (!$namaPart) {
                throw new \Exception('Part number tidak ditemukan.');
            }

            $kcpApplication->table('trans_store_rak')->insert([
                'part_number' => $partNumber,
                'nama_part'   => $namaPart,
                'kd_rak'      => $kdRak,
                'user_id'     => Auth::id(),
                'created_at'  => now()
            ]);

            $kcpApplication->commit();

            $this->reset('part_number', 'kd_rak');
            $this->dispatch('saved');
            $this->dispatch('success', ['message' => 'Berhasil scan part number dan kode rak.']);

        } catch (\Exception $e) {
            $kcpApplication->rollBack();

            $this->reset('part_number', 'kd_rak');
            $this->dispatch('saved');
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function update_status()
    {
        try {
            DB::beginTransaction();

            $update = DB::table('trans_store_rak')
                ->where('status', 'unfinished')
                ->update([
                    'status' => 'finished',
                    'updated_at' => now()
                ]);

            if ($update > 0) {
                DB::commit();

                $this->dispatch('success', ['message' => 'Berhasil update status.']);
            } else {
                throw new \Exception("Tidak ada data yang diupdate.");
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function export()
    {
        $from_date = Carbon::parse($this->from_date)->startOfDay();
        $to_date = Carbon::parse($this->to_date)->endOfDay();

        $filename = 'store_rak_result_' . $from_date . '_' . $to_date . '.xlsx';

        return Excel::download(new StoreRakExport($from_date, $to_date), $filename);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $deleted = DB::table('trans_store_rak')
                ->where('id', $id)
                ->delete();

            DB::commit();

            $this->dispatch('success', ['message' => 'Data berhasil dihapus.']);

            $this->dispatch('saved');
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('saved');

            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $user = Auth::user();
        $status = $user->hasRole('inventory') ? 'finished' : 'unfinished';

        $items = DB::table('trans_store_rak')
            ->join('users', 'users.id', '=', 'trans_store_rak.user_id')
            ->select([
                'trans_store_rak.id',
                'part_number',
                'nama_part',
                'kd_rak',
                'users.username',
                'trans_store_rak.created_at'
            ])
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('inventory')) {
            $items->where('trans_store_rak.status', $status);
        } else {
            $items->where('trans_store_rak.status', $status)->where('user_id', Auth::id());
        }

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->format('Y-m-d');
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->format('Y-m-d');

        $items = $items->when($this->from_date && $this->to_date, function ($query) use ($fromDateFormatted, $toDateFormatted) {
            return $query->whereBetween(DB::raw('DATE(trans_store_rak.created_at)'), [$fromDateFormatted, $toDateFormatted]);
        })->paginate();

        return view('livewire.store-rak.index-store-rak', compact('items'));
    }
}
