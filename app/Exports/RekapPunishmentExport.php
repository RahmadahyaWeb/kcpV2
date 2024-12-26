<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapPunishmentExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $items;

    public function __construct($fromDate, $toDate, $items)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->items = $items;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sales = array_keys($this->items);

        $sheets[] = new RekapSheet($sales, $this->fromDate, $this->toDate, $this->items);

        $sheets[] = new KunjunganSheet($sales, $this->fromDate, $this->toDate);

        $sheets[] = new FrekuensiSheet($sales, $this->fromDate, $this->toDate);

        foreach ($this->items as $user_sales => $value) {
            $sheets[] = new SalesSheet($user_sales, $this->fromDate, $this->toDate, $value);
        }

        return $sheets;
    }
}
