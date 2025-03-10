<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanInvoiceExport implements FromCollection, WithMapping, WithTitle, WithColumnFormatting, WithHeadings, WithEvents
{
    private $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function title(): string
    {
        return "LAPORAN INVOICE";
    }

    public function collection()
    {
        $items = collect($this->items);

        return $items->map(function ($row) {
            return $row;
        });
    }

    public function headings(): array
    {
        return [
            'NO INVOICE',
            'KODE TOKO',
            'NAMA TOKO',
            'PRODUK PART',
            'KELOMPOK PART',
            'NOMINAL INVOICE',
            'STATUS PEMBAYARAN',
            'TANGGAL INVOICE',
            'TANGGAL JATUH TEMPO',
            'TANGGAL PEMBAYARAN INVOICE',
            'PEMBAYARAN VIA',
            'BANK',
            'TELAT PEMBAYARAN (HARI)'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->getDelegate()->setCellValueExplicit('B' . $row, $sheet->getCell('B' . $row)->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        ];
    }

    public function map($row): array
    {
        $noinv = $row['noinv'];
        $kd_outlet = $row['kd_outlet'];
        $nm_outlet = $row['nm_outlet'];
        $nominal_invoice = $row['amount_total'];
        $produk_part = $row['product_part'];
        $kelompok_part = $row['kelompok_part'];
        $pembayaran_via = $row['pembayaran_via'];
        $bank = $row['bank'];

        $tanggal_invoice = date('Y-m-d', strtotime($row['crea_date']));
        $tanggal_jatuh_tempo = date('Y-m-d', strtotime($row['tgl_jth_tempo']));
        $tanggal_pembayaran = isset($row['tanggal_pembayaran']) ? date('Y-m-d', strtotime($row['tanggal_pembayaran'])) : null;

        $tanggal_invoice_excel = Date::dateTimeToExcel(new DateTime($tanggal_invoice));
        $tanggal_jatuh_tempo_excel = Date::dateTimeToExcel(new DateTime($tanggal_jatuh_tempo));
        $tanggal_pembayaran_excel = isset($tanggal_pembayaran) ? Date::dateTimeToExcel(new DateTime($tanggal_pembayaran)) : null;

        $tanggal_jatuh_tempo = Carbon::createFromFormat('Y-m-d', $tanggal_jatuh_tempo)->startOfDay();
        $tanggal_sekarang = Carbon::now()->startOfDay();
        $tanggal_pembayaran_carbon = $tanggal_pembayaran ? Carbon::createFromFormat('Y-m-d', $tanggal_pembayaran)->startOfDay() : null;

        if ($tanggal_pembayaran_carbon) {
            // Hitung keterlambatan hanya jika tanggal pembayaran ada
            if ($tanggal_pembayaran_carbon->greaterThan($tanggal_jatuh_tempo)) {
                $hari_terlambat = abs($tanggal_pembayaran_carbon->diffInDays($tanggal_jatuh_tempo));
            } else {
                $hari_terlambat = 0;
            }
        } else {
            // Cek apakah belum jatuh tempo
            if ($tanggal_sekarang->lessThanOrEqualTo($tanggal_jatuh_tempo)) {
                $status_pembayaran = 'BELUM JATUH TEMPO';
                $hari_terlambat = 0;
            } else {
                $status_pembayaran = 'JATUH TEMPO';
                $hari_terlambat = abs($tanggal_sekarang->diffInDays($tanggal_jatuh_tempo));
            }
        }

        // Output status pembayaran dan keterlambatan
        $flag_pembayaran_lunas = $row['flag_pembayaran_lunas'];

        $status_pembayaran = $flag_pembayaran_lunas === 'Y' ? 'LUNAS' : ($status_pembayaran ?? 'BELUM LUNAS');

        return [
            $noinv,
            $kd_outlet,
            $nm_outlet,
            $produk_part,
            $kelompok_part,
            $nominal_invoice,
            $status_pembayaran,
            $tanggal_invoice_excel,
            $tanggal_jatuh_tempo_excel,
            $tanggal_pembayaran_excel,
            $pembayaran_via,
            $bank,
            $hari_terlambat
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
