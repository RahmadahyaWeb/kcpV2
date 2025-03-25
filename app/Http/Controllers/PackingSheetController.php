<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackingSheetController extends Controller
{
    public function print_label($nops)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('trns_packingsheet_details_dus')
            ->where('nops', $nops)
            ->get();

        $labels = [];

        foreach ($items as $item) {
            $data_outlet = $kcpinformation->table('mst_outlet')
                ->where('kd_outlet', $item->kd_outlet)
                ->first();

            $provinsi = $kcpinformation->table('mst_provinsi')
                ->where('kode_prp', $data_outlet->kode_prp)
                ->value('provinsi');

            $kabupaten = $kcpinformation->table('mst_area')
                ->where('kode_kab', $data_outlet->kode_kab)
                ->value('nm_area');

            // Masukkan semua data ke dalam array $labels
            $labels[] = [
                'kd_outlet' => $data_outlet->kd_outlet,
                'nama_outlet' => $data_outlet->nm_outlet,
                'provinsi' => $provinsi,
                'kabupaten' => $kabupaten,
                'nops' => $item->nops,
                'tanggal_ps' => date('d-m-Y', strtotime($item->crea_date)),
                'no_dus' => $item->no_dus,
                'alamat' => $data_outlet->almt_pengiriman,
            ];
        }

        $data = ['labels' => $labels];

        $pdf = Pdf::loadView('livewire.packing-sheet.print-label', $data)->setPaper('A4');

        return $pdf->stream();
    }
}
