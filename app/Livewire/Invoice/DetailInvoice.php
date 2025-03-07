<?php

namespace App\Livewire\Invoice;

use App\Http\Controllers\API\SalesOrderController;
use App\Http\Controllers\ExportController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DetailInvoice extends Component
{
    public $target = 'sendToBosnet, saveProgram, deleteProgram, print';

    public $invoice;
    public $header;
    public $search_program;
    public $kd_outlet;
    public $nominal_program_display = 0;
    public $nama_program;
    public $nominal_program;
    public $details;
    public $bonus_toko;
    public $nominal_total;
    public $nominalSuppProgram;

    /**
     * Initialize the component with an invoice.
     *
     * @param string $invoice Invoice number
     */
    public function mount($noinv)
    {
        $this->invoice = $noinv;
    }

    /**
     * Save the program details to the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveProgram()
    {
        $this->validate([
            'nama_program'      => 'required',
            'nominal_program'   => 'required|numeric|min:0',
        ]);

        if ($this->nominal_program > (int) str_replace('.', '', $this->nominal_program_display)) {
            $this->addError('nominal_program', 'Nominal tidak boleh melebihi ketentuan.');
            return;
        }

        try {
            $kcpInformation = DB::connection('kcpinformation');
            $kcpApplication = DB::connection('mysql');

            $kcpInformation->beginTransaction();
            $kcpApplication->beginTransaction();

            // Validate program existence
            $program = $kcpInformation->table('trns_ach_toko_bonus')
                ->where('no_program', $this->nama_program)
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Log history
            DB::table('history_bonus_invoice')->insert([
                'no_program'                => $this->nama_program,
                'nm_program'                => $program->nm_program,
                'nominal_program'           => $this->nominal_program,
                'nominal_program_before'    => $program->nominal,
                'nominal_program_after'     => $program->nominal - $this->nominal_program,
                'noinv'                     => $this->invoice,
                'nominal_invoice_before'    => $this->header->amount_total,
                'nominal_invoice_after'     => $this->header->amount_total - $this->nominal_program,
                'crea_date'                 => now(),
                'crea_by'                   => Auth::user()->username
            ]);

            // Update invoice header
            DB::connection('kcpinformation')->table('trns_inv_header')
                ->where('noinv', $this->invoice)
                ->decrement('amount_total', $this->nominal_program);

            // Update bonus
            DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('no_program', $this->nama_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->decrement('nominal', $this->nominal_program);

            $kcpInformation->commit();
            $kcpApplication->commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a program from the sales order.
     *
     * @param int $id Program ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteProgram($id)
    {
        try {
            $kcpInformation = DB::connection('kcpinformation');
            $kcpApplication = DB::connection('mysql');

            $kcpInformation->beginTransaction();
            $kcpApplication->beginTransaction();

            // Fetch the program details
            $program = DB::table('history_bonus_invoice')
                ->where('id', $id)
                ->select(['nominal_program', 'no_program'])
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Revert updates to invoice and bonus
            DB::connection('kcpinformation')->table('trns_inv_header')
                ->where('noinv', $this->invoice)
                ->increment('amount_total', $program->nominal_program);

            DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('no_program', $program->no_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->increment('nominal', $program->nominal_program);

            // Delete the program from history_bonus_invoice
            DB::table('history_bonus_invoice')
                ->where('id', $id)
                ->delete();

            $kcpInformation->commit();
            $kcpApplication->commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the nominal display value when the program name is changed.
     */
    public function updatedNamaProgram()
    {
        $nominal = $this->nama_program
            ? (int) DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('kd_outlet', $this->kd_outlet)
                ->where('no_program', $this->nama_program)
                ->value('nominal') ?? 0
            : 0;

        // Format sebagai Rupiah
        $this->nominal_program_display = number_format($nominal, 0, ',', '.');
    }

    /**
     * Send the sales order to Bosnet.
     *
     * @return void
     */
    public function sendToBosnet()
    {
        $user = Auth::user();

        if ($user->hasRole('super-user')) {
            try {
                $controller = new SalesOrderController();
                $controller->sendToBosnet(new Request(['invoice' => $this->invoice]));

                session()->flash('success', "Data SO berhasil diteruskan ke BOSNET");
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
            }
        } else {
            session()->flash('error', 'FITUR SEDANG DIPERBAIKI, SEMENTARA BELUM BISA DIPAKAI, TERIMA KASIH.');
        }

        // try {
        //     $controller = new SalesOrderController();
        //     $controller->sendToBosnet(new Request(['invoice' => $this->invoice]));

        //     session()->flash('success', "Data SO berhasil diteruskan ke BOSNET");
        // } catch (\Exception $e) {
        //     session()->flash('error', $e->getMessage());
        // }
    }

    public function print($noinv)
    {
        $details = $this->details;

        $header = $this->header;

        // CEK FLAG PRINT
        if ($header->cetak >= 1) {
            return back()->with('error', 'Invoice tidak dapat diprint lebih dari satu kali');
        }

        DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $noinv)
            ->update([
                "status"            => "C",
                "ket_status"        => "CLOSE",
                "cetak"             => 1,
            ]);

        $sumTotalNominal = 0;
        $sumTotalDPP = 0;
        $sumTotalDisc = 0;

        foreach ($details as $value) {
            $sumTotalNominal = $sumTotalNominal + $value->nominal;
            $sumTotalDPP = $sumTotalDPP + $value->nominal_total;
            $sumTotalDisc = $sumTotalDisc + $value->nominal_disc;
            $nominalPPn = ($value->nominal_total / config('tax.ppn_factor')) * config('tax.ppn_percentage');
        }

        $dpp = round($sumTotalNominal) / config('tax.ppn_factor');
        $nominalPPn = round($dpp) * config('tax.ppn_percentage');
        $dppDisc = round($sumTotalDPP) / config('tax.ppn_factor');
        $nominalPPnDisc = round($dppDisc * config('tax.ppn_percentage'));

        // CEK INVOICE BOSNET APAKAH SUDAH ADA ATAU BELUM
        $cek_invoice_bosnet = DB::table('invoice_bosnet')
            ->where('noso', $header->noso)
            ->where('noinv', $header->noinv)
            ->first();

        if (!$cek_invoice_bosnet) {
            DB::table('invoice_bosnet')
                ->insert([
                    'noso'          => $header->noso,
                    'noinv'         => $header->noinv,
                    'kd_outlet'     => $header->kd_outlet,
                    'nm_outlet'     => $header->nm_outlet,
                    'amount_total'  => $sumTotalDPP,
                    'amount'        => $sumTotalNominal,
                    'amount_disc'   => $sumTotalDisc,
                    'crea_date'     => $header->crea_date,
                    'tgl_jth_tempo' => $header->tgl_jth_tempo,
                    'user_sales'    => $header->user_sales,
                    'flag_print'    => 'Y'
                ]);
        }

        $suppProgram = DB::table('history_bonus_invoice')
            ->where('noinv', $noinv)
            ->get();

        $master_toko = DB::connection('kcpinformation')
            ->table('mst_outlet')
            ->where('kd_outlet', $header->kd_outlet)
            ->first();

        $alamat_toko = $master_toko->almt_outlet;

        $data = [
            'invoices'       => $details,
            'header'         => $header,
            'suppProgram'    => $suppProgram,
            'alamat_toko'    => $alamat_toko
        ];

        $pdf = Pdf::loadView('livewire.invoice.print', $data);

        return response()->stream(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $noinv . '.pdf"',
            'Content-Transfer-Encoding' =>  'binary'
        ]);
    }

    public function batal_print($noinv)
    {
        $kcpInformation = DB::connection('kcpinformation');

        try {
            $kcpInformation->beginTransaction();

            $kcpInformation->table('trns_inv_header')
                ->where('noinv', $noinv)
                ->update([
                    "cetak" => 0,
                ]);

            $kcpInformation->commit();
        } catch (\Exception $e) {
            $kcpInformation->rollBack();

            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $this->details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $this->invoice)
            ->get();

        $sumTotalNominal = 0;
        $sumTotalDPP = 0;
        $sumTotalDisc = 0;

        foreach ($this->details as $value) {
            $sumTotalNominal = $sumTotalNominal + $value->nominal;
            $sumTotalDPP = $sumTotalDPP + $value->nominal_total;
            $sumTotalDisc = $sumTotalDisc + $value->nominal_disc;
            $nominalPPn = ($value->nominal_total / config('tax.ppn_factor')) * config('tax.ppn_percentage');
        }

        $dpp = round($sumTotalNominal) / config('tax.ppn_factor');
        $nominalPPn = round($dpp) * config('tax.ppn_percentage');
        $dppDisc = round($sumTotalDPP) / config('tax.ppn_factor');
        $nominalPPnDisc = round($dppDisc * config('tax.ppn_percentage'));

        $this->nominalSuppProgram = DB::table('history_bonus_invoice')
            ->where('noinv', $this->invoice)
            ->sum('nominal_program');

        try {
            DB::connection('kcpinformation')->beginTransaction();

            DB::connection('kcpinformation')
                ->table('trns_inv_header')
                ->where('noinv', $this->invoice)
                ->update([
                    "amount_dpp"        => ROUND($dpp),
                    "amount_ppn"        => ROUND($nominalPPn),
                    "amount"            => ROUND($sumTotalNominal),
                    "amount_disc"       => ROUND($sumTotalDisc),
                    "amount_dpp_disc"   => ROUND($dppDisc),
                    "amount_ppn_disc"   => ROUND($nominalPPnDisc),
                    "amount_total"      => ROUND($sumTotalDPP - $this->nominalSuppProgram),
                ]);

            DB::connection('kcpinformation')->commit();
        } catch (\Exception $e) {
            DB::connection('kcpinformation')->rollBack();

            abort(500);
        }

        $this->header = DB::connection('kcpinformation')
        ->table('trns_inv_header')
        ->where('noinv', $this->invoice)
        ->first();

        $this->bonus_toko = DB::connection('kcpinformation')
            ->table('trns_ach_toko_bonus')
            ->where('kd_outlet', $this->header->kd_outlet)
            ->where('nominal', '>', 0)
            ->whereYear('crea_date', 2025)
            ->get();

        $this->kd_outlet = $this->header->kd_outlet;

        $invoice_status = DB::table('invoice_bosnet')
            ->where('noinv', $this->invoice)
            ->first();

        return view('livewire.invoice.detail-invoice', [
            'invoices'  => $this->details,
            'programs' => DB::table('history_bonus_invoice')
                ->where('noinv', $this->invoice)
                ->get(),
            'header' => $this->header,
            'bonus' => $this->bonus_toko,
            'invoice_status' => $invoice_status,
            'sumTotalNominal' => $sumTotalNominal,
            'sumTotalDPP' => $sumTotalDPP,
            'sumTotalDisc' => $sumTotalDisc,
        ]);
    }
}
