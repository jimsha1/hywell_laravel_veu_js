<?php

use App\Http\Controllers\admin\AccountController;


use Illuminate\Support\Facades\Route;



Route::controller(AccountController::class)->group(function () {
    Route::post('accounts/store',  'store')->name('accounts.store');
    Route::get('accounts/list', 'show')->name('accounts.list');
    Route::put('accounts/update', 'update')->name('accounts.update');
    Route::get('parent-accounts/list', 'getParentAccounts')->name('parent-accounts.index');
});
