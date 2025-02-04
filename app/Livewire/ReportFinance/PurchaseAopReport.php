<?php

namespace App\Livewire\ReportFinance;

use App\Exports\InvoiceAopExport;
use App\Exports\InvoiceAopRekapExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseAopReport extends Component
{
    public $target = 'export_to_excel';
    public $from_date;
    public $to_date;
    public $jenis_laporan;

    public function export_to_excel()
    {
        $this->validate([
            'from_date'     => 'required',
            'to_date'       => 'required',
            'jenis_laporan' => 'required',
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->format('Ymd');
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->format('Ymd');

        if ($this->jenis_laporan == 'laporan') {
            return $this->export_laporan($fromDateFormatted, $toDateFormatted);
        } else if ($this->jenis_laporan == 'rekap') {
            return $this->export_rekap($fromDateFormatted, $toDateFormatted);
        }
    }

    public function export_rekap($fromDateFormatted, $toDateFormatted)
    {
        $filename = "rekap_invoice_aop_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new InvoiceAopRekapExport($this->from_date, $this->to_date), $filename);
    }

    public function export_laporan($fromDateFormatted, $toDateFormatted)
    {
        $filename = "invoice_aop_{$fromDateFormatted}_-_{$toDateFormatted}.xlsx";

        return Excel::download(new InvoiceAopExport($this->from_date, $this->to_date), $filename);
    }

    public function render()
    {
        return view('livewire.report-finance.purchase-aop-report');
    }
}
