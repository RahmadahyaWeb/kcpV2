<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreRakExport implements FromCollection, WithHeadings
{
    public $from_date;
    public $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DB::table('trans_store_rak')
            ->select([
                'part_number',
                'nama_part',
                'kd_rak',
                'created_at'
            ])
            ->where('status', 'finished')
            ->whereBetween('created_at', [$this->from_date, $this->to_date])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'PART NUMBER',
            'NAMA PART',
            'KODE RAK',
            'SCANNED AT'
        ];
    }
}
