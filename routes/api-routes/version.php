<?php


use App\Http\Controllers\admin\VersionController;
use Illuminate\Support\Facades\Route;

Route::controller(VersionController::class)->group(function () {
    Route::get('ios-version', 'getIosVersion')->name('ios-version.get');


});
