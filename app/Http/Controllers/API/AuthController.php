<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle the login request.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'username'  => 'required',
            'password'  => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Coba login dengan email dan password
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('kcpapplication')->plainTextToken;

            return response()->json([
                'message'       => 'authorized',
                'access_token'  => $token,
                'token_type'    => 'Bearer',
            ], 200);
        }

        // Jika login gagal
        return response()->json(['message' => 'unauthorized'], 401);
    }
}
