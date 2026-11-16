<?php


use App\Http\Controllers\admin\OpeningBalanceController;
use Illuminate\Support\Facades\Route;

Route::controller(OpeningBalanceController::class)->group(function () {


    // Master Data
    Route::get('opening-balance/list', 'openingBalanceList')->name('opening-balance.list');
    Route::get('opening-balance/customer-list', 'openingBalanceCustomerList')->name('opening-balance.customer-list');
    Route::get('opening-balance/supplier-list', 'openingBalanceSupplierList')->name('opening-balance.supplier-list');
    Route::post('opening-balance/store', 'openingBalanceStore')->name('opening-balance.store');
    Route::put('opening-balance/update', 'openingBalanceUpdate')->name('opening-balance.update');
    Route::delete('opening-balance/close', 'openingBalanceClose')->name('opening-balance.close');
});
