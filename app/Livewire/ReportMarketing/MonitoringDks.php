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

        // Convert master_toko_kcpinformation menjadi array keyBy berdasarkan kd_toko
        $masterTokoArray = $master_toko_kcpinformation->keyBy('kd_outlet')->toArray();

        // Misalkan $searchCriteria adalah parameter pencarian yang bisa diubah sesuai kebutuhan
        $searchCriteria = [
            'kd_toko' => $this->kd_toko,  // Nilai ini bisa diambil dari input pencarian atau form
            // Anda bisa menambahkan kriteria lain jika perlu, seperti nama toko, kota, dll
        ];

        $mergedItems = $items->filter(function ($item) use ($masterTokoArray, $searchCriteria) {
            // Cek apakah ada data yang cocok berdasarkan kd_toko dan sesuai dengan $searchCriteria
            $isMatch = true; // Variabel untuk menandai apakah item cocok dengan kriteria pencarian

            // Jika ada pencarian berdasarkan kd_toko, kita filter
            if (isset($searchCriteria['kd_toko']) && $item->kd_toko != $searchCriteria['kd_toko']) {
                $isMatch = false; // Item tidak cocok dengan pencarian
            }

            // Jika item cocok dengan pencarian, kita lanjutkan untuk menggabungkan data tambahan
            if ($isMatch && isset($masterTokoArray[$item->kd_toko])) {
                // Gabungkan data tambahan dari $masterTokoArray ke dalam $item
                $item->nama_toko = $masterTokoArray[$item->kd_toko]->nm_outlet; // Misalnya menambahkan nama toko
                // Anda bisa menambahkan field lain sesuai kebutuhan
            }

            return $isMatch; // Mengembalikan apakah item cocok dengan pencarian
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
