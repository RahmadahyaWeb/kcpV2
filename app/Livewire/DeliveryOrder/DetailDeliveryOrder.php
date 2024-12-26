<?php

namespace App\Livewire\DeliveryOrder;

use App\Http\Controllers\API\DeliveryOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailDeliveryOrder extends Component
{
    public $target = 'sendToBosnet, terima_lkh, terimaSJ';
    public $no_lkh;
    public $header;
    public $items;
    public $ready_to_sent = false;
    public $status = false;
    public $terima_lkh_status = false;

    /**
     * Initialize component and authenticate to fetch token.
     *
     * @param string $lkh
     */
    public function mount(string $no_lkh)
    {
        $this->no_lkh = $no_lkh;
    }

    /**
     * Send the sales order to Bosnet.
     *
     * @return void
     */
    public function sendToBosnet(): void
    {
        try {
            $controller = new DeliveryOrderController();
            $controller->sendToBosnet(new Request([
                'lkh'    => $this->no_lkh,
                'items'  => $this->items,
                'header' => $this->header,
            ]));

            session()->flash('success', "Data DO berhasil dikirim!");
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public static function cek_status($noinv)
    {
        return DB::table('invoice_bosnet')
            ->where('noinv', $noinv)
            ->first('status_bosnet');
    }

    public function terimaSJ($id)
    {
        DB::connection('kcpinformation')
            ->table('trns_lkh_details')
            ->where('id', $id)
            ->update([
                'terima_ar' => 'Y'
            ]);

        session()->flash('success', 'Berhasil terima SJ');
    }

    public function terima_lkh($no_lkh)
    {
        DB::connection('kcpinformation')
            ->table('trns_lkh_details')
            ->where('no_lkh', $no_lkh)
            ->update([
                'terima_ar' => 'Y'
            ]);

        DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->where('no_lkh', $no_lkh)
            ->update([
                'terima_ar' => 'Y'
            ]);

        session()->flash('success', 'Berhasil terima LKH');

        $this->redirect('/delivery-order');
    }

    public function render()
    {
        $this->items = DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->select([
                'trns_lkh_header.no_lkh',
                'trns_so_header.noso',
                'trns_so_header.kd_outlet',
                'trns_so_header.nm_outlet',
                'trns_inv_header.noinv',
                'trns_lkh_header.crea_date',
                'trns_inv_header.user_sales',
                'trns_lkh_details.no_packingsheet',
                'trns_lkh_details.koli',
                'trns_lkh_details.no_urut',
                'trns_lkh_details.expedisi',
                'trns_lkh_details.id',
                'trns_lkh_details.terima_ar'
            ])
            ->join('trns_lkh_details', 'trns_lkh_details.no_lkh', '=', 'trns_lkh_header.no_lkh')
            ->join('trns_so_header', 'trns_so_header.no_packingsheet', '=', 'trns_lkh_details.no_packingsheet')
            ->join('trns_inv_header', 'trns_inv_header.noso', '=', 'trns_so_header.noso')
            ->where('trns_lkh_header.no_lkh', $this->no_lkh)
            ->get();

        $count_status_kcp = 0;
        $count_status_bosnet = 0;
        $count_terima_ar = 0;
        foreach ($this->items as $value) {
            $status = DetailDeliveryOrder::cek_status($value->noinv) ? DetailDeliveryOrder::cek_status($value->noinv)->status_bosnet : null;

            if ($value->terima_ar == 'Y') {
                $count_terima_ar += 1;
            }

            if (isset($status)) {
                if ($status == 'KCP') {
                    $count_status_kcp += 1;
                } else {
                    $count_status_bosnet += 1;
                }
            } else {
                $count_status_kcp += 1;
            }
        }

        if ($count_terima_ar == count($this->items)) {
            $this->terima_lkh_status = true;
        }

        if ($count_status_bosnet == count($this->items) && $count_terima_ar == count($this->items)) {
            $this->ready_to_sent = true;
        }

        $this->header = DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->where('no_lkh', $this->no_lkh)
            ->first();

        $total_status_sukses =   DB::table('do_bosnet')
            ->where('no_lkh', $this->no_lkh)
            ->where('status_bosnet', 'BOSNET')
            ->count();

        if ($total_status_sukses == count($this->items)) {
            $this->status = true;
        }

        return view('livewire.delivery-order.detail-delivery-order');
    }
}
