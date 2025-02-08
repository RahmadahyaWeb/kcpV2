<?php

namespace App\Livewire\CustomerPayment;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class IndexBonusToko extends Component
{
    public $target = "";

    public function fetch_items()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $from_date = Carbon::now()->startOfMonth();
        $to_date = Carbon::now()->endOfMonth();

        return $kcpinformation->table('trns_inv_header')
            ->join('mst_outlet', 'mst_outlet.kd_outlet', 'trns_inv_header.kd_outlet')
            ->whereRaw('(amount - amount_disc) = 0')
            ->where('trns_inv_header.status', 'C')
            ->where('trns_inv_header.flag_pembayaran_lunas', 'N')
            ->whereBetween('trns_inv_header.crea_date', [$from_date, $to_date])
            ->select([
                'trns_inv_header.*',
                'mst_outlet.nm_outlet'
            ])
            ->orderBy('crea_date', 'desc')
            ->get();
    }

    public function potong_piutang($noinv)
    {
        $kcpinformation = DB::connection('kcpinformation');

        try {
            $kcpinformation->beginTransaction();

            $kcpinformation->table('trns_inv_header')
                ->where('noinv', $noinv)
                ->update([
                    'status' => 'C'
                ]);

            $kcpinformation->commit();

            session()->flash('success', 'Berhasil potong piutang');
        } catch (\Exception $e) {
            $kcpinformation->rollBack();

            session()->flash('error', $e);
        }
    }

    public function render()
    {
        $items = $this->fetch_items();

        return view('livewire.customer-payment.index-bonus-toko', compact(
            'items'
        ));
    }
}
