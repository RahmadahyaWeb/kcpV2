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
        $katalog    = $request->get('katalog');
        $kd_toko    = $request->kode_toko;

        // Validasi Lokasi
        if (!$latitude || !$longitude) {
            return $this->redirectBackWithError('Lokasi tidak ditemukan!');
        }

        // Validasi Check-In dan Check-Out atau Katalog
        try {
            $type = $this->determineCheckType($kd_toko, $user, $katalog);
        } catch (\Exception $e) {
            return $this->redirectBackWithError($e->getMessage());
        }

        // Validasi Toko Aktif
        $toko_aktif = $this->validateActiveStore($kd_toko);

        if (!$toko_aktif) {
            return $this->redirectBackWithError("Toko dengan kode $kd_toko tidak aktif!");
        }

        $provinsiToko = DB::table('mst_expedition')
            ->where('kd_expedition', $kd_toko)
            ->value('kode_prp');

        // Penyesuaian Waktu
        $waktu_kunjungan = $this->adjustVisitTime($provinsiToko);

        // Proses Penyimpanan Data
        return $this->processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan, $katalog);
    }

    private function redirectBackWithError($message)
    {
        return redirect()->route('dks.index')->with('error', $message);
    }

    private function determineCheckType($kd_toko, $user, $katalog)
    {
        $check = DB::table('trans_dkd')
            ->where('kd_toko', $kd_toko)
            ->where('user_sales', $user)
            ->where('type', '!=', 'katalog')
            ->whereDate('tgl_kunjungan', now()->toDateString())
            ->count();

        if ($check == 0) {
            if ($katalog == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda belum melakukan check in!');
            }
            return 'in';
        }

        if ($check == 2) {
            if ($katalog == 'Y') {
                throw new \Exception('Tidak dapat scan katalog. Anda sudah melakukan check out!');
            }
            throw new \Exception('Anda sudah melakukan check out!');
        }

        if ($check == 1) {
            if ($katalog == 'Y') {
                return 'katalog';
            }

            return 'out';
        }
    }

    private function validateActiveStore($kd_toko)
    {
        return DB::table('master_toko')
            ->where('kd_toko', $kd_toko)
            ->where('status', 'active')
            ->first();
    }

    private function adjustVisitTime($kd_provinsi)
    {
        return ($kd_provinsi == '6200') ? now()->subHour() : now();
    }

    private function processStore($type, $kd_toko, $user, $latitude, $longitude, $keterangan, $waktu_kunjungan, $katalog)
    {
        DB::beginTransaction();
        try {
            // Data yang akan disimpan
            $data = [
                'tgl_kunjungan'     => now(),
                'user_sales'        => $user,
                'kd_toko'           => $kd_toko,
                'waktu_kunjungan'   => $waktu_kunjungan,
                'type'              => $type,
                'latitude'          => $latitude,
                'longitude'         => $longitude,
                'keterangan'        => $keterangan,
                'created_by'        => $user,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            // Simpan data
            DB::table('trans_dkd')->insert($data);
            DB::commit();

            $action = $katalog == 'Y' ? 'scan katalog' : "check $type";
            return redirect()->route('dks.index')->with('success', "Berhasil melakukan $action");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->redirectBackWithError($e->getMessage());
        }
    }
}
