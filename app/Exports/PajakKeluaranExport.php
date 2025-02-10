<?php

namespace App\Exports;

use App\Exports\Pajak\PajakKeluaranDetailSheet;
use App\Exports\Pajak\PajakKeluaranHeaderSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PajakKeluaranExport implements WithMultipleSheets
{
    private $headers;
    private $details;

    public function __construct($headers, $details)
    {
        $this->headers = $headers;
        $this->details = $details;
    }

    public function sheets(): array
    {
        return [
            new PajakKeluaranHeaderSheet($this->headers),
            new PajakKeluaranDetailSheet($this->details),
        ];
    }
}
