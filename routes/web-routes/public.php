<?php

use App\Http\Controllers\public\PublicController;
use Illuminate\Support\Facades\Route;

Route::controller(PublicController::class)->group(function () {
    Route::get('public-login', 'showLoginPage')->name('public.login');
    Route::get('public-home', 'showHomePage')->name('public.home');
    Route::get('remove-my-account', 'removedAccount');
    Route::get('privacy-policy', 'privacy');
    Route::post('remove-my-account-post', 'removedAccountPost')->name('delete-account-request-submit');
});
