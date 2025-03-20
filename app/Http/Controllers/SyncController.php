<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function sync_limit_kredit($kd_outlet)
    {
        $query = DB::connection('kcpinformation')->select("
            SELECT
  p.kd_outlet,
  p.nm_outlet,
  p.nominal_plafond_upload,
  p.nominal_plafond,
  IFNULL(SUM(h.hutang), 0) AS hutang
FROM
  (SELECT kd_outlet, nm_outlet, nominal_plafond_upload, nominal_plafond
   FROM kcpinformation.trns_plafond) p
LEFT JOIN
  (SELECT a.kd_outlet, a.nm_outlet, (a.amount_total - IFNULL(b.nominal, 0)) AS hutang
   FROM kcpinformation.trns_inv_header a
   LEFT JOIN (SELECT noinv, kd_outlet, SUM(nominal) AS nominal
              FROM kcpinformation.trns_pembayaran_piutang
              WHERE status = 'C'
              GROUP BY noinv) b ON a.noinv = b.noinv
   LEFT JOIN (SELECT x.noinv, SUM(y.nominal_total) AS nominal_retur
              FROM kcpinformation.trns_retur_header x
              JOIN kcpinformation.trns_retur_details y ON x.noretur = y.noretur
              WHERE x.flag_approve1 = 'Y'
              GROUP BY x.noinv) c ON a.noinv = c.noinv
   WHERE a.flag_pembayaran_lunas = 'N' AND a.flag_batal = 'N') h ON p.kd_outlet = h.kd_outlet
WHERE p.kd_outlet = '$kd_outlet'
GROUP BY p.kd_outlet
        ");

        foreach ($query as $key => $value) {
            if ($value->hutang == 0) {
                $nominal_plafond = $value->nominal_plafond_upload;
            } else {
                $nominal_plafond = $value->nominal_plafond_upload - $value->hutang;
            }

            DB::connection('kcpinformation')
                ->table('trns_plafond')
                ->where('kd_outlet', $kd_outlet)
                ->update([
                    'nominal_plafond' => $nominal_plafond
                ]);
        }
    }

    public function sync_intransit()
    {
        $kcpapplication = DB::connection('mysql');
        $kcpinformation = DB::connection('kcpinformation');

        $invoice_aop = $kcpapplication->table('invoice_aop_header')
            ->whereDate('billingDocumentDate', '>=', '2025-03-01')
            // ->whereIn('invoiceAop', ['4009709954', '4009709906'])
            ->select('SPB', 'customerTo', 'invoiceAop', 'billingDocumentDate')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($invoice_aop->isEmpty()) {
            Log::info("Tidak ada invoice pembelian.");
            throw new \Exception("Tidak ada invoice pembelian yang perlu di sync.");
        }

        foreach ($invoice_aop as $value) {
            try {
                $kcpinformation->beginTransaction();
                $spb_array = array_map('trim', explode(',', $value->SPB));

                foreach ($spb_array as $spb) {
                    $no_sp_aop = $spb . $value->customerTo;
                    $tanggal_invoice = $value->billingDocumentDate;

                    $invoice_aop_details = $kcpapplication->table('invoice_aop_detail')
                        ->where('SPB', $spb)
                        ->get();

                    $partNumbers = $invoice_aop_details->pluck('materialNumber');
                    $partData = $kcpinformation->table('mst_part')
                        ->whereIn('part_no', $partNumbers)
                        ->get(['part_no', 'nm_part']);

                    $details = $invoice_aop_details->map(function ($item) use ($partData) {
                        $item->nm_part = optional($partData->firstWhere('part_no', $item->materialNumber))->nm_part;
                        return $item;
                    });

                    $invalidItems = $details->filter(fn($item) => is_null($item->nm_part));
                    if ($invalidItems->isNotEmpty()) {
                        Log::info("Invoice $no_sp_aop dilewati karena beberapa item tidak memiliki nm_part", [
                            'invalid_items' => $invalidItems->pluck('materialNumber')->toArray()
                        ]);
                        $kcpinformation->rollBack();
                        continue;
                    }

                    if (!$kcpinformation->table('intransit_header')->where('no_sp_aop', $no_sp_aop)->exists()) {
                        $kcpinformation->table('intransit_header')->insert([
                            'no_sp_aop' => $no_sp_aop,
                            'delivery_note' => $no_sp_aop,
                            'kd_gudang_aop' => $value->customerTo,
                            'tgl_packingsheet' => date('Y-m-d', strtotime($tanggal_invoice)),
                            'status' => 'I',
                            'ket_status' => 'INTRANSIT',
                            'crea_date' => now(),
                            'crea_by' => 'SYSTEM'
                        ]);
                    }

                    $isInserted = false;
                    foreach ($invoice_aop_details as $item) {
                        if (!$kcpinformation->table('intransit_details')
                            ->where('delivery_note', $no_sp_aop)
                            ->where('part_no', $item->materialNumber)
                            ->where('no_sp_aop', $item->invoiceAop)
                            ->exists()) {
                            $kcpinformation->table('intransit_details')->insert([
                                'no_sp_aop' => $item->invoiceAop,
                                'delivery_note' => $no_sp_aop,
                                'kd_gudang_aop' => $value->customerTo,
                                'part_no' => $item->materialNumber,
                                'qty' => $item->qty,
                                'status' => 'I',
                                'crea_date' => now(),
                                'crea_by' => 'SYSTEM'
                            ]);
                            $isInserted = true;
                        }
                    }

                    if ($isInserted) {
                        Log::info("Invoice $no_sp_aop berhasil disinkronisasi", [
                            'details' => $invoice_aop_details->map(fn($item) => [
                                'part_no' => $item->materialNumber,
                                'qty' => $item->qty
                            ])->toArray()
                        ]);
                    } else {
                        Log::info("Invoice $no_sp_aop dilewati karena semua data sudah ada di intransit");
                    }
                }
                $kcpinformation->commit();
            } catch (\Exception $e) {
                $kcpinformation->rollBack();
                Log::error("Gagal menyinkronkan invoice $no_sp_aop", ['error' => $e->getMessage()]);
            }
        }
    }
}
