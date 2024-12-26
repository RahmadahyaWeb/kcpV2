<?php

namespace App\Livewire\Dks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

class Submit extends Component
{
    public $kode_toko;
    public $katalog = 'N';

    public function mount($kode_toko, Request $request)
    {
        $this->kode_toko = $kode_toko;

        if ($request->katalog) {
            $this->katalog = 'Y';
        }
    }

    public function render()
    {
        $toko = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->select(['kd_outlet', 'nm_outlet', 'latitude', 'longitude'])
            ->where('kd_outlet', $this->kode_toko)
            ->first();

        if ($toko == null) {
            session()->flash('error', 'Kode toko tidak ditemukan.');
            abort(404);
        }

        $check = DB::table('trans_dks')
            ->where('kd_toko', $this->kode_toko)
            ->where('user_sales', Auth::user()->username)
            ->where('type', 'in')
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->count();

        $katalog = $this->katalog;

        if ($katalog == 'Y') {
            $check = 'katalog';
        }

        return view('livewire.dks.submit', compact(
            'toko',
            'katalog',
            'check'
        ));
    }
}
