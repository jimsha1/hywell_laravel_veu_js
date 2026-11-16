<?php

use App\Http\Controllers\admin\SupplierController;
use Illuminate\Support\Facades\Route;

Route::controller(SupplierController::class)->group(function () {

    Route::post('supplier/store', 'supplierStore')->name('supplier.store');
    Route::put('supplier/update', 'supplierStore')->name('supplier.update');
    Route::get('supplier/list', 'supplierList')->name('supplier.list');
    Route::delete('delete/supplier','supplierDelete')->name('supplier.delete');
});
