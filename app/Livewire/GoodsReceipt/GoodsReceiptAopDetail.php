<?php

namespace App\Livewire\GoodsReceipt;

use App\Http\Controllers\API\GoodsReceiptAOPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoodsReceiptAopDetail extends Component
{
    public $target = 'send_to_bosnet';
    public $invoiceAop;
    public $selectedItems = [];
    public $selectAll = false;
    public $items_with_qty;

    public function mount($invoiceAop)
    {
        $this->invoiceAop = $invoiceAop;
    }

    public function send_to_bosnet()
    {
        try {
            $controller = new GoodsReceiptAOPController();
            $controller->sendToBosnet(new Request([
                'invoiceAop'    => $this->invoiceAop,
                'items'         => $this->selectedItems,
            ]));

            session()->flash('success', "Data GR berhasil dikirim!");

            $this->selectedItems = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            $this->selectedItems = [];
            $this->selectAll = false;

            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updatedSelectAll($value)
    {
        dd($value);
        if ($value) {
            // Pilih semua item yang memenuhi syarat dan status bukan 'BOSNET'
            $this->selectedItems = collect($this->items_with_qty)
                ->filter(function ($item) {
                    // Periksa apakah qty >= qty_terima - asal_qty dan status bukan BOSNET
                    return $item->qty >= ($item->qty_terima - ($item->asal_qty ? $item->asal_qty->sum('qty') : 0))
                        && $item->status != 'BOSNET';
                })
                ->pluck('materialNumber')
                ->toArray();
        } else {
            // Kosongkan daftar yang dipilih
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems($value)
    {
        $selectedItems[] = $value;
        $this->selectAll = false;
    }

    public function find_qty_in_other_invoice($part_no, $spb)
    {
        $items = DB::table('invoice_aop_detail')
            ->select(['qty', 'invoiceAop']) // Tambahkan invoiceAop
            ->where('SPB', $spb)
            ->where('materialNumber', $part_no)
            ->where('invoiceAop', '<>', $this->invoiceAop)
            ->get();

        return $items;
    }

    public function render()
    {
        $kcp_information = DB::connection('kcpinformation');

        // Ambil SPB dari invoice_aop_header
        $spb = DB::table('invoice_aop_header')
            ->where('invoiceAop', $this->invoiceAop)
            ->value('SPB');

        // Ambil data items dari invoice_aop_detail
        $items = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->get();

        // Total items terkirim
        $total_items_terkirim = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->where('status', 'BOSNET')
            ->count();

        // Ambil data intransit dari intransit_details
        $intransit = $kcp_information->table('intransit_details')
            ->where('no_sp_aop', $spb)
            ->get();

        // Kelompokkan qty_terima berdasarkan part_no
        $grouped_data = [];

        foreach ($intransit as $value) {
            $part_no = $value->part_no;

            if (isset($grouped_data[$part_no])) {
                $grouped_data[$part_no] += $value->qty_terima;
            } else {
                $grouped_data[$part_no] = $value->qty_terima;
            }
        }

        // Proses items dan tambahkan informasi jika qty_terima lebih besar
        $items_with_qty = $items->map(function ($item) use ($grouped_data, $spb) {
            $material_number = $item->materialNumber;

            // Default nilai qty_terima
            $item->qty_terima = isset($grouped_data[$material_number]) ? $grouped_data[$material_number] : 0;

            // Tambahkan field 'asal_qty' jika qty_terima > qty
            if ($item->qty_terima > $item->qty) {
                $other_invoice_qty = $this->find_qty_in_other_invoice($material_number, $spb);

                // Format data asal qty
                $item->asal_qty = $other_invoice_qty->map(function ($other) {
                    return [
                        'qty' => $other->qty,
                        'invoice' => $other->invoiceAop, // Tambahkan invoiceAop
                    ];
                });
            } else {
                $item->asal_qty = [];
            }

            return $item;
        });

        $this->items_with_qty = $items_with_qty;

        return view('livewire.goods-receipt.goods-receipt-aop-detail', compact(
            'items_with_qty',
            'spb',
            'total_items_terkirim'
        ));
    }
}
