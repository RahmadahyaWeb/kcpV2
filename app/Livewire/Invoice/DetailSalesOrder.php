<?php

namespace App\Livewire\Invoice;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailSalesOrder extends Component
{
    public $target = "create_invoice";
    public $noso;

    public function mount($noso)
    {
        $this->noso = $noso;
    }

    public function fetch_items($kcpinformation)
    {
        return $kcpinformation->table('trns_so_details as details')
            ->where('details.noso', $this->noso)
            ->where('details.status', "C")
            ->orderBy('details.part_no', 'desc')
            ->lockForUpdate()
            ->get();
    }

    public function fetch_header($kcpinformation)
    {
        return $kcpinformation->table('trns_so_header as header')
            ->join('mst_outlet as outlet', 'outlet.kd_outlet', '=', 'header.kd_outlet')
            ->where('header.noso', $this->noso)
            ->select([
                'header.noso',
                'header.user_sales',
                'header.area_so',
                'outlet.kd_outlet',
                'outlet.nm_outlet',
                'outlet.jth_tempo',
            ])
            ->lockForUpdate()
            ->first();
    }

    public function create_invoice($noso)
    {
        $kcpinformation = DB::connection('kcpinformation');

        try {
            $kcpinformation->beginTransaction();

            $so_header = $this->fetch_header($kcpinformation);
            $so_details = $this->fetch_items($kcpinformation);

            $noinv = $this->generate_invoice_number($kcpinformation);
            $noinv_formatted = 'INV-' . date("Ym") . '-' . $noinv;

            // CREATE INV HEADER
            $this->create_inv_header($kcpinformation, $so_header, $noinv_formatted);

            // CREATE INV DETAILS
            $this->create_inv_details($kcpinformation, $so_details, $noinv_formatted);

            // UPDATE HEADER SO
            $kcpinformation->table('trns_so_header')
                ->where('noso', $so_header->noso)
                ->update([
                    'no_invoice'        => $noinv_formatted,
                    'flag_invoice'      => "Y",
                    'flag_invoice_date' => now()
                ]);

            // COMMIT
            $kcpinformation->commit();

            session()->flash('success', 'Invoice berhasil dibuat silahkan cetak Nota pada list.');
            $this->redirectRoute('invoice.index');

        } catch (\Exception $e) {
            $kcpinformation->rollBack();
            throw $e;
        }
    }

    private function create_inv_header($kcpinformation, $so_header, $noinv_formatted)
    {
        return $kcpinformation->table('trns_inv_header')
            ->insert([
                'noinv'         => $noinv_formatted,
                'area_inv'      => $so_header->area_so,
                'noso'          => $so_header->noso,
                'kd_outlet'     => $so_header->kd_outlet,
                'nm_outlet'     => $so_header->nm_outlet,
                'status'        => 'O',
                'ket_status'    => 'OPEN',
                'user_sales'    => $so_header->user_sales,
                'tgl_jth_tempo' => date('Y-m-d', strtotime("+" . $so_header->jth_tempo . " days")),
                'crea_date'     => now(),
                'crea_by'       => Auth::user()->username
            ]);
    }

    private function create_inv_details($kcpinformation, $so_details, $noinv_formatted)
    {
        $nominal_total = 0;
        $kd_gudang = "GD1";

        foreach ($so_details as $detail) {
            $kcpinformation->table('trns_inv_details')
                ->insert([
                    'noinv'         => $noinv_formatted,
                    'area_inv'      => $detail->area_so,
                    'kd_outlet'     => $detail->kd_outlet,
                    'part_no'       => $detail->part_no,
                    'nm_part'       => $detail->nm_part,
                    'qty'           => $detail->qty,
                    'hrg_pcs'       => $detail->hrg_pcs,
                    'disc'          => $detail->disc,
                    'nominal'       => $detail->nominal_gudang,
                    'nominal_disc'  => $detail->nominal_disc_gudang,
                    'nominal_total' => $detail->nominal_total_gudang,
                    'status'        => 'O',
                    'crea_date'     => now(),
                    'crea_by'       => Auth::user()->username
                ]);

            $nominal_total += $detail->nominal_total_gudang;

            // PENGURANGAN STOCK
            $this->pengurangan_stock($kcpinformation, $detail->qty_gudang, $kd_gudang, $detail->kd_outlet, $detail->part_no, $noinv_formatted, $detail);
        }

        // PENGURANGAN PLAFOND
        $this->pengurangan_plafond($kcpinformation, $detail->kd_outlet, $nominal_total);
    }

