<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Controller to handle Sales Order operations, including sending data to BOSNET.
 */
class SalesOrderController extends Controller
{
    /**
     * Send the sales order data to BOSNET.
     *
     * @param Request $request
     * @throws \Exception
     * @return void
     */
    public function sendToBosnet(Request $request)
    {
        $invoice = $request->invoice;

        $log_controller = LogController::class;

        try {
            // Fetch the invoice header
            $header = DB::table('invoice_bosnet')->where('noinv', $invoice)->first();
            if (!$header) {
                throw new \Exception('Invoice not found');
            }

            // Calculate payment term
            $paymentTermId = Carbon::parse($header->crea_date)->startOfDay()
                ->diffInDays(Carbon::parse($header->tgl_jth_tempo)->startOfDay());

            // Initialize totals
            $decDPPTotal = 0;
            $decTaxTotal = 0;

            // Generate invoice items
            $items = $this->generateInvoiceItems($invoice, $decDPPTotal, $decTaxTotal);

            // Prepare the data for sending
            $dataToSend = $this->prepareDataToSend($header, $paymentTermId, $decDPPTotal, $decTaxTotal, $items);

            // Send data to BOSNET
            $response = $this->sendDataToBosnet($dataToSend);

            if ($response) {
                // Update the invoice status after successful data sending
                DB::table('invoice_bosnet')->where('noinv', $invoice)->update([
                    'status_bosnet'     => 'BOSNET',
                    'send_to_bosnet'    => now()
                ]);

                $log_controller::log_api($dataToSend, '', true);
            } else {
                throw new \Exception('Failed to send data to BOSNET');
            }
        } catch (\Exception $e) {
            $log_controller::log_api($dataToSend, $e->getMessage(), false);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Generate the invoice items to send to BOSNET.
     *
     * @param string $invoice
     * @param float $decDPPTotal
     * @param float $decTaxTotal
     * @return array
     */
    private function generateInvoiceItems($invoice, &$decDPPTotal, &$decTaxTotal)
    {
        $items = [];
        $invoiceItems = $this->getInvoice($invoice);

        foreach ($invoiceItems as $value) {
            // Generate individual item details
            $item = $this->generateInvoiceItem($value, $decDPPTotal, $decTaxTotal);
            $items[] = $item;
        }

        // Add support program if any
        $this->addSupportProgram($items, $invoice, $decDPPTotal, $decTaxTotal);

        return $items;
    }

    /**
     * Add support program details to the items list.
     *
     * @param array $items
     * @param string $invoice
     * @param float $decDPPTotal
     * @param float $decTaxTotal
     * @return void
     */
    private function addSupportProgram(array &$items, $invoice, &$decDPPTotal, &$decTaxTotal)
    {
        // Check if there is any support program related to this invoice
        $supportProgram = DB::table('history_bonus_invoice')
            ->where('noinv', $invoice)
            ->sum('nominal_program');

        if ($supportProgram) {
            // Create a new item for the support program
            $item = [
                'szOrderItemTypeId'  => "DISKON",
                'szProductId'        => "",
                'decDiscProcent'     => 0,
                'decQty'             => 0,
                'szUomId'            => "",
                'decPrice'           => 0,
                'decDiscount'        => $supportProgram,
                'bTaxable'           => true,
                'decTax'             => - ($supportProgram - ($supportProgram / config('tax.ppn_factor'))),
                'decAmount'          => 0,
                'decDPP'             => - ($supportProgram / config('tax.ppn_factor')),
                'szPaymentType'      => "TDB",
                'deliveryList'       => [],
                'bonusSourceList'    => [],
            ];

            // Update totals
            $decDPPTotal += $item['decDPP'];
            $decTaxTotal += $item['decTax'];

            // Add item to the items array
            $items[] = $item;
        }
    }

    private function generateInvoiceItem($value, &$decDPPTotal, &$decTaxTotal)
    {
        // Calculate DPP and PPN for the item
        // $unitPrice = $value->nominal_total / $value->qty; // Harga per unit
        // $decPrice = $unitPrice; // Alias untuk harga per unit
        // $decAmount = $value->nominal_total; // Total nominal
        // $decDPP = $unitPrice * $value->qty / config('tax.ppn_factor'); // Dasar Pengenaan Pajak
        // $decTax = $decDPP * config('tax.ppn_percentage'); // PPN

        // $unitPrice = $value->hrg_pcs / config('tax.ppn_factor');
        // $decPrice = $unitPrice;
        // $decDisc = $value->nominal_disc / config('tax.ppn_factor');
        // $decDiscPerItem = $decDisc / $value->qty;
        // $decDPP = round($decPrice * $value->qty - $decDisc);
        // $otherDpp = 11 / 12 * $decDPP;
        // $ppn = 12;
        // $decTax = round($otherDpp * $ppn / 100);
        // $decAmount = $decDPP + $decTax;

        $decPrice = $value->hrg_pcs;
        $qty = $value->qty;
        $decDisc =  $value->nominal_disc;
        $decDiscPerItem = $decDisc / $qty;
        $decAmount = $decPrice * $qty;
        $decDPP = round(($decAmount - $decDisc) / config('tax.ppn_factor'));
        $decTax = round($decDPP * config('tax.ppn_percentage'));

        // Update total DPP and PPN
        $decDPPTotal += $decDPP;
        $decTaxTotal += $decTax;

        $kd_outlet = $this->removeLeadingZero($value->kd_outlet);

        return [
            'szOrderItemTypeId' => "JUAL",
            'szProductId' => $value->part_no,
            'decDiscProcent' => $value->disc,
            'decQty' => $value->qty,
            'szUomId' => "PCS",
            'decPrice' => $decPrice,
            'decDiscount' => $decDisc,
            'bTaxable' => true,
            'decTax' => $decTax,
            'decAmount' => $decAmount,
            'decDPP' => $decDPP,
            'szPaymentType' => "NON",
            'deliveryList' => [
                [
                    'dtmDelivery' => date('Y-m-d H:i:s', strtotime($value->crea_date)),
                    'szCustId' => $kd_outlet,
                    'decQty' => $value->qty,
                    'szFromWpId' => config('api.workplace_id'),
                ],
            ]
        ];
    }

    /**
     * Prepare the complete data structure to send to BOSNET.
     *
     * @param object $header
     * @param int $paymentTermId
     * @param float $decDPPTotal
     * @param float $decTaxTotal
     * @param array $items
     * @return array
     */
    private function prepareDataToSend($header, $paymentTermId, $decDPPTotal, $decTaxTotal, $items)
    {
        if (empty($header->user_sales) || $header->user_sales === '') {
            $user_sales = 'admincounter';
        } else {
            $user_sales = $header->user_sales;
        }

        $kd_outlet = $this->removeLeadingZero($header->kd_outlet);

        return [
            'szAppId' => "BDI.KCP",
            'fSoData' => [
                // 'szFSoId'           => $header->noso,
                'szFSoId'           => "SO-202502-00295-F",
                'szOrderTypeId'     => 'JUAL',
                'dtmOrder'          => date('Y-m-d H:i:s', strtotime($header->crea_date)),
                'szCustId'          => $kd_outlet,
                'decAmount'         => $decDPPTotal,
                'decTax'            => $decTaxTotal,
                'szShipToId'        => $kd_outlet,
                'szStatus'          => "OPE",
                'szCcyId'           => "IDR",
                'szCcyRateId'       => "BI",
                'szSalesId'         => $user_sales,
                'docStatus'         => [
                    'bApplied'      => true,
                    'szWorkplaceId' => config('api.workplace_id')
                ],
                'szPaymentTermId' => $paymentTermId . " HARI",
                'szRemark' => 'api',
                'dtmExpiration' => date('Y-m-d H:i:s', strtotime('+7 days', strtotime($header->crea_date))),
                'itemList' => $items
            ]
        ];
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

            $url = 'https://omnichannel.ngecosystem.com/API/OC/NGE/v1/SD/FSo/SaveFSo';

            $response = Http::withHeaders([
                'Token' => $token
            ])->post($url, $payload);

            $data_json = $response->json();

            if ($response->successful()) {

                if ($data_json['statusCode'] == 500) {
                    throw new \Exception($data_json['statusMessage']);
                } else {
                    return true;
                }
            } else {
                throw new \Exception($data_json['message']);
            }
        } else {
            throw new \Exception('BOSNET not responding');
        }
    }

    /**
     * Fetch the invoice details from KCP system.
     *
     * @param string $invoice
     * @return Collection
     * @throws \Exception
     */
    private function getInvoice($invoice)
    {
        return $details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $invoice)
            ->get();
    }

    private function removeLeadingZero($str)
    {
        // Cek apakah string dimulai dengan angka 0
        if (preg_match('/^0\d/', $str)) {
            // Mengubah menjadi integer, lalu kembali ke string
            return (string)(int)$str;
        }
        // Jika tidak ada leading zero, kembalikan string aslinya
        return $str;
    }
}
