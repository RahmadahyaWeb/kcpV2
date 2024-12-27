<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

class Dashboard extends Component
{
    public $data;

    public function fetch_data_for_graph()
    {
        $jualQuery = DB::table('kcpinformation.trns_inv_header as a')
            ->selectRaw("SUBSTR(a.crea_date, 1, 7) as periode, SUM(b.nominal_total) as jml")
            ->join('kcpinformation.trns_inv_details as b', 'a.noinv', '=', 'b.noinv')
            ->join('kcpinformation.mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->whereRaw("SUBSTR(a.crea_date, 1, 4) = '2024'")
            ->where('a.flag_batal', 'N')
            ->where('c.supplier', 'ASTRA OTOPART')
            ->groupByRaw("SUBSTR(a.crea_date, 1, 7)");

        $returQuery = DB::table('kcpinformation.trns_retur_header as a')
            ->selectRaw("SUBSTR(a.flag_nota_date, 1, 7) as periode, SUM(b.nominal_total) as retur")
            ->join('kcpinformation.trns_retur_details as b', 'a.noretur', '=', 'b.noretur')
            ->join('kcpinformation.mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->whereRaw("SUBSTR(a.flag_nota_date, 1, 4) = '2024'")
            ->where('a.flag_batal', 'N')
            ->where('c.supplier', 'ASTRA OTOPART')
            ->groupByRaw("SUBSTR(a.flag_nota_date, 1, 7)");

        $result_1 = DB::table(DB::raw("({$jualQuery->toSql()}) as jual"))
            ->selectRaw("(jual.jml - IFNULL(retur.retur, 0)) as jml")
            ->mergeBindings($jualQuery)
            ->leftJoinSub($returQuery, 'retur', 'jual.periode', '=', 'retur.periode')
            ->orderBy('jual.periode')
            ->get();

        $result_2 = DB::table('kcpinformation.mst_target_produk')
            ->selectRaw("CONCAT(periode, '-', '01') as periode, SUM(jan) as jmlTarget")
            ->where('periode', date('Y'))
            ->whereNotIn('kd_area', [''])
            ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
            ->groupBy('periode')
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '02') as periode, SUM(feb) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '03') as periode, SUM(mar) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '04') as periode, SUM(apr) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '05') as periode, SUM(mei) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '06') as periode, SUM(jun) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '07') as periode, SUM(jul) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '08') as periode, SUM(agt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '09') as periode, SUM(spt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '10') as periode, SUM(okt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '11') as periode, SUM(nop) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                DB::table('kcpinformation.mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '12') as periode, SUM(des) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->orderBy('periode')
            ->get();

        $arrPenjualan = [];
        $arrTarget = [];

        foreach ($result_1 as $vCmdPenjualan) {
            $arrPenjualan[] = floatval($vCmdPenjualan->jml);
        }

        foreach ($result_2 as $vCmdTarget) {
            $arrTarget[] = floatval($vCmdTarget->jmlTarget);
        }

        return [
            'arrPenjualan'  => $arrPenjualan,
            'arrTarget'     => $arrTarget,
        ];
    }

    public function render()
    {
        $data = $this->fetch_data_for_graph();

        $this->data = $data;

        return view('livewire.dashboard', compact('data'));
    }
}
