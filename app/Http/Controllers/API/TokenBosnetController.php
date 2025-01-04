<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TokenBosnetController extends Controller
{
    public static function signInForSecretKey()
    {
        // Data request yang akan dikirim
        $payload = [
            'szAppId' => 'BDI.KCP',
            'szSecretKey' => config('api.secret_key'),
            'bSecretAsKey' => true,
        ];

        // Endpoint tujuan
        $url = 'https://omnichannel.ngecosystem.com/API/OC/NGE/v1/SM/Auth/SignInForSecretKey';

        try {
            // Kirim POST request menggunakan Http client Laravel
            $response = Http::post($url, $payload);

            // Periksa response
            if ($response->successful()) {
                $data = $response->json(); // Ambil data JSON

                // Kembalikan data secara langsung
                return $data;  // Kembalikan array data yang berisi 'szToken'
            } else {
                return [
                    'status' => 'error',
                    'message' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            // Tangani error
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
