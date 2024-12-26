<?php

namespace App\Livewire\Purchase;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class PurchaseAop extends Component
{
    use WithFileUploads;
    use WithPagination, WithoutUrlPagination;

    public $target = 'save, invoiceAop, tanggalJatuhTempo, flag_po';
    public $surat_tagihan, $rekap_tagihan, $invoiceAop, $tanggalJatuhTempo;

    public $flag_po = 'N';

    public function save()
    {
        $this->validate([
            'surat_tagihan' => 'required|file|mimes:txt|max:2048',
            'rekap_tagihan' => 'required|file|mimes:txt|max:2048',
        ], [
            'surat_tagihan.required' => 'Upload file surat tagihan.',
            'rekap_tagihan.required' => 'Upload file rekap tagihan.',
        ]);

        $suratTagihanFileName = $this->surat_tagihan->getClientOriginalName();
        $rekapTagihanFileName = $this->rekap_tagihan->getClientOriginalName();

        preg_match('/_(\d{8})_/', $rekapTagihanFileName, $rekapTanggalMatch);
        preg_match('/_(\d{8})_/', $suratTagihanFileName, $suratTanggalMatch);

        if (!empty($rekapTanggalMatch[1]) && !empty($suratTanggalMatch[1])) {
            $rekapTanggal = $rekapTanggalMatch[1];
            $suratTanggal = $suratTanggalMatch[1];

            if ($rekapTanggal !== $suratTanggal) {
                $this->addError('surat_tagihan', 'Tanggal surat tagihan dan rekap tagihan tidak sesuai.');
            }
        }

        // VALIDASI NAMA FILE
        if ($this->surat_tagihan && !str_contains($suratTagihanFileName, 'surat_tagihan')) {
            $this->addError('surat_tagihan', 'File tidak sesuai.');
        }

        if ($this->rekap_tagihan && !str_contains($rekapTagihanFileName, 'rekap_tagihan')) {
            $this->addError('rekap_tagihan', 'File tidak sesuai.');
        }

        $listOfError = $this->getErrorBag();

        if (empty($listOfError->all())) {
            $this->explodeLines();
        }
    }

    public function explodeLines()
    {
        // Proses file surat_tagihan
        if ($this->surat_tagihan) {
            $suratContent = file_get_contents($this->surat_tagihan->getRealPath());
            $suratLines = explode("\n", trim($suratContent));

            // SURAT TAGIHAN HEADER
            $suratTagihanHeader = str_getcsv(array_shift($suratLines), "\t");
        }

        // Proses file rekap_tagihan
        if ($this->rekap_tagihan) {
            $rekapContent = file_get_contents($this->rekap_tagihan->getRealPath());
            $rekapLines = explode("\n", trim($rekapContent));

            // REKAP TAGIHAN HEADER
            $rekapTagihanHeader = str_getcsv(array_shift($rekapLines), "\t");
        }

        $this->rawData($suratLines, $rekapLines);
    }

    public function rawData($suratLines, $rekapLines)
    {
        // DATA MENTAH SURAT TAGIHAN
        $suratTagihanArray = [];
        foreach ($suratLines as $line) {
            $data = str_getcsv($line, "\t");
            $suratTagihanArray[] = $data;
        }

        // DATA MENTAH REKAP TAGIHAN
        $rekapTagihanArray = [];
        foreach ($rekapLines as $line) {
            $data = str_getcsv($line, "\t");
            $rekapTagihanArray[] = $data;
        }

        $this->combinedRawData($suratTagihanArray, $rekapTagihanArray);
    }

