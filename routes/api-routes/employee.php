<?php

use App\Http\Controllers\admin\EmployeeController;

use Illuminate\Support\Facades\Route;

Route::controller(EmployeeController::class)->group(function () {
    // Employee Management (Users with salesman/salesexecutive roles)
    Route::post('employee/store', 'storeEmployee')->name('employees.store');
    Route::get('employees/mainlist/', 'getEmployeesMain')->name('employees.list-main');
    Route::post('employees/delete', 'employeeDelete')->name('employee.delete');

    Route::get('employees/details/{id}', 'getEmployeeDetails')->name('employee.details');
    Route::match(['POST', 'PUT'], 'employees/update/{id}', 'updateEmployee')->name('employees.update');

});
