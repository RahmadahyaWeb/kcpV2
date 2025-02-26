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

    public $target = "save, status";
    public $part_number;
    public $kd_rak;
    public $from_date;
    public $to_date;
    public $status = 'N';

    public $label;

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

    public function create_label()
    {
        $this->validate([
            'label' => ['required']
        ]);

        $kcpApplication = DB::connection('mysql');

        $kcpApplication->table('store_rak_header')
            ->insert([
                'label'         => $this->label,
                'user_id'       => Auth::user()->username,
                'created_at'    => now()
            ]);

        $this->reset('label');

        $this->dispatch('success', ['message' => 'Berhasil buat label.']);
    }

    public function lihat_detail($header_id)
    {
        $this->redirectRoute('store-rak.detail', ['header_id' => $header_id]);
    }

    public function render()
    {
        $items = DB::table('store_rak_header')
            ->where('status', $this->status)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('livewire.store-rak.index-store-rak', compact('items'));
    }
}
