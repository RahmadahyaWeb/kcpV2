<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StoreRakExport implements FromCollection, WithHeadings
{
    public $label;
    public $header_id;

    public function __construct($label, $header_id)
    {
        $this->label = $label;
        $this->header_id = $header_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DB::table('store_rak_details as details')
            ->join('store_rak_header as header', 'header.id', '=', 'details.header_id')
            ->select([
                'header.label',
                'details.part_number',
                'details.nama_part',
                'details.kd_rak',
                'details.user_id',
                'details.created_at'
            ])
            ->where('header.id', $this->header_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'LABEL',
            'PART NUMBER',
            'NAMA PART',
            'KODE RAK',
            'SCAN BY',
            'TANGGAL SCAN'
        ];
    }
}
