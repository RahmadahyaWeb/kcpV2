<?php

namespace App\Livewire\CustomerPayment;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailCustomerPayment extends Component
{
    public $no_piutang;
    public $model;
    public $target = 'potong_piutang';
    public $customer_payment_header;
    public $customer_payment_details;

    public function mount($no_piutang)
    {
        $this->no_piutang = $no_piutang;
        $this->target = 'potong_piutang';
        $this->model = DetailCustomerPayment::class;

        $kcpapplication = DB::connection('mysql');

        $this->customer_payment_header = $kcpapplication
            ->table('customer_payment_header')
            ->where('no_piutang', $this->no_piutang)
            ->first();

        $this->customer_payment_details = $kcpapplication
            ->table('customer_payment_details')
            ->where('no_piutang', $this->no_piutang)
            ->get();
    }

    public static function get_nominal_invoice($no_invoice)
    {
        $no_invoice_formatted = self::formatInvoiceNumber($no_invoice);

        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_header')
            ->where('noinv', $no_invoice_formatted)
            ->value('amount_total');
    }

    private function cek_invoice($no_invoice)
    {
        $no_invoice_formatted = self::formatInvoiceNumber($no_invoice);

        $kcpinformation = DB::connection('kcpinformation');

        return $kcpinformation->table('trns_inv_header')
            ->where('noinv', $no_invoice_formatted)
            ->first();
    }

    private static function formatInvoiceNumber($no_invoice)
    {
        if (strpos($no_invoice, '/') !== false) {
            return str_replace('/', '-', $no_invoice);
        } else {
            return $no_invoice;
        }
    }