    public function combinedRawData($suratTagihanArray, $rekapTagihanArray)
    {
        // PROSES PENGGABUNGAN DATA MENTAH SURAT TAGIHAN DAN REKAP TAGIHAN
        $combinedArray = [];

        foreach ($suratTagihanArray as $index => $suratData) {
            if (isset($rekapTagihanArray[$index])) {
                $rekapData = $rekapTagihanArray[$index];

                $combinedArray[] = [
                    'CUSTOMER_NUMBER'       => $suratData[0],
                    'CUSTOMER_NAME'         => $suratData[1],
                    'BILLING_NUMBER'        => $suratData[2],
                    'BILLING_DOCUMENT_DATE' => $suratData[3],
                    'MATERIAL_NUMBER'       => $suratData[4],
                    'BILLING_QTY'           => intval($suratData[5]),
                    'BILLING_AMOUNT'        => intval($suratData[6]),
                    'SPB_NO'                => $suratData[7],
                    'TANGGAL_CETAK_FAKTUR'  => $suratData[8],
                    'TANGGAL_JATUH_TEMPO'   => $suratData[9],
                    'BILLING_AMOUNT_PPN'    => intval($rekapData[5]),
                    'ADD_DISCOUNT'          => isset($rekapData[6]) ? intval($rekapData[6]) : 0,
                    'CASH_DISCOUNT'         => isset($rekapData[7]) ? intval($rekapData[7]) : 0,
                    'EXTRA_DISCOUNT'        => isset($rekapData[8]) ? intval($rekapData[8]) : 0,
                ];
            } else {
                $combinedArray[] = [
                    'CUSTOMER_NUMBER'       => $suratData[0],
                    'CUSTOMER_NAME'         => $suratData[1],
                    'BILLING_NUMBER'        => $suratData[2],
                    'BILLING_DOCUMENT_DATE' => $suratData[3],
                    'MATERIAL_NUMBER'       => $suratData[4],
                    'BILLING_QTY'           => intval($suratData[5]),
                    'BILLING_AMOUNT'        => intval($suratData[6]),
                    'SPB_NO'                => $suratData[7],
                    'TANGGAL_CETAK_FAKTUR'  => $suratData[8],
                    'TANGGAL_JATUH_TEMPO'   => $suratData[9],
                    'BILLING_AMOUNT_PPN'    => 0,
                    'ADD_DISCOUNT'          => 0,
                    'CASH_DISCOUNT'         => 0,
                    'EXTRA_DISCOUNT'        => 0,
                ];
            }
        }

        $this->groupedCombinedArray($combinedArray);
    }

    public function groupedCombinedArray($combinedArray)
    {
        // Group by BILLING_NUMBER and MATERIAL_NUMBER
        $groupedArray = [];
        $groupedData = [];

        foreach ($combinedArray as $item) {
            $key = $item['BILLING_NUMBER'] . '|' . $item['MATERIAL_NUMBER'];

            if (!isset($groupedArray[$key])) {
                $groupedArray[$key] = [
                    'CUSTOMER_NUMBER'           => $item['CUSTOMER_NUMBER'],
                    'CUSTOMER_NAME'             => $item['CUSTOMER_NAME'],
                    'BILLING_NUMBER'            => $item['BILLING_NUMBER'],
                    'BILLING_DOCUMENT_DATE'     => $item['BILLING_DOCUMENT_DATE'],
                    'MATERIAL_NUMBER'           => $item['MATERIAL_NUMBER'],
                    'BILLING_QTY'               => 0,
                    'BILLING_AMOUNT'            => 0,
                    'SPB_NO'                    => $item['SPB_NO'],
                    'TANGGAL_CETAK_FAKTUR'      => $item['TANGGAL_CETAK_FAKTUR'],
                    'TANGGAL_JATUH_TEMPO'       => $item['TANGGAL_JATUH_TEMPO'],
                    'BILLING_AMOUNT_PPN'        => 0,
                    'ADD_DISCOUNT'              => 0,
                    'CASH_DISCOUNT'             => 0,
                    'EXTRA_DISCOUNT'            => 0,
                ];
            }

            $groupedArray[$key]['BILLING_QTY'] += $item['BILLING_QTY'];
            $groupedArray[$key]['BILLING_AMOUNT'] += $item['BILLING_AMOUNT'];
            $groupedArray[$key]['EXTRA_DISCOUNT'] += $item['EXTRA_DISCOUNT'];
            $groupedArray[$key]['CASH_DISCOUNT'] += $item['CASH_DISCOUNT'];
            $groupedArray[$key]['CASH_DISCOUNT'] += $item['CASH_DISCOUNT'];
            $groupedArray[$key]['BILLING_AMOUNT_PPN'] += $item['BILLING_AMOUNT_PPN'];
            $groupedArray[$key]['ADD_DISCOUNT'] += $item['ADD_DISCOUNT'];
        }

        $groupedArray = array_values($groupedArray);

        foreach ($groupedArray as $item) {
            $billingNumber = $item['BILLING_NUMBER'];
            if (!isset($groupedData[$billingNumber])) {
                $groupedData[$billingNumber] = [];
            }
            $groupedData[$billingNumber][] = $item;
        }

        try {
            DB::beginTransaction();

            $this->createInvoiceHeader($groupedData);
            $this->createInvoiceDetail($groupedArray);

            DB::commit();

            session()->flash('success', 'Data AOP berhasil diupload.');

            $this->dispatch('file-uploaded');
            $this->reset('surat_tagihan');
            $this->reset('rekap_tagihan');
        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('error', $e->getMessage());
        }
    }

