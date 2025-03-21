<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function sendToBosnet()
    {
        $invoices = DB::table('invoice_bosnet')
            ->where(function ($query) {
                $query->where('status_invoice', 'KCP')
                    ->orWhere('status_invoice', 'FAILED');
            })
            ->where('status_bosnet', 'BOSNET')
            ->get();

        if ($invoices->isEmpty()) {
            Log::info('Tidak ada invoice.');
        } else {
            $log_controller = LogController::class;

            foreach ($invoices as $invoice) {
                try {
                    // Mulai transaksi untuk invoice
                    DB::beginTransaction();

                    $item = DB::connection('kcpinformation')
                        ->table('trns_inv_header')
                        ->where('noinv', $invoice->noinv)
                        ->first();

                    // Persiapkan data untuk dikirim ke BOSNET
                    $dataToSend = $this->prepareBosnetData($item);

                    // Kirim data ke BOSNET dan tangani response
                    $this->sendDataToBosnet($dataToSend);

                    // Update status invoice jika berhasil
                    DB::table('invoice_bosnet')
                        ->where('noinv', $invoice->noinv)
                        ->update([
                            'status_invoice' => 'BOSNET',
                            'invoice_send_to_bosnet' => now()
                        ]);

                    Log::info("Berhasil kirim invoice: $invoice->noinv");

                    DB::commit(); // Commit transaksi setelah berhasil mengupdate status

                    $log_controller::log_api($dataToSend, '', true);
                } catch (\Exception $e) {
                    // Tangani error per invoice, update status ke FAILED dan lanjutkan ke invoice berikutnya
                    DB::rollBack(); // Rollback transaksi jika ada error

                    DB::table('invoice_bosnet')
                        ->where('noinv', $invoice->noinv)
                        ->update([
                            'status_invoice' => 'FAILED',
                            'invoice_send_to_bosnet' => now()
                        ]);

                    Log::error('Error occurred for invoice ' . $invoice->noinv . ': ' . $e->getMessage());

                    $log_controller::log_api($dataToSend, $e->getMessage(), false);

                    continue; // Lanjutkan ke invoice berikutnya
                }
            }
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
     * Prepares the data to be sent to BOSNET.
     */
    private function prepareBosnetData($item)
    {
        $decDPPTotal = 0;
        $decTaxTotal = 0;

        // Calculate the payment term
        $paymentTermId = $this->calculatePaymentTerm($item->crea_date, $item->tgl_jth_tempo);

        // Generate the list of sales order items
        $items = $this->generateSalesOrderItems($item, $decDPPTotal, $decTaxTotal);

        if (empty($item->user_sales) || $item->user_sales === '') {
            $user_sales = 'admincounter';
        } else {
            $user_sales = $item->user_sales;
        }

        $kd_outlet = $this->removeLeadingZero($item->kd_outlet);

        return [
            "szAppId"               => "BDI.KCP",
            "fdoData"   => [
                "szDoId"            => $item->noinv,
                "szFSoId"           => $item->noso,
                "szLogisticType"    => "POS",
                "szOrderTypeId"     => "JUAL",
                "dtmDelivery"       => now(),
                "szCustId"          => $kd_outlet,
                "decAmount"         => $decDPPTotal,
                "decTax"            => $decTaxTotal,
                "szCcyId"           => "IDR",
                "szCcyRateId"       => "BI",
                "szVehicleId"       => "",
                "szDriverId"        => "",
                "szSalesId"         => $user_sales,
                "szCarrierId"       => "DC",
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
     * Generates a list of sales order items for BOSNET.
     */
    private function generateSalesOrderItems($item, &$decDPPTotal, &$decTaxTotal)
    {
        $salesOrderItems = $this->getInvoice($item->noinv);
        $items = [];

        // Loop through each sales order item and calculate the amounts
        foreach ($salesOrderItems as $orderItem) {
            // $decTax = round(((($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty) / config('tax.ppn_factor')) * config('tax.ppn_percentage'));
            // $decAmount = ($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty;
            // $decDPP = round((($orderItem->nominal_total / $orderItem->qty) * $orderItem->qty) / config('tax.ppn_factor'));
            // $decPrice = $orderItem->nominal_total / $orderItem->qty;

            // $unitPrice = $orderItem->hrg_pcs / config('tax.ppn_factor');
            // $decPrice = $unitPrice;
            // $decDisc = $orderItem->nominal_disc / config('tax.ppn_factor');
            // $decDiscPerItem = $decDisc / $orderItem->qty;
            // $decDPP = round($decPrice * $orderItem->qty - $decDisc);
            // $otherDpp = 11 / 12 * $decDPP;
            // $ppn = 12;
            // $decTax = round($otherDpp * $ppn / 100);
            // $decAmount = $decDPP + $decTax;

            $decPrice = $orderItem->hrg_pcs;
            $qty = $orderItem->qty;
            $disc = $orderItem->disc;

            $decAmount = $decPrice * $qty;

            $decDisc =  round($decAmount * ($disc / 100));
            $decDiscPerItem = $decDisc / $qty;

            $decDPP = round(($decAmount - $decDisc) / config('tax.ppn_factor'));
            $decTax = round($decDPP * config('tax.ppn_percentage'));

            // Update totals
            $decDPPTotal += $decDPP;
            $decTaxTotal += $decTax;

            // Add the item to the list
            $items[] = [
                'szOrderItemTypeId'  => "JUAL",
                'szProductId'        => $orderItem->part_no,
                'decQty'             => $qty,
                'szUomId'            => "PCS",
                'decPrice'           => $decPrice,
                'decDiscount'        => $decDisc,
                'bTaxable'           => true,
                'decTax'             => $decTax,
                'decAmount'          => $decAmount,
                'decDPP'             => $decDPP,
                'szPaymentType'      => "NON",
            ];
        }

        // Add support program if applicable
        $this->addSupportProgram($items, $item->noinv, $decDPPTotal, $decTaxTotal);

        $decDPPTotal = round($decDPPTotal);
        $decTaxTotal = round($decTaxTotal);

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
                'decTax'             => -round(($supportProgram - ($supportProgram / config('tax.ppn_factor')))),
                'decAmount'          => 0,
                'decDPP'             => -round(($supportProgram / config('tax.ppn_factor'))),
                'szPaymentType'      => "TDB",
            ];

            // Update totals
            $decDPPTotal += $item['decDPP'];
            $decTaxTotal += $item['decTax'];

            $items[] = $item;
        }
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

    private function removeLeadingZero($str) {
        // Cek apakah string dimulai dengan angka 0
        if (preg_match('/^0\d/', $str)) {
            // Mengubah menjadi integer, lalu kembali ke string
            return (string)(int)$str;
        }
        // Jika tidak ada leading zero, kembalikan string aslinya
        return $str;
    }
}
