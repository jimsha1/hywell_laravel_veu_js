<?php


use App\Http\Controllers\admin\AccountTypeController;

use Illuminate\Support\Facades\Route;

Route::controller(AccountTypeController::class)->group(function () {

    Route::get('accountTypes/list', 'show')->name('accountTypes.list');
});

