<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReturManualController extends Controller
{
    public function sendToBosnet(Request $request)
    {
        $no_retur = 'RTU-FIXING-01005';
        $no_invoice = 'INV-202502-01005';

        $this->sendPartialDataToBosnet($no_retur, $no_invoice);
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

            Log::info("Berhasil kirim retur invoice: $no_invoice");

            DB::commit(); // Commit transaksi setelah berhasil mengupdate status

            $log_controller::log_api($dataToSend, '', true);
        } catch (\Exception $e) {
            // Tangani error per invoice, update status ke FAILED dan lanjutkan ke invoice berikutnya
            DB::rollBack(); // Rollback transaksi jika ada error

            Log::error('Error occurred for return invoice ' . $no_invoice . ': ' . $e->getMessage());

            $log_controller::log_api($dataToSend, $e->getMessage(), false);

            throw new \Exception('Error occurred for return invoice ' . $no_invoice . ': ' . $e->getMessage());
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
        dd($data);
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
        $items = $this->generateReturItems($no_retur, $decDPPTotal, $decTaxTotal, $item->noinv);

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
                "dtmDelivery"       => Carbon::parse($flag_nota_date)->format('Y-m-d'),
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
    private function generateReturItems($no_retur, &$decDPPTotal, &$decTaxTotal, $no_invoice)
    {
        $returItems = $this->getRetur($no_invoice);
        $items = [];

        // Loop through each retur item and calculate the amounts
        foreach ($returItems as $returItem) {

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
                'decDiscount'        => -$decDiscPerItem,
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
     * @return array The invoice data.
     *
     * @throws \RuntimeException if invoice data cannot be retrieved.
     */
    public function getRetur($no_invoice)
    {
        return DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $no_invoice)
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
