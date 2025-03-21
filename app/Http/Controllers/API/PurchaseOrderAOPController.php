<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PurchaseOrderAOPController extends Controller
{
    /**
     * Send the purchase order data to BOSNET.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToBosnet(Request $request)
    {
        try {
            // Validate the request input
            $request->validate([
                'invoiceAop' => 'required|string',
            ]);

            $invoiceAop = $request->invoiceAop;

            // Process and send data to BOSNET API
            $this->processAndSendToBosnet($invoiceAop);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Process the data and prepare the payload to send to BOSNET API.
     *
     * @param string $invoiceAop
     * @throws \Exception
     * @return void
     */
    private function processAndSendToBosnet($invoiceAop)
    {
        $log_controller = LogController::class;

        try {
            // Retrieve the invoice header data
            $invoiceHeader = DB::table('invoice_aop_header')
                ->select(['*'])
                ->where('invoiceAop', $invoiceAop)
                ->first();

            if (!$invoiceHeader) {
                throw new \Exception("Invoice header not found for invoiceAop: {$invoiceAop}");
            }

            // Retrieve the invoice detail data
            $invoiceDetails = DB::table('invoice_aop_detail')
                ->select(['*'])
                ->where('invoiceAop', $invoiceAop)
                ->get();

            if ($invoiceDetails->isEmpty()) {
                throw new \Exception("Invoice details not found for invoiceAop: {$invoiceAop}");
            }

            // Prepare the item list
            $items = $this->prepareItems($invoiceDetails);

            // Calculate payment term ID
            $paymentTermId = $this->calculatePaymentTermId($invoiceHeader->billingDocumentDate, $invoiceHeader->tanggalJatuhTempo);

            // Prepare the payload
            $dataToSend = $this->preparePayload($invoiceHeader, $items, $paymentTermId);

            // Send data to BOSNET
            $response = $this->sendDataToBosnet($dataToSend);

            if ($response) {
                // Update the invoice status after successful data sending
                DB::table('invoice_aop_header')
                    ->where('invoiceAop', $invoiceAop)
                    ->update([
                        'flag_po'   => 'Y',
                        'po_date'   => now()
                    ]);

                $log_controller::log_api($dataToSend, '', true);
            } else {
                throw new \Exception('Failed to send data to BOSNET');
            }
        } catch (\Exception $e) {
            $log_controller::log_api($dataToSend, $e->getMessage(), false);

            throw new \Exception("Failed to process and send data to BOSNET: " . $e->getMessage());
        }
    }

    /**
     * Send the data to BOSNET via an HTTP request (e.g., Guzzle or cURL).
     *
     * @param array $data
     * @return bool
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

            $url = 'https://omnichannel.ngecosystem.com/API/OC/NGE/v1/PUR/FPo/SaveFPo';

            $response = Http::withHeaders([
                'Token' => $token
            ])->post($url, $payload);

            $data = $response->json();

            if ($response->successful()) {
                if ($data['statusCode'] == 500) {
                    throw new \Exception($data['statusMessage']);
                } else {
                    return true;
                }
            } else {
                throw new \Exception($data['message']);
            }
        } else {
            throw new \Exception('BOSNET not responding');
        }
    }

    /**
     * Prepare the item list for the payload.
     *
     * @param \Illuminate\Support\Collection $invoiceDetails
     * @return array
     */
    private function prepareItems($invoiceDetails)
    {
        $items = [];

        // KIRIM GRAND TOTAL
        // foreach ($invoiceDetails as $detail) {
        //     // $decAmount = ($detail->price * config('tax.ppn_percentage')) + $detail->price; //12.951.649,83
        //     $decAmount = round(($detail->price * config('tax.ppn_percentage')) + $detail->price); //12.951.649

        //     $items[] = [
        //         'szProductId'          => $detail->materialNumber,
        //         'decQty'               => $detail->qty,
        //         'szUomId'              => "PCS",
        //         'decPrice'             => $decAmount / $detail->qty,
        //         'bTaxable'             => true,
        //         'decDiscount'          => 0,
        //         'decDiscPercentage'    => 0,
        //         'decDPP'               => $detail->price,
        //         'decPPN'               => $detail->price * config('tax.ppn_percentage'),
        //         'decAmount'            => $decAmount,
        //         'purchaseItemTypeId'   => "BELI",
        //         'deliveryList'         => [['qty' => $detail->qty]],
        //     ];
        // }

        $groupedItems = [];

        foreach ($invoiceDetails as $detail) {
            $materialNumber = $detail->materialNumber;
            $decPPN = $detail->price * config('tax.ppn_percentage');
            $decAmount = round(($detail->price * config('tax.ppn_percentage')) + $detail->price);

            if (!isset($groupedItems[$materialNumber])) {
                // Jika belum ada, buat entry baru
                $groupedItems[$materialNumber] = [
                    'szProductId'          => $materialNumber,
                    'decQty'               => $detail->qty,
                    'szUomId'              => "PCS",
                    'decPrice'             => $decAmount / $detail->qty,
                    'bTaxable'             => true,
                    'decDiscount'          => 0,
                    'decDiscPercentage'    => 0,
                    'decDPP'               => $detail->price,
                    'decPPN'               => $decPPN,
                    'decAmount'            => $decAmount,
                    'purchaseItemTypeId'   => "BELI",
                    'deliveryList'         => [['qty' => $detail->qty]],
                ];
            } else {
                // Jika sudah ada, lakukan penjumlahan nilai yang relevan
                $groupedItems[$materialNumber]['decQty'] += $detail->qty;
                $groupedItems[$materialNumber]['decDPP'] += $detail->price;
                $groupedItems[$materialNumber]['decPPN'] += $decPPN;
                $groupedItems[$materialNumber]['decAmount'] += $decAmount;
                $groupedItems[$materialNumber]['decPrice'] = $groupedItems[$materialNumber]['decAmount'] / $groupedItems[$materialNumber]['decQty'];
                $groupedItems[$materialNumber]['deliveryList'][0]['qty'] += $detail->qty;
            }
        }

        // Ubah menjadi indexed array
        $items = array_values($groupedItems);

        return $items;
    }

    /**
     * Calculate the payment term ID based on the billing and due dates.
     *
     * @param string $billingDate
     * @param string $dueDate
     * @return string
     */
    private function calculatePaymentTermId($billingDate, $dueDate)
    {
        $billingDate = Carbon::parse($billingDate);
        $dueDate = Carbon::parse($dueDate);

        $days = $billingDate->diffInDays($dueDate);
        return $days . " HARI";
    }

    /**
     * Prepare the payload for the BOSNET API.
     *
     * @param object $invoiceHeader
     * @param array $items
     * @param string $paymentTermId
     * @return array
     */
    private function preparePayload($invoiceHeader, $items, $paymentTermId)
    {
        return [
            'szAppId' => "BDI.KCP",
            'fPoData' => [
                'szFPo_sId'              => $invoiceHeader->invoiceAop,
                'dtmPO'                  => Carbon::parse($invoiceHeader->billingDocumentDate)->toDateTimeString(),
                'szSupplierId'           => "AOP",
                'bReturn'                => false,
                'szDescription'          => "po api",
                'szCcyId'                => "IDR",
                'paymentTermId'          => $paymentTermId,
                'purchaseTypeId'         => "BELI",
                'szPOReceiptIdForReturn' => "",
                'DocStatus'              => [
                    'bApplied'      => true,
                    'szWorkplaceId' => config('api.workplace_id')
                ],
                'ItemList' => $items,
            ],
        ];
    }
}
