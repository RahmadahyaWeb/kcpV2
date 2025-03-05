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

    public function sync_intransit_v1()
    {
        // Koneksi ke database
        $kcpapplication = DB::connection('mysql');
        $kcpinformation = DB::connection('kcpinformation');

        // Ambil data dari tabel invoice_aop_header
        $invoice_aop = $kcpapplication->table('invoice_aop_header')
            ->whereDate('billingDocumentDate', '>=', '2025-02-28')
            ->select('SPB', 'customerTo')
            ->orderBy('created_at', 'desc')
            ->groupBy('SPB', 'customerTo')
            ->get();

        // Ambil data dari tabel intransit_header
        $intransit_aop = $kcpinformation->table('intransit_header')
            ->orderBy('crea_date', 'desc')
            ->pluck('no_sp_aop');

        // Filter invoice yang belum masuk ke intransit
        $not_intransit = $invoice_aop->filter(function ($invoice) use ($intransit_aop) {
            // Membuat no_sp_aop sesuai kondisi
            $no_sp_aop = (strpos($invoice->SPB, 'DN') !== false)
                ? $invoice->SPB
                : $invoice->SPB . $invoice->customerTo; // Gabungkan SPB dan customerTo jika SPB tidak mengandung 'DN'

            $invoice->no_sp_aop = $no_sp_aop;

            return !$intransit_aop->contains($no_sp_aop); // Cocokkan dengan no_sp_aop yang ada di intransit
        });

        dd($not_intransit);

        if ($not_intransit->isEmpty()) {
            Log::info("Tidak ada invoice pembelian.");
            throw new \Exception("Tidak ada invoice pembelian yang perlu di sync.");
            return;
        }

        $result = [
            'success_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'success_invoices' => [],
            'failed_invoices' => [],
            'skipped_invoices' => []
        ];

        foreach ($not_intransit as $value) {
            try {
                $kcpinformation->beginTransaction();

                $no_sp_aop = $value->no_sp_aop;
                $kd_gudang_aop = $value->customerTo;

                $invoice_aop_details = $kcpapplication->table('invoice_aop_detail')
                    ->where('SPB', $value->SPB)
                    ->get();

                // Mengambil data nm_part dari database 'kcpinformation'
                $partNumbers = $invoice_aop_details->pluck('materialNumber'); // Ambil semua materialNumber
                $partData = $kcpinformation
                    ->table('mst_part')
                    ->whereIn('part_no', $partNumbers)
                    ->get(['part_no', 'nm_part']);

                // Gabungkan data nm_part ke dalam $details
                $details = $invoice_aop_details->map(function ($item) use ($partData) {
                    $nmPart = $partData->firstWhere('part_no', $item->materialNumber);
                    $item->nm_part = $nmPart ? $nmPart->nm_part : null; // Menambahkan nm_part ke item
                    return $item;
                });

                // Cek apakah ada item yang tidak memiliki nm_part
                $invalidItems = $details->filter(fn($item) => is_null($item->nm_part));

                if ($invalidItems->isNotEmpty()) {
                    $result['skipped_count']++;
                    $result['skipped_invoices'][] = [
                        'invoice' => $no_sp_aop,
                        'invalid_items' => $invalidItems->pluck('materialNumber')->toArray(),
                    ];
                    $kcpinformation->rollBack();
                    continue;
                }

                // INTRANSIT HEADER
                $kcpinformation->table('intransit_header')
                    ->insert([
                        'no_sp_aop' => $no_sp_aop,
                        'kd_gudang_aop' => $kd_gudang_aop,
                        'tgl_packingsheet' => now(),
                        'status' => 'I',
                        'ket_status' => 'INTRANSIT',
                        'crea_date' => now(),
                        'crea_by' => 'SYSTEM'
                    ]);

                foreach ($invoice_aop_details as $item) {
                    // INTRANSIT DETAILS
                    $kcpinformation->table('intransit_details')
                        ->insert([
                            'no_sp_aop' => $no_sp_aop,
                            'kd_gudang_aop' => $kd_gudang_aop,
                            'part_no' => $item->materialNumber,
                            'qty' => $item->qty,
                            'status' => 'I',
                            'crea_date' => now(),
                            'crea_by' => 'SYSTEM'
                        ]);
                }

                $kcpinformation->commit();
                $result['success_count']++;
                $result['success_invoices'][] = [
                    'invoice' => $no_sp_aop,
                    'details' => $invoice_aop_details->map(fn($item) => [
                        'part_no' => $item->materialNumber,
                        'qty' => $item->qty
                    ])->toArray(),
                ];
            } catch (\Exception $e) {
                $kcpinformation->rollBack();
                $result['failed_count']++;
                $result['failed_invoices'][] = [
                    'invoice' => $no_sp_aop,
                    'error' => $e->getMessage(),
                ];
                continue;
            }
        }

        dd($result);

        // Return hasil dalam bentuk array
        return $result;
    }

    public function sync_intransit()
    {
        // Koneksi ke database
        $kcpapplication = DB::connection('mysql');
        $kcpinformation = DB::connection('kcpinformation');

        // Ambil data dari tabel invoice_aop_header
        $invoice_aop = $kcpapplication->table('invoice_aop_header')
            ->whereDate('billingDocumentDate', '>=', '2025-02-28')
            // ->whereIn('invoiceAop', ['4009708981', '4009708980'])
            ->select('SPB', 'customerTo', 'invoiceAop')
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil data dari tabel intransit_header
        // $intransit_aop = $kcpinformation->table('intransit_header')
        //     ->orderBy('crea_date', 'desc')
        //     ->pluck('no_sp_aop');

        if ($invoice_aop->isEmpty()) {
            Log::info("Tidak ada invoice pembelian.");
            throw new \Exception("Tidak ada invoice pembelian yang perlu di sync.");
            return;
        }

        $result = [
            'success_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'success_invoices' => [],
            'failed_invoices' => [],
            'skipped_invoices' => []
        ];

        foreach ($invoice_aop as $key => $value) {
            try {
                $kcpinformation->beginTransaction();

                $invoiceAop = $value->invoiceAop;
                $spb = $value->SPB;
                $kd_gudang_aop = $value->customerTo;
                $no_sp_aop = "$spb" . "$kd_gudang_aop";

                $invoice_aop_details = $kcpapplication->table('invoice_aop_detail')
                    ->where('invoiceAop', $invoiceAop)
                    ->get();

                // Mengambil data nm_part dari database 'kcpinformation'
                $partNumbers = $invoice_aop_details->pluck('materialNumber'); // Ambil semua materialNumber
                $partData = $kcpinformation
                    ->table('mst_part')
                    ->whereIn('part_no', $partNumbers)
                    ->get(['part_no', 'nm_part']);

                // Gabungkan data nm_part ke dalam $details
                $details = $invoice_aop_details->map(function ($item) use ($partData) {
                    $nmPart = $partData->firstWhere('part_no', $item->materialNumber);
                    $item->nm_part = $nmPart ? $nmPart->nm_part : null; // Menambahkan nm_part ke item
                    return $item;
                });

                // Cek apakah ada item yang tidak memiliki nm_part
                $invalidItems = $details->filter(fn($item) => is_null($item->nm_part));

                if ($invalidItems->isNotEmpty()) {
                    $result['skipped_count']++;
                    $result['skipped_invoices'][] = [
                        'invoice' => $no_sp_aop,
                        'invalid_items' => $invalidItems->pluck('materialNumber')->toArray(),
                    ];
                    $kcpinformation->rollBack();
                    continue;
                }

                // INTRANSIT HEADER
                $exists_header = $kcpinformation->table('intransit_header')
                    ->where('no_sp_aop', $invoiceAop)
                    ->exists();

                if (!$exists_header) {
                    $kcpinformation->table('intransit_header')
                        ->insert([
                            'no_sp_aop' => $invoiceAop,
                            'delivery_note' => $no_sp_aop,
                            'kd_gudang_aop' => $kd_gudang_aop,
                            'tgl_packingsheet' => now(),
                            'status' => 'I',
                            'ket_status' => 'INTRANSIT',
                            'crea_date' => now(),
                            'crea_by' => 'SYSTEM'
                        ]);
                }

                $isInserted = false;

                foreach ($invoice_aop_details as $item) {
                    // Cek apakah data sudah ada
                    $exists_detail = $kcpinformation->table('intransit_details')
                        ->where('no_sp_aop', $invoiceAop)
                        ->where('part_no', $item->materialNumber)
                        ->exists();

                    if ($exists_detail) {
                        continue; // Skip jika sudah ada
                    }

                    // Insert jika belum ada
                    $kcpinformation->table('intransit_details')
                        ->insert([
                            'no_sp_aop' => $invoiceAop,
                            'delivery_note' => $no_sp_aop,
                            'kd_gudang_aop' => $kd_gudang_aop,
                            'part_no' => $item->materialNumber,
                            'qty' => $item->qty,
                            'status' => 'I',
                            'crea_date' => now(),
                            'crea_by' => 'SYSTEM'
                        ]);

                    $isInserted = true;
                }

                // $kcpinformation->commit();
                if ($isInserted) {
                    $result['success_count']++;
                    $result['success_invoices'][] = [
                        'invoice' => $no_sp_aop,
                        'details' => $invoice_aop_details->map(fn($item) => [
                            'part_no' => $item->materialNumber,
                            'qty' => $item->qty
                        ])->toArray(),
                    ];
                } else {
                    $result['skipped_count']++;
                    $result['skipped_invoices'][] = [
                        'invoice' => $no_sp_aop,
                        'reason' => 'Semua data sudah ada di intransit_details',
                    ];
                }
            } catch (\Exception $e) {
                $kcpinformation->rollBack();
                $result['failed_count']++;
                $result['failed_invoices'][] = [
                    'invoice' => $no_sp_aop,
                    'error' => $e->getMessage(),
                ];
                continue;
            }
        }

        dd($result);

        // Return hasil dalam bentuk array
        return $result;
    }
}
