<?php

namespace App\Livewire\Dkd;

use App\Http\Controllers\DkdController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SubmitDaftarKehadiranDriver extends Component
{
    public $kode_toko;
    public $katalog = 'N';

    public function mount($kode_toko, Request $request)
    {
        $this->kode_toko = $kode_toko;
    }

    public function render()
    {
        if (strpos($this->kode_toko, 'E_') !== false) {
            $toko = DB::connection('mysql')
                ->table('mst_expedition')
                ->select(['kd_expedition as kd_outlet', 'nama_expedition as nm_outlet', 'latitude', 'longitude'])
                ->where('kd_expedition', $this->kode_toko)
                ->first();
        } else {
            $toko = DB::connection('kcpinformation')
                ->table('mst_outlet')
                ->select(['kd_outlet', 'nm_outlet', 'latitude', 'longitude'])
                ->where('kd_outlet', $this->kode_toko)
                ->first();
        }

        if ($toko == null) {
            session()->flash('error', 'Kode toko tidak ditemukan.');
            abort(404);
        }

        $check = DkdController::determineCheckType($this->kode_toko, Auth::user()->username);

        return view('livewire.dkd.submit-daftar-kehadiran-driver', compact(
            'toko',
            'check'
        ));
    }
}
