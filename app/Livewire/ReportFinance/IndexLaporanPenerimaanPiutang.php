<?php

namespace App\Livewire\ReportFinance;

use App\Exports\LaporanPenerimaanPiutang;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexLaporanPenerimaanPiutang extends Component
{
    public $target = 'export_to_excel';
    public $from_date, $to_date, $selected_stores = [], $type_pembayaran, $tampilkan_semua_invoice = false;
    public $search_toko;

    public function export_to_excel()
    {
        $this->validate([
            'type_pembayaran'   => 'required',
            'from_date'         => 'required|date',
            'to_date'           => 'required|date',
        ]);

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();
        $selected_stores = $this->selected_stores;
        $type_pembayaran = $this->type_pembayaran;

        $data_penerimaan_piutang = $this->fetch_penerimaan_piutang($fromDateFormatted, $toDateFormatted, $selected_stores, $type_pembayaran);

        $filename = "laporan_penerimaan_piutang" . $fromDateFormatted . "_" . $toDateFormatted . ".xlsx";

        return Excel::download(new LaporanPenerimaanPiutang($data_penerimaan_piutang), $filename);
    }

    public function fetch_penerimaan_piutang($fromDateFormatted, $toDateFormatted, $selected_stores, $type_pembayaran)
    {
        $results = DB::table('customer_payment_header as header')
            ->join('customer_payment_details as detail', 'detail.no_piutang', '=', 'header.no_piutang')
            ->select(
                'header.no_piutang',
                'header.crea_date',
                'header.kd_outlet',
                'header.nm_outlet',
                'header.nominal_potong',
                'header.pembayaran_via',
                'detail.nominal',
                'detail.bank'
            )
            ->when($type_pembayaran != 'ALL', function ($query) use ($type_pembayaran) {
                $query->where('header.pembayaran_via', $type_pembayaran);
            })
            ->when(!empty($selected_stores), function ($query) use ($selected_stores) {
                $query->whereIn('header.kd_outlet', $selected_stores);
            })
            ->where('header.status', '<>', 'F')
            ->whereBetween('header.crea_date', [$fromDateFormatted, $toDateFormatted])
            ->orderBy('header.no_piutang')
            ->get();

        $nested = [];
        foreach ($results as $row) {
            $no_piutang = $row->no_piutang;
            if (!isset($nested[$no_piutang])) {
                $nested[$no_piutang] = [
                    'no_piutang'        => $row->no_piutang,
                    'nominal_potong'    => $row->nominal_potong,
                    'tanggal_potong'    => $row->crea_date,
                    'kd_outlet'         => $row->kd_outlet,
                    'nm_outlet'         => $row->nm_outlet,
                    'pembayaran_via'    => $row->pembayaran_via,
                    'details'           => [],
                ];
            }

            $nested[$no_piutang]['details'][] = [
                'no_piutang'    => $row->no_piutang,
                'nominal'       => $row->nominal,
                'bank'          => $row->bank
            ];
        }

        return array_values($nested);
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $master_toko = $kcpinformation->table('mst_outlet')
            ->where('status', 'Y')
            ->where(function ($query) {
                $query->where('nm_outlet', 'like', '%' . $this->search_toko . '%')
                    ->orWhere('kd_outlet', 'like', '%' . $this->search_toko . '%');
            })
            ->get();

        return view('livewire.report-finance.index-laporan-penerimaan-piutang', compact(
            'master_toko'
        ));
    }
}
