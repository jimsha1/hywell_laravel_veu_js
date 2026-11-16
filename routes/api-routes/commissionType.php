<?php

use App\Http\Controllers\admin\CommissionTypeController;
use App\Http\Controllers\admin\VehicleController;
use Illuminate\Support\Facades\Route;

Route::controller(CommissionTypeController::class)->group(function () {

    Route::post('commission-type/store', 'commissionTypeStore')->name('commission-type.store');
    Route::put('commission-type/update', 'commissionTypeStore')->name('commission-type.update');
    Route::get('commission-type/list', 'commissionTypeList')->name('commission-type.list');
   
    
});
