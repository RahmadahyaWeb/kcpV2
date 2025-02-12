<?php

namespace App\Livewire\ReportMarketing;

use App\Exports\LaporanInvoiceExport;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class IndexLaporanInvoice extends Component
{
    public $target = 'export_to_excel';
    public $from_date, $to_date, $selected_stores = [], $type_invoice;
    public $search_toko;

    public function export_to_excel()
    {
        $this->validate([
            'from_date'     => ['required'],
            'to_date'       => ['required'],
            'type_invoice'  => ['required'],
        ]);

        $kcpinformation = DB::connection('kcpinformation');

        $fromDateFormatted = \Carbon\Carbon::parse($this->from_date)->startOfDay();
        $toDateFormatted = \Carbon\Carbon::parse($this->to_date)->endOfDay();
        $selected_stores = $this->selected_stores;

        $type_invoice = $this->type_invoice;

        if ($type_invoice == 'Y') {
            $operator_flag_pembayaran_lunas = "=";
        } else {
            $operator_flag_pembayaran_lunas = "<>";
        }

        $invoices = $this->fetch_invoices($kcpinformation, $fromDateFormatted, $toDateFormatted, $selected_stores, $operator_flag_pembayaran_lunas);

        $product_parts = $this->get_product_parts($kcpinformation, $invoices->pluck('noinv')->toArray());

        $merged_data = $invoices->map(function ($invoice) use ($product_parts) {
            $parts = $product_parts[$invoice->noinv] ?? [
                'product_part' => null,
                'supplier'     => null,
                'kelompok_part' => null
            ];

            return array_merge((array) $invoice, $parts);
        });

        $sorted_data = $merged_data->sortBy('kd_outlet')->values();

        $filename = "laporan_invoice_" . $fromDateFormatted . "_" . $toDateFormatted . ".xlsx";

        return Excel::download(new LaporanInvoiceExport($sorted_data), $filename);
    }

    public function fetch_invoices($kcpinformation, $from_date, $to_date, $selected_stores, $operator_flag_pembayaran_lunas)
    {
        $query = $kcpinformation->table('trns_inv_header as header')
            ->select([
                'header.noinv',
                'header.amount_total',
                'header.crea_date',
                'header.tgl_jth_tempo',
                'outlet.kd_outlet',
                'outlet.nm_outlet'
            ])
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'header.kd_outlet')
            ->join('trns_inv_details as details', 'details.noinv', '=', 'header.noinv')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereBetween('header.crea_date', [$from_date, $to_date])
            ->where('flag_batal', '<>', 'Y')
            ->where('flag_pembayaran_lunas', $operator_flag_pembayaran_lunas, 'Y')
            ->groupBy('header.noinv');

        // Tambahkan whereIn jika selected_stores tidak kosong
        if (!empty($selected_stores)) {
            $query->whereIn('header.kd_outlet', $selected_stores);
        }

        return $query->get();
    }

    public function get_product_parts($kcpinformation, $noinv_list)
    {
        return $kcpinformation->table('trns_inv_details as details')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->whereIn('details.noinv', $noinv_list)
            ->groupBy('details.noinv', 'part.produk_part', 'part.supplier', 'part.kelompok_part')
            ->select([
                'details.noinv',
                'part.produk_part',
                'part.supplier',
                'part.kelompok_part'
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->noinv => [
                    'product_part' => $item->produk_part,
                    'supplier'     => $item->supplier,
                    'kelompok_part' => $item->kelompok_part
                ]];
            });
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

        return view('livewire.report-marketing.index-laporan-invoice', compact('master_toko'));
    }
}
