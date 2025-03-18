<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function sync_limit_kredit($kd_outlet)
    {
        $result = DB::connection('kcpinformation')->table(DB::raw('(
            select kd_outlet, nm_outlet, nominal_plafond_upload, nominal_plafond
            from kcpinformation.trns_plafond
        ) as plafond'))
            ->select(
                'plafond.kd_outlet',
                'plafond.nm_outlet',
                'plafond.nominal_plafond_upload',
                'plafond.nominal_plafond',
                DB::raw('IFNULL(SUM(hutang.hutang), 0) + IFNULL(hutang_bg.nominal_bg, 0) AS hutang')
            )
            ->leftJoin(DB::raw('(
                select a.noinv, a.kd_outlet, a.nm_outlet,
                       (a.amount_total - IFNULL(b.nominal, 0)) as hutang
                from kcpinformation.trns_inv_header a
                left join (
                    select noinv, kd_outlet, sum(nominal) as nominal
                    from kcpinformation.trns_pembayaran_piutang
                    where status = "C"
                    group by noinv
                ) b on (a.noinv = b.noinv)
                left join (
                    select x.noinv, sum(y.nominal_total) as nominal_retur
                    from kcpinformation.trns_retur_header x
                    join kcpinformation.trns_retur_details y on (x.noretur = y.noretur)
                    where x.flag_approve1 = "Y"
                    group by x.noinv
                ) c on (a.noinv = c.noinv)
                where a.flag_pembayaran_lunas = "N" and a.flag_batal = "N"
            ) as hutang'), 'plafond.kd_outlet', '=', 'hutang.kd_outlet')
            ->leftJoin(DB::raw('(
                select a.kd_outlet, a.nm_outlet, sum(a.nominal_potong) as nominal_bg
                from kcpinformation.trns_pembayaran_piutang_header a
                left join kcpinformation.trns_bg_header b on (
                    a.no_bg = b.from_bg
                    and b.flag_batal = "N"
                )
                where a.pembayaran_via = "BG"
                and IFNULL(b.from_bg, "-") = "-"
                AND a.flag_batal = "N"
                group by a.kd_outlet
            ) as hutang_bg'), 'plafond.kd_outlet', '=', 'hutang_bg.kd_outlet')
            ->where('plafond.kd_outlet', '=', $kd_outlet)
            ->groupBy('plafond.kd_outlet')
            ->get();

        dd($result);
    }

    public function sync_intransit()
    {
        $kcpapplication = DB::connection('mysql');
        $kcpinformation = DB::connection('kcpinformation');

        $invoice_aop = $kcpapplication->table('invoice_aop_header')
            ->whereDate('billingDocumentDate', '>=', '2025-02-28')
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
