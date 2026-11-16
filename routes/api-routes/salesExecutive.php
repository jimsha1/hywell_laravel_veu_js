<?php

use App\Http\Controllers\admin\CustomerController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\salesExecutive\SalesExecutiveController;
use Illuminate\Support\Facades\Route;

// middleware('api_auth_middleware')->
Route::prefix('employee')->group(function () {
    Route::controller(SalesExecutiveController::class)->group(function () {
        Route::post('get-sales-executive-profile', 'getSalesExecutiveProfile');

        Route::post('update-user-profile', 'updateProfile');
        Route::post('update-user-profile-image', 'updateProfileImage');

        Route::post('get-sales-man-list', 'getSalesManList');

        Route::post('update-product-price', 'updateProductPrice');


        Route::post('store-invoice-draft', 'storeInvoiceDraft');

        Route::post('update-invoice-draft', 'updateInvoiceDraft');
        Route::post('list-invoice-draft', 'listInvoiceItems');

        Route::post('store-invoice-request', 'storeInvoiceRequest');

        Route::post('get-order-report', 'getOrdersReport');
    });
});
