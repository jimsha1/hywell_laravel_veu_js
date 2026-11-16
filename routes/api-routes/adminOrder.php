<?php

use App\Http\Controllers\admin\AdminOrderController;
use App\Http\Controllers\admin\ProductCategoryController;
use App\Http\Controllers\admin\SalesReturnController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminOrderController::class)->group(function () {

    Route::post('admin-order/store', 'adminOrderStore')->name('admin-order.store');
    Route::get('admin-order/list', 'adminrOrderList')->name('admin-order.list');
    Route::get('orders-for-payment-edit', 'getOrdersForPaymentEdit')->name('orders.for.payment.edit');
    Route::get('order-details-with-payments', 'getOrderDetailsWithPayments')->name('order.details.with.payments');
    Route::post('verify-password-and-get-order', 'verifyPasswordAndGetOrder')->name('verify.password.and.get.order');


    Route::post('update-all-order-payments', 'updateAllOrderPayments')->name('update.all.order.payments');
});

Route::controller(SalesReturnController::class)->group(function () {
    Route::post('sales-return/create', 'createSalesReturn')->name('sales-return.create');
    Route::post('sales-return/approve', 'approveSalesReturn')->name('sales-return.approve');
    Route::post('sales-return/reject', 'rejectSalesReturn')->name('sales-return.reject');
    Route::get('sales-return/list', 'getAllSalesReturns')->name('sales-return.list');
    Route::get('sales-return/details', 'getSalesReturnDetails')->name('sales-return.details');
    Route::get('orders-for-return', 'getOrdersForReturn')->name('orders.for.return');
    Route::get('customer-credit-balance','getCustomerCreditBalance')->name('customer-credit-balance');
});
