<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

class InvoiceBosnetExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return DB::table('invoice_bosnet')
            ->select([
                'noinv',
                'amount_total'
            ])
            ->orderBy('noinv', 'desc')
            ->get();
    }
}
