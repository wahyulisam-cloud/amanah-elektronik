<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PelangganAuthController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\PelangganDataController;
use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PenyewaanController;
use App\Http\Controllers\Api\PenyewaanDetailController;


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| PELANGGAN REGISTER - MOBILE
|--------------------------------------------------------------------------
*/

Route::post('/auth/pelanggan/register', [
    PelangganAuthController::class,
    'register'
]);


/*
|--------------------------------------------------------------------------
| PELANGGAN LOGIN - MOBILE
|--------------------------------------------------------------------------
|
| POST /api/pelanggan/login
|
*/

Route::post('/pelanggan/login', [
    PelangganAuthController::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| PELANGGAN - ADMIN
|--------------------------------------------------------------------------
|
*/

Route::post('/pelanggan', [
    PelangganController::class,
    'store'
]);


/*
|--------------------------------------------------------------------------
| KATALOG ALAT - PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/alat', [
    AlatController::class,
    'index'
]);

Route::get('/alat/{id}', [
    AlatController::class,
    'show'
]);


/*
|--------------------------------------------------------------------------
| KATEGORI - PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/kategori', [
    KategoriController::class,
    'index'
]);

Route::get('/kategori/{id}', [
    KategoriController::class,
    'show'
]);



/*
|--------------------------------------------------------------------------
| ADMIN PROTECTED ROUTES
|--------------------------------------------------------------------------
|
| Semua route di bawah membutuhkan JWT ADMIN.
|
*/

Route::middleware('auth:api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ADMIN AUTH
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::get('/me', [
        AuthController::class,
        'me'
    ]);


    /*
    |--------------------------------------------------------------------------
    | ADMIN DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ]);

    Route::get('/dashboard/chart', [
        DashboardController::class,
        'chartPenyewaan'
    ]);


    /*
    |--------------------------------------------------------------------------
    | KATEGORI
    |--------------------------------------------------------------------------
    */

    Route::post('/kategori', [
        KategoriController::class,
        'store'
    ]);

    Route::put('/kategori/{id}', [
        KategoriController::class,
        'update'
    ]);

    Route::delete('/kategori/{id}', [
        KategoriController::class,
        'destroy'
    ]);


    /*
|--------------------------------------------------------------------------
| ALAT
|--------------------------------------------------------------------------
*/

    Route::post('/alat', [
        AlatController::class,
        'store'
    ]);

    Route::match(['put', 'post'], '/alat/{id}', [
        AlatController::class,
        'update'
    ]);

    Route::delete('/alat/{id}', [
        AlatController::class,
        'destroy'
    ]);


    /*
    |--------------------------------------------------------------------------
    | PELANGGAN - ADMIN
    |--------------------------------------------------------------------------
    */

    Route::get('/pelanggan', [
        PelangganController::class,
        'index'
    ]);

    Route::get('/pelanggan/{pelanggan}', [
        PelangganController::class,
        'show'
    ]);

    Route::put('/pelanggan/{pelanggan}', [
        PelangganController::class,
        'update'
    ]);

    Route::delete('/pelanggan/{pelanggan}', [
        PelangganController::class,
        'destroy'
    ]);


    /*
    |--------------------------------------------------------------------------
    | DATA IDENTITAS PELANGGAN - ADMIN
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'pelanggan-data',
        PelangganDataController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PENYEWAAN - ADMIN
    |--------------------------------------------------------------------------
    */

    Route::get('/penyewaan', [
        PenyewaanController::class,
        'index'
    ]);

    Route::post('/penyewaan', [
        PenyewaanController::class,
        'store'
    ]);

    Route::get('/penyewaan/{id}', [
        PenyewaanController::class,
        'show'
    ]);

    Route::put('/penyewaan/{id}', [
        PenyewaanController::class,
        'update'
    ]);

    Route::delete('/penyewaan/{id}', [
        PenyewaanController::class,
        'destroy'
    ]);

    Route::put('/penyewaan/{id}/kembali', [
        PenyewaanController::class,
        'kembalikan'
    ]);


    /*
    |--------------------------------------------------------------------------
    | PENYEWAAN DETAIL - ADMIN
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'penyewaan-detail',
        PenyewaanDetailController::class
    );
});



/*
|--------------------------------------------------------------------------
| PELANGGAN PROTECTED ROUTES
|--------------------------------------------------------------------------
|
| Semua route di bawah membutuhkan JWT PELANGGAN.
|
*/

Route::middleware('auth:pelanggan')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post('/auth/pelanggan/logout', [
        PelangganAuthController::class,
        'logout'
    ]);


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/auth/pelanggan/me', [
        PelangganAuthController::class,
        'me'
    ]);
    Route::get('/auth/pelanggan/register', [
        PelangganAuthController::class,
        'register'
    ]);

    Route::put('/auth/pelanggan/profile', [
        PelangganAuthController::class,
        'updateProfile'
    ]);


    /*
    |--------------------------------------------------------------------------
    | GANTI PASSWORD
    |--------------------------------------------------------------------------
    */

    Route::put('/auth/pelanggan/password', [
        PelangganAuthController::class,
        'updatePassword'
    ]);


    /*
    |--------------------------------------------------------------------------
    | BUAT PENYEWAAN
    |--------------------------------------------------------------------------
    */

    Route::post('/auth/pelanggan/penyewaan', [
        PenyewaanController::class,
        'store'
    ]);


    /*
    |--------------------------------------------------------------------------
    | RIWAYAT PENYEWAAN
    |--------------------------------------------------------------------------
    */

    Route::get('/auth/pelanggan/penyewaan', [
        PenyewaanController::class,
        'riwayatPelanggan'
    ]);


    /*
    |--------------------------------------------------------------------------
    | DETAIL PENYEWAAN
    |--------------------------------------------------------------------------
    */

    Route::get('/auth/pelanggan/penyewaan/{id}', [
        PenyewaanController::class,
        'showPelanggan'
    ]);
});
