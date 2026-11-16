<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

foreach (glob(__DIR__ . '/api-routes/*.php') as $file) {
    require_once $file;
}

Route::controller(LoginController::class)->group(function () {

    Route::post('/user-authentication', 'authenticationApi')->name('authenticationApi');
});



    // Route::controller(QrCodeController::class)->group(function () {
    //     Route::get('download-qr-pdf', 'downloadQrCodesPdf')->name('qr_code.print');
    // });