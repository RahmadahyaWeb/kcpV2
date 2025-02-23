<?php

namespace App\Livewire\StoreRak;

use App\Exports\StoreRakExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class DetailStoreRak extends Component
{
    public $target = "save, to_date";
    public $part_number;
    public $kd_rak;
    public $from_date;
    public $to_date;

    public $header_id;
    public $label;
    public $status;

    public function mount($header_id)
    {
        $this->header_id = $header_id;
    }

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

            $kcpApplication->table('store_rak_details')->insert([
                'header_id'   => $this->header_id,
                'part_number' => $partNumber,
                'nama_part'   => $namaPart,
                'kd_rak'      => $kdRak,
                'user_id'     => Auth::user()->username,
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

            $update = DB::table('store_rak_header')
                ->where('id', $this->header_id)
                ->where('status', 'N')
                ->update([
                    'status' => 'Y',
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
        $filename = 'store_rak_result_' . $this->label . '.xlsx';

        return Excel::download(new StoreRakExport($this->label, $this->header_id), $filename);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $deleted = DB::table('store_rak_details')
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
        $items = DB::table('store_rak_details')
            ->where('header_id', $this->header_id)
            ->get();

        $header = DB::table('store_rak_header')
            ->where('id', $this->header_id)
            ->first();

        $this->label = $header->label;
        $this->status = $header->status;

        return view('livewire.store-rak.detail-store-rak', compact('items'));
    }
}
