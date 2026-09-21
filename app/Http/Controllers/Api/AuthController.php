<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * LOGIN ADMIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'admin_username' => 'required',
            'admin_password' => 'required',
        ]);

        try {

            $credentials = [
                'admin_username' => $request->admin_username,
                'password' => $request->admin_password,
            ];

            /*
            |--------------------------------------------------------------------------
            | WAJIB EKSPLISIT GUARD ADMIN
            |--------------------------------------------------------------------------
            */

            if (!$token = Auth::guard('api')->attempt($credentials)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Username atau Password salah.',
                    'data' => null,
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'token' => $token,
                'data' => Auth::guard('api')->user(),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error.',
                'data' => null,
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * DATA ADMIN YANG SEDANG LOGIN
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'message' => 'Data admin berhasil diambil.',
            'data' => Auth::guard('api')->user(),
        ], 200);
    }


    /**
     * LOGOUT ADMIN
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }
}