<?php

namespace App\Livewire\Dkd;

use App\Exports\RekapDaftarKehadiranDriverExport;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class RekapDaftarKehadiranDriver extends Component
{
    public $target = 'export';
    public $fromDate;
    public $toDate;
    public $user_sales = 'all';
    public $laporan;

    public function export()
    {
        $this->validate([
            'fromDate'      => 'required',
            'toDate'        => 'required',
            'laporan'       => 'required'
        ]);

        $usersQuery = User::role('salesman')->where('status', 'active');

        if ($this->user_sales != 'all') {
            $usersQuery->where('username', '=', $this->user_sales);
        }

        $users = $usersQuery->get();

        if ($this->laporan == 'rekap_punishment') {
            return $this->exportRekapPunishment($users);
        }
    }

    public function exportRekapPunishment($users)
    {
        $dates = $this->getDateRange();

        $usersData = [];

        $usersData = [];

        $tokoAbsen = config('absen_toko.absen_toko');
        $cekInSelanjutnyaCache = [];

        foreach ($users as $user) {
            $userData = collect();

            // Ambil semua data untuk tanggal yang diperlukan dalam satu query
            $dailyData = DB::table('trans_dkd AS in_data')
                ->select(
                    'in_data.user_sales',
                    'in_data.waktu_kunjungan AS waktu_cek_in',
                    DB::raw('COALESCE(out_data.waktu_kunjungan, in_data.waktu_kunjungan) AS waktu_cek_out'),
                    'in_data.tgl_kunjungan',
                    'out_data.keterangan',
                    'in_data.kd_toko',
                    'katalog_data.katalog_at',
                    'users.name',
                    DB::raw('
                        CASE
                            WHEN out_data.waktu_kunjungan IS NOT NULL
                            THEN TIMESTAMPDIFF(MINUTE, in_data.waktu_kunjungan, out_data.waktu_kunjungan)
                            ELSE NULL
                        END AS lama_kunjungan'),
                    DB::raw('0 AS durasi_perjalanan'),
                    'in_data.id',
                )
                ->leftJoin('trans_dkd AS out_data', function ($join) {
                    $join->on('in_data.user_sales', '=', 'out_data.user_sales')
                        ->whereColumn('in_data.kd_toko', 'out_data.kd_toko')
                        ->whereColumn('in_data.tgl_kunjungan', 'out_data.tgl_kunjungan')
                        ->where('out_data.type', '=', 'out');
                })
                ->leftJoin('users', 'users.username', '=', 'in_data.user_sales')
                ->leftJoin('trans_dkd AS katalog_data', function ($join) {
                    $join->on('in_data.user_sales', '=', 'katalog_data.user_sales')
                        ->whereColumn('in_data.kd_toko', 'katalog_data.kd_toko')
                        ->whereColumn('in_data.tgl_kunjungan', 'katalog_data.tgl_kunjungan')
                        ->where('katalog_data.type', '=', 'katalog');
                })
                ->whereIn('in_data.tgl_kunjungan', $dates)
                ->where('in_data.user_sales', $user->username)
                ->where('in_data.type', 'in')
                ->orderBy('in_data.created_at', 'asc')
                ->get()
                ->groupBy('tgl_kunjungan');

            // Jika tidak ada data untuk hari tersebut, tambahkan data kosong
            foreach ($dates as $date) {
                if (!isset($dailyData[$date])) {
                    $userData->push((object)[
                        'user_sales' => $user->username,
                        'nama_toko' => null,
                        'waktu_cek_in' => null,
                        'waktu_cek_out' => null,
                        'tgl_kunjungan' => $date,
                        'keterangan' => null,
                        'kd_toko' => null,
                        'katalog_at' => null,
                        'lama_kunjungan' => null,
                        'durasi_perjalanan' => 0,
                        'name' => $user->name
                    ]);
                } else {
                    $dailyData[$date]->each(function ($item) use ($userData, $cekInSelanjutnyaCache, $tokoAbsen) {
                        // Periksa apakah cekInSelanjutnya sudah ada dalam cache
                        if (!isset($cekInSelanjutnyaCache[$item->user_sales][$item->tgl_kunjungan])) {
                            $cekInSelanjutnya = DB::table('trans_dkd')
                                ->select(['*'])
                                ->where('user_sales', $item->user_sales)
                                ->whereDate('tgl_kunjungan', $item->tgl_kunjungan)
                                ->where('type', 'in')
                                ->where('waktu_kunjungan', '>', $item->waktu_cek_in)
                                ->first();

                            // Cache hasilnya
                            $cekInSelanjutnyaCache[$item->user_sales][$item->tgl_kunjungan] = $cekInSelanjutnya;
                        } else {
                            $cekInSelanjutnya = $cekInSelanjutnyaCache[$item->user_sales][$item->tgl_kunjungan];
                        }

                        // Proses durasi perjalanan
                        if ($cekInSelanjutnya) {
                            $cek_out = Carbon::parse($item->waktu_cek_out);
                            $cek_in  = Carbon::parse($cekInSelanjutnya->waktu_kunjungan);
                            $selisih = $cek_out->diff($cek_in);
                            $lama_perjalanan = sprintf('%02d:%02d:%02d', $selisih->h, $selisih->i, $selisih->s);
                            $item->durasi_perjalanan = $lama_perjalanan;
                        }

                        // Cek jika toko termasuk dalam absen_toko
                        if (in_array($item->kd_toko, $tokoAbsen)) {
                            $item->durasi_perjalanan = 0;
                        }

                        // Tambahkan item ke userData
                        $userData->push((object)$item);
                    });
                }
            }

            // Simpan data per user
            $usersData[$user->username] = $userData;
        }

        $fromDateFormatted = \Carbon\Carbon::parse($this->fromDate)->format('Ymd');
        $toDateFormatted = \Carbon\Carbon::parse($this->toDate)->format('Ymd');

        $filename = "rekap-daftar-kehadiran-driver_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new RekapDaftarKehadiranDriverExport($this->fromDate, $this->toDate, $usersData), $filename);
    }

    private function getDateRange()
    {
        $dateBeginLoop = new DateTime($this->fromDate);
        $dateEndLoop = new DateTime($this->toDate);
        $dates = [];

        while ($dateBeginLoop <= $dateEndLoop) {
            $dates[] = $dateBeginLoop->format('Y-m-d');
            $dateBeginLoop->modify('+1 day');
        }

        return $dates;
    }

    public function render()
    {
        $users = User::role('driver')
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.dkd.rekap-daftar-kehadiran-driver', compact(
            'users'
        ));
    }
}
