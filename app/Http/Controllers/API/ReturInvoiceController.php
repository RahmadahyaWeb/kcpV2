<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReturInvoiceController extends Controller
{
    public function sendToBosnet(Request $request)
    {
        $no_retur = $request->no_retur;
        $no_invoice = $request->no_invoice;

        if ($no_retur && $no_invoice) {
            $this->sendPartialDataToBosnet($no_retur, $no_invoice);
        }
    }

    public function sendPartialDataToBosnet($no_retur, $no_invoice)
    {
        $log_controller = LogController::class;

        try {
            // Mulai transaksi untuk invoice
            DB::beginTransaction();

            $item = DB::connection('kcpinformation')
                ->table('trns_inv_header')
                ->where('noinv', $no_invoice)
                ->first();

            // Persiapkan data untuk dikirim ke BOSNET
            $dataToSend = $this->prepareBosnetData($item, $no_retur);

            // Kirim data ke BOSNET
            $this->sendDataToBosnet($dataToSend);

            DB::connection('kcpinformation')
                ->table('trns_retur_header')
                ->where('noinv', $item->noinv)
                ->update([
                    'flag_bosnet' => 'Y',
                    'retur_send_to_bosnet' => now()
                ]);

            Log::info("Berhasil kirim retur invoice: $no_invoice");

            DB::commit(); // Commit transaksi setelah berhasil mengupdate status

            $log_controller::log_api($dataToSend, '', true);
        } catch (\Exception $e) {
            // Tangani error per invoice, update status ke FAILED dan lanjutkan ke invoice berikutnya
            DB::rollBack(); // Rollback transaksi jika ada error

            DB::connection('kcpinformation')
                ->table('trns_retur_header')
                ->where('noinv', $item->noinv)
                ->update([
                    'flag_bosnet' => 'F',
                    'retur_send_to_bosnet' => now()
                ]);

            Log::error('Error occurred for return invoice ' . $no_invoice . ': ' . $e->getMessage());

            $log_controller::log_api($dataToSend, $e->getMessage(), false);

            throw new \Exception('Error occurred for return invoice ' . $no_invoice . ': ' . $e->getMessage());
        }
    }

    // public function sendBulkDataToBosnet()
    // {
    //     $retur_items = DB::connection('kcpinformation')
    //         ->table('trns_retur_header')
    //         ->where([
    //             ['flag_reject', '=', 'N'],
    //             ['flag_batal', '=', 'N'],
    //             ['flag_approve1', '=', 'Y'],
    //             ['flag_nota', '=', 'Y'],
    //         ])
    //         ->whereDate('crea_date', '>=', '2025-01')
    //         ->where(function ($query) {
    //             $query->where('flag_bosnet', '=', 'N')
    //                 ->orWhere('flag_bosnet', '=', 'F');
    //         })
    //         ->get();

    //     foreach ($retur_items as $retur) {
    //         try {
    //             // Mulai transaksi untuk invoice
    //             DB::beginTransaction();

    //             $item = DB::connection('kcpinformation')
    //                 ->table('trns_inv_header')
    //                 ->where('noinv', $retur->noinv)
    //                 ->first();

    //             // Persiapkan data untuk dikirim ke BOSNET
    //             $dataToSend = $this->prepareBosnetData($item, $retur->noretur);

    //             // Kirim data ke BOSNET
    //             $response = $this->sendDataToBosnet($dataToSend);

    //             if ($response) {
    //                 DB::connection('kcpinformation')
    //                     ->table('trns_retur_header')
    //                     ->update([
    //                         'flag_bosnet' => 'Y',
    //                         'retur_send_to_bosnet' => now()
    //                     ]);
    //                 Log::info("Berhasil kirim retur invoice: $retur->noinv");
    //             } else {
    //                 // Jika gagal mengirim ke BOSNET, update status menjadi FAILED
    //                 DB::connection('kcpinformation')
    //                     ->table('trns_retur_header')
    //                     ->update([
    //                         'flag_bosnet' => 'F',
    //                         'retur_send_to_bosnet' => now()
    //                     ]);
    //                 Log::error("Gagal kirim retur invoice: $retur->noinv");
    //             }

    //             DB::commit(); // Commit transaksi setelah berhasil mengupdate status

    //         } catch (\Exception $e) {
    //             // Tangani error per invoice, update status ke FAILED dan lanjutkan ke invoice berikutnya
    //             DB::rollBack(); // Rollback transaksi jika ada error
    //             DB::connection('kcpinformation')
    //                 ->table('trns_retur_header')
    //                 ->update([
    //                     'flag_bosnet' => 'F',
    //                     'retur_send_to_bosnet' => now()
    //                 ]);
    //             Log::error('Error occurred for return invoice ' . $retur->noinv . ': ' . $e->getMessage());
    //             continue;
    //         }
    //     }
    // }

    /**
     * Placeholder function for sending data to BOSNET.
     *
     * @param array $data
     * @return bool Returns true if data is successfully sent to BOSNET.
     */
    private function sendDataToBosnet($data)
    {
        $credential = TokenBosnetController::signInForSecretKey();

        if (isset($credential['status'])) {
            throw new \Exception('Connection refused by BOSNET');
        }

        if ($credential && $credential['szStatus'] == 'READY') {
            $token = $credential['szToken'];

            $payload = $data;

            $url = 'https://omnichannel.ngecosystem.com/API/OC/NGE/v1/SD/FDo/SaveFDo';

            $response = Http::withHeaders([
                'Token' => $token
            ])->post($url, $payload);

            $data = $response->json();

            if ($response->successful()) {

                if ($data['statusCode'] == 500) {
                    throw new \Exception($data['statusMessage']);
                }
            } else {
                throw new \Exception($data['message']);
            }
        } else {
            throw new \Exception('BOSNET not responding');
        }
    }

    /**
     * Prepares the data to be sent to BOSNET.
     */
    private function prepareBosnetData($item, $no_retur)
    {
        $decDPPTotal = 0;
        $decTaxTotal = 0;

        // Calculate the payment term
        $paymentTermId = $this->calculatePaymentTerm($item->crea_date, $item->tgl_jth_tempo);

        // Generate the list of retur items
        $items = $this->generateReturItems($no_retur, $decDPPTotal, $decTaxTotal);

        if (empty($item->user_sales) || $item->user_sales === '') {
            $user_sales = 'admincounter';
        } else {
            $user_sales = $item->user_sales;
        }

        // Flag nota date
        $flag_nota_date = DB::connection('kcpinformation')
            ->table('trns_retur_header')
            ->where('noinv', $item->noinv)
            ->value('flag_nota_date');

        return [
            "szAppId"               => "BDI.KCP",
            "fdoData"   => [
                "szDoId"            => $no_retur,
                "szFSoId"           => "",
                "szLogisticType"    => "POS",
                "szOrderTypeId"     => "RETUR",
                // "dtmDelivery"       => Carbon::parse($flag_nota_date)->format('Y-m-d'),
                "dtmDelivery"       => date('Y-m-d'),
                "szCustId"          => $item->kd_outlet,
                "decAmount"         => -$decDPPTotal,
                "decTax"            => -$decTaxTotal,
                "szCcyId"           => "IDR",
                "szCcyRateId"       => "BI",
                "szVehicleId"       => "",
                "szDriverId"        => "",
                "szSalesId"         => $user_sales,
                "szCarrierId"       => "DC",
                "szRemark"          => "do retur api",
                "szPaymentTermId"   => "{$paymentTermId} HARI",
                "szWarehouseId"     => "GD1",
                "szStockTypeId"     => "Good Stock",
                'docStatus'         => [
                    'bApplied'      => true,
                    'szWorkplaceId' => config('api.workplace_id')
                ],
                "itemList"          => $items,
            ]
        ];
    }

    /**
     * Generates a list of retur items for BOSNET.
     */
    private function generateReturItems($no_retur, &$decDPPTotal, &$decTaxTotal)
    {
        $returItems = $this->getRetur($no_retur);
        $items = [];

        // Loop through each retur item and calculate the amounts
        foreach ($returItems as $returItem) {
            // $decTax = round(((($returItem->nominal_total / $returItem->qty) * $returItem->qty) / config('tax.ppn_factor')) * config('tax.ppn_percentage'));
            // $decAmount = ($returItem->nominal_total / $returItem->qty) * $returItem->qty;
            // $decDPP = round((($returItem->nominal_total / $returItem->qty) * $returItem->qty) / config('tax.ppn_factor'));
            // $decPrice = $returItem->nominal_total / $returItem->qty;

            $decPrice = $returItem->hrg_pcs;
            $qty = $returItem->qty;
            $decDisc =  $returItem->nominal_disc;
            $decDiscPerItem = $decDisc / $qty;
            $decAmount = $decPrice * $qty;
            $decDPP = round(($decAmount - $decDisc) / config('tax.ppn_factor'));
            $decTax = round($decDPP * config('tax.ppn_percentage'));

            // Update totals
            $decDPPTotal += $decDPP;
            $decTaxTotal += $decTax;

            // Add the item to the list
            $items[] = [
                'szOrderItemTypeId'  => "RETUR",
                'szProductId'        => $returItem->part_no,
                'decQty'             => -$returItem->qty,
                'szUomId'            => "PCS",
                'decPrice'           => $decPrice,
                'decDiscount'        => -$decDisc,
                'bTaxable'           => true,
                'decTax'             => -$decTax,
                'decAmount'          => -$decAmount,
                'decDPP'             => -$decDPP,
                'szPaymentType'      => "NON",
            ];
        }

        $decDPPTotal = round($decDPPTotal);
        $decTaxTotal = round($decTaxTotal);

        return $items;
    }

    /**
     * Retrieves the invoice data from KcpInformation.
     *
     * @param string $no_retur
     * @return array The invoice data.
     *
     * @throws \RuntimeException if invoice data cannot be retrieved.
     */
    public function getRetur($no_retur)
    {
        return DB::connection('kcpinformation')
            ->table('trns_retur_details')
            ->where('noretur', $no_retur)
            ->get();
    }

    /**
     * Calculates the payment term in days between billing date and due date.
     *
     * @param string $billingDate
     * @param string $dueDate
     * @return int The number of days between the two dates.
     */
    private function calculatePaymentTerm($billingDate, $dueDate)
    {
        $billingDate = Carbon::parse($billingDate)->format('Y-m-d');
        $dueDate = Carbon::parse($dueDate)->format('Y-m-d');

        $days = Carbon::parse($billingDate)->diffInDays($dueDate);

        return $days;
    }
}
