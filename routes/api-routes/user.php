<?php

use App\Http\Controllers\admin\acl\employee\EmployeeController;
use App\Http\Controllers\admin\acl\UserManagementController;
use App\Http\Controllers\employee\RankingSystemController;
use Illuminate\Support\Facades\Route;

Route::controller(UserManagementController::class)->group(function () {
    Route::post('add-user', 'userStore');
    Route::get('get-users', 'getUsers');
    Route::put('update-user', 'userStore');
    Route::delete('delete-user', 'userDelete')->name('user.delete');

    Route::post('send-forgot-password-otp', 'sendForgotOtp');
    Route::post('verify-forgot-password-otp', 'verifyForgotOtp');
    Route::post('change-forgot-password-otp', 'changeForgotPassword');

    Route::get('sales-man-list', 'getSalesMan');
});
