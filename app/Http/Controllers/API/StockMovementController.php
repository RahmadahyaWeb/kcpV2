<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockMovementController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data'                      => 'required|array',
            'data.*.status'             => 'required|string',
            'data.*.keterangan'         => 'required|string',
            'data.*.kd_gudang'          => 'nullable|string|max:100',
            'data.*.part_no'            => 'nullable|string|max:120',
            'data.*.qty'                => 'required|integer',
            'data.*.debet_qty'          => 'required|integer',
            'data.*.kredit_qty'         => 'required|integer',
            'data.*.stock_sebelum'      => 'required|integer',
            'data.*.stock_sesudah'      => 'required|integer',
            'data.*.stock_on_hand'      => 'required|integer',
            'data.*.stock_booking'      => 'required|integer',
            'data.*.stock_in_transit'   => 'required|integer',
            'data.*.stock_type'         => 'nullable',
            'data.*.crea_date'          => 'nullable|date',
            'data.*.crea_by'            => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $request->data;

        try {
            // Insert bulk data into the database
            DB::connection('mysql')->table('trns_log_stock')->insert($data);

            return response()->json([
                'status'    => 'success',
                'message'   => 'Data stored successfully.',
                'data'      => $data
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ], 500);
        }
    }
}