    public function potong_piutang()
    {
        $jumlah_details = count($this->customer_payment_details);
        $pass = 0;

        foreach ($this->customer_payment_details as $value) {
            $cek_invoice = $this->cek_invoice($value->noinv);

            if (is_null($cek_invoice)) {
                session()->flash('error', 'Invoice tidak ditemukan.');
                return;
            }

            $nominal_invoice = DetailCustomerPayment::get_nominal_invoice($value->noinv);
            $nominal_potong = $value->nominal;

            if ($nominal_invoice >= $nominal_potong) {
                $pass += 1;
            }
        }

        if (!$jumlah_details == $pass) {
            session()->flash('error', 'Nominal pembayaran tidak sesuai dengan nominal invoice.');
            return;
        }

        if ($jumlah_details == $pass) {
            try {
                $kcpapplication = DB::connection('mysql');
                $kcpinformation = DB::connection('kcpinformation');

                $kcpapplication->beginTransaction();
                $kcpinformation->beginTransaction();

                $kcpinformation->table('trns_pembayaran_piutang_header')
                    ->insert([
                        'nopiutang'         => $this->customer_payment_header->no_piutang,
                        'area_piutang'      => $this->customer_payment_header->area_piutang,
                        'kd_outlet'         => $this->customer_payment_header->kd_outlet,
                        'nm_outlet'         => $this->customer_payment_header->nm_outlet,
                        'nominal_potong'    => $this->customer_payment_header->nominal_potong,
                        'pembayaran_via'    => $this->customer_payment_header->pembayaran_via,
                        'no_bg'             => $this->customer_payment_header->no_bg,
                        'jth_tempo_bg'      => $this->customer_payment_header->tgl_jth_tempo_bg,
                        'bank'              => $this->customer_payment_details[0]->bank,
                        'status'            => 'C',
                        'no_bg'             => $this->customer_payment_header->no_bg,
                        'crea_date'         => $this->customer_payment_header->crea_date,
                        'crea_by'           => $this->customer_payment_header->crea_by,
                    ]);

                foreach ($this->customer_payment_details as $value) {
                    $kcpinformation->table('trns_pembayaran_piutang')
                        ->insert([
                            'noinv'             => $value->noinv,
                            'nopiutang'         => $value->no_piutang,
                            'kd_outlet'         => $value->kd_outlet,
                            'nm_outlet'         => $value->nm_outlet,
                            'nominal'           => $value->nominal,
                            'keterangan'        => $value->keterangan,
                            'pembayaran_via'    => $value->pembayaran_via,
                            'no_bg'             => $value->no_bg,
                            'jth_tempo_bg'      => $value->tgl_jth_tempo_bg,
                            'status'            => 'C',
                            'crea_date'         => $value->crea_date,
                            'crea_by'           => $value->crea_by,
                        ]);

                    // FLAG PEMBAYARAN LUNAS
                    $paymentSummary = DB::connection('kcpinformation')->table('trns_pembayaran_piutang AS pay')
                        ->selectRaw('pay.noinv, SUM(pay.nominal) AS total_pembayaran')
                        ->where('pay.noinv', $value->noinv)
                        ->where('pay.status', '<>', 'B')
                        ->groupBy('pay.noinv');

                    $returnSummary = DB::connection('kcpinformation')->table('trns_retur_details AS ret_detail')
                        ->selectRaw('ret_detail.noinv, SUM(ret_detail.nominal_total) AS total_retur')
                        ->leftJoin('trns_retur_header AS ret_header', 'ret_detail.noretur', '=', 'ret_header.noretur')
                        ->where('ret_detail.noinv', $value->noinv)
                        ->groupBy('ret_detail.noinv');

                    $result = DB::connection('kcpinformation')->table('trns_inv_header AS inv')
                        ->select([
                            'inv.noinv AS nomor_invoice',
                            'inv.area_inv AS area_invoice',
                            'inv.kd_outlet AS kode_outlet',
                            'inv.nm_outlet AS nama_outlet',
                            'inv.crea_date AS tanggal_dibuat',
                            'inv.tgl_jth_tempo AS tanggal_jatuh_tempo',
                            'inv.amount_total AS total_nominal_invoice',
                            DB::raw('payment_summary.total_pembayaran'),
                            DB::raw('return_summary.total_retur')
                        ])
                        ->where('inv.noinv', $value->noinv)
                        ->leftJoinSub($paymentSummary, 'payment_summary', 'inv.noinv', '=', 'payment_summary.noinv')
                        ->leftJoinSub($returnSummary, 'return_summary', 'inv.noinv', '=', 'return_summary.noinv')
                        ->first();

                    if ($result->total_nominal_invoice <= $result->total_pembayaran) {
                        $kcpinformation->table('trns_inv_header')
                            ->where('noinv', $value->noinv)
                            ->update([
                                'flag_pembayaran_lunas' => 'Y'
                            ]);
                    }
                }

                $kcpapplication->table('customer_payment_header')
                    ->where('no_piutang', $this->no_piutang)
                    ->update([
                        'status' => 'C'
                    ]);

                $kcpapplication->table('customer_payment_details')
                    ->where('no_piutang', $this->no_piutang)
                    ->update([
                        'status' => 'C'
                    ]);

                switch ($this->customer_payment_header->pembayaran_via) {
                    case 'CASH':
                    case 'TRANSFER':
                        $kcpinformation->table('trns_plafond')
                            ->where('kd_outlet',  $this->customer_payment_header->kd_outlet)
                            ->increment('nominal_plafond',  $this->customer_payment_header->nominal_potong);
                        break;

                    case 'BG':
                        break;

                    default:
                        throw new \Exception("Jenis pembayaran '$this->customer_payment_header->pembayaran_via' tidak dikenali.");
                }

                $kcpapplication->commit();
                $kcpinformation->commit();

                $this->redirect('/customer-payment');

                session()->flash('success', 'Penerimaan piutang toko berhasil.');
            } catch (\Exception $e) {
                $kcpapplication->rollBack();
                $kcpinformation->rollBack();

                session()->flash('error', $e->getMessage());
                return;
            }
        }
    }

    public function render()
    {
        if (!$this->customer_payment_header) {
            abort(404);
        }

        $invoices = DB::table('customer_payment_details')
            ->where('no_piutang', $this->no_piutang)
            ->pluck('noinv');

        $noinv_string = implode(',', $invoices->toArray());

        dd($noinv_string);


        return view('livewire.customer-payment.detail-customer-payment');
    }
}
