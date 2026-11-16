<?php

use App\Http\Controllers\admin\FinancialYearController;
use Illuminate\Support\Facades\Route;

Route::controller(FinancialYearController::class)->group(function () {


    // Master Data
    Route::get('financial-year/list', 'financialYearList')->name('financial-year.list');
    Route::post('financial-year/store', 'financialYearStore')->name('financial-year.store');
    Route::put('financial-year/update', 'financialYearUpdate')->name('financial-year.update');
    Route::delete('financial-year/close', 'financialYearClose')->name('financial-year.close');
});
