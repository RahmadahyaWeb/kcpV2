<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public static function log_api($dataToSend, $response, $status = false)
    {
        if ($status == true) {
            $status = 'SUKSES';
        } else {
            $status = 'GAGAL';
        }

        DB::table('log_api')
            ->insert([
                'date'      => now(),
                'request'   => json_encode($dataToSend),
                'response'  => json_encode($response),
                'status'    => $status
            ]);
    }
}
