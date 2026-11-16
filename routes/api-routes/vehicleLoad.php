<?php

use App\Http\Controllers\admin\StockController;
use Illuminate\Support\Facades\Route;

Route::controller(StockController::class)->group(function () {
    Route::get('vehicle-load/list', 'vehicleLoadList')->name('vehicle-load.list');
    Route::delete('approve/vehicleLoad', 'vehicleLoadApproved')->name('approve-vehicleLoad.delete');
    Route::delete('approve_extra_load_to_vehicle', 'approveExtraLoadToVehicle')->name('approve-vehicleLoad');
    Route::delete('reject/vehicleLoad', 'vehicleLoadReject')->name('reject-vehicleLoad');
    Route::delete('reject_extra_load_to_vehicle', 'rejectExtraLoadToVehicle')->name('reject-vehicleLoad');
    Route::get('vehicle-load-return/list', 'vehicleLoadReturnList')->name('vehicle-load-return.list');
    Route::delete('return/vehicleLoad', 'vehicleLoadReturn')->name('return-vehicleLoad.delete');

    Route::delete('approve_return_stock', 'approveReturnStock')->name('approve-return-stock');

    Route::delete('reject_return_stock', 'rejectReturnStock')->name('reject-return-stock');
    Route::delete('reject_closing_request', 'rejectClosingRequest')->name('reject-closing-request');

    Route::get('get-pending-amount-for-orders', 'getPendingAmount')->name('get-pending-amount-for-orders');
    Route::get('get-customer-pending-amount', 'getCustomerPendingAmount')->name('get-customer-pending-amount');
});
