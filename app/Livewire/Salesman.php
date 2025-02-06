<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Salesman extends Component
{
    public $target = 'periode';
    public $data_salesman;
    public $periode;

    public function mount()
    {
        $this->periode = date('Y-m', strtotime('2024-11'));
    }

    public function fetch_invoice_salesman()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        $invoice = $kcpinformation->table('user as salesman')
            ->leftJoin('trns_inv_header as invoice', function ($join) use ($periode) {
                $join->on('salesman.username', '=', 'invoice.user_sales')
                    ->whereRaw("SUBSTR(invoice.crea_date, 1, 7) = ?", [$periode])
                    ->where('invoice.flag_batal', '<>', 'Y');
            })
            ->where('salesman.role', 'SALESMAN')
            ->where('salesman.status', 'Y')
            ->select(
                'salesman.username as user_sales',
                'salesman.fullname',
                DB::raw('COALESCE(SUM(invoice.amount_total), 0) as total_amount')
            )
            ->groupBy('salesman.username', 'salesman.fullname')
            ->orderBy('total_amount', 'desc')
            ->get();

        $retur = $kcpinformation->table('trns_inv_header')
            ->join('trns_retur_header as retur_header', 'retur_header.noinv', '=', 'trns_inv_header.noinv')
            ->join('trns_retur_details as retur_detail', 'retur_header.noretur', '=', 'retur_detail.noretur')
            ->select('trns_inv_header.user_sales', DB::raw('SUM(retur_detail.nominal_total) as total_retur'))
            ->where(DB::raw('SUBSTR(retur_header.flag_nota_date, 1, 7)'), '=', [$periode])
            ->where('retur_header.flag_nota', '=', 'Y')
            ->where('trns_inv_header.flag_batal', '<>', 'Y')
            ->groupBy('trns_inv_header.user_sales')
            ->get();

        foreach ($invoice as $salesman) {
            // Cari data retur untuk user_sales yang sama (dengan strtolower untuk case-insensitive matching)
            $returnData = $retur->firstWhere(function ($item) use ($salesman) {
                return strtolower($item->user_sales) === strtolower($salesman->user_sales);
            });

            // Jika ada data retur, tambahkan total_retur, jika tidak set ke 0
            $salesman->total_retur = $returnData ? $returnData->total_retur : 0;

            $salesman->total = $salesman->total_amount - $salesman->total_retur;
        }

        return [
            'invoice' => $invoice,
        ];
    }

    public function get_product_parts($noinv_list)
    {
        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_details as details')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereIn('details.noinv', $noinv_list)
            ->groupBy('details.noinv', 'part.produk_part', 'part.supplier')
            ->select([
                'details.noinv',
                'part.produk_part',
                'part.supplier'
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noinv => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier
                ]];
            });
    }

    public function get_product_parts_non_aop($noretur_list)
    {
        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_retur_details as details')
            ->join('trns_retur_header as retur_header', 'retur_header.noretur', '=', 'details.noretur')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereIn('details.noretur', $noretur_list)
            ->groupBy('details.noretur', 'part.produk_part', 'part.supplier')
            ->select([
                'details.noretur',
                'part.produk_part',
                'part.supplier',
                DB::raw('SUM(details.nominal_total) as total_retur')
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noretur => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier,
                    'amount_total' => $item->total_retur
                ]];
            });
    }

    public function fetch_invoice()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        $invoices = $kcpinformation->table('trns_inv_header as header')
            ->select([
                'header.noinv',
                'header.amount_total',
                'outlet.flag_2w',
                'outlet.flag_4w',
                'outlet.area_group_2w',
                'outlet.area_group_4w',
            ])
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'header.kd_outlet')
            ->join('trns_inv_details as details', 'details.noinv', '=', 'header.noinv')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereRaw("SUBSTR(header.crea_date, 1, 7) = ?", [$periode])
            ->where('flag_batal', '<>', 'Y')
            ->groupBy('header.noinv')
            ->get();

        $product_parts = $this->get_product_parts($invoices->pluck('noinv')->toArray());

        // Ambil semua user_sales yang memiliki produk Astra
        $sales_astra = $kcpinformation->table('mst_partsales as sales')
            ->join('mst_part as part', 'part.produk_part', '=', 'sales.produk_part')
            ->where('part.supplier', 'ASTRA OTOPART')
            ->select('sales.user_sales')
            ->distinct()
            ->get();

        // Ambil semua sales dari hasil query
        $astra_sales = $sales_astra->pluck('user_sales')->toArray();

        $salesmen = $kcpinformation->table('user')
            ->join('mst_areatoko as area', 'area.user_sales', '=', 'user.username')
            ->select([
                'user.username',
                DB::raw("GROUP_CONCAT(CASE WHEN area.type_group = '2W' THEN area.area_group END SEPARATOR ', ') as areas_2W"),
                DB::raw("GROUP_CONCAT(CASE WHEN area.type_group = '4W' THEN area.area_group END SEPARATOR ', ') as areas_4W"),
                DB::raw("CASE WHEN user.username IN (" . implode(',', array_map(function ($item) {
                    return "'$item'";
                }, $astra_sales)) . ") THEN 'ASTRA' ELSE 'NON ASTRA' END as sales_type")
            ])
            ->groupBy('user.username')
            ->get();

        // Buat array untuk mapping area ke salesman
        $salesman_by_area = [];

        foreach ($salesmen as $salesman) {
            $areas_2w = explode(', ', $salesman->areas_2W ?? '');
            $areas_4w = explode(', ', $salesman->areas_4W ?? '');

            // Memasukkan salesman ke dalam array berdasarkan area dan sales_type
            foreach (array_filter($areas_2w) as $area) {
                $salesman_by_area[$area][$salesman->sales_type][] = $salesman->username;
            }

            foreach (array_filter($areas_4w) as $area) {
                $salesman_by_area[$area][$salesman->sales_type][] = $salesman->username;
            }
        }

        // Buat struktur data sesuai format yang diinginkan
        $report = [];

        $targets = $kcpinformation->table('mst_target_produk')
            ->selectRaw("
                area,
                SUM(jan) as jmlJan,
                SUM(feb) as jmlFeb,
                SUM(mar) as jmlMar,
                SUM(apr) as jmlApr,
                SUM(mei) as jmlMay,
                SUM(jun) as jmlJun,
                SUM(jul) as jmlJul,
                SUM(agt) as jmlAug,
                SUM(spt) as jmlSep,
                SUM(okt) as jmlOct,
                SUM(nop) as jmlNov,
                SUM(des) as jmlDec
            ")
            ->where('periode', date('Y', strtotime($this->periode)))
            ->whereNotIn('kd_area', ['']);

        $targets_aop = $targets
            ->whereNotIn('produk_part', ['BRIO PART', 'ICHIDAI PART', 'LIQUID'])
            ->groupBy('area')  // Group by area untuk ambil target per area
            ->get()
            ->keyBy('area');

        $targets_non_aop = $targets
            ->whereIn('produk_part', ['BRIO PART', 'ICHIDAI PART', 'LIQUID'])
            ->groupBy('area')  // Group by area untuk ambil target per area
            ->get()
            ->keyBy('area');

        $bulan = date('m', strtotime($this->periode)); // Mendapatkan bulan dari periode

        $bulanMapping = [
            '01' => 'jmlJan',
            '02' => 'jmlFeb',
            '03' => 'jmlMar',
            '04' => 'jmlApr',
            '05' => 'jmlMay',
            '06' => 'jmlJun',
            '07' => 'jmlJul',
            '08' => 'jmlAug',
            '09' => 'jmlSep',
            '10' => 'jmlOct',
            '11' => 'jmlNov',
            '12' => 'jmlDec',
        ];

        foreach ($invoices as $invoice) {
            $area = ($invoice->flag_2w == 'Y') ? $invoice->area_group_2w : $invoice->area_group_4w;

            if (!empty($area) && $area != 'FLEET USER') {
                $data = [
                    'noinv'         => $invoice->noinv,
                    'product_part'  => $product_parts[$invoice->noinv]['product_part'] ?? null,
                    'supplier'      => $product_parts[$invoice->noinv]['supplier'] ?? null,
                    'amount_total'  => $invoice->amount_total
                ];

                // Jika area belum ada di report, inisialisasi
                if (!isset($report[$area])) {
                    $report[$area] = [
                        'salesman_astra'   => [],
                        'salesman_non_astra' => [],
                        'total_inv_astra'      => 0,
                        'total_inv_non_astra'  => 0,
                        'target_aop' => 0, // Menambahkan target AOP dengan nilai awal 0
                        'target_non_aop' => 0, // Menambahkan target Non-AOP dengan nilai awal 0
                        'persen_aop' => 0, // Menambahkan kolom persen_aop dengan nilai awal 0
                        'persen_non_aop' => 0, // Menambahkan kolom persen_non_aop dengan nilai awal 0
                        'total_retur_astra' => 0,
                        'total_retur_non_astra' => 0,
                        'total_astra' => 0,
                        'total_non_astra' => 0,
                    ];
                }

                // Hitung total invoice berdasarkan supplier
                if ($data['supplier'] === 'ASTRA OTOPART') {
                    $report[$area]['total_inv_astra'] += $data['amount_total'];
                    $report[$area]['total_astra'] += $data['amount_total'];
                } else {
                    $report[$area]['total_inv_non_astra'] += $data['amount_total'];
                    $report[$area]['total_non_astra'] += $data['amount_total'];
                }

                // Salesman ASTRA
                if (isset($salesman_by_area[$area]['ASTRA'])) {
                    $report[$area]['salesman_astra'] = array_unique(array_merge($report[$area]['salesman_astra'], $salesman_by_area[$area]['ASTRA']));
                }

                // Salesman NON ASTRA
                if (isset($salesman_by_area[$area]['NON ASTRA'])) {
                    $report[$area]['salesman_non_astra'] = array_unique(array_merge($report[$area]['salesman_non_astra'], $salesman_by_area[$area]['NON ASTRA']));
                }

                // Target Area Astra (AOP)
                if (isset($targets_aop[$area])) {
                    $target_aop = $targets_aop[$area];

                    $bulanKolomAop = $bulanMapping[$bulan]; // Mengambil kolom sesuai dengan bulan
                    $report[$area]['target_aop'] = $target_aop->$bulanKolomAop ?? 0; // Menambahkan target AOP berdasarkan bulan yang sesuai
                }

                // Target Area Non-Astra (Non-AOP)
                if (isset($targets_non_aop[$area])) {
                    $target_non_aop = $targets_non_aop[$area];
                    $bulanKolomNonAop = $bulanMapping[$bulan]; // Mengambil kolom sesuai dengan bulan
                    $report[$area]['target_non_aop'] = $target_non_aop->$bulanKolomNonAop ?? 0; // Menambahkan target Non-AOP berdasarkan bulan yang sesuai
                }

                // Hitung persentase AOP
                if ($report[$area]['target_aop'] > 0) {
                    $report[$area]['persen_aop'] = round(($report[$area]['total_inv_astra'] / $report[$area]['target_aop']) * 100);
                }

                // Hitung persentase Non-AOP
                if ($report[$area]['target_non_aop'] > 0) {
                    $report[$area]['persen_non_aop'] = round(($report[$area]['total_inv_non_astra'] / $report[$area]['target_non_aop']) * 100);
                }
            }
        }

        $data_retur = $kcpinformation->table('trns_retur_header as retur_header')
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'retur_header.kd_outlet')
            ->select([
                'retur_header.noretur',
                'outlet.area_group_2w',
                'outlet.area_group_4w',
                'outlet.flag_2w',
            ])
            ->where(DB::raw('SUBSTR(retur_header.flag_nota_date, 1, 7)'), '=', [$periode])
            ->where('retur_header.flag_nota', '=', 'Y')
            ->groupBy('retur_header.noretur') // Group by area
            ->get();

        $product_parts_non_aop = $this->get_product_parts_non_aop($data_retur->pluck('noretur')->toArray());

        foreach ($data_retur as $retur) {
            $area = ($retur->flag_2w == 'Y') ? $retur->area_group_2w : $retur->area_group_4w;

            if (!empty($area) && $area != 'FLEET USER') {
                $data = [
                    'noretur'       => $retur->noretur,
                    'supplier'      => $product_parts_non_aop[$retur->noretur]['supplier'] ?? null,
                    'amount_total'  => $product_parts_non_aop[$retur->noretur]['amount_total'] ?? null
                ];
            }

            // Hitung total retur berdasarkan supplier
            if ($data['supplier'] === 'ASTRA OTOPART') {
                $report[$area]['total_retur_astra'] += $data['amount_total'];
                $report[$area]['total_astra'] -= $data['amount_total'];
            } else {
                $report[$area]['total_retur_non_astra'] += $data['amount_total'];
                $report[$area]['total_non_astra'] -= $data['amount_total'];
            }

            if ($report[$area]['target_aop'] > 0) {
                $report[$area]['persen_aop'] -= round(($report[$area]['total_retur_astra'] / $report[$area]['target_aop']) * 100);
            }

            // Hitung persentase Non-AOP
            if ($report[$area]['target_non_aop'] > 0) {
                $report[$area]['persen_non_aop'] -= round(($report[$area]['total_retur_non_astra'] / $report[$area]['target_non_aop']) * 100);
            }
        }

        return $report;
    }

    public function render()
    {
        $report = $this->fetch_invoice();

        // $data = $this->fetch_invoice_salesman();

        // $this->data_salesman = [
        //     'labels' => $data['invoice']->pluck('fullname')->toArray(),
        //     'amount' => $data['invoice']->pluck('total')->toArray(),
        //     'retur'  => $data['invoice']->pluck('total_retur')->toArray()
        // ];

        // $salesmanData = $data['invoice'];

        return view('livewire.salesman', compact('report'));
    }
}
