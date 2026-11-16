<?php

use App\Http\Controllers\admin\acl\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::controller(UserManagementController::class)->group(function () {
    Route::post('roles/store', 'roleStore')->name('purchase.store');
    Route::put('roles/update', 'roleStore')->name('purchase.update');
    Route::get('roles/list', 'roleList')->name('purchase.list');
    Route::delete('delete/role', 'roleDelete')->name('purchase.delete');

});
