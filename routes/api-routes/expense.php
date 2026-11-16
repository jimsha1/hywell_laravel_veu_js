<?php

use App\Http\Controllers\admin\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::controller(ExpenseController::class)->group(function () {
    // Expense CRUD
    Route::get('expenses/list', 'list')->name('expenses.list');
    Route::post('expenses/store', 'store')->name('expenses.store');
    Route::put('expenses/update', 'updateExpenses')->name('expenses.update');
    Route::put('expenses/pay', 'payExpense')->name('expenses.pay');
    Route::get('expenses/{id}', 'show')->name('expenses.show');
    Route::delete('expenses/{id}', 'destroy')->name('expenses.destroy');

    // Categories
    Route::get('expenses/categories/list', 'getCategories')->name('expenses.categories.list');
    Route::post('expenses/categories/store', 'storeCategory')->name('expenses.categories.store');

    // Reports
    Route::get('expenses/summary/report', 'getSummary')->name('expenses.summary');
    Route::get('expenses/accounts/list', 'getExpenseAccounts')->name('expenses.accounts.list');
    Route::get('expenses/liability/list', 'getLiabilityAccounts')->name('expenses.liability.list');


    Route::post('expenses-update-status', 'updateExpenseStatus')->name('expenses-update-status');
});
