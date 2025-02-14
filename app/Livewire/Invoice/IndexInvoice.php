<?php

namespace App\Livewire\Invoice;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IndexInvoice extends Component
{
    public function print($noinv)
    {
        $this->redirectRoute('invoice.detail', $noinv, true, true);
    }

    public function detail_so($noso)
    {
        $this->redirectRoute('invoice.sales-order.detail', $noso, true, true);
    }

    public function fetch_sales_orders($kcpinformation)
    {

        return $kcpinformation->table('trns_so_header as header')
            ->join('trns_so_details as details', 'header.noso', '=', 'details.noso')
            ->join('user as user', 'user.username', '=', 'header.user_sales')
            ->where([
                ['header.status', '=', 'C'],
                ['header.flag_selesai', '=', 'Y'],
                ['header.flag_cetak_gudang', '=', 'Y'],
                ['header.flag_vald_gudang', '=', 'Y'],
                ['header.flag_packingsheet', '=', 'Y'],
                ['header.flag_invoice', '=', 'N'],
                ['header.flag_reject', '=', 'N']
            ])
            ->whereIn('header.no_packingsheet', function ($query) {
                $query->select('nops')
                    ->from('trns_packingsheet_header')
                    ->where('status', '=', 'C');
            })
            ->groupBy('header.noso', 'header.kd_outlet', 'header.nm_outlet')
            ->select(
                'header.noso',
                'header.area_so',
                'header.kd_outlet',
                'header.nm_outlet',
                'user.fullname',
                DB::raw('SUM(details.nominal_total_gudang) as nominal_total')
            )
            ->get();
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $ppn_factor = config('tax.ppn_factor');

        $sales_orders = $this->fetch_sales_orders($kcpinformation);

        $invoices = $kcpinformation->table('trns_inv_header as a')
            ->join('trns_inv_details as b', 'a.noinv', '=', 'b.noinv')
            ->select(
                'a.noinv',
                'a.area_inv',
                'a.noso',
                'a.kd_outlet',
                'a.nm_outlet',
                'a.tgl_jth_tempo',
                DB::raw('ROUND(SUM(b.nominal)) as nominal_ppn'),
                DB::raw('ROUND(SUM(b.nominal_disc)) as nominal_disc_ppn'),
                DB::raw('ROUND(SUM(b.nominal_total)) as nominal_total_ppn'),
                DB::raw('ROUND(SUM(b.nominal) / ' . $ppn_factor . ') as nominal_nonppn'),
                DB::raw('ROUND(SUM(b.nominal_disc) / ' . $ppn_factor . ') as nominal_disc_noppn'),
                DB::raw('ROUND(SUM(b.nominal_total) / ' . $ppn_factor . ') as nominal_total_noppn')
            )
            ->where('a.status', '=', 'O')
            ->where('a.flag_batal', '=', 'N')
            ->groupBy('a.noinv')
            ->get();

        $total_invoice_data = DB::table('invoice_bosnet')
            ->whereDate('crea_date', '>=', Carbon::now()->startOfMonth())
            ->whereDate('crea_date', '<=', Carbon::now()->endOfMonth())
            ->selectRaw('sum(amount_total) as total_invoice, count(*) as total_invoice_terbentuk')
            ->first();

        $total_invoice = $total_invoice_data->total_invoice;
        $total_invoice_terbentuk = $total_invoice_data->total_invoice_terbentuk;

        return view('livewire.invoice.index-invoice', compact(
            'sales_orders',
            'invoices',
            'total_invoice',
            'total_invoice_terbentuk'
        ));
    }
}
