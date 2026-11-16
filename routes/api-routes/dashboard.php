<?php

use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\SalesManController;
use Illuminate\Support\Facades\Route;

Route::controller(DashboardController::class)->group(function () {
    Route::get('get-dashboard-data', 'getDashboardData');

    // api/sales-man-list
    Route::get('sales-man-list-main', 'getSalesManList')->name('sales-man.list');
    Route::get('godown-stock-list', 'getGodownStockList')->name('godown-stock.list');
    Route::get('vehicle-stock-list', 'getVehicleStockList')->name('vehicle-stock.list');
    Route::get('employee-commission-list', 'employeeCommissionList')->name('employee-commission.list');


    // Route::get('get-home-page-data', 'getHomePageData')->name('get-home-page-data');

    // try {
    //     return $this->dashboardService->getDashboardData($request);
    // } catch (Exception $e) {
    //     return errorResponse(null, $e->getMessage(), 100);
    // }
});
