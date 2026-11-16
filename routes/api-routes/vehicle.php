<?php

use App\Http\Controllers\admin\VehicleController;
use Illuminate\Support\Facades\Route;

Route::controller(VehicleController::class)->group(function () {

    Route::post('vehicle/store', 'vehicleStore')->name('vehicle.store');
    Route::put('vehicle/update', 'vehicleStore')->name('vehicle.update');
    Route::get('vehicle/list', 'vehicleList')->name('vehicle.list');
    Route::delete('delete/vehicle', 'vehicleDelete')->name('vehicle.delete');
});
