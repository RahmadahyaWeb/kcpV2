<?php

namespace App\Livewire\Intransit;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailIntransit extends Component
{
    public $target = '';

    public $delivery_note;
    public $selectedItems = [];

    public function mount($delivery_note)
    {
        $this->delivery_note = $delivery_note;
    }

    public function updatedSelectedItems($value)
    {
        $selectedItems[] = $value;
    }

    public function save()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_details')
            ->whereIn('id', $this->selectedItems)
            ->get();

        try {
            $kcpinformation->beginTransaction();

            foreach ($items as $item) {
                if ($item->qty == $item->qty_terima && $item->status == 'I' && $item->kd_rak <> '') {

                    // DATA MASTER PART
                    $data_master_part = $kcpinformation->table('mst_part')
                        ->where('part_no', $item->part_no)
                        ->first();

                    if (!$data_master_part) {
                        $kcpinformation->table('mst_part')
                            ->insert([
                                'part_no' => $item->part_no,
                                'status' => 'Y',
                                'crea_date' => now(),
                                'crea_by' => Auth::user()->username,
                            ]);
                    }

                    // STOCK PART
                    $data_stock_part = $kcpinformation->table('mst_gudang')
                        ->join('stock_part', 'stock_part.kd_gudang', '=', 'mst_gudang.kd_gudang')
                        ->where('mst_gudang.kd_gudang_aop', $item->kd_gudang_aop)
                        ->where('stock_part.part_no', $item->part_no)
                        ->where('stock_part.status', 'A')
                        ->get();

                    dd($data_stock_part);
                }
            }
        } catch (\Exception $e) {
            $kcpinformation->rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $items = $kcpinformation->table('intransit_details')
            ->where('no_sp_aop', $this->delivery_note)
            ->where('status', 'I')
            ->get();

        return view('livewire.intransit.detail-intransit', compact('items'));
    }
}
