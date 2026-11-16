<?php

use App\Http\Controllers\admin\PurchaseController;
use App\Http\Controllers\admin\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::controller(PurchaseController::class)->group(function () {

    Route::post('purchase/store', 'purchaseStore')->name('purchase.store');
    Route::put('purchase/update', 'purchaseStore')->name('purchase.update');
    Route::get('purchase/list', 'purchaseList')->name('purchase.list');
    Route::delete('delete/purchase', 'purchaseDelete')->name('purchase.delete');
    Route::get('next-purchase-ref', 'generateNextPurchaseRefCode')->name('next-purchase-ref');
    
    Route::post('purchase-credit-payment', 'purchaseCreditPayment')->name('purchase-credit-payment');
});

Route::controller(PurchaseReturnController::class)->group(function () {
    Route::post('purchase-return/create', 'createPurchaseReturn')->name('purchase-return.create');
    Route::post('purchase-return/approve', 'approvePurchaseReturn')->name('purchase-return.approve');
    Route::post('purchase-return/reject', 'rejectPurchaseReturn')->name('purchase-return.reject');
    Route::get('purchase-return/list', 'getAllPurchaseReturns')->name('purchase-return.list');
    Route::get('purchase-return/details', 'getPurchaseReturnDetails')->name('purchase-return.details');
    Route::get('purchases-for-return', 'getPurchasesForReturn')->name('purchases.for.return');
});
