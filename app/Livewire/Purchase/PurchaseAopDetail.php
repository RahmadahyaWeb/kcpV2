<?php

namespace App\Livewire\Purchase;

use App\Http\Controllers\API\PurchaseOrderAOPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PurchaseAopDetail extends Component
{
    public $target = 'updateFlag, saveFakturPajak, saveProgram, destroyProgram, sendToBosnet, calculate';

    public $fakturPajak;
    public $editingFakturPajak;

    public $classProgram;
    public $styleProgram;

    public $classFakturPajak;
    public $styleFakturPajak;

    public $invoiceAop;
    public $totalAmount;
    public $totalQty;

    public $price;
    public $addDiscount;
    public $extraPlafonDiscount_upload;
    public $extraPlafonDiscount_input;
    public $cashDiscount;
    public $netSales_input;
    public $netSales_upload;
    public $tax_input;
    public $tax_upload;
    public $grandTotal_input;
    public $grandTotal_upload;

    #[Validate('required')]
    public $potonganProgram = '';

    #[Validate('required')]
    public $keteranganProgram = '';

    public $customerTo;
    public $tanggalInvoice;
    public $isAvailable = true;

    public function mount($invoiceAop)
    {
        $this->invoiceAop = $invoiceAop;
    }

    public function sendToBosnet()
    {
        session()->flash('error', 'fitur ditutup sementara');

        // try {
        //     $controller = new PurchaseOrderAOPController();
        //     $controller->sendToBosnet(new Request(['invoiceAop' => $this->invoiceAop]));

        //     session()->flash('success', "Data PO berhasil dikirim!");
        // } catch (\Exception $e) {
        //     session()->flash('error', $e->getMessage());
        // }
    }

    public function openModalFakturPajak()
    {
        $invoice = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->first();

        $this->fakturPajak = $invoice->fakturPajak;

        $this->dispatch('open-modal-faktur-pajak');
    }

    public function closeModalFakturPajak()
    {
        $this->dispatch('hide-modal-faktur-pajak');
    }

    public function openModalProgram()
    {
        $this->dispatch('open-modal-program');
    }

    public function closeModalProgram()
    {
        $this->resetValidation(['potonganProgram', 'keteranganProgram']);

        $this->dispatch('hide-modal-program');
    }

    public function saveProgram()
    {
        $validated = $this->validate();

        $validated['customerTo'] = $this->customerTo;
        $validated['invoiceAop'] = $this->invoiceAop;
        $validated['tanggalInvoice'] = $this->tanggalInvoice;

        DB::table('program_aop')
            ->insert($validated);

        $this->dispatch('hide-modal-program');

        $this->reset('potonganProgram');
        $this->reset('keteranganProgram');
    }

    public function destroyProgram($id)
    {
        DB::table('program_aop')
            ->where('id', $id)
            ->delete();
    }

    public function saveFakturPajak()
    {
        DB::table('invoice_aop_header')
            ->where('invoiceAop', $this->invoiceAop)
            ->update([
                'fakturPajak' => $this->fakturPajak
            ]);

        $this->dispatch('hide-modal-faktur-pajak');
    }

    public function updateFlag($invoiceAop)
    {
        $flag_final = DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)
            ->value('flag_final');

        if ($flag_final == 'Y') {
            $flag_final = 'N';
        } else {
            $flag_final = 'Y';
        }

        try {
            DB::table('invoice_aop_header')
                ->where('invoiceAop', $invoiceAop)
                ->update([
                    'flag_final'  => $flag_final,
                    'final_date'  => now()
                ]);

            session()->flash('success', "Flag $invoiceAop berhasil disimpan.");
        } catch (\Exception $e) {
            session()->flash('error', "Gagal update flag: " . $e->getMessage());
        }
    }

    public function calculate($type)
    {
        $sum_amount = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('amount');

        if ($type == 'floor') {
            $tax = floor($sum_amount * config('tax.ppn_percentage'));
        } else {
            $tax = round($sum_amount * config('tax.ppn_percentage'));
        }

        $grand_total = $tax + $sum_amount;

        DB::table('invoice_aop_header')
            ->where('invoiceAop', $this->invoiceAop)
            ->update([
                'tax' => $tax,
                'grandTotal' => $grand_total
            ]);
    }

    public function saveChanges($materialNumber)
    {
        $total_amount_input = $this->details[$materialNumber]['amount']; // 500.000
        $total_qty = $this->details[$materialNumber]['qty']; // 11

        // Find the correct detail item by $id
        $items = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->where('materialNumber', $materialNumber);

        $price_per_pcs = $total_amount_input / $total_qty; // 45454

        foreach ($items->get() as $value) {
            DB::table('invoice_aop_detail')
                ->where('invoiceAop', $this->invoiceAop)
                ->where('materialNumber', $materialNumber)
                ->where('id', $value->id)
                ->update([
                    'amount' => $price_per_pcs * $value->qty
                ]);
        }
    }

    public $details = [];

    public function render()
    {
        $header = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->first();

        // Mengambil data invoice_aop_detail dari database default
        $details = DB::table('invoice_aop_detail')
            ->select('*')
            ->where('invoiceAop', $this->invoiceAop)
            ->orderBy('materialNumber', 'asc')
            ->get();

        // Mengambil data nm_part dari database 'kcpinformation'
        $partNumbers = $details->pluck('materialNumber'); // Ambil semua materialNumber
        $partData = DB::connection('kcpinformation')
            ->table('mst_part')
            ->whereIn('part_no', $partNumbers)
            ->get(['part_no', 'nm_part']);

        // Gabungkan data nm_part ke dalam $details
        $details = $details->map(function ($item) use ($partData) {
            $nmPart = $partData->firstWhere('part_no', $item->materialNumber);
            $item->nm_part = $nmPart ? $nmPart->nm_part : null; // Menambahkan nm_part ke item

            // Jika tidak ada nm_part, set $this->isAvailable = false
            if (!$item->nm_part) {
                $this->isAvailable = false;
            }

            return $item;
        });

        // Group the details by materialNumber and sum qty and amount
        $detailsGrouped = $details->groupBy('materialNumber')->map(function ($group) {
            return [
                'materialNumber' => $group->first()->materialNumber,
                'nm_part' => $group->first()->nm_part,
                'qty' => $group->sum('qty'),
                'amount' => $group->sum('amount'),
            ];
        });

        // Update the details property to the grouped data
        $this->details = $detailsGrouped;

        // Kalkulasi totalAmount, totalQty, price, addDiscount, extraPlafonDiscount, dll.
        $totalAmount = $detailsGrouped->sum('amount');
        $totalQty = $detailsGrouped->sum('qty');

        // Additional calculations for price, discounts, etc.
        $price = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('price');
        $addDiscount = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('addDiscount');
        $extraPlafonDiscount_upload = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('extraPlafonDiscount');

        $dataProgramAop = DB::table('program_aop')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop);

        $programAop = $dataProgramAop->get();

        $extraPlafonDiscount_input = $dataProgramAop->sum('potonganProgram');

        $this->extraPlafonDiscount_input = $extraPlafonDiscount_input;
        $this->price = $price;
        $this->addDiscount = $addDiscount;
        $this->extraPlafonDiscount_upload = $extraPlafonDiscount_upload;

        $this->netSales_input = $totalAmount - $extraPlafonDiscount_input;
        $this->netSales_upload = $totalAmount - $extraPlafonDiscount_upload;

        $this->tax_input = intval($this->netSales_input * config('tax.ppn_percentage'));
        $this->tax_upload = intval($this->netSales_upload * config('tax.ppn_percentage'));

        $this->grandTotal_input = $this->netSales_input + $this->tax_input;
        $this->grandTotal_upload = $this->netSales_upload + $this->tax_upload;

        $this->totalAmount = $totalAmount;
        $this->totalQty = $totalQty;

        $this->fakturPajak = $header->fakturPajak;
        $this->tanggalInvoice = $header->billingDocumentDate;
        $this->customerTo = $header->customerTo;

        return view('livewire.purchase.purchase-aop-detail', compact('header', 'programAop'));
    }
}
