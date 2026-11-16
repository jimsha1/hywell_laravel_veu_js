<?php

use App\Http\Controllers\admin\PayrollController;
use App\Http\Controllers\AdvanceSalaryController;
use Illuminate\Support\Facades\Route;

Route::controller(PayrollController::class)->group(function () {
    // Employee Management (Users with salesman/salesexecutive roles)
    Route::get('employees/list', 'getEmployees')->name('employees.list');

    Route::put('employees/{id}', 'updateEmployee')->name('employees.update');
    Route::get('employees/salary-eligible', 'getSalaryEligibleUsers')->name('employees.salary-eligible');

    // Payroll Management
    Route::get('payrolls/list', 'getPayrolls')->name('payrolls.list');
    Route::post('payrolls/store', 'storePayroll')->name('payrolls.store');
    Route::get('payrolls/{id}', 'showPayroll')->name('payrolls.show');
    Route::post('payrolls/{id}/pay', 'payPayroll')->name('payrolls.pay');
    Route::post('payrolls/{id}/cancel', 'cancelPayroll')->name('payrolls.cancel');
    Route::post('payrolls/generate-bulk', 'generateBulkPayroll')->name('payrolls.generate-bulk');
    Route::get('payrolls/summary/report', 'getSummary')->name('payrolls.summary');


    Route::get('/reference-data/{type}', 'getReferenceData')->name('payroll.reference-data');

    Route::get('commission/list', 'getCommissionList')->name('commission.list');
    Route::post('commission/store', 'storeCommission')->name('commission.store');
    Route::post('commission/{id}/pay', 'payCommission')->name('commission.pay');
    Route::post('commission/{id}/cancel', 'cancelCommission')->name('commission.cancel');


    Route::get('advances/list', 'getAdvances')->name('advances.list');
    Route::post('advances/store', 'storeAdvance')->name('advances.store');
    Route::get('advances/{id}', 'showAdvance')->name('advances.show');
    Route::post('advances/{id}/pay', 'payAdvance')->name('advances.pay');
    Route::post('advances/{id}/cancel', 'cancelAdvance')->name('advances.cancel');

    // Route::get('advances/summary/report', 'getSummary')->name('advances.summary');
});
