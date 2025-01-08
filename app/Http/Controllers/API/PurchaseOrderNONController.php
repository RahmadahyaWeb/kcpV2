<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PurchaseOrderNONController extends Controller
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
                'invoiceNon' => 'required|string',
            ]);

            $invoiceNon = $request->invoiceNon;

            // Process and send data to BOSNET API
            $this->processAndSendToBosnet($invoiceNon);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Process the data and prepare the payload to send to BOSNET API.
     *
     * @param string $invoiceNon
     * @throws \Exception
     * @return void
     */
    private function processAndSendToBosnet($invoiceNon)
    {
        try {
            // Retrieve the invoice header data
            $invoiceHeader = DB::table('invoice_non_header')
                ->select(['*'])
                ->where('invoiceNon', $invoiceNon)
                ->first();

            if (!$invoiceHeader) {
                throw new \Exception("Invoice header not found for invoiceNon: {$invoiceNon}");
            }

            // Retrieve the invoice detail data
            $invoiceDetails = DB::table('invoice_non_detail')
                ->select(['*'])
                ->where('invoiceNon', $invoiceNon)
                ->get();

            if ($invoiceDetails->isEmpty()) {
                throw new \Exception("Invoice details not found for invoiceNon: {$invoiceNon}");
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
                DB::table('invoice_non_header')
                    ->where('invoiceNon', $invoiceNon)
                    ->update([
                        'status'        => "BOSNET",
                        'sendToBosnet'  => now()
                    ]);
            } else {
                throw new \Exception('Failed to send data to BOSNET');
            }
        } catch (\Exception $e) {
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
        // return true;

        dd($data);

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

        foreach ($invoiceDetails as $detail) {
            $items[] = [
                'szProductId'          => $detail->materialNumber,
                'decQty'               => $detail->qty,
                'szUomId'              => "PCS",
                'decPrice'             => $detail->amount / $detail->qty,
                'bTaxable'             => true,
                'decDiscount'          => 0,
                'decDiscPercentage'    => 0,
                'decDPP'               => round($detail->amount / config('tax.ppn_factor')),
                'decPPN'               => round(($detail->amount / config('tax.ppn_factor')) * config('tax.ppn_percentage')),
                'decAmount'            => $detail->amount,
                'purchaseItemTypeId'   => "BELI",
                'deliveryList'         => [['qty' => $detail->qty]],
            ];
        }

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
                'szFPo_sId'              => $invoiceHeader->invoiceNon,
                'dtmPO'                  => Carbon::parse($invoiceHeader->billingDocumentDate)->toDateTimeString(),
                'szSupplierId'           => $invoiceHeader->supplierCode,
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
