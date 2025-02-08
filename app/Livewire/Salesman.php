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
        $this->periode = date('Y-m');
    }

    public function get_product_parts($noinv_list)
    {
        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_details as details')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereIn('details.noinv', $noinv_list)
            ->groupBy('details.noinv', 'part.produk_part', 'part.supplier', 'part.kategori_part')
            ->select([
                'details.noinv',
                'part.produk_part',
                'part.supplier',
                'part.kategori_part'
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noinv => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier,
                    'kategori_part' => $item->kategori_part
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
                'part.kategori_part',
                DB::raw('SUM(details.nominal_total) as total_retur')
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noretur => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier,
                    'amount_total' => $item->total_retur,
                    'kategori_part' => $item->kategori_part
                ]];
            });
    }

    public function fetch_invoices($kcpinformation, $periode)
    {
        return $kcpinformation->table('trns_inv_header as header')
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
    }

    public function fetch_sales($kcpinformation)
    {
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

        return $salesman_by_area;
    }

    public function fetch_target_aop($kcpinformation)
    {
        return $kcpinformation->table('mst_target_produk')
            ->joinSub(
                $kcpinformation->table('mst_part')
                    ->select('produk_part', 'kategori_part')
                    ->distinct(), // Menghindari duplikasi kategori part
                'part',
                'part.produk_part',
                '=',
                'mst_target_produk.produk_part'
            )
            ->where('periode', date('Y', strtotime($this->periode)))
            ->whereNotIn('kd_area', [''])
            ->whereNotIn('mst_target_produk.produk_part', ['BRIO PART', 'ICHIDAI PART', 'LIQUID'])
            ->whereIn('part.kategori_part', ['2W', '4W'])
            ->selectRaw("
            part.kategori_part,
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
            ->groupBy('part.kategori_part', 'area')
            ->orderBy('part.kategori_part')
            ->orderBy('area')
            ->get()
            ->groupBy('kategori_part')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    return [
                        $item->area => [
                            "jmlJan" => $item->jmlJan,
                            "jmlFeb" => $item->jmlFeb,
                            "jmlMar" => $item->jmlMar,
                            "jmlApr" => $item->jmlApr,
                            "jmlMay" => $item->jmlMay,
                            "jmlJun" => $item->jmlJun,
                            "jmlJul" => $item->jmlJul,
                            "jmlAug" => $item->jmlAug,
                            "jmlSep" => $item->jmlSep,
                            "jmlOct" => $item->jmlOct,
                            "jmlNov" => $item->jmlNov,
                            "jmlDec" => $item->jmlDec,
                        ],
                    ];
                });
            });
    }

    public function fetch_target_non_aop($kcpinformation)
    {
        $targets = $kcpinformation->table('mst_target_produk')
            ->selectRaw("
            area,
            part.kategori_part,
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
            ->join('mst_part as part', 'part.produk_part', '=', 'mst_target_produk.produk_part')
            ->where('periode', date('Y', strtotime($this->periode)))
            ->whereNotIn('kd_area', ['']);


        return (clone $targets)
            ->whereIn('mst_target_produk.produk_part', ['BRIO PART', 'ICHIDAI PART', 'LIQUID'])
            ->groupBy('area')
            ->get() // Pastikan diambil datanya
            ->keyBy('area');
    }

    public function fetch_invoice()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $periode = $this->periode;

        $bulan = date('m', strtotime($this->periode));

        $invoices = $this->fetch_invoices($kcpinformation, $periode);

        $product_parts = $this->get_product_parts($invoices->pluck('noinv')->toArray());

        $salesman_by_area = $this->fetch_sales($kcpinformation);

        $targets_aop = $this->fetch_target_aop($kcpinformation);

        $targets_non_aop = $this->fetch_target_non_aop($kcpinformation);

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

        $report = [];

        foreach ($invoices as $invoice) {
            $area = ($invoice->flag_2w === 'Y') ? $invoice->area_group_2w : $invoice->area_group_4w;

            if (empty($area) || $area === 'FLEET USER') {
                continue;
            }

            $noinv = $invoice->noinv;
            $supplier = $product_parts[$noinv]['supplier'] ?? null;
            $kategori_part = $product_parts[$noinv]['kategori_part'] ?? null;
            $amount_total = $invoice->amount_total;

            if (!isset($report[$area])) {
                $report[$area] = array_fill_keys([
                    'salesman_astra',
                    'salesman_non_astra'
                ], []) + array_fill_keys([
                    'total_inv_astra',
                    'total_inv_non_astra',
                    'total_retur_astra',
                    'total_retur_non_astra',
                    'total_astra',
                    'total_non_astra',
                    'total_2w_astra',
                    'total_4w_astra',
                    'target_2w',
                    'target_4w',
                    'target_aop',
                    'target_non_aop',
                    'persen_aop',
                    'persen_non_aop',
                    'persen_2w_aop',
                    'persen_4w_aop'
                ], 0);
            }

            // Hitung total invoice berdasarkan supplier
            if ($supplier === 'ASTRA OTOPART') {
                $kategori_part === '2W'
                    ? $report[$area]['total_2w_astra'] += $amount_total
                    : $report[$area]['total_4w_astra'] += $amount_total;

                $report[$area]['total_inv_astra'] += $amount_total;
                $report[$area]['total_astra'] += $amount_total;
            } else {
                $report[$area]['total_inv_non_astra'] += $amount_total;
                $report[$area]['total_non_astra'] += $amount_total;
            }

            // Salesman ASTRA
            if (!empty($salesman_by_area[$area]['ASTRA'])) {
                $report[$area]['salesman_astra'] = array_unique(array_merge(
                    $report[$area]['salesman_astra'],
                    $salesman_by_area[$area]['ASTRA']
                ));
            }

            // Salesman NON ASTRA
            if (!empty($salesman_by_area[$area]['NON ASTRA'])) {
                $report[$area]['salesman_non_astra'] = array_unique(array_merge(
                    $report[$area]['salesman_non_astra'],
                    $salesman_by_area[$area]['NON ASTRA']
                ));
            }

            $bulanKolom = $bulanMapping[$bulan] ?? null;

            if ($bulanKolom) {
                $report[$area]['target_2w'] = $targets_aop['2W'][$area][$bulanKolom] ?? 0;
                $report[$area]['target_4w'] = $targets_aop['4W'][$area][$bulanKolom] ?? 0;
                $report[$area]['target_non_aop'] = $targets_non_aop[$area]->$bulanKolom ?? 0;
            }

            // Hitung persentase AOP
            $target_total_aop = $report[$area]['target_2w'] + $report[$area]['target_4w'];
            if ($target_total_aop > 0) {
                $report[$area]['persen_aop'] = ($report[$area]['total_inv_astra'] / $target_total_aop) * 100;
            }

            // Hitung persentase AOP 2W
            if ($report[$area]['target_2w'] > 0) {
                $report[$area]['persen_2w_aop'] = ($report[$area]['total_2w_astra'] / $report[$area]['target_2w']) * 100;
            }

            // Hitung persentase AOP 4W
            if ($report[$area]['target_4w'] > 0) {
                $report[$area]['persen_4w_aop'] = ($report[$area]['total_4w_astra'] / $report[$area]['target_4w']) * 100;
            }

            // Hitung persentase Non-AOP
            if ($report[$area]['target_non_aop'] > 0) {
                $report[$area]['persen_non_aop'] = round(($report[$area]['total_inv_non_astra'] / $report[$area]['target_non_aop']) * 100);
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
            ->groupBy('retur_header.noretur')
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

            // Hitung persentase AOP
            if ($report[$area]['target_2w'] > 0 || $report[$area]['target_4w'] > 0) {
                $report[$area]['persen_aop'] -= ($report[$area]['total_retur_astra'] / ($report[$area]['target_2w'] + $report[$area]['target_4w'])) * 100;
            }

            // Hitung persentase Non-AOP
            if ($report[$area]['target_non_aop'] > 0) {
                $report[$area]['persen_non_aop'] -= ($report[$area]['total_retur_non_astra'] / $report[$area]['target_non_aop']) * 100;
            }
        }

        return $report;
    }

    public function fetch_anomali_invoice()
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

        $inv_kosong = [];

        foreach ($invoices as $invoice) {
            $area = ($invoice->flag_2w == 'Y') ? $invoice->area_group_2w : $invoice->area_group_4w;

            if (empty($area) || $area == 'FLEET USER') {
                $data = [
                    'area'          => $area,
                    'noinv'         => $invoice->noinv,
                    'product_part'  => $product_parts[$invoice->noinv]['product_part'] ?? null,
                    'supplier'      => $product_parts[$invoice->noinv]['supplier'] ?? null,
                    'amount_total'  => $invoice->amount_total,
                    'kategori_part' => $product_parts[$invoice->noinv]['kategori_part'] ?? null
                ];

                if ($data['supplier'] == 'ASTRA OTOPART') {
                    $data_astra = [
                        'area'          => $area,
                        'noinv'         => $invoice->noinv,
                        'product_part'  => $product_parts[$invoice->noinv]['product_part'] ?? null,
                        'supplier'      => $product_parts[$invoice->noinv]['supplier'] ?? null,
                        'amount_total'  => $invoice->amount_total,
                        'kategori_part' => $product_parts[$invoice->noinv]['kategori_part'] ?? null
                    ];
                    $inv_kosong[] = $data_astra;
                }
            }
        }

        dd($inv_kosong);
    }

    public function render()
    {

        // $this->fetch_anomali_invoice();
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