    private function pengurangan_stock($kcpinformation, $qty, $kd_gudang, $kd_outlet, $part_no, $noinv_formatted, $detail)
    {
        $data_stock = $this->cek_stock_part($kcpinformation, $kd_gudang, $part_no);

        $updated_stock = $data_stock->stock - $qty;
        $updated_booking_stock = $data_stock->stock_booking - $qty;

        // Update stok utama
        $kcpinformation->table('stock_part')
            ->where('part_no', $part_no)
            ->update([
                'stock'         => $updated_stock,
                'stock_booking' => $updated_booking_stock
            ]);

        // Tentukan rak berdasarkan outlet
        $kondisi_rak = match ($kd_outlet) {
            'V2' => "kd_rak = 'Kon.Assa'",
            'NW' => "kd_rak = 'Kanvasan'",
            default => "kd_rak <> 'Kanvasan' and kd_rak <> 'Kon.Assa'",
        };

        // Ambil data rak
        $id_stock_part = $data_stock->id;
        $data_rak = $kcpinformation->table('stock_part_rak')
            ->where('id_stock_part', $id_stock_part)
            ->where('qty', '>', 0)
            ->whereRaw($kondisi_rak)
            ->get();

        // Proses pengurangan stok per rak
        $inv_qty = $qty; // qty inv

        foreach ($data_rak as $rak) {
            $id_rak = $rak->id;

            if ($rak->qty >= $inv_qty) {
                $new_qty = $rak->qty - $inv_qty;

                $kcpinformation->table('stock_part_rak')
                    ->where('id', $id_rak)
                    ->update([
                        'qty' => $new_qty
                    ]);

                if ($new_qty > 0) {
                    $this->log_stock_rak($kcpinformation, $kd_gudang, $rak->kd_rak, $part_no, $inv_qty, $new_qty, $kd_outlet);
                } else {
                    $this->log_stock_rak($kcpinformation, $kd_gudang, $rak->kd_rak, $part_no, $rak->qty, $new_qty, $kd_outlet);
                }
            } else if ($rak->qty < $inv_qty) {
                $kcpinformation->table('stock_part_rak')
                    ->where('id', $id_rak)
                    ->update([
                        'qty' => 0
                    ]);

                $this->log_stock_rak($kcpinformation, $kd_gudang, $rak->kd_rak, $part_no, $rak->qty, 0, $kd_outlet);
            }

            $cek_stock = $this->cek_stock_part($kcpinformation, $kd_gudang, $part_no);

            // Log Stock
            $kcpinformation->table('trns_log_stock')
                ->insert([
                    'status'        => 'PENJUALAN',
                    'keterangan'    => "PENJUALAN KCP/" . $detail->area_so . "/" . $noinv_formatted,
                    'kd_gudang'     => $cek_stock->kd_gudang,
                    'part_no'       => $part_no,
                    'qty'           => $detail->qty_gudang,
                    'debet'         => 0,
                    'kredit'        => $detail->qty_gudang,
                    'stock'         => $cek_stock->stock,
                    'crea_date'     => now(),
                    'crea_by'       => Auth::user()->username
                ]);
        }
    }

    private function cek_stock_part($kcpinformation, $kd_gudang, $part_no)
    {
        return $kcpinformation->table('stock_part')
            ->where('kd_gudang', $kd_gudang)
            ->where('part_no', $part_no)
            ->first();
    }

    private function generate_invoice_number($kcpinformation)
    {
        $item = $kcpinformation->table('trns_inv_header')
            ->select('noinv')
            ->whereRaw("SUBSTR(noinv, 5, 4) = ?", [date("Y")])
            ->whereRaw("SUBSTR(noinv, 1, 3) = 'INV'")
            ->orderBy('noinv', 'desc')
            ->lockForUpdate()
            ->first();

        $noinv = $item ? substr($item->noinv, 11, 5) : '00000';
        $Vnoinv = $noinv + 1;
        $Vnoinv = str_pad($Vnoinv, 5, '0', STR_PAD_LEFT);

        return $Vnoinv;
    }

    private function log_stock_rak($kcpinformation, $kd_gudang, $kd_rak, $part_no, $qty, $stock_rak, $kd_outlet)
    {
        return $kcpinformation->table('trns_log_stock_rak')
            ->insert([
                'status'        => 'PENJUALAN',
                'keterangan'    => 'PENJUALAN ' . $kd_outlet,
                'kd_gudang'     => $kd_gudang,
                'kd_rak'        => $kd_rak,
                'part_no'       => $part_no,
                'qty'           => $qty,
                'debet'         => 0,
                'kredit'        => $qty,
                'stock_rak'     => $stock_rak,
                'crea_date'     => now(),
                'crea_by'       => Auth::user()->username
            ]);
    }

    private function pengurangan_plafond($kcpinformation, $kd_outlet, $nominal_total)
    {
        return $kcpinformation->table('trns_plafond')
            ->where('kd_outlet', $kd_outlet)
            ->whereRaw('nominal_plafond >= ?', [$nominal_total])
            ->decrement('nominal_plafond', $nominal_total);
    }

    public function render()
    {
        $kcpinformation = DB::connection('kcpinformation');

        $header = $this->fetch_header($kcpinformation);

        $items = $this->fetch_items($kcpinformation);

        return view('livewire.invoice.detail-sales-order', compact(
            'items',
            'header'
        ));
    }
}
