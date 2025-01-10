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

    public function render()
    {
        $ppn_factor = config('tax.ppn_factor');

        $invoices = DB::connection('kcpinformation')
            ->table('trns_inv_header as a')
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
            'invoices',
            'total_invoice',
            'total_invoice_terbentuk'
        ));
    }
}
