<?php

use App\Http\Controllers\admin\OpeningStockController;
use Illuminate\Support\Facades\Route;

Route::controller(OpeningStockController::class)->group(function () {

    Route::post('opening_stock/store', 'openingStockStore')->name('opening-stock.store');
    Route::get('opening_stock/list', 'openingStockList')->name('opening-stock.store');
});
