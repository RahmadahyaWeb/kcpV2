<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function print($noso)
    {
        $kcpinformation = DB::connection('kcpinformation');

        $header = $kcpinformation->table('trns_so_header')
            ->where('noso', $noso)
            ->first();

        if (!($header->flag_selesai == 'Y' && $header->flag_cetak_gudang == 'N')) {
            echo "<b> Maaf, SO masih diperiksa Fakturis </b>";
            exit;
        }

        $details = $kcpinformation->table('trns_so_details as details')
            ->join('mst_part as part', 'part.part_no', '=', 'details.part_no')
            ->where('details.noso', $noso)
            ->orderBy('details.part_no')
            ->select('details.*', 'part.produk_part')
            ->get();

        $data_outlet = $kcpinformation->table('mst_outlet')
            ->where('kd_outlet', $header->kd_outlet)
            ->first();

        $data_kabupaten = $kcpinformation->table('mst_area')
            ->where('kode_kab', $data_outlet->kode_kab)
            ->value('nm_area');

        $data_provinsi = $kcpinformation->table('mst_provinsi')
            ->where('kode_prp', $data_outlet->kode_prp)
            ->value('provinsi');

        if ($data_outlet->kode_prp == "6300") {
            $kode_gudang = "GD1";
        } elseif ($data_outlet->kode_prp == "6200") {
            $kode_gudang = "GD1";
        }

        $html_isi = '';
        $html_catatan = '';
        $i = 1;

        $nama_gudang = $kcpinformation->table('mst_gudang')
            ->where('kd_gudang', $kode_gudang)
            ->value('nm_gudang');

        $list_federal = [
            "FEDERAL BATTERY",
            "FEDERAL PARTS",
            "FEDERAL TUBE 2W"
        ];

        $data = [
            'kcpinformation' => $kcpinformation,
            'noso' => $noso,
            'header' => $header,
            'details' => $details,
            'data_outlet' => $data_outlet,
            'data_kabupaten' => $data_kabupaten,
            'data_provinsi' => $data_provinsi,
            'nama_gudang' => $nama_gudang,
            'kode_gudang' => $kode_gudang,
            'list_federal' => $list_federal
        ];

        $kcpinformation->table('trns_so_header')
            ->where('noso', $noso)
            ->update([
                'flag_cetak_gudang' => 'Y',
                'flag_cetak_gudang_date' => now(),
                'modi_date' => now(),
                'modi_by' => Auth::user()->username
            ]);

        $pdf = Pdf::loadView('livewire.sales-order.print', $data)->setPaper('letter');

        return $pdf->stream();
    }
}
