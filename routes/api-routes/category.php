<?php

use App\Http\Controllers\admin\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::controller(ProductCategoryController::class)->group(function () {

    Route::post('category/store', 'categoryStore')->name('category.store');
    Route::put('category/update', 'categoryStore')->name('category.update');
    Route::get('category/list', 'categoryList')->name('category.list');
    Route::delete('delete/category','categoryDelete')->name('category.delete');
});
