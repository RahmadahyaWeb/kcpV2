<?php

namespace App\Livewire\Master;

use App\Models\MasterToko;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class IndexMasterToko extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target = 'sync_lokasi,kode_toko,nama_toko,status';

    public $kode_toko;
    public $nama_toko;
    public $status;

    public function sync_lokasi()
    {
        // Mulai transaksi di koneksi 'kcpinformation'
        DB::connection('kcpinformation')->beginTransaction();

        try {
            // Ambil data dari tabel mst_outlet di database kcpinformation
            $master_toko_kcpinformation = DB::connection('kcpinformation')
                ->table('mst_outlet')
                ->get();

            // Ambil data dari tabel master_toko di database kcpapplication
            $master_toko_kcpapplication = DB::connection('kcpapplication')
                ->table('master_toko')
                ->get();

            // Looping untuk menyinkronkan latitude dan longitude
            foreach ($master_toko_kcpinformation as $key => $value) {
                // Ambil kode toko dari mst_outlet
                $kode_toko = $value->kd_outlet;

                // Cari data yang sesuai di master_toko berdasarkan kode_toko
                $toko_kcpapplication = $master_toko_kcpapplication->firstWhere('kd_toko', $kode_toko);

                // Pastikan data ditemukan dan memiliki nilai latitude dan longitude
                if ($toko_kcpapplication) {
                    $latitude = $toko_kcpapplication->latitude;
                    $longitude = $toko_kcpapplication->longitude;

                    // Update mst_outlet dengan latitude dan longitude
                    DB::connection('kcpinformation')
                        ->table('mst_outlet')
                        ->where('kd_outlet', $kode_toko)
                        ->update([
                            'latitude' => $latitude,
                            'longitude' => $longitude
                        ]);
                }
            }

            // Commit transaksi jika semua berhasil
            DB::connection('kcpinformation')->commit();

            session()->flash('success', 'Sync lokasi berhasil!');
        } catch (\Exception $e) {
            // Rollback transaksi jika ada error
            DB::connection('kcpinformation')->rollBack();

            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $items = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->select([
                'mst_outlet.*',
                'mst_provinsi.provinsi',
                'mst_area.nm_area'
            ])
            ->leftJoin('mst_provinsi', 'mst_provinsi.kode_prp', '=', 'mst_outlet.kode_prp')
            ->leftJoin('mst_area', 'mst_area.kode_kab', '=', 'mst_outlet.kode_kab')
            ->where('mst_outlet.kd_outlet', 'like', '%' . $this->kode_toko . '%')
            ->where('mst_outlet.nm_outlet', 'like', '%' . $this->nama_toko . '%')
            ->where('mst_outlet.status', 'like', '%' . $this->status . '%')
            ->orderBy('kd_outlet', 'asc')
            ->paginate();

        return view('livewire.master.index-master-toko', compact(
            'items'
        ));
    }
}
