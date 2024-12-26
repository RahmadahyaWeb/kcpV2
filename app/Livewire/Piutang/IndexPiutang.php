<?php

namespace App\Livewire\Piutang;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPiutang extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $target = 'selected_kd_outlet, kd_outlet, show_detail';
    public $kd_outlet = '';
    public $selected_kd_outlet;
    public $show = false;
    public $items;
    public $kalkulasi_total_piutang;

    public function updatedKdOutlet()
    {
        $this->reset('selected_kd_outlet', 'items');
        $this->show = false;
    }

    public function updatedSelectedKdOutlet()
    {
        $this->show = false;
    }

    public function show_detail()
    {
        $this->show = !$this->show;
    }

    public function render()
    {

        $list_toko = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('status', 'Y')
            ->where('kd_outlet', 'like', '%' . $this->kd_outlet . '%')
            ->orderBy('nm_outlet')
            ->get();

        if ($this->selected_kd_outlet) {
            $query = DB::connection('kcpinformation')->table('kcpinformation.trns_inv_header AS invoice')
                ->select(
                    'invoice.noinv',
                    'invoice.area_inv',
                    'invoice.kd_outlet',
                    'invoice.nm_outlet',
                    'invoice.amount_total',
                    'invoice.crea_date',
                    'invoice.tgl_jth_tempo',
                    DB::raw('IFNULL(payment.total_payment, 0) AS total_payment'),
                    DB::raw('(invoice.amount_total - IFNULL(payment.total_payment, 0)) AS remaining_balance')
                )
                ->leftJoin(DB::raw('(SELECT
                    payment_details.noinv,
                    SUM(payment_details.nominal) AS total_payment
                FROM
                    kcpinformation.trns_pembayaran_piutang_header AS payment_header
                JOIN
                    kcpinformation.trns_pembayaran_piutang AS payment_details
                    ON payment_header.nopiutang = payment_details.nopiutang
                WHERE
                    payment_header.flag_batal = "N"
                GROUP BY
                    payment_details.noinv) AS payment'), 'invoice.noinv', '=', 'payment.noinv')
                ->where('invoice.flag_batal', 'N')
                ->where('invoice.flag_pembayaran_lunas', 'N')
                ->where('invoice.kd_outlet', $this->selected_kd_outlet)
                ->whereRaw('invoice.amount_total <> IFNULL(payment.total_payment, 0)');

            // Ambil data untuk tabel
            $this->items = $query->get();

            // Hitung total piutang dan total pembayaran jika tidak ada data
            $totals = $query->selectRaw('SUM(invoice.amount_total) AS total_piutang')
                ->selectRaw('SUM(IFNULL(payment.total_payment, 0)) AS total_payment')
                ->first();

            $total_payment = $totals->total_payment;
            $total_piutang = $totals->total_piutang;
        } else {
            $this->items = collect([]);
            $total_payment = 0;
            $total_piutang = 0;
        }

        return view('livewire.piutang.index-piutang', [
            'list_toko'     => $list_toko,
            'items'         => $this->items,
            'total_payment' => $total_payment,
            'total_piutang' => $total_piutang
        ]);
    }
}
