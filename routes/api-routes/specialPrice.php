<?php

use App\Http\Controllers\admin\SpecialPriceController;
use Illuminate\Support\Facades\Route;

Route::controller(SpecialPriceController::class)->group(function () {

    Route::post('special-price/store', 'specialPriceStore')->name('special-price.store');
    Route::put('special-price/update', 'specialPriceStore')->name('special-price.update');
    Route::get('special-price/list', 'specialPriceList')->name('special-price.list');
    Route::delete('delete/special-price', 'specialPriceDelete')->name('special-price.delete');
});
