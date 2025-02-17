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

        $invoices = $kcpinformation->table('trns_inv_header as header')
            ->where('header.flag_pembayaran_lunas', '<>', 'Y')
            ->where('header.flag_batal', '<>', 'Y')
            ->where('header.status', '=', 'C')
            ->whereDate('header.crea_date', '<=', $dateEnd)
            ->get();

        dd($invoices);
    }

    public function render()
    {
        return view('livewire.report-finance.aging-report');
    }
}
