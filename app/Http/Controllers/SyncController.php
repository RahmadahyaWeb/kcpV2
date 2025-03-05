<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function sync_limit_kredit($kd_outlet)
    {
        $result = DB::connection('kcpinformation')->table(DB::raw('(
            select kd_outlet, nm_outlet, nominal_plafond_upload, nominal_plafond
            from kcpinformation.trns_plafond
        ) as plafond'))
            ->select(
                'plafond.kd_outlet',
                'plafond.nm_outlet',
                'plafond.nominal_plafond_upload',
                'plafond.nominal_plafond',
                DB::raw('IFNULL(SUM(hutang.hutang), 0) + IFNULL(hutang_bg.nominal_bg, 0) AS hutang')
            )
            ->leftJoin(DB::raw('(
                select a.noinv, a.kd_outlet, a.nm_outlet,
                       (a.amount_total - IFNULL(b.nominal, 0)) as hutang
                from kcpinformation.trns_inv_header a
                left join (
                    select noinv, kd_outlet, sum(nominal) as nominal
                    from kcpinformation.trns_pembayaran_piutang
                    where status = "C"
                    group by noinv
                ) b on (a.noinv = b.noinv)
                left join (
                    select x.noinv, sum(y.nominal_total) as nominal_retur
                    from kcpinformation.trns_retur_header x
                    join kcpinformation.trns_retur_details y on (x.noretur = y.noretur)
                    where x.flag_approve1 = "Y"
                    group by x.noinv
                ) c on (a.noinv = c.noinv)
                where a.flag_pembayaran_lunas = "N" and a.flag_batal = "N"
            ) as hutang'), 'plafond.kd_outlet', '=', 'hutang.kd_outlet')
            ->leftJoin(DB::raw('(
                select a.kd_outlet, a.nm_outlet, sum(a.nominal_potong) as nominal_bg
                from kcpinformation.trns_pembayaran_piutang_header a
                left join kcpinformation.trns_bg_header b on (
                    a.no_bg = b.from_bg
                    and b.flag_batal = "N"
                )
                where a.pembayaran_via = "BG"
                and IFNULL(b.from_bg, "-") = "-"
                AND a.flag_batal = "N"
                group by a.kd_outlet
            ) as hutang_bg'), 'plafond.kd_outlet', '=', 'hutang_bg.kd_outlet')
            ->where('plafond.kd_outlet', '=', $kd_outlet)
            ->groupBy('plafond.kd_outlet')
            ->get();

        dd($result);
    }

    public function sync_intransit()
    {
        // Koneksi ke database
        $kcpapplication = DB::connection('mysql');
        $kcpinformation = DB::connection('kcpinformation');

        // Ambil data dari tabel invoice_aop_header
        $invoice_aop = $kcpapplication->table('invoice_aop_header')
            ->orderBy('created_at', 'desc')
            ->select('SPB', 'invoiceAop', 'customerTo')
            ->where('invoiceAop', '4009708980')
            ->get();

        // Ambil data dari tabel intransit_header
        $intransit_aop = $kcpinformation->table('intransit_header')
            ->orderBy('crea_date', 'desc')
            ->pluck('no_sp_aop');

        // Filter invoice yang belum masuk ke intransit
        $not_intransit = $invoice_aop->filter(function ($invoice) use ($intransit_aop) {
            // Membuat no_sp_aop sesuai kondisi
            $no_sp_aop = (strpos($invoice->SPB, 'DN') !== false)
                ? $invoice->SPB
                : $invoice->SPB . $invoice->customerTo; // Gabungkan SPB dan customerTo jika SPB tidak mengandung 'DN'

            $invoice->no_sp_aop = $no_sp_aop;

            return !$intransit_aop->contains($no_sp_aop); // Cocokkan dengan no_sp_aop yang ada di intransit
        });

        if ($not_intransit->isEmpty()) {
            Log::info("Tidak ada invoice pembelian.");
            throw new \Exception("Tidak ada invoice pembelian yang perlu di sync.");
            return;
        }

        foreach ($not_intransit as $value) {
            try {

                $kcpinformation->beginTransaction();

                $no_sp_aop = $value->no_sp_aop;
                $kd_gudang_aop = $value->customerTo;

                $invoice_aop_details = $kcpapplication->table('invoice_aop_detail')
                    ->where('SPB', $value->SPB)
                    ->where('invoiceAop', '4009708980')
                    ->get();

                // INTRANSIT HEADER
                $kcpinformation->table('intransit_header')
                    ->insert([
                        'no_sp_aop' => $no_sp_aop,
                        'kd_gudang_aop' => $kd_gudang_aop,
                        'tgl_packingsheet' => now(),
                        'status' => 'I',
                        'ket_status' => 'INTRANSIT',
                        'crea_date' => now(),
                        'crea_by' => 'SYSTEM'
                    ]);

                foreach ($invoice_aop_details as $item) {
                    // INTRANSIT DETAILS
                    $kcpinformation->table('intransit_details')
                        ->insert([
                            'no_sp_aop' => $no_sp_aop,
                            'kd_gudang_aop' => $kd_gudang_aop,
                            'part_no' => $item->materialNumber,
                            'qty' => $item->qty,
                            'status' => 'I',
                            'crea_date' => now(),
                            'crea_by' => 'SYSTEM'
                        ]);
                }

                $kcpinformation->commit();

                Log::info("Berhasil convert invoice $no_sp_aop");
            } catch (\Exception $e) {
                $kcpinformation->rollBack();

                Log::info("Gagal convert invoice $no_sp_aop menjadi intransit: " . $e->getMessage());

                continue;
            }
        }

        // Hasil: list invoice yang belum ada di intransit
        return $not_intransit;
    }
}
