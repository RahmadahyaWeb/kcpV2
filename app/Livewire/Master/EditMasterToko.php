<?php

namespace App\Livewire\Master;

use App\Models\MasterToko;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditMasterToko extends Component
{
    public $nama_toko;
    public $kode_toko;
    public $latitude;
    public $longitude;
    public $frekuensi;

    public function mount($kode_toko)
    {
        $item = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('kd_outlet', $kode_toko)
            ->first();

        $this->kode_toko = $item->kd_outlet;
        $this->nama_toko = $item->nm_outlet;
        $this->latitude = $item->latitude;
        $this->longitude = $item->longitude;
        $this->frekuensi = $item->frekuensi;
    }

    public function save()
    {
        $kcpinformation = DB::connection('kcpinformation');

        try {
            $kcpinformation->beginTransaction();

            $kcpinformation->table('mst_outlet')
                ->where('kd_outlet', $this->kode_toko)
                ->update([
                    'latitude'      => $this->latitude,
                    'longitude'     => $this->longitude,
                    'frekuensi'     => $this->frekuensi
                ]);

            $kcpinformation->commit();

            session()->flash('success', 'Changes have been saved successfully!');

            $this->redirect(IndexMasterToko::class, true);
        } catch (\Exception $e) {
            $kcpinformation->rollBack();

            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.master.edit-master-toko');
    }
}
