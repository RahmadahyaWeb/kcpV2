<?php

namespace App\Livewire\Pajak;

use App\Exports\PajakMasukanExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexPajakMasukan extends Component
{
    public $target = '';
    public $from_date, $to_date;

    public function export_to_excel()
    {
        $this->validate([
            'from_date'     => ['required'],
            'to_date'       => ['required'],
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();

        $filename = "pajak_masukan" . $fromDateFormatted . "_" . $toDateFormatted . ".xlsx";

        return Excel::download(new PajakMasukanExport($fromDateFormatted, $toDateFormatted), $filename);
    }

    public function render()
    {
        return view('livewire.pajak.index-pajak-masukan');
    }
}
