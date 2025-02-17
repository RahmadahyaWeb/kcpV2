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

        // Query utama
        $invoices = $kcpinformation->table('trns_inv_header as inv')
            ->select(
                'inv.noinv',
                'inv.area_inv',
                'inv.kd_outlet',
                'inv.nm_outlet',
                'inv.amount_total',
                'inv.crea_date',
                'inv.tgl_jth_tempo',
                DB::raw('IFNULL(byr.total_byr, 0) as total_byr'),
                DB::raw('(inv.amount_total - IFNULL(byr.total_byr, 0)) as sisa')
            )
            ->leftJoin(
                DB::raw("(SELECT b.noinv, SUM(b.nominal) as total_byr
                          FROM trns_pembayaran_piutang_header a
                          JOIN trns_pembayaran_piutang b
                          ON a.nopiutang = b.nopiutang
                          WHERE DATE_FORMAT(b.crea_date, '%Y-%m-%d') <= '{$dateEnd}'
                          AND a.flag_batal = 'N'
                          GROUP BY b.noinv) as byr"),
                'inv.noinv',
                '=',
                'byr.noinv'
            )
            ->where('inv.flag_batal', 'N')
            ->where('inv.flag_pembayaran_lunas', 'N')
            ->whereDate('inv.crea_date', '<=', $dateEnd)
            ->orderBy('inv.kd_outlet')
            ->get();

        // Menambahkan Aging berdasarkan selisih tanggal jatuh tempo
        // Proses aging
        $report = $invoices->map(function ($invoice) use ($dateEnd) {
            $dueDate = strtotime($invoice->tgl_jth_tempo);
            $currentDate = strtotime($dateEnd);
            $overdueDays = ($currentDate - $dueDate) / (60 * 60 * 24);

            // Default values
            $invoice->belum_jatuh_tempo = 0;
            $invoice->overdue_1_7 = 0;
            $invoice->overdue_8_20 = 0;
            $invoice->overdue_21_50 = 0;
            $invoice->overdue_lebih_50 = 0;

            if ($overdueDays < 0) {
                $invoice->belum_jatuh_tempo = $invoice->sisa_piutang;
            } elseif ($overdueDays >= 1 && $overdueDays <= 7) {
                $invoice->overdue_1_7 = $invoice->sisa_piutang;
            } elseif ($overdueDays >= 8 && $overdueDays <= 20) {
                $invoice->overdue_8_20 = $invoice->sisa_piutang;
            } elseif ($overdueDays >= 21 && $overdueDays <= 50) {
                $invoice->overdue_21_50 = $invoice->sisa_piutang;
            } elseif ($overdueDays > 50) {
                $invoice->overdue_lebih_50 = $invoice->sisa_piutang;
            }

            $invoice->total_piutang = $invoice->sisa_piutang;

            return $invoice;
        });

        dd($report);

        // Menampilkan hasil
        return $invoices;
    }

    public function render()
    {
        return view('livewire.report-finance.aging-report');
    }
}
