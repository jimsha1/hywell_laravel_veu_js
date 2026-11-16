<?php


use App\Http\Controllers\admin\AttendanceController;
use Illuminate\Support\Facades\Route;



Route::controller(AttendanceController::class)->group(function () {
    Route::post('attendance/store',  'store')->name('attendance.store');

    Route::get('attendance/employees/list', 'employeeListForAttendance')->name('attendance.employees.list');
});
