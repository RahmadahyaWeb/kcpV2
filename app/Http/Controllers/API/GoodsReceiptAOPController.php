<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Http;

class GoodsReceiptAOPController extends Controller
{
    /**
     * Send data to Bosnet API after retrieving invoice details.
     *
     * @param Request $request
     * @return void
     */
    public function sendToBosnet(Request $request)
    {
        $log_controller = LogController::class;

        try {
            $kcpapplication = DB::connection('mysql');

            $kcpapplication->beginTransaction();

            $invoiceAop = $request->invoiceAop;
            $items = $request->items;

            // Prepare data to send and items to update
            $itemsToUpdate = [];
            $materialNumberToSave = implode(',', $items); // Concatenate material numbers

            // Retrieve invoice header details
            $invoiceHeader = DB::table('invoice_aop_header')
                ->select('*')
                ->where('invoiceAop', $invoiceAop)
                ->first();

            // Retrieve invoice details
            $invoiceDetails = DB::table('invoice_aop_detail')
                ->select('*')
                ->where('invoiceAop', $invoiceAop)
                ->whereIn('materialNumber', $items)
                ->get();

            // Generate GR number
            $no_gr = $this->generateGRNumber($invoiceHeader->SPB, $invoiceAop, $materialNumberToSave);

            // Calculate payment term
            $paymentTermId = $this->calculatePaymentTerm($invoiceHeader);

            // Prepare item list for Bosnet API request
            $items = $this->prepareItemList($invoiceDetails);

            // Prepare data to send to Bosnet
            $dataToSent = $this->prepareDataToSend($invoiceHeader, $no_gr, $paymentTermId, $items);

            // Collect items to update
            $itemsToUpdate[] = $items;

            // Send data to Bosnet API
            if ($this->sendDataToBosnet($dataToSent)) {
                // Update items status in the database
                $this->updateItemsStatus($invoiceAop, $itemsToUpdate);

                $log_controller::log_api($dataToSent, '', true);

                $kcpapplication->commit();
            }
        } catch (Exception $e) {
            $kcpapplication->rollBack();

            $log_controller::log_api($dataToSent, $e->getMessage(), false);

            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Calculate payment term based on billing and due dates.
     *
     * @param object $invoiceHeader
     * @return string
     */
    private function calculatePaymentTerm($invoiceHeader)
    {
        $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
        $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

        return $billingDate->diffInDays($dueDate) . " HARI";
    }

    /**
     * Prepare item list for Bosnet API request.
     *
     * @param array $details
     * @return array
     */
    private function prepareItemList($details)
    {
        $items = [];
        foreach ($details as $value) {
            $items[] = [
                'szProductId'           => $value->materialNumber,
                'decQty'                => $value->qty,
                'szUomId'               => "PCS",
                'purchaseITemTypeId'    => "BELI"
            ];
        }
        return $items;
    }

    /**
     * Prepare the data to send to Bosnet API.
     *
     * @param object $invoiceHeader
     * @param string $no_gr
     * @param string $paymentTermId
     * @param array $items
     * @return array
     */
    private function prepareDataToSend($invoiceHeader, $no_gr, $paymentTermId, $items)
    {
        return [
            'szAppId'                     => "BDI.KCP",
            'fPoReceiptData'    => [
                'szPoId'                    => $invoiceHeader->invoiceAop,
                'szFPoReceipt_sId'          => $no_gr,
                'dtmReceipt'                => now(),
                // 'szRefDn'                   => $invoiceHeader->SPB,
                'szRefDn'                   => "13684,13685,13686,13687,13690",
                'dtmRefDn'                  => $invoiceHeader->billingDocumentDate,
                'szWarehouseId'             => "GD1",
                'szStockTypeId'             => "Good Stock",
                'paymentTermId'             => $paymentTermId,
                'szWorkplaceId'             => config('api.workplace_id'),
                'szCarrierId'               => "DC",
                'szVehicleId'               => "",
                'szDriverId'                => "",
                'szVehicleNumber'           => "default-kcp",
                'szDriverNm'                => "default-kcp",
                'szDescription'             => "api",
                'DocStatus'                 => [
                    'bApplied'      => true,
                    'szWorkplaceId' => config('api.workplace_id')
                ],
                'ItemList'                  => $items
            ]
        ];
    }

    /**
     * Update items status in the database.
     *
     * @param string $invoiceAop
     * @param array $itemsToUpdate
     * @return void
     */
    private function updateItemsStatus($invoiceAop, $itemsToUpdate)
    {
        foreach ($itemsToUpdate as $items) {
            foreach ($items as $item) {
                $materialNumber = $item['szProductId'];

                DB::table('invoice_aop_detail')
                    ->where('invoiceAop', $invoiceAop)
                    ->where('materialNumber', $materialNumber)
                    ->update([
                        'status'        => 'BOSNET',
                        'gr_date'       => now()
                    ]);
            }
        }
    }

    /**
     * Generate GR (Goods Receipt) number based on SPB and items.
     *
     * @param string $spb
     * @param string $items
     * @return string
     */
    public function generateGRNumber($spb, $invoiceAop, $items)
    {
        try {
            $tahun = Carbon::now()->year;
            $bulan = Carbon::now()->month;

            // Get the last GR number from the database
            $lastGR = DB::table('goods_receipt')
                ->orderBy('created_at', 'desc')
                ->first();

            // Generate the new GR number
            $nomor_urut = $lastGR ? (int)substr($lastGR->no_gr, -4) + 1 : 1;
            $no_gr = 'GR-AOP-' . $tahun . $bulan . '-' . str_pad($nomor_urut, 4, '0', STR_PAD_LEFT);

            // Insert the new GR record into the database
            DB::table('goods_receipt')->insert([
                'no_gr'         => $no_gr,
                'invoice'       => $invoiceAop,
                'spb'           => $spb,
                'items'         => $items,
                'created_at'    => now()
            ]);

            return $no_gr;
        } catch (Exception $e) {
            throw new Exception("Failed to generate GR number: " . $e->getMessage());
        }
    }

    /**
     * Send data to Bosnet API.
     *
     * @param array $dataToSent
     * @return bool
     */
    public function sendDataToBosnet($data)
    {
        $credential = TokenBosnetController::signInForSecretKey();

        if (isset($credential['status'])) {
            throw new \Exception('Connection refused by BOSNET');
        }

        if ($credential && $credential['szStatus'] == 'READY') {
            $token = $credential['szToken'];

            $payload = $data;

            $url = 'https://omnichannel.ngecosystem.com/API/OC/NGE/v1/PUR/FPoReceipt/SaveFPoReceipt';

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
}
