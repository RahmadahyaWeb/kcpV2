<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InvoiceAopProgramExport implements FromCollection, WithMapping, WithHeadings, WithColumnFormatting
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    public function collection()
    {
        return DB::table('program_aop')
            ->select([
                'invoiceAop',
                'keteranganProgram',
                'potonganProgram',
                'tanggalInvoice'
            ])
            ->whereBetween('tanggalInvoice', [$this->from_date, $this->to_date])
            ->orderBy('tanggalInvoice', 'asc')
            ->get();
    }

    public function map($row): array
    {
        return [
            $row->invoiceAop,
            $row->keteranganProgram,
            $row->potonganProgram,
            Date::dateTimeToExcel(Carbon::parse($row->tanggalInvoice)),
        ];
    }

    public function headings(): array
    {
        return [
            'NO INVOICE AOP',
            'KETERANGAN PROGRAM',
            'POTONGAN PROGRAM',
            'TANGGAL INVOICE'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
