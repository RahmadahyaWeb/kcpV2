<?php

namespace App\Livewire\GoodsReceipt;

use App\Http\Controllers\API\GoodsReceiptNONController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoodsReceiptNonAopDetail extends Component
{
    public $target = 'send_to_bosnet';
    public $invoiceNon;
    public $selectedItems = [];
    public $selectAll = false;
    public $items_with_qty;

    public function mount($invoiceNon)
    {
        $this->invoiceNon = $invoiceNon;
    }

    public function send_to_bosnet()
    {
        try {
            $controller = new GoodsReceiptNONController();
            $controller->sendToBosnet(new Request([
                'invoiceNon'    => $this->invoiceNon,
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

    public function find_qty_in_other_invoice($part_no, $invoiceNon)
    {
        $items = DB::table('invoice_non_detail')
            ->select(['qty', 'invoiceAop'])
            ->where('invoiceNon', $invoiceNon)
            ->where('materialNumber', $part_no)
            ->where('invoiceNon', '<>', $this->invoiceNon)
            ->get();

        return $items;
    }

    public function render()
    {
        $kcp_information = DB::connection('kcpinformation');

        $invoiceNon = $this->invoiceNon;

        $items = DB::table('invoice_non_detail')
            ->where('invoiceNon', $this->invoiceNon)
            ->get();

        // Total items terkirim
        $total_items_terkirim = DB::table('invoice_non_detail')
            ->where('invoiceNon', $this->invoiceNon)
            ->where('status', 'BOSNET')
            ->count();

        // Ambil data intransit dari intransit_details
        $intransit = $kcp_information->table('intransit_details')
            ->where('no_sp_aop', $this->invoiceNon)
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
        $items_with_qty = $items->map(function ($item) use ($grouped_data, $invoiceNon) {
            $material_number = $item->materialNumber;

            // Default nilai qty_terima
            $item->qty_terima = isset($grouped_data[$material_number]) ? $grouped_data[$material_number] : 0;

            // Tambahkan field 'asal_qty' jika qty_terima > qty
            if ($item->qty_terima > $item->qty) {
                $other_invoice_qty = $this->find_qty_in_other_invoice($material_number, $invoiceNon);

                // Format data asal qty
                $item->asal_qty = $other_invoice_qty->map(function ($other) {
                    return [
                        'qty' => $other->qty,
                        'invoice' => $other->invoiceNon,
                    ];
                });
            } else {
                $item->asal_qty = [];
            }

            return $item;
        });

        $this->items_with_qty = $items_with_qty;

        return view('livewire.goods-receipt.goods-receipt-non-aop-detail', compact(
            'items_with_qty',
            'total_items_terkirim'
        ));
    }
}
