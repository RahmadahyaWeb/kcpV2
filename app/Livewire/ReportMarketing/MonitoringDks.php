<?php

namespace App\Livewire\ReportMarketing;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class MonitoringDks extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $target = 'toDate, user_sales, kd_toko';

    public $fromDate;
    public $toDate;
    public $user_sales;
    public $kd_toko;

    public function render()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $items = DB::table('trans_dks AS in_data')
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
            ->leftJoin('trans_dks AS out_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'out_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'out_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'out_data.tgl_kunjungan')
                    ->where('out_data.type', '=', 'out');
            })
            ->leftJoin('trans_dks AS katalog_data', function ($join) {
                $join->on('in_data.user_sales', '=', 'katalog_data.user_sales')
                    ->whereColumn('in_data.kd_toko', 'katalog_data.kd_toko')
                    ->whereColumn('in_data.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                    ->where('katalog_data.type', '=', 'katalog');
            })
            ->where('in_data.type', 'in')
            ->when($this->fromDate && $this->toDate, function ($query) {
                return $query->whereBetween('in_data.tgl_kunjungan', [$this->fromDate, $this->toDate]);
            })
            ->when($this->kd_toko, function ($query) {
                return $query->where('in_data.kd_toko', $this->kd_toko);
            })
            ->when($this->user_sales, function ($query) {
                return $query->where('in_data.user_sales', $this->user_sales);
            })
            ->whereDate('in_data.tgl_kunjungan', '>=', $startOfMonth)
            ->orderBy('in_data.created_at', 'desc')
            ->get();

        // Ambil data dari database lain
        $master_toko_kcpinformation = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('status', 'Y')
            ->get();

        // Convert $master_toko_kcpinformation to an associative array indexed by kd_toko
        $masterTokoIndexed = $master_toko_kcpinformation->keyBy('kd_outlet');

        // Iterate over $items and merge data from $masterTokoIndexed based on kd_toko
        $mergedItems = $items->map(function ($item) use ($masterTokoIndexed) {
            // Find the corresponding toko data based on kd_toko
            $tokoData = $masterTokoIndexed->get($item->kd_toko);

            // If a match is found, merge the data
            if ($tokoData) {
                // You can merge fields here, for example adding the 'status' field from master_toko_kcpinformation
                $item->nama_toko = $tokoData->nm_outlet; // or any other field you want to merge
            } else {
                $item->nama_toko = '';
            }

            return $item;
        });

        // Lakukan paginasi setelah data digabungkan
        $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Ambil halaman saat ini
        $perPage = 15; // Tentukan jumlah item per halaman
        $currentItems = $mergedItems->slice(($currentPage - 1) * $perPage, $perPage)->values(); // Ambil item yang sesuai dengan halaman

        // Membuat paginator baru
        $paginatedItems = new LengthAwarePaginator(
            $currentItems, // Data yang dipaginate
            $mergedItems->count(), // Total item
            $perPage, // Jumlah item per halaman
            $currentPage, // Halaman saat ini
            ['path' => LengthAwarePaginator::resolveCurrentPath()] // URL path untuk pagination
        );

        $items = $paginatedItems;

        $absen_toko = config('absen_toko.absen_toko');

        $sales = User::role('salesman')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.report-marketing.monitoring-dks', compact(
            'items',
            'absen_toko',
            'master_toko_kcpinformation',
            'sales'
        ));
    }
}
