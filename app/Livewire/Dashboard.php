<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

class Dashboard extends Component
{
    public $data;
    public $performance;

    public function fetch_data_for_graph()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $jualQuery = $kcpinformation->table('trns_inv_header as a')
            ->selectRaw("SUBSTR(a.crea_date, 1, 7) as periode, SUM(b.nominal_total) as jml")
            ->join('trns_inv_details as b', 'a.noinv', '=', 'b.noinv')
            ->join('mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->whereRaw("SUBSTR(a.crea_date, 1, 4) = '2025'")
            ->where('a.flag_batal', 'N')
            ->where('c.supplier', 'ASTRA OTOPART')
            ->groupByRaw("SUBSTR(a.crea_date, 1, 7)");

        $returQuery = $kcpinformation->table('trns_retur_header as a')
            ->selectRaw("SUBSTR(a.flag_nota_date, 1, 7) as periode, SUM(b.nominal_total) as retur")
            ->join('trns_retur_details as b', 'a.noretur', '=', 'b.noretur')
            ->join('mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->whereRaw("SUBSTR(a.flag_nota_date, 1, 4) = '2025'")
            ->where('a.flag_batal', 'N')
            ->where('c.supplier', 'ASTRA OTOPART')
            ->groupByRaw("SUBSTR(a.flag_nota_date, 1, 7)");

        $result_1 = $kcpinformation->table(DB::raw("({$jualQuery->toSql()}) as jual"))
            ->selectRaw("(jual.jml - IFNULL(retur.retur, 0)) as jml")
            ->mergeBindings($jualQuery)
            ->leftJoinSub($returQuery, 'retur', 'jual.periode', '=', 'retur.periode')
            ->orderBy('jual.periode')
            ->get();

        $result_2 = $kcpinformation->table('mst_target_produk')
            ->selectRaw("CONCAT(periode, '-', '01') as periode, SUM(jan) as jmlTarget")
            ->where('periode', date('Y'))
            ->whereNotIn('kd_area', [''])
            ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
            ->groupBy('periode')
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '02') as periode, SUM(feb) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '03') as periode, SUM(mar) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '04') as periode, SUM(apr) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '05') as periode, SUM(mei) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '06') as periode, SUM(jun) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '07') as periode, SUM(jul) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '08') as periode, SUM(agt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '09') as periode, SUM(spt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '10') as periode, SUM(okt) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
                    ->selectRaw("CONCAT(periode, '-', '11') as periode, SUM(nop) as jmlTarget")
                    ->where('periode', date('Y'))
                    ->whereNotIn('kd_area', [''])
                    ->whereNotIn('produk_part', ['NON AOP AIR AKI', 'NON AOP PENTIL', 'NON AOP AIR COLANT'])
                    ->groupBy('periode')
            )
            ->union(
                $kcpinformation->table('mst_target_produk')
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

    public function fetch_monthly_target()
    {
        $date_month = date('Y-m');

        // Get total sales
        $sales = DB::connection('kcpinformation')->table('trns_inv_header as a')
            ->join('trns_inv_details as b', 'a.noinv', '=', 'b.noinv')
            ->join('mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->where('c.supplier', '=', 'ASTRA OTOPART')
            ->where('a.flag_batal', '=', 'N')
            ->whereRaw("DATE_FORMAT(a.crea_date, '%Y-%m') = ?", [$date_month])
            ->sum('b.nominal_total');

        // Get total returns
        $returns = DB::connection('kcpinformation')->table('trns_retur_header as a')
            ->join('trns_retur_details as b', 'a.noretur', '=', 'b.noretur')
            ->join('mst_part as c', 'b.part_no', '=', 'c.part_no')
            ->where('c.supplier', '=', 'ASTRA OTOPART')
            ->where('a.flag_batal', '=', 'N')
            ->whereRaw("DATE_FORMAT(a.flag_nota_date, '%Y-%m') = ?", [$date_month])
            ->sum('b.nominal_total');

        // Calculate final amount
        $finalAmount = $sales - ($returns ?? 0);

        $initial_target = $this->getMonthlyTargets();

        $month = substr(date("Y-m"), 5, 2);
        if ($month == '01') {
            $bulan = 0;
        } elseif ($month == '02') {
            $bulan = 1;
        } elseif ($month == '03') {
            $bulan = 2;
        } elseif ($month == '04') {
            $bulan = 3;
        } elseif ($month == '05') {
            $bulan = 4;
        } elseif ($month == '06') {
            $bulan = 5;
        } elseif ($month == '07') {
            $bulan = 6;
        } elseif ($month == '08') {
            $bulan = 7;
        } elseif ($month == '09') {
            $bulan = 8;
        } elseif ($month == '10') {
            $bulan = 9;
        } elseif ($month == '11') {
            $bulan = 10;
        } elseif ($month == '12') {
            $bulan = 11;
        }

        $target = $initial_target[$bulan]->jmlTarget;

        if ($target == 0) {
            $performance = array(0);
        } else {
            $performance = round(($finalAmount / $target) * 100, 0);
        }

        return intval($performance);
    }

    public function getMonthlyTargets()
    {
        $months = [
            ['column' => 'jan', 'month' => '01'],
            ['column' => 'feb', 'month' => '02'],
            ['column' => 'mar', 'month' => '03'],
            ['column' => 'apr', 'month' => '04'],
            ['column' => 'mei', 'month' => '05'],
            ['column' => 'jun', 'month' => '06'],
            ['column' => 'jul', 'month' => '07'],
            ['column' => 'agt', 'month' => '08'],
            ['column' => 'spt', 'month' => '09'],
            ['column' => 'okt', 'month' => '10'],
            ['column' => 'nop', 'month' => '11'],
            ['column' => 'des', 'month' => '12']
        ];

        $queries = [];

        foreach ($months as $month) {
            $query = DB::connection('kcpinformation')->table('mst_target_produk')
                ->selectRaw("CONCAT(periode, '-', ?) as periode, SUM(" . $month['column'] . ") as jmlTarget", [$month['month']])
                ->where('periode', date('Y'))
                ->whereRaw('kd_area <> ?', ['']);

            $queries[] = $query;
        }

        // Combine all queries using union
        $finalQuery = array_reduce($queries, function ($carry, $query) {
            if (!$carry) {
                return $query;
            }
            return $carry->union($query);
        });

        return $finalQuery->get();
    }

    public function render()
    {
        $data = $this->fetch_data_for_graph();

        $this->performance = $this->fetch_monthly_target();

        $this->data = $data;

        $total_invoice = DB::table('invoice_bosnet')
            ->whereDate('crea_date', '>=', Carbon::now()->startOfMonth())
            ->whereDate('crea_date', '<=', Carbon::now()->endOfMonth())
            ->sum('amount_total');

        $total_invoice_terbentuk = DB::table('invoice_bosnet')
            ->whereDate('crea_date', '>=', Carbon::now()->startOfMonth())
            ->whereDate('crea_date', '<=', Carbon::now()->endOfMonth())
            ->count();

        return view('livewire.dashboard', compact(
            'total_invoice',
            'total_invoice_terbentuk'
        ));
    }
}
