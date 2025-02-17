<?php

namespace App\Livewire\ReportFinance;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgingReport extends Component
{
    public $target = 'export';
    public $from_date, $to_date, $jenis_laporan;

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function export_to_excel()
    {
        $this->validate([
            'from_date' => ['required'],
            'to_date' => ['required'],
            'jenis_laporan' => ['required'],
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();

        if ($this->jenis_laporan == 'aging') {
            $this->export_aging($fromDateFormatted, $toDateFormatted);
        }
    }

    public function export_aging($from_date, $to_date)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $dateEnd = '2025-02-17'; // Contoh tanggal akhir
        $area = 'some_area'; // Ganti dengan filter area yang diperlukan

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
            ->where('invoice.kd_outlet', 'ES')
            ->whereRaw('invoice.amount_total <> IFNULL(payment.total_payment, 0)');

        // Ambil data untuk tabel
        $items = $query->get();

        // Hitung total piutang dan total pembayaran jika tidak ada data
        $totals = $query->selectRaw('SUM(invoice.amount_total) AS total_piutang')
            ->selectRaw('SUM(IFNULL(payment.total_payment, 0)) AS total_payment')
            ->first();

        dd($items);
    }

    public function render()
    {
        return view('livewire.report-finance.aging-report');
    }
}
