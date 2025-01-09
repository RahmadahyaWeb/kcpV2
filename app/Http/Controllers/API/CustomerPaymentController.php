<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function index()
    {
        return view('customer-payment.index');
    }

    public function detail($no_piutang)
    {
        return view('customer-payment.detail', compact('no_piutang'));
    }

    public function history()
    {
        return view('customer-payment.history');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'headers'                               => 'required|array',
            'headers.*.no_piutang'                  => 'required|string|unique:customer_payment_header,no_piutang',
            'headers.*.area_piutang'                => 'string',
            'headers.*.kd_outlet'                   => 'required|string',
            'headers.*.nm_outlet'                   => 'required|string',
            'headers.*.nominal_potong'              => 'required|numeric',
            'headers.*.pembayaran_via'              => 'required|string',
            'headers.*.bank'                        => 'nullable',
            'headers.*.no_bg'                       => 'nullable|string',
            'headers.*.tgl_jth_tempo_bg'            => 'nullable|date',
            'headers.*.status'                      => 'required|string',
            'headers.*.crea_date'                   => 'required|date',
            'headers.*.crea_by'                     => 'required|string',

            'headers.*.details'                     => 'required|array',
            'headers.*.details.*.noinv'             => 'required|string',
            'headers.*.details.*.no_piutang'        => 'required|string',
            'headers.*.details.*.kd_outlet'         => 'required|string',
            'headers.*.details.*.nm_outlet'         => 'required|string',
            'headers.*.details.*.nominal'           => 'required|numeric',
            'headers.*.details.*.keterangan'        => 'nullable|string',
            'headers.*.details.*.pembayaran_via'    => 'required|string',
            'headers.*.details.*.no_bg'             => 'nullable|string',
            'headers.*.details.*.tgl_jth_tempo_bg'  => 'nullable|date',
            'headers.*.details.*.bank'              => 'nullable',
            'headers.*.details.*.status'            => 'required|string',
            'headers.*.details.*.crea_date'         => 'required|date',
            'headers.*.details.*.crea_by'           => 'required|string',
        ]);

        DB::beginTransaction(); // Memulai transaksi

        try {
            foreach ($validated['headers'] as $header) {
                // Simpan Header
                $headerData = $header;

                // Cek apakah pembayaran_via = 'TRANSFER'
                if ($headerData['pembayaran_via'] === 'TRANSFER') {
                    $bank = implode(', ', array_column($headerData['bank'], 'bank_name'));
                    $headerData['bank'] = $bank;
                } else {
                    $headerData['bank'] = '';
                }

                unset($headerData['details']); // Hapus details sebelum insert header

                DB::table('customer_payment_header')->insert($headerData);

                // Simpan Details
                foreach ($header['details'] as $detail) {
                    // Cek apakah pembayaran_via = 'TRANSFER' untuk detail
                    if ($detail['pembayaran_via'] === 'TRANSFER' || $detail['pembayaran_via'] === 'BG') {
                        $detailBank = implode(', ', array_column($detail['bank'], 'bank_name'));
                        $detail['bank'] = $detailBank;
                    } else {
                        $detail['bank'] = '';
                    }

                    DB::table('customer_payment_details')->insert($detail);
                }
            }

            DB::commit(); // Komit transaksi jika semua berhasil

            return response()->json([
                'status'  => 'success',
                'message' => 'Data stored successfully.',
                'headers' => $validated['headers'],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi kesalahan

            return response()->json([
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
