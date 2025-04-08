<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DkdController extends Controller
{
    public function store(Request $request)
    {
        // Data Input User
        $latitude   = $request->latitude;
        $longitude  = $request->longitude;
        $keterangan = strtolower($request->keterangan);
        $user       = Auth::user()->username;
        $kd_toko    = $request->kode_toko;

        // Validasi Lokasi
        if (!$latitude || !$longitude) {
            return $this->redirectBackWithError('Lokasi tidak ditemukan!');
        }

        // Validasi Check-In dan Check-Out
        try {
            $type = $this->determineCheckType($kd_toko, $user);
        } catch (\Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        $provinsiToko = DB::table('mst_expedition')
            ->where('kd_expedition', $kd_toko)
            ->value('kd_prp');

        // Penyesuaian Waktu
        $waktu_kunjungan = $this->adjustVisitTime($provinsiToko);

        // Proses Penyimpanan Data
        return $this->processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan);
    }

    private function redirectBackWithError($message)
    {
        return redirect()->route('daftar-kehadiran-driver.index')->with('error', $message);
    }

    public static function determineCheckType($kd_toko, $user)
    {
        // Mengecek apakah ada check-in yang belum diikuti dengan check-out pada hari yang sama
        $lastCheckIn = DB::table('trans_dkd')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->where('type', 'in') // Hanya mencari 'check-in'
            ->orderByDesc('created_at') // Mengambil check-in terakhir
            ->first();

        if (!$lastCheckIn) {
            // Jika tidak ada check-in sebelumnya pada hari ini, maka absensi pertama adalah 'check-in'
            return 'in';
        }

        // Mengecek apakah check-in terakhir sudah ada check-out untuk reference yang sama
        $lastCheckOut = DB::table('trans_dkd')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->where('reference', $lastCheckIn->id) // Menggunakan reference yang sama dengan check-in terakhir
            ->where('type', 'out') // Cek apakah ada 'check-out' untuk reference yang sama
            ->exists();

        if ($lastCheckOut) {
            // Jika sudah ada check-out, maka absensi berikutnya adalah check-in
            return 'in';
        }

        // Jika belum ada check-out, maka absensi berikutnya adalah check-out
        return 'out';
    }

    private function adjustVisitTime($kd_provinsi)
    {
        return (strval($kd_provinsi) == '6200') ? now()->subHour() : now();
    }

    private function processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan)
    {
        DB::beginTransaction();
        try {
            // Jika absensi 'check-in', kita harus membuat reference baru
            $reference = null;
            if ($type == 'in') {
                $reference = DB::table('trans_dkd')->insertGetId([
                    'kd_toko'          => $kd_toko,
                    'user_sales'       => $user,
                    'tgl_kunjungan'    => now(),
                    'type'             => 'in',
                    'latitude'         => $latitude,
                    'longitude'        => $longitude,
                    'keterangan'       => $keterangan,
                    'created_by'       => $user,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                    'waktu_kunjungan'  => $waktu_kunjungan
                ]);
            }

            // Jika absensi 'check-out', kita akan mengambil reference dari check-in terakhir yang belum diikuti check-out
            if ($type == 'out') {
                $lastCheckIn = DB::table('trans_dkd')
                    ->where('kd_toko', $kd_toko)
                    ->where('user_sales', $user)
                    ->whereDate('tgl_kunjungan', now()->toDateString())
                    ->where('type', 'in') // Mengambil check-in terakhir
                    ->orderByDesc('created_at')
                    ->first();

                // Menggunakan reference dari check-in terakhir untuk check-out
                $reference = $lastCheckIn->id;

                // Menyimpan check-out
                DB::table('trans_dkd')->insert([
                    'kd_toko'          => $kd_toko,
                    'user_sales'       => $user,
                    'tgl_kunjungan'    => now(),
                    'type'             => 'out',
                    'latitude'         => $latitude,
                    'longitude'        => $longitude,
                    'keterangan'       => $keterangan,
                    'reference'        => $reference,  // Menggunakan reference dari check-in
                    'created_by'       => $user,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                    'waktu_kunjungan'  => $waktu_kunjungan
                ]);
            }

            DB::commit();

            // Aksi untuk menampilkan pesan sukses
            $action = "check $type";
            return redirect()->route('daftar-kehadiran-driver.index')->with('success', "Berhasil melakukan $action");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->redirectBackWithError($e->getMessage());
        }
    }
}
