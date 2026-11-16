<?php

use App\Http\Controllers\admin\GlobalTaxController;
use Illuminate\Support\Facades\Route;

Route::controller(GlobalTaxController::class)->group(function () {

    Route::post('tax/store', 'taxStore')->name('tax.store');
    Route::put('tax/update', 'taxStore')->name('tax.update');
    Route::get('tax/list', 'taxList')->name('tax.store');
    Route::delete('delete/tax', 'taxDelete')->name('tax.delete');
});
