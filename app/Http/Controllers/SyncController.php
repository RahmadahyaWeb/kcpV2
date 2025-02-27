<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
