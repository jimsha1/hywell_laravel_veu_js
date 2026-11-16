<?php

use App\Http\Controllers\admin\CustomerController;
use Illuminate\Support\Facades\Route;

Route::controller(CustomerController::class)->group(function () {
    Route::post('customer/store', 'customerStore')->name('customer.store');
    Route::put('customer/update', 'customerStore')->name('customer.update');
    Route::get('customer/list', 'customerList')->name('customer.list');
    Route::delete('delete/customer', 'customerDelete')->name('customer.delete');

    Route::post('customer/assign-product', 'assignProductStore')->name('assign-product.store');
    Route::get('customer/assign-product/list', 'assignProductList')->name('assign-product.list');
    Route::delete('customer/assign-product/delete', 'assignProductDelete')->name('assign-product.delete');

    Route::get('customer-details/list', 'customerDetailList')->name('customer-details.list');


    // both for payment
    Route::post('redeem-credit', 'payCreditBalance')->name('redeem.credit');
    Route::post('pay-credit-balance', 'payCreditBalanceMultiplePayment')->name('pay.credit');


    Route::get('outside-salesman-list', 'outsideSalesmanList')->name('outside-salesman.list');
    Route::get('outside-salesman-balance','getOutsideSalesmanBalance')->name('outside-salesman-balance');
    Route::post('pay-outside-credit-balance', 'payOutsideCreditBalanceMultiplePayment')->name('pay.outside-credit');
});
