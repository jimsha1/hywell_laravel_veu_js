<?php

use App\Http\Controllers\admin\AccountingReportController;
use Illuminate\Support\Facades\Route;

Route::controller(AccountingReportController::class)->prefix('accounting')->group(function () {
    // Financial Reports
    Route::get('trial-balance', 'getTrialBalance')->name('accounting.trial-balance');
    Route::get('balance-sheet', 'getBalanceSheet')->name('accounting.balance-sheet');
    Route::get('income-statement', 'getIncomeStatement')->name('accounting.income-statement');
    
    // Transaction Reports
    Route::get('day-book', 'getDayBook')->name('accounting.day-book');
    Route::get('cash-book', 'getCashBook')->name('accounting.cash-book');
    Route::get('general-ledger', 'getGeneralLedger')->name('accounting.general-ledger');
    Route::get('journal-entries', 'getJournalEntries')->name('accounting.journal-entries');
    
    // Master Data
    Route::get('chart-of-accounts', 'getChartOfAccounts')->name('accounting.chart-of-accounts');
    Route::get('accounts-list', 'getAccountsList')->name('accounting.accounts-list');
    Route::get('cash-bank-accounts-list', 'getCashBankAccountsList')->name('accounting.cash-bank-accounts-list');
});




