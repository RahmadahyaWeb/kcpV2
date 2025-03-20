<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapDaftarKehadiranDriverExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $items;

    public function __construct($fromDate, $toDate, $items)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->items = $items;

        dd($items);
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->items as $user_sales => $value) {
            $sheets[] = new DriverSheet($user_sales, $this->fromDate, $this->toDate, $value);
        }

        return $sheets;
    }
}
