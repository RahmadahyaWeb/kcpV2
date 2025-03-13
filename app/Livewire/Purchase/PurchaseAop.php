<?php

namespace App\Livewire\Purchase;

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class PurchaseAop extends Component
{
    use WithFileUploads;
    use WithPagination, WithoutUrlPagination;

    public $target = 'save, invoiceAop, tanggalJatuhTempo, flag_po, billing_doc_date, dn, sync_intransit, customer_to';
    public $surat_tagihan, $rekap_tagihan, $invoiceAop, $tanggalJatuhTempo, $dn, $billing_doc_date, $customer_to;

    public $flag_po = 'N';

    public function sync_intransit()
    {
        try {
            $controller = new SyncController();
            $result = $controller->sync_intransit();

            session()->flash('sync_result', $result);
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
            Log::error('Error: ' . $e->getMessage());
        }
    }

    public function save()
    {
        // session()->flash('error', 'Fitur sedang dalam perbaikan');

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
        // Ambil semua billing number unik dari surat tagihan
        $billingNumbers = array_unique(array_column($suratTagihanArray, 2));

        // Filter rekap tagihan hanya untuk billing number yang ada di surat tagihan
        $filteredRekapTagihan = array_values(array_filter($rekapTagihanArray, function ($item) use ($billingNumbers) {
            return isset($item[2]) && in_array($item[2], $billingNumbers);
        }));

        $combinedArray = [];

        foreach ($suratTagihanArray as $suratData) {
            $billingNumber = $suratData[2]; // Ambil billing number dari surat tagihan
            $billingAmount = intval($suratData[6]);

            // Filter rekap tagihan yang memiliki billing number yang sama
            $matchingRekap = array_values(array_filter($filteredRekapTagihan, function ($item) use ($billingNumber) {
                return isset($item[2]) && $item[2] == $billingNumber;
            }));

            // Cari nilai terdekat di rekapTagihanArray
            $closestRekap = null;
            $closestDiff = PHP_INT_MAX;

            foreach ($matchingRekap as $rekapData) {
                $rekapAmountPPN = intval($rekapData[5]); // Ambil nilai BILLING_AMOUNT_PPN
                $estimatedAmount = $rekapAmountPPN / 1.11; // Perhitungan yang benar

                // Hitung selisih antara billing amount dengan estimated amount
                $diff = abs($billingAmount - $estimatedAmount);

                if ($diff < $closestDiff) {
                    $closestDiff = $diff;
                    $closestRekap = $rekapData;
                }
            }

            if ($closestRekap !== null) {
                $combinedArray[] = [
                    'CUSTOMER_NUMBER'       => $suratData[0],
                    'CUSTOMER_NAME'         => $suratData[1],
                    'BILLING_NUMBER'        => $billingNumber,
                    'BILLING_DOCUMENT_DATE' => $suratData[3],
                    'MATERIAL_NUMBER'       => $suratData[4],
                    'BILLING_QTY'           => intval($suratData[5]),
                    'BILLING_AMOUNT'        => $billingAmount,
                    'SPB_NO'                => $suratData[7],
                    'TANGGAL_CETAK_FAKTUR'  => $suratData[8],
                    'TANGGAL_JATUH_TEMPO'   => $suratData[9],
                    'BILLING_AMOUNT_PPN'    => intval($closestRekap[5]),
                    'ADD_DISCOUNT'          => isset($closestRekap[6]) ? intval($closestRekap[6]) : 0,
                    'CASH_DISCOUNT'         => isset($closestRekap[7]) ? intval($closestRekap[7]) : 0,
                    'EXTRA_DISCOUNT'        => isset($closestRekap[8]) ? intval($closestRekap[8]) : 0,
                ];
            } else {
                // Jika tidak ada nilai yang mendekati, set default 0
                $combinedArray[] = [
                    'CUSTOMER_NUMBER'       => $suratData[0],
                    'CUSTOMER_NAME'         => $suratData[1],
                    'BILLING_NUMBER'        => $billingNumber,
                    'BILLING_DOCUMENT_DATE' => $suratData[3],
                    'MATERIAL_NUMBER'       => $suratData[4],
                    'BILLING_QTY'           => intval($suratData[5]),
                    'BILLING_AMOUNT'        => $billingAmount,
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
        // Group by BILLING_NUMBER, SPB_NO, and MATERIAL_NUMBER
        $groupedArray = [];
        $groupedData = [];

        foreach ($combinedArray as $item) {
            $key = $item['BILLING_NUMBER'] . '|' . $item['SPB_NO'] . '|' . $item['MATERIAL_NUMBER'];

            if (!isset($groupedArray[$key])) {
                $groupedArray[$key] = [
                    'CUSTOMER_NUMBER'           => $item['CUSTOMER_NUMBER'],
                    'CUSTOMER_NAME'             => $item['CUSTOMER_NAME'],
                    'BILLING_NUMBER'            => $item['BILLING_NUMBER'],
                    'BILLING_DOCUMENT_DATE'     => $item['BILLING_DOCUMENT_DATE'],
                    'SPB_NO'                    => $item['SPB_NO'],
                    'MATERIAL_NUMBER'           => $item['MATERIAL_NUMBER'],
                    'BILLING_QTY'               => 0,
                    'BILLING_AMOUNT'            => 0,
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
            $groupedArray[$key]['BILLING_AMOUNT_PPN'] += $item['BILLING_AMOUNT_PPN'];
            $groupedArray[$key]['ADD_DISCOUNT'] += $item['ADD_DISCOUNT'];
            $groupedArray[$key]['CASH_DISCOUNT'] += $item['CASH_DISCOUNT'];
            $groupedArray[$key]['EXTRA_DISCOUNT'] += $item['EXTRA_DISCOUNT'];
        }

        // Ubah dari associative array ke indexed array
        $groupedArray = array_values($groupedArray);

        // Tambahkan filter sementara untuk hanya menampilkan data dengan BILLING_NUMBER 4009709627
        $filteredGroupedArray = array_filter($groupedArray, function ($item) {
            return $item['BILLING_NUMBER'] == '4009709627';
        });

        // Konversi ke array numerik kembali setelah filter
        $filteredGroupedArray = array_values($filteredGroupedArray);

        // Group data berdasarkan BILLING_NUMBER
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
        $dataToInsert = []; // Array untuk menyimpan semua data sebelum insert

        foreach ($groupedData as $billingNumber => $data) {
            // Kumpulkan semua SPB_NO unik
            $spbNos = [];
            $billingQty = 0;

            foreach ($data as $value) {
                if (!in_array($value['SPB_NO'], $spbNos)) {
                    $spbNos[] = $value['SPB_NO'];
                }

                // Hitung total billingQty
                $billingQty += $value['BILLING_QTY'];
            }

            $spbNoString = implode(',', $spbNos);

            // CEK APAKAH DATA SUDAH ADA SEBELUMNYA
            $exists = DB::table('invoice_aop_header_new')
                ->where('invoiceAop', $billingNumber)
                ->exists();

            if (!$exists) {
                $dataToInsert[] = [
                    'invoiceAop'            => $billingNumber,
                    'SPB'                   => $spbNoString,
                    'customerTo'            => $data[0]['CUSTOMER_NUMBER'],
                    'customerName'          => $data[0]['CUSTOMER_NAME'],
                    'kdGudang'              => $data[0]['CUSTOMER_NUMBER'] == 'KCP01001' ? 'GD1' : 'GD2',
                    'billingDocumentDate'   => date('Y-m-d', strtotime($data[0]['BILLING_DOCUMENT_DATE'])),
                    'tanggalCetakFaktur'    => $data[0]['TANGGAL_CETAK_FAKTUR'] == '00.00.0000' ? NULL : date('Y-m-d', strtotime($data[0]['TANGGAL_CETAK_FAKTUR'])),
                    'tanggalJatuhTempo'     => date('Y-m-d', strtotime($data[0]['TANGGAL_JATUH_TEMPO'])),
                    'qty'                   => $billingQty,
                    'price'                 => 0,
                    'addDiscount'           => 0,
                    'extraPlafonDiscount'   => 0,
                    'cashDiscount'          => 0,
                    'netSales'              => 0,
                    'tax'                   => 0,
                    'amount'                => 0,
                    'grandTotal'            => 0,
                    'uploaded_by'           => Auth::user()->username,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
            }
        }

        // Debugging untuk melihat semua data yang akan disimpan
        // dd($dataToInsert);

        // Jika data tidak kosong, lakukan insert
        if (!empty($dataToInsert)) {
            DB::table('invoice_aop_header_new')->insert($dataToInsert);
        }
    }

    public function createInvoiceDetail($groupedArray)
    {
        $dataToInsert = []; // Array untuk menyimpan semua data sebelum insert

        foreach ($groupedArray as $data) {
            // CEK APAKAH DATA SUDAH ADA SEBELUMNYA
            $exists = DB::table('invoice_aop_detail_new')
                ->where('invoiceAop', $data['BILLING_NUMBER'])
                ->where('materialNumber', $data['MATERIAL_NUMBER'])
                ->where('SPB', $data['SPB_NO'])
                ->exists();

            if (!$exists) {
                $dataToInsert[] = [
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
                ];
            }
        }

        // Jika ada data yang akan dimasukkan, lakukan insert sekaligus (bulk insert)
        if (!empty($dataToInsert)) {
            DB::table('invoice_aop_detail_new')->insert($dataToInsert);
        }
    }


    public function render()
    {
        $items = DB::table('invoice_aop_header_new')
            ->select(['*'])
            ->where('invoiceAop', 'like', '%' . $this->invoiceAop . '%')
            ->where('SPB', 'like', '%' . $this->dn . '%')
            ->when($this->tanggalJatuhTempo, function ($query) {
                return $query->where('tanggalJatuhTempo', $this->tanggalJatuhTempo);
            })
            ->when($this->billing_doc_date, function ($query) {
                return $query->where('billingDocumentDate', $this->billing_doc_date);
            })
            ->when($this->flag_po, function ($query) {
                return $query->where('flag_po', $this->flag_po);
            })
            ->when($this->customer_to, function ($query) {
                return $query->where('customerTo', $this->customer_to);
            })
            ->orderBy('invoiceAop', 'desc')
            ->paginate(20);

        return view('livewire.purchase.purchase-aop', compact(
            'items'
        ));
    }
}
