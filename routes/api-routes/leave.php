<?php


use App\Http\Controllers\admin\LeaveController;
use Illuminate\Support\Facades\Route;



Route::controller(LeaveController::class)->group(function () {
    Route::post('leave-request-store',  'store')->name('leave-request.store');
    Route::post('leave-status/change',  'updateStatus')->name('leave-status.change');

    Route::get('leave-request/list', 'show')->name('leave-request.list');
});
