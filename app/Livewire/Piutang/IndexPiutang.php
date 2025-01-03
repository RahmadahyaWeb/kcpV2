<?php

namespace App\Livewire\Piutang;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPiutang extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $target = 'selected_kd_outlet, kd_outlet, show_detail, show_all_piutang';
    public $kd_outlet = '';
    public $selected_kd_outlet = 's3';
    public $show = false;
    public $items;
    public $kalkulasi_total_piutang;
    public $remaining_balance_keseluruhan;
    public $total_piutang_keseluruhan;
    public $total_payment_keseluruhan;
    public $total_bg;

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

    public function show_all_piutang()
    {

        // Subquery untuk pembayaran
        $paymentSubquery = DB::connection('kcpinformation')
            ->table('trns_pembayaran_piutang_header as payment_header')
            ->select('payment_details.noinv', DB::raw('SUM(payment_details.nominal) AS total_payment'))
            ->join('trns_pembayaran_piutang as payment_details', 'payment_header.nopiutang', '=', 'payment_details.nopiutang')
            ->where('payment_header.flag_batal', '=', 'N')
            ->groupBy('payment_details.noinv');

        // Subquery pertama
        $subquery1 = DB::connection('kcpinformation')
            ->table('trns_inv_header as invoice')
            ->selectRaw('
            SUM(invoice.amount_total) AS total_piutang,
            SUM(IFNULL(payment.total_payment, 0)) AS total_payment,
            NULL AS no_bg,
            NULL AS area_inv,
            NULL AS kd_outlet,
            NULL AS nm_outlet,
            NULL AS nominal_bg,
            NULL AS crea_date,
            NULL AS jth_tempo_bg
        ')
            ->leftJoinSub($paymentSubquery, 'payment', 'invoice.noinv', '=', 'payment.noinv')
            ->where('invoice.flag_batal', '=', 'N')
            ->where('invoice.flag_pembayaran_lunas', '=', 'N')
            ->whereRaw('invoice.amount_total <> IFNULL(payment.total_payment, 0)');

        // Subquery kedua
        $subquery2 = DB::connection('kcpinformation')
            ->table('trns_pembayaran_piutang_header as piutang_header')
            ->selectRaw('
                NULL AS total_piutang,
                0 AS total_payment,
                piutang_header.no_bg AS no_bg,
                piutang_header.area_piutang AS area_invoice,
                piutang_header.kd_outlet AS outlet_code,
                piutang_header.nm_outlet AS outlet_name,
                SUM(piutang_header.nominal_potong) AS bg_nominal,
                bg_header.crea_date AS creation_date,
                piutang_header.jth_tempo_bg AS bg_due_date
            ')
            ->leftJoin('trns_bg_header as bg_header', function ($join) {
                $join->on('piutang_header.no_bg', '=', 'bg_header.from_bg')
                    ->where('bg_header.flag_batal', '=', 'N');
            })
            ->where(function ($query) {
                $query->whereRaw("(piutang_header.pembayaran_via = 'BG' AND IFNULL(bg_header.from_bg, '-') = '-')")
                    ->orWhereRaw("(piutang_header.pembayaran_via = 'BG' AND DATE_FORMAT(bg_header.crea_date, '%Y-%m-%d') = ?)", [date('Y-m-d')]);
            })
            ->groupBy('piutang_header.no_bg');


        // Gabungkan kedua subquery
        $combinedQuery = $subquery1
            ->unionAll($subquery2);

        // Query utama
        $result = DB::connection('kcpinformation')
            ->table(DB::raw("({$combinedQuery->toSql()}) AS combined_data"))
            ->mergeBindings($combinedQuery)
            ->selectRaw('
                SUM(total_piutang) AS total_piutang,
                SUM(total_payment) AS total_payment,
                SUM(nominal_bg) AS nominal_bg
            ')
            ->first();

        $this->total_piutang_keseluruhan = $result->total_piutang;
        $this->total_payment_keseluruhan = $result->total_payment;
        $this->total_bg = $result->nominal_bg;

        // Hitung sisa piutang keseluruhan
        $this->remaining_balance_keseluruhan = $this->total_piutang_keseluruhan - $this->total_payment_keseluruhan + $this->total_bg;
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
            'total_piutang' => $total_piutang,
        ]);
    }
}
