<?php

namespace App\Livewire\ReportFinance;

use App\Exports\AgingExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AgingReport extends Component
{
    use WithPagination;

    public $target = 'export_to_excel, show_data, search_kd_outlet, export_to_excel';
    public $from_date, $to_date, $jenis_laporan;
    public $search_kd_outlet;

    public $show = false;

    public $result = [];

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

        if ($this->jenis_laporan == 'aging') {
            return $this->export_aging();
        }
    }

    public function export_aging()
    {
        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();

        $filename = "LAPORAN_AGING_" . date('Y-m-d', strtotime($toDateFormatted)) . ".xlsx";

        return Excel::download(new AgingExport($this->result), $filename);
    }

    public function show_data()
    {
        $this->validate([
            'from_date' => ['required'],
            'to_date' => ['required'],
            'jenis_laporan' => ['required'],
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();

        $this->reset('search_kd_outlet');
        $this->fetch_data($toDateFormatted);
    }

    public function fetch_data($to_date, $search_kd_outlet = null)
    {
        $kcpinformation = DB::connection('kcpinformation');

        // Ambil list toko
        $list_toko_query = $kcpinformation->table('mst_outlet')
            ->where('status', 'Y');

        // Jika ada parameter pencarian, tambahkan filter untuk kd_outlet
        if ($search_kd_outlet) {
            $list_toko_query->where('kd_outlet', 'LIKE', '%' . $search_kd_outlet . '%');
        }

        $list_toko = $list_toko_query->get();

        // Ambil semua data invoice
        $invoiceQuery = DB::table('kcpinformation.trns_inv_header AS invoice')
            ->select(
                'invoice.noinv',
                'invoice.kd_outlet',
                'mst_outlet.nm_outlet',
                DB::raw("'INVOICE' AS jenis_transaksi"),
                'invoice.amount_total',
                DB::raw('invoice.tgl_jth_tempo AS tgl_transaksi'),
                DB::raw('IFNULL(payment.total_payment, 0) AS total_payment'),
                DB::raw('0 AS total_retur'),
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
            payment_details.noinv
    ) AS payment'), 'invoice.noinv', '=', 'payment.noinv')
            ->leftJoin('mst_outlet', 'invoice.kd_outlet', '=', 'mst_outlet.kd_outlet')
            ->where('invoice.flag_batal', 'N')
            ->where('invoice.flag_pembayaran_lunas', 'N')
            ->whereIn('invoice.kd_outlet', $list_toko->pluck('kd_outlet')->toArray())
            ->whereDate('invoice.crea_date', '<=', $to_date)
            ->where('invoice.noinv', 'NOT LIKE', 'RTU%');

        $returQuery = DB::table('kcpinformation.trns_retur_header AS retur')
            ->select(
                'retur.noretur AS noinv',
                'retur.kd_outlet',
                'mst_outlet.nm_outlet',
                DB::raw("'RETUR' AS jenis_transaksi"),
                DB::raw('SUM(detail.nominal) AS amount_total'),
                DB::raw('retur.tgl_retur AS tgl_transaksi'),
                DB::raw('0 AS total_payment'),
                DB::raw('SUM(detail.nominal) AS total_retur'),
                DB::raw('0 AS remaining_balance')
            )
            ->join('kcpinformation.trns_retur_details AS detail', 'retur.noretur', '=', 'detail.noretur')
            ->leftJoin('mst_outlet', 'retur.kd_outlet', '=', 'mst_outlet.kd_outlet')
            ->where('retur.flag_batal', 'N')
            ->whereIn('retur.kd_outlet', $list_toko->pluck('kd_outlet')->toArray())
            ->whereDate('retur.tgl_retur', '<=', $to_date)
            ->groupBy('retur.noretur', 'retur.kd_outlet', 'retur.tgl_retur', 'mst_outlet.nm_outlet');

        $unionQuery = $invoiceQuery->unionAll($returQuery);

        $mainQuery = DB::table(DB::raw("({$unionQuery->toSql()}) AS transaksi"))
            ->mergeBindings($unionQuery)
            ->groupBy('transaksi.kd_outlet'); // group langsung di level SQL


        dd($mainQuery);

        // Inisialisasi hasil akhir
        $result = [];

        // Tambahkan outlet yang tidak memiliki piutang ke dalam hasil
        foreach ($list_toko as $outlet) {
            $kd_outlet = $outlet->kd_outlet;

            // Jika outlet memiliki invoice, ambil data tersebut, jika tidak, buat entry baru
            $invoices = $groupedData->get($kd_outlet, collect([]));

            // Ambil nama outlet dan limit kredit dari invoice pertama atau set default
            $nm_outlet = $outlet->nm_outlet;
            $limit_kredit = $invoices->first()->limit_kredit ?? 0;

            $result[$kd_outlet] = [
                'kd_outlet' => $kd_outlet,
                'nm_outlet' => $nm_outlet, // Nama outlet
                'limit_kredit' => $limit_kredit, // Limit kredit
                'sisa_limit_kredit' => $limit_kredit, // Inisialisasi sisa limit kredit
                'overdue_1_7' => ['total_amount' => 0, 'invoice_count' => 0, 'invoice_numbers' => []],
                'overdue_8_20' => ['total_amount' => 0, 'invoice_count' => 0, 'invoice_numbers' => []],
                'overdue_21_50' => ['total_amount' => 0, 'invoice_count' => 0, 'invoice_numbers' => []],
                'overdue_over_50' => ['total_amount' => 0, 'invoice_count' => 0, 'invoice_numbers' => []],
                'not_overdue' => ['total_amount' => 0, 'invoice_count' => 0, 'invoice_numbers' => []], // Kategori baru untuk not overdue
                'total_piutang' => 0, // Inisialisasi total piutang
            ];

            // Iterasi setiap invoice untuk outlet yang memiliki piutang
            foreach ($invoices as $invoice) {
                $overdue_days = $invoice->overdue_days;

                // Tentukan kategori overdue
                if ($overdue_days >= 1 && $overdue_days <= 7) {
                    $category = 'overdue_1_7';
                } elseif ($overdue_days >= 8 && $overdue_days <= 20) {
                    $category = 'overdue_8_20';
                } elseif ($overdue_days >= 21 && $overdue_days <= 50) {
                    $category = 'overdue_21_50';
                } elseif ($overdue_days > 50) {
                    $category = 'overdue_over_50';
                } else {
                    $category = 'not_overdue'; // Untuk invoice yang belum overdue
                }

                // Tambahkan amount dan hitung jumlah invoice
                $result[$kd_outlet][$category]['total_amount'] += $invoice->remaining_balance;
                $result[$kd_outlet][$category]['invoice_count']++;

                // Tambahkan nomor invoice ke dalam daftar invoice_numbers untuk kategori ini
                $result[$kd_outlet][$category]['invoice_numbers'][] = $invoice->noinv;

                // Tambahkan nilai remaining_balance ke total_piutang
                $result[$kd_outlet]['total_piutang'] += $invoice->remaining_balance;
            }

            // Hitung sisa limit kredit
            $result[$kd_outlet]['sisa_limit_kredit'] = $result[$kd_outlet]['limit_kredit'] - $result[$kd_outlet]['total_piutang'];
        }

        // Output hasil
        $this->result = $result;
        $this->show = true;
    }

    public function render()
    {
        $perPage = 10;
        $search_kd_outlet = $this->search_kd_outlet ?? '';

        if ($search_kd_outlet) {
            $this->result = collect($this->result)->filter(function ($item, $key) use ($search_kd_outlet) {
                return stripos($key, $search_kd_outlet) !== false;
            })->toArray();
        }

        $collection = collect($this->result);
        $items = $collection->forPage($this->paginators['page'] ?? 1, $perPage);

        $items = new LengthAwarePaginator($items, $collection->count(), $perPage, $this->paginators['page'] ?? 1);

        return view('livewire.report-finance.aging-report', compact('items', 'search_kd_outlet'));
    }
}
