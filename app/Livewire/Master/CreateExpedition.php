<?php

namespace App\Livewire\Master;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateExpedition extends Component
{
    public $kd_expedition, $nama_expedition, $latitude, $longitude;

    public function save()
    {
        $this->validate([
            'kd_expedition'     => ['required'],
            'nama_expedition'   => ['required'],
            'latitude'          => ['required'],
            'longitude'         => ['required'],
        ]);

        DB::table('mst_expedition')
            ->insert([
                'kd_expedition'     => $this->kd_expedition,
                'nama_expedition'   => $this->nama_expedition,
                'latitude'          => $this->latitude,
                'longitude'         => $this->longitude,
                'created_at'        => now(),
                'crea_by'           => Auth::user()->username
            ]);

        session()->flash('success', 'Changes have been saved successfully');

        $this->redirectRoute('expedition.index');
    }

    public function render()
    {
        return view('livewire.master.create-expedition');
    }
}
