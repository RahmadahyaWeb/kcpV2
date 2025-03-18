<?php

namespace App\Livewire\Dkd;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IndexDaftarKehadiranDriver extends Component
{
    public function fetchDks()
    {
        $items = DB::table('trans_dkd AS in_data')
            ->select(
                'in_data.user_sales',
                'in_data.waktu_kunjungan AS waktu_cek_in',
                'out_data.waktu_kunjungan AS waktu_cek_out',
                'in_data.tgl_kunjungan',
                'out_data.keterangan',
                'in_data.kd_toko',
                'katalog_data.katalog_at',
                DB::raw('
                CASE
                    WHEN out_data.waktu_kunjungan IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, in_data.waktu_kunjungan, out_data.waktu_kunjungan)
                    ELSE NULL
                END AS lama_kunjungan')
            )
            ->leftJoin('trans_dkd AS out_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'out_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'out_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'out_data.tgl_kunjungan')
                    ->where('out_data.type', '=', 'out');
            })
            ->leftJoin('trans_dkd AS katalog_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'katalog_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'katalog_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                    ->where('katalog_data.type', '=', 'katalog');
            })
            ->where('in_data.type', 'in')
            ->where('in_data.user_sales', Auth::user()->username)
            ->whereDate('in_data.tgl_kunjungan', '=', now())
            ->orderBy('in_data.created_at', 'desc')
            ->get();

        // Ambil data dari database lain
        $master_toko_kcpinformation = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('status', 'Y')
            ->get();

        // Convert master_toko_kcpinformation menjadi array keyBy berdasarkan kd_toko
        $masterTokoArray = $master_toko_kcpinformation->keyBy('kd_outlet')->toArray();

        // Merge data dari $masterTokoArray ke dalam $items
        $mergedItems = $items->map(function ($item) use ($masterTokoArray) {
            // Gabungkan data tambahan dari $masterTokoArray ke dalam $item
            if (isset($masterTokoArray[$item->kd_toko])) {
                $item->nama_toko = $masterTokoArray[$item->kd_toko]->nm_outlet; // Menambahkan nama toko
                // Anda bisa menambahkan field lain sesuai kebutuhan
            }
            return $item;
        });

        return $mergedItems;
    }

    public function render()
    {
        $items = collect($this->fetchDks());

        $absen_toko = config('absen_toko.absen_toko');

        return view('livewire.dkd.index-daftar-kehadiran-driver', compact(
            'items',
            'absen_toko'
        ));
    }
}