    public function createInvoiceHeader($groupedData)
    {
        foreach ($groupedData as $billingNumber => $data) {
            $qty = 0;
            $addDiscount = 0;
            $amount = 0;
            $price = 0;
            $extraPlafonDiscount = 0;
            $netSales = 0;
            $tax = 0;
            $grandTotal = 0;
            $cashDiscount = 0;

            foreach ($data as $key => $value) {
                $qty += $value['BILLING_QTY'];
                $amount += $value['BILLING_AMOUNT'] + $value['EXTRA_DISCOUNT'];
                $addDiscount += $value['ADD_DISCOUNT'];
                $extraPlafonDiscount += $value['EXTRA_DISCOUNT'];
                $cashDiscount += $value['CASH_DISCOUNT'];
            }

            $price = $amount + $addDiscount;
            $netSales = $amount - $extraPlafonDiscount;
            $tax = floor($netSales * config('tax.ppn_percentage'));
            $grandTotal = intval($netSales + $tax);

            // CEK APAKAH DATA SUDAH ADA SEBELUMNYA
            $exists = DB::table('invoice_aop_header')
                ->where('invoiceAop', $data[0]['BILLING_NUMBER'])
                ->exists();

            // PROSES PENYIMPANAN KE DALAM TABLE INVOICE_AOP_HEADER
            if (!$exists) {
                DB::table('invoice_aop_header')->insert([
                    'invoiceAop'            => $data[0]['BILLING_NUMBER'],
                    'SPB'                   => $data[0]['SPB_NO'],
                    'customerTo'            => $data[0]['CUSTOMER_NUMBER'],
                    'customerName'          => $data[0]['CUSTOMER_NAME'],
                    'kdGudang'              => $data[0]['CUSTOMER_NUMBER'] == 'KCP01001' ? 'GD1' : 'GD2',
                    'billingDocumentDate'   => date('Y-m-d', strtotime($data[0]['BILLING_DOCUMENT_DATE'])),
                    'tanggalCetakFaktur'    => $data[0]['TANGGAL_CETAK_FAKTUR'] == '00.00.0000' ? NULL : date('Y-m-d', strtotime($data[0]['TANGGAL_CETAK_FAKTUR'])),
                    'tanggalJatuhTempo'     => date('Y-m-d', strtotime($data[0]['TANGGAL_JATUH_TEMPO'])),
                    'qty'                   => $qty,
                    'price'                 => $price,
                    'addDiscount'           => $addDiscount,
                    'amount'                => $amount,
                    'cashDiscount'          => $cashDiscount,
                    'netSales'              => $netSales,
                    'tax'                   => $tax,
                    'grandTotal'            => $grandTotal,
                    'extraPlafonDiscount'   => $extraPlafonDiscount,
                    'uploaded_by'           => Auth::user()->username,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }
        }
    }

    public function createInvoiceDetail($groupedArray)
    {
        foreach ($groupedArray as $billingNumber => $data) {
            // CEK APAKAH DATA SUDAH ADA SEBELUMNYA
            $exists = DB::table('invoice_aop_detail')
                ->where('invoiceAop', $data['BILLING_NUMBER'])
                ->where('materialNumber', $data['MATERIAL_NUMBER'])
                ->exists();

            if (!$exists) {
                DB::table('invoice_aop_detail')
                    ->insert([
                        'invoiceAop'            => $data['BILLING_NUMBER'],
                        'SPB'                   => $data['SPB_NO'],
                        'customerTo'            => $data['CUSTOMER_NUMBER'],
                        'materialNumber'        => $data['MATERIAL_NUMBER'],
                        'qty'                   => $data['BILLING_QTY'],
                        'price'                 => $data['BILLING_AMOUNT'],
                        'extraPlafonDiscount'   => $data['EXTRA_DISCOUNT'],
                        'amount'                => $data['BILLING_AMOUNT'] + $data['EXTRA_DISCOUNT'],
                        'addDiscount'           => $data['ADD_DISCOUNT'],
                        'uploaded_by'           => Auth::user()->username,
                        'created_at'            => now(),
                        'updated_at'            => now()
                    ]);
            }
        }
    }

    public function render()
    {
        $items = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', 'like', '%' . $this->invoiceAop . '%')
            ->when($this->tanggalJatuhTempo, function ($query) {
                return $query->where('tanggalJatuhTempo', $this->tanggalJatuhTempo);
            })
            ->when($this->flag_po, function ($query) {
                return $query->where('flag_po', $this->flag_po);
            })
            ->orderBy('billingDocumentDate', 'desc')
            ->paginate(20);

        return view('livewire.purchase.purchase-aop', compact(
            'items'
        ));
    }
}
