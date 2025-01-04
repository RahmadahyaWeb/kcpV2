<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DeliveryOrderController extends Controller
{
    /**
     * Main function to send the delivery order to BOSNET.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Exception if sending data to BOSNET fails.
     */
    public function sendToBosnet(Request $request)
    {
        $lkh = $request->lkh;
        $items = $request->items;
        $header = $request->header;

        foreach ($items as $value) {
            $data = DB::connection('kcpinformation')
                ->table('trns_inv_header')
                ->where('noinv', $value->noinv)
                ->first();

            $value->tgl_jth_tempo = $data->tgl_jth_tempo;
            $value->crea_date = date('Y-m-d', strtotime($data->crea_date));
        }

        try {
            DB::beginTransaction();

            // Iterate through items and send data to BOSNET
            foreach ($items as $item) {
                $dataToSend = $this->prepareBosnetData($item, $header);

                // Send data to BOSNET and check if the response is successful
                $response = $this->sendDataToBosnet($dataToSend);

                if ($response) {
                    DB::table('do_bosnet')
                        ->where('no_lkh', $lkh)
                        ->insert([
                            'no_lkh'            => $lkh,
                            'noinv'             => $item->noinv,
                            'status_bosnet'     => 'BOSNET',
                            'send_to_bosnet'    => now(),
                        ]);
                } else {
                    DB::rollBack();
                    throw new \Exception('Failed to send data to BOSNET.');
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

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
     * Prepares the data to be sent to BOSNET.
     */
    private function prepareBosnetData($item, $header)
    {
        $decDPPTotal = 0;
        $decTaxTotal = 0;

        // Calculate the payment term
        $paymentTermId = $this->calculatePaymentTerm($item->crea_date, $item->tgl_jth_tempo);

        // Generate the list of sales order items
        $items = $this->generateSalesOrderItems($item, $decDPPTotal, $decTaxTotal, $header);

        return [
            "szAppId"               => "BDI.KCP",
            "fdoData"   => [
                "szDoId"            => $item->noinv,
                "szFSoId"           => $item->noso,
                "szLogisticType"    => "INV",
                "szOrderTypeId"     => "JUAL",
                "dtmDelivery"       => Carbon::parse($item->crea_date)->toDateTimeString(),
                "szCustId"          => $item->kd_outlet,
                "decAmount"         => $decDPPTotal,
                "decTax"            => $decTaxTotal,
                "szCcyId"           => "IDR",
                "szCcyRateId"       => "BI",
                "szVehicleId"       => $header->plat_mobil,
                "szDriverId"        => $header->driver,
                "szSalesId"         => $item->user_sales,
                "szCarrierId"       => "",
                "szRemark"          => "api",
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
     * Calculates the payment term in days between billing date and due date.
     *
     * @param string $billingDate
     * @param string $dueDate
     * @return int The number of days between the two dates.
     */
    private function calculatePaymentTerm($billingDate, $dueDate)
    {
        return Carbon::parse($billingDate)->diffInDays(Carbon::parse($dueDate));
    }

    /**
     * Generates a list of sales order items for BOSNET.
     */
    private function generateSalesOrderItems($item, &$decDPPTotal, &$decTaxTotal, $header)
    {
        $salesOrderItems = $this->getInvoice($item->noinv);
        $items = [];

        // Loop through each sales order item and calculate the amounts
        foreach ($salesOrderItems as $orderItem) {
            $decTax = ((($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty) / config('tax.ppn_factor')) * config('tax.ppn_percentage');
            $decAmount = ($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty;
            $decDPP = (($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty) / config('tax.ppn_factor');
            $decPrice = $orderItem->nominal_total / $orderItem->qty;

            // Update totals
            $decDPPTotal += $decDPP;
            $decTaxTotal += $decTax;

            // Add the item to the list
            $items[] = [
                'szOrderItemTypeId'  => "JUAL",
                'szProductId'        => $orderItem->part_no,
                'decQty'             => $orderItem->qty,
                'szUomId'            => "PCS",
                'decPrice'           => $decPrice,
                'decDiscount'        => 0,
                'bTaxable'           => true,
                'decTax'             => $decTax,
                'decAmount'          => $decAmount,
                'decDPP'             => $decDPP,
                'szPaymentType'      => "NON",
            ];
        }

        // Add support program if applicable
        $this->addSupportProgram($items, $item->noinv, $decDPPTotal, $decTaxTotal);

        return $items;
    }

    /**
     * Retrieves the invoice data from KcpInformation.
     *
     * @param string $invoiceNumber
     * @return array The invoice data.
     *
     * @throws \RuntimeException if invoice data cannot be retrieved.
     */
    public function getInvoice($invoiceNumber)
    {
        return DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $invoiceNumber)
            ->get();
    }

    /**
     * Adds support program to the items list if applicable.
     *
     * @param array $items Reference to the items array
     * @param string $invoiceNumber
     * @param float $decDPPTotal Reference to the total DPP
     * @param float $decTaxTotal Reference to the total tax
     */
    private function addSupportProgram(array &$items, $invoiceNumber, &$decDPPTotal, &$decTaxTotal)
    {
        $supportProgram = DB::table('history_bonus_invoice')
            ->where('noinv', $invoiceNumber)
            ->sum('nominal_program');

        if ($supportProgram) {
            $item = [
                'szOrderItemTypeId'  => "DISKON",
                'szProductId'        => "",
                'decQty'             => 0,
                'szUomId'            => "",
                'decPrice'           => 0,
                'decDiscount'        => $supportProgram,
                'bTaxable'           => true,
                'decTax'             => - ($supportProgram - ($supportProgram / config('tax.ppn_factor'))),
                'decAmount'          => 0,
                'decDPP'             => - ($supportProgram / config('tax.ppn_factor')),
                'szPaymentType'      => "TDB",
            ];

            // Update totals
            $decDPPTotal += $item['decDPP'];
            $decTaxTotal += $item['decTax'];

            $items[] = $item;
        }
    }
}
