<?php

namespace App\Livewire\Master;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditExpedition extends Component
{
    public $kd_expedition, $nama_expedition, $latitude, $longitude, $kd_prp;

    public function mount($kd_expedition)
    {
        $expedition = DB::table('mst_expedition')
            ->where('kd_expedition', $kd_expedition)
            ->first();

        if ($expedition == null) {
            session()->flash('error', 'Kode tidak ditemukan.');
            abort(404);
        }

        $this->kd_expedition = $expedition->kd_expedition;
        $this->nama_expedition = $expedition->nama_expedition;
        $this->latitude = $expedition->latitude;
        $this->longitude = $expedition->longitude;
        $this->kd_prp = $expedition->kd_prp;
    }

    public function save()
    {
        $this->validate([
            'nama_expedition'   => ['required'],
            'latitude'          => ['required'],
            'longitude'         => ['required'],
            'kd_prp'            => ['required']
        ]);

        DB::table('mst_expedition')
            ->where('kd_expedition', $this->kd_expedition)
            ->update([
                'nama_expedition'   => $this->nama_expedition,
                'latitude'          => $this->latitude,
                'longitude'         => $this->longitude,
                'kd_prp'            => $this->kd_prp,
                'updated_at'        => now(),
            ]);

        session()->flash('success', 'Changes have been saved successfully');

        $this->redirectRoute('expedition.index', [], true, true);
    }

    public function render()
    {
        return view('livewire.master.edit-expedition');
    }
}
