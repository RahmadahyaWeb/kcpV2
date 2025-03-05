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
                dd($item);
                if ($item->qty == $item->qty_terima && $item->status == 'I' && $item->kd_rak <> '') {
                    // KODE GUDANG
                    $kd_gudang = ($item->kd_gudang_aop == 'KCP01001') ? 'GD1' : 'GD2';

                    // USER
                    $user = Auth::user()->username;

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
                                'crea_by' => $user,
                            ]);
                    }

                    // STOCK PART
                    $data_stock_part = $kcpinformation->table('mst_gudang')
                        ->join('stock_part', 'stock_part.kd_gudang', '=', 'mst_gudang.kd_gudang')
                        ->where('mst_gudang.kd_gudang_aop', $item->kd_gudang_aop)
                        ->where('stock_part.part_no', $item->part_no)
                        ->where('stock_part.status', 'A')
                        ->first();

                    if (!$data_stock_part) {
                        $kcpinformation->table('stock_part')
                            ->insert([
                                'kd_gudang' => $kd_gudang,
                                'part_no' => $item->part_no,
                                'stock' => $item->qty,
                                'status' => 'A',
                                'ket_status' => 'READY',
                                'crea_date' => now(),
                                'crea_by' => $user
                            ]);
                    } else {
                        $stock_update = $data_stock_part->stock + $item->qty;

                        $kcpinformation->table('stock_part')
                            ->where('part_no', $item->part_no)
                            ->where('kd_gudang', $kd_gudang)
                            ->update(['stock' => $stock_update]);
                    }

                    $id_stock_part = $kcpinformation->table('stock_part')
                        ->where('part_no', $item->part_no)
                        ->where('kd_gudang', $kd_gudang)
                        ->first();

                    // HANDLE RAK
                    $this->handleStockRak($kcpinformation, $id_stock_part->id, $item, $kd_gudang, $user);

                    // UPDATE INTRANSIT DETAIL
                    $kcpinformation->table('intransit_details')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'T',
                            'modi_date' => now(),
                            'mode_by' => $user
                        ]);
                }
            }

            // $kcpinformation->commit();
        } catch (\Exception $e) {
            $kcpinformation->rollBack();
            session()->flash('error', $e->getMessage());
        }
    }

    private function handleStockRak($kcpinformation, $id_stock_part, $item, $kd_gudang, $user)
    {
        $data_rak = $kcpinformation->table('stock_part_rak')
            ->where('id_stock_part', $id_stock_part)
            ->where('kd_rak', $item->kd_rak)
            ->first();

        if (!$data_rak) {
            $kcpinformation->table('stock_part_rak')
                ->insert([
                    'id_stock_part' => $id_stock_part,
                    'kd_rak' => $item->kd_rak,
                    'qty' => $item->qty,
                    'status' => 'Y',
                    'crea_date' => now(),
                    'crea_by' => $user
                ]);
        } else {
            $id_rak = $data_rak->id;
            $qty_rak = $data_rak->qty + $item->qty;
            $kcpinformation->table('stock_part_rak')
                ->where('id', $id_rak)
                ->update(['qty' => $qty_rak]);
        }

        // LOG STOCK RAK
        $kcpinformation->table('trns_log_stock_rak')
            ->insert([
                'status' => 'PENERIMAAN',
                'keterangan' => "PENERIMAAN " . $item->no_sp_aop . " dengan P/S " . $item->no_packingsheet,
                'kd_gudang' => $kd_gudang,
                'kd_rak' => $item->kd_rak,
                'part_no' => $item->part_no,
                'qty' => $item->qty,
                'debet' => $item->qty,
                'kredit' => 0,
                'stock_rak' => isset($qty_rak) ? $qty_rak : $item->qty,
                'crea_date' => now(),
                'crea_by' => $user
            ]);
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
