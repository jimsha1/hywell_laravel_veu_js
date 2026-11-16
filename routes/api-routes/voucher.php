<?php

use App\Http\Controllers\admin\ExpenseController;
use App\Http\Controllers\admin\VoucherController;
use Illuminate\Support\Facades\Route;

Route::controller(VoucherController::class)->group(function () {
    // Voucher CRUD
    Route::get('voucher/list', 'list')->name('vouchers.list');
    Route::post('vouchers/store', 'store')->name('vouchers.store');
    Route::put('vouchers/update', 'updateVoucher')->name('vouchers.update');
    Route::put('vouchers/pay', 'payVoucher')->name('vouchers.pay');
    Route::get('vouchers/{id}', 'show')->name('vouchers.show');
    Route::delete('vouchers/{id}', 'destroy')->name('vouchers.destroy');
    //  Route::post('voucher/get-crdit-amount', [VoucherController::class, 'getCreditAmount'])->name('voucher.get-credit-amount');

    
    // Route::post('voucher/get-crdit-amount', 'getCreditAmount')->name('voucher.get-credit-amount');
    Route::get('vouchers/accounts/list', 'getVoucherAccounts')->name('vouchers.accounts.list');

    Route::post('vouchers-update-status', 'updateVoucherStatus')->name('vouchers-update-status');
});
