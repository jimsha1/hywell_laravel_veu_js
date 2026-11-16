<?php

use App\Http\Controllers\admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::controller(ProductController::class)->group(function () {

    Route::post('product/store', 'productStore')->name('product.store');
    Route::put('product/update', 'productStore')->name('product.update');
    Route::get('product/list', 'productList')->name('product.list');
    Route::delete('delete/product', 'productDelete')->name('product.delete');
});
