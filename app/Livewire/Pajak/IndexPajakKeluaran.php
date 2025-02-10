<?php

namespace App\Livewire\Pajak;

use App\Exports\PajakKeluaranExport;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexPajakKeluaran extends Component
{
    public $target = '';

    public $from_date, $to_date;

    public function export_to_excel()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $this->validate([
            'from_date' => ['required'],
            'to_date'   => ['required']
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();

        $periode = date('Y-m', strtotime('2025-01'));

        $headers = $kcpinformation->table('trns_inv_header as header')
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', 'header.kd_outlet')
            ->whereBetween('header.crea_date', [$fromDateFormatted, $toDateFormatted])
            // ->whereRaw("SUBSTR(header.crea_date, 1, 7) = ?", [$periode])
            ->where('header.flag_batal', '<>', 'Y')
            ->where('outlet.kd_outlet', '<>', 'NW')
            ->whereNotIn('header.noinv', [
                'INV-202501-00001',
                'INV-202501-00002',
                'INV-202501-00003',
                'INV-202501-00005',
                'INV-202501-00006',
            ])
            ->whereNot('header.noinv', 'like', 'RTU%')
            ->orderBy('header.noinv', 'asc')
            ->select([
                'header.noinv as referensi',
                'header.crea_date as tanggal_faktur',
                'outlet.nm_outlet as nama_pembeli',
                'outlet.almt_outlet as alamat_pembeli',
                'outlet.nik as nik',
                'outlet.no_npwp as npwp',
                'outlet.email as email'
            ])
            ->get()
            ->map(function ($header, $index) {
                $header->baris = $index + 1;
                return $header;
            });

        $details = $kcpinformation->table('trns_inv_details as detail')
            ->whereIn('detail.noinv', $headers->pluck('referensi')->toArray())
            ->select([
                'detail.noinv as referensi',
                'detail.nm_part as nama_barang',
                'detail.hrg_pcs as harga_satuan',
                'detail.qty as jumlah_barang',
                'detail.nominal as nominal',
                'detail.nominal_total as nominal_total',
                'detail.nominal_disc as nominal_disc',
            ])
            ->get();

        // Kelompokkan detail berdasarkan 'Referensi'
        $groupedDetails = [];
        foreach ($details as $detail) {
            $referensi = $detail->referensi;
            if (!isset($groupedDetails[$referensi])) {
                $groupedDetails[$referensi] = [];
            }
            $groupedDetails[$referensi][] = $detail;
        }

        // Tambahkan nomor baris pada detail berdasarkan referensi
        // Pastikan nomor baris pada detail sama dengan header berdasarkan referensi
        $detailsWithBaris = [];
        foreach ($headers as $header) {
            $referensi = $header->referensi;
            if (isset($groupedDetails[$referensi])) {
                foreach ($groupedDetails[$referensi] as $detail) {
                    $detail->baris = $header->baris; // Gunakan nomor baris dari header
                    $detailsWithBaris[] = $detail;
                }
            }
        }

        $filename = "pajak_keluaran_" . $fromDateFormatted . "_" . $toDateFormatted . ".xlsx";

        return Excel::download(new PajakKeluaranExport($headers, $detailsWithBaris), $filename);
    }

    public function render()
    {
        return view('livewire.pajak.index-pajak-keluaran');
    }
}
