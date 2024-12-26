<?php

namespace App\Livewire\Purchase;

use App\Http\Controllers\API\PurchaseOrderNONController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PurchaseNonAopDetail extends Component
{
    public $target = 'addItem, destroyItem, sendToBosnet';
    public $search;
    public $invoiceNon;
    public $customerTo;
    public $total;

    #[Validate('required')]
    public $materialNumber;

    #[Validate('required')]
    public $qty;

    #[Validate('required')]
    public $price;

    #[Validate('required')]
    public $totalFisik = 0;

    public $extraPlafonDiscount = 0;

    public function sendToBosnet()
    {
        try {
            $controller = new PurchaseOrderNONController();
            $controller->sendToBosnet(new Request(['invoiceNon' => $this->invoiceNon]));

            session()->flash('success', "Data PO berhasil dikirim!");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function updatedQty()
    {
        $this->calculateTotal();
    }

    public function updatedPrice()
    {
        $this->calculateTotal();
    }

    public function updatedTotalFisik()
    {
        $this->calculateTotal();
    }

    private function calculateTotal()
    {
        $this->total = (int)$this->qty * (int)$this->price;
        $this->extraPlafonDiscount = (int)$this->total - (int)$this->totalFisik;
    }

    public function addItem()
    {
        $this->validate();

        DB::table('invoice_non_detail')
            ->insert([
                'invoiceNon'            => $this->invoiceNon,
                'SPB'                   => '',
                'customerTo'            => $this->customerTo,
                'materialNumber'        => $this->materialNumber,
                'qty'                   => $this->qty,
                'price'                 => $this->price,
                'extraPlafonDiscount'   => $this->extraPlafonDiscount,
                'amount'                => ($this->price * $this->qty) - $this->extraPlafonDiscount,
                'created_by'            => Auth::user()->username,
                'created_at'            => now(),
                'updated_at'            => now()
            ]);

        $currentHeader = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->first();

        $newQty = $currentHeader->qty + $this->qty;
        $newTotalAmount = $currentHeader->amount + (($this->price * $this->qty) - $this->extraPlafonDiscount);
        $newPrice = $currentHeader->price + $this->price;
        $newExtraPlafonDiscount = $currentHeader->extraPlafonDiscount + $this->extraPlafonDiscount;

        DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->update([
                'qty'                   => $newQty,
                'price'                 => $newPrice,
                'amount'                => $newTotalAmount,
                'extraPlafonDiscount'   => $newExtraPlafonDiscount
            ]);

        session()->flash('success', "Item dengan No Part $this->materialNumber berhasil ditambahkan.");

        $this->reset('materialNumber', 'qty', 'price', 'extraPlafonDiscount', 'totalFisik', 'search');
    }

    public function destroyItem($id)
    {
        $currentHeader = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->first();

        $currentItem = DB::table('invoice_non_detail')
            ->where('id', $id)
            ->first();

        DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->update([
                'qty'                   => $currentHeader->qty - $currentItem->qty,
                'price'                 => $currentHeader->price - $currentItem->price,
                'amount'                => $currentHeader->amount - $currentItem->amount,
                'extraPlafonDiscount'   => $currentHeader->extraPlafonDiscount - $currentItem->extraPlafonDiscount
            ]);

        DB::table('invoice_non_detail')
            ->where('id', $id)
            ->delete();

        session()->flash('success', "Item berhasil berhasil dihapus.");
    }

    public function updateFlag()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $kcpinformation->beginTransaction();
        DB::beginTransaction();

        try {
            DB::table('invoice_non_header')
                ->where('invoiceNon', $this->invoiceNon)
                ->update([
                    'flag_selesai'  => 'Y',
                    'updated_at'    => now()
                ]);

            $items = DB::table('invoice_non_detail')
                ->where('invoiceNon', $this->invoiceNon)
                ->get();

            foreach ($items as $item) {
                DB::connection('kcpinformation')
                    ->table('intransit_details')
                    ->insert([
                        'no_sp_aop'     => $item->invoiceNon,
                        'kd_gudang_aop' => $item->customerTo,
                        'part_no'       => $item->materialNumber,
                        'qty'           => $item->qty,
                        'status'        => 'I',
                        'crea_date'     => now(),
                        'crea_by'       => Auth::user()->username
                    ]);
            }

            // Commit transaksi jika tidak ada error
            DB::commit();
            $kcpinformation->commit();

            // Memberikan feedback kepada user
            session()->flash('success', "Flag $this->invoiceNon berhasil disimpan.");
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();
            $kcpinformation->rollBack();

            // Menangani error, memberikan feedback jika terjadi kesalahan
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function mount($invoiceNon)
    {
        $this->invoiceNon = $invoiceNon;
    }

    public function render()
    {
        $header = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->leftJoin('master_supplier', 'invoice_non_header.supplierCode', '=', 'master_supplier.supplierCode')
            ->first();

        $this->customerTo = $header->customerTo;

        $search = $this->search;

        $nonAopParts = DB::connection('kcpinformation')
            ->table('mst_part')
            ->where('status', 1)
            ->where('supplier', '=', $header->supplierCode)
            ->get();

        $nonAopParts = $nonAopParts->toArray();

        $nonAopParts = array_filter($nonAopParts, function ($item) use ($search) {
            return strpos(strtolower($item->part_no), strtolower($search)) !== false;
        });

        $details = DB::table('invoice_non_detail')
            ->where('invoiceNon', $this->invoiceNon)
            ->get();

        return view('livewire.purchase.purchase-non-aop-detail', compact('header', 'nonAopParts', 'details'));
    }
}
