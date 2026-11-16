<?php

use App\Http\Controllers\admin\CustomerController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\ReportController;
use App\Http\Controllers\salesMan\SalesManAppController;
use Illuminate\Support\Facades\Route;

//middleware('api_auth_middleware')->
Route::prefix('employee')->group(function () {
    Route::controller(SalesManAppController::class)->group(function () {
        Route::post('get-sales-man-profile', 'getSalesManProfile');
        // Route::post('update-user-profile', 'updateProfile');
        // Route::post('update-user-profile-image', 'updateProfileImage');

        Route::post('get-vehicle-list', 'getVehicleList');
        Route::post('get-product-list', 'getProductList');

        Route::post('open-vehicle-load-request', 'openVehicleLoadRequest');
        Route::post('add-extra-load-request', 'addExtraLoadToVehicleRequest');
        Route::post('close-vehicle-load-details', 'closeVehicleLoadDetails');
        Route::post('close-vehicle-load-stock', 'closeVehicleLoadStock');

        Route::post('get-orders-request-list', 'getOrdersRequestList');
        Route::post('get-order-request-info', 'getOrderRequestDetails');

        Route::post('mark-order-as-delivered', 'deliveryOrder');
        Route::post('pay-pending-order-amount', 'payPendingOrderAmount');

        Route::post('sales-man-invoice', 'salesManDeliverWithInvoice');
        Route::post('get-commission-types', 'getCommissionType');
        Route::post('return-vehicle-stock',  'returnVehicleStock')->name('return-vehicle-stock');
        Route::post('request-return-vehicle-stock', 'requestReturnVehicleStock');
        Route::post('reject-order', 'rejectOrder');
    });

    Route::controller(CustomerController::class)->group(function () {

        // need to pass platform : api for mobile app
        Route::post('get-customers-list', 'customerList');
        Route::post('get-customer-details', 'getCustomerDetails');
    });

    Route::controller(DashboardController::class)->group(function () {
        Route::post('get-home-page-data', 'getHomePageData');
        Route::post('get-pending-expenses', 'getPendingExpenses');
    });
    Route::controller(ReportController::class)->group(function () {
        Route::post('get-customer-due-report', 'customerDueReportList');
        Route::post('get-customer-credit-report', 'customer_credit_report_list');
        Route::post('get-customer-payment-report', 'payment_report_list');
    });
});
