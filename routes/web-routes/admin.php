<?php


use App\Http\Controllers\admin\AccountController;
use App\Http\Controllers\admin\AccountTypeController;
use App\Http\Controllers\admin\acl\UserManagementController;
use App\Http\Controllers\admin\AdminOrderController;
use App\Http\Controllers\admin\CommissionTypeController;
use App\Http\Controllers\admin\CustomerController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\AccountingReportController;
use App\Http\Controllers\admin\AttendanceController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\ExpenseController;
use App\Http\Controllers\admin\FinanceManagement\EditCustomerOpeningBalance;
use App\Http\Controllers\admin\FinanceManagement\EditCustomerOpeningBalanceController;
use App\Http\Controllers\admin\FinancialYearController;
use App\Http\Controllers\admin\GlobalTaxController;
use App\Http\Controllers\admin\PayrollController;
use App\Http\Controllers\admin\ProductCategoryController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\PurchaseController;
use App\Http\Controllers\admin\PurchaseReturnController;
use App\Http\Controllers\admin\ReportController;
use App\Http\Controllers\admin\SalesReturnController;
use App\Http\Controllers\admin\SpecialPriceController;
use App\Http\Controllers\admin\StockController;
use App\Http\Controllers\admin\SupplierController;
use App\Http\Controllers\admin\VehicleController;
use App\Http\Controllers\admin\InvoicePrintController;
use App\Http\Controllers\admin\LeaveController;
use App\Http\Controllers\admin\OpeningBalanceController;
use App\Http\Controllers\admin\OpeningStockController;
use App\Http\Controllers\admin\reports\ProductWiseSalesReportController;
use App\Http\Controllers\admin\VoucherController;
use App\Models\OpeningBalance;
use Illuminate\Support\Facades\Route;

Route::middleware('check.auth')->prefix('admin')->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('admin-dashboard');
    });

    Route::controller(UserManagementController::class)->group(function () {
        Route::get('manage-users', 'index')->name('manage.users.index');
        Route::get('manage-roles', 'roleIndex')->name('manage.roles.index');
        Route::get('manage-permissions', 'permmsionIndex')->name('manage.permissions.index');
    });

    Route::controller(SupplierController::class)->group(function () {
        Route::get('suppliers', 'index')->name('suppliers.index');
    });

    Route::controller(VehicleController::class)->group(function () {
        Route::get('vehicle', 'index')->name('vehicle.index');
    });

    Route::controller(ProductCategoryController::class)->group(function () {
        Route::get('product-category', 'index')->name('product-category.index');
    });

    Route::controller(ProductController::class)->group(function () {
        Route::get('products', 'index')->name('products.index');
    });

    Route::controller(SpecialPriceController::class)->group(function () {
        Route::get('special-price', 'index')->name('special-price.index');
    });
    Route::controller(AccountController::class)->group(function () {
        Route::get('accounts', 'index')->name('accounts.index');
        // Route::get('account-types', 'getAccountTypes')->name('account-types.index');
        Route::get('parent-accounts', 'getParentAccounts')->name('parent-accounts.index');
    });
    Route::controller(AccountTypeController::class)->group(function () {
        Route::get('account-types/list', 'show')->name('account-types.list');
        // Route::get('account-types', 'getAccountTypes')->name('account-types.index');
        // Route::get('parent-accounts', 'getParentAccounts')->name('parent-accounts.index');
    });

    Route::controller(CustomerController::class)->group(function () {
        Route::get('customer', 'index')->name('customer.index');
        Route::get('customer-info/{id}', 'customerInfo')->name('customer.info');
        Route::get('customer-transaction-pdf/{id}', 'exportCustomerTransactionPDF')->name('customer.transaction.pdf');
        Route::get('customer-list-pdf', 'exportCustomerListPDF')->name('customer.list.pdf');
        Route::get('credit-payment', 'creditPaymentIndex')->name('credit-payment.index');
        Route::get('outside-credit-payment', 'outsidecreditPaymentIndex')->name('outside-credit-payment.index');


        Route::get('customer-export-excel', 'exportExcel')->name('customer.list.excel');
    });

    Route::controller(PurchaseController::class)->group(function () {
        Route::get('purchase', 'index')->name('purchase.index');
    });

    Route::controller(StockController::class)->group(function () {
        Route::get('approved-load', 'index')->name('approved-load.index');
    });

    Route::controller(GlobalTaxController::class)->group(function () {
        Route::get('global-tax', 'index')->name('global-tax.index');
    });

    Route::controller(CommissionTypeController::class)->group(function () {
        Route::get('commission-type', 'index')->name('commission-type.index');
    });

    Route::controller(StockController::class)->group(function () {
        Route::get('return-load', 'returnLoad')->name('return-load.index');
    });

    Route::controller(AdminOrderController::class)->group(function () {
        Route::get('admin-order', 'index')->name('admin-order.index');
        Route::get('admin-order-list', 'orderList')->name('admin-order-list');
        Route::get('edit-order-payment', 'editOrderPayment')->name('edit-order-payment.index');
    });

    Route::controller(SalesReturnController::class)->group(function () {
        Route::get('sales-return', 'index')->name('sales-return.index');
    });

    Route::controller(PurchaseReturnController::class)->group(function () {
        Route::get('purchase-return', 'index')->name('purchase-return.index');
    });

    Route::controller(OpeningStockController::class)->group(function () {
        Route::get('opening-stock', 'index')->name('opening-stock.index');
    });

    Route::controller(ReportController::class)->group(function () {
        // Purchase
        Route::get('purchase-report', 'purchase_report_index')->name('purchase-report.index');
        Route::post('purchase-report-list', 'purchase_report_list')->name('purchase-report.list');
        Route::get('purchase-report-list-pdf',  'purchase_report_list_pdf')->name("purchase-report-export-to-pdf");
        Route::get('purchase-report-list-excel',  'purchaseExportToExcel')->name("purchase-report-export-to-excel");
        // Order
        Route::get('order-report', 'order_report_index')->name('order-report.index');
        Route::post('order-report-list', 'order_report_list')->name('order-report.list');
        Route::get('order-report-list-pdf',  'order_report_list_pdf')->name("order-report-export-to-pdf");
        Route::get('order-report-list-excel',  'orderExportToExcel')->name("order-report-export-to-excel");
        // Open Close
        Route::get('open-close-report', 'open_close_report_index')->name('open-close.index');
        Route::post('open-close-report-list', 'open_close_report_list')->name('open-close-report.list');
        Route::get('open-close-report-list-pdf',  'open_close_report_list_pdf')->name("open-close-report-export-to-pdf");
        Route::get('open-close-report-list-excel',  'openCloseExportToExcel')->name("open-close-report-export-to-excel");
        // Sales
        Route::get('sales-report', 'sales_report_index')->name('sales-report.index');
        Route::post('sales-report-list', 'sales_report_list')->name('sales-report.list');
        Route::get('sales-report-list-pdf',  'sales_report_list_pdf')->name("sales-report-export-to-pdf");
        Route::get('sales-report-list-excel',  'salesExportToExcel')->name("sales-report-export-to-excel");
        // Daily Sales Report
        Route::get('daily-sales-report', 'daily_sales_report_index')->name('daily-sales-report.index');
        Route::post('daily-sales-report-list', 'daily_sales_report_list')->name('daily-sales-report.list');
        // Employee Payment Collection Report
        Route::get('payment-collection-report', 'employee_payment_collection_report_index')->name('employee-payment-collection-report.index');
        Route::post('employee-payment-collection-report-list', 'employee_payment_collection_report_list')->name('employee-payment-collection-report.list');
        // Get Order Details
        Route::post('get-order-details', 'get_order_details')->name('get-order-details');
        // Customer Credit Report
        Route::get('customer-credit-report', 'customer_credit_report_index')->name('customer-credit-report.index');
        Route::post('customer-credit-report-list', 'customer_credit_report_list')->name('customer-credit-report.list');
        Route::get('outside-customer-credit-report', 'outside_customer_credit_report_index')->name('outside-customer-credit-report.index');
        Route::post('outside-customer-credit-report-list', 'outside_customer_credit_report_list')->name('outside-customer-credit-report.list');
        Route::get('customer-credit-report-list-pdf', 'customer_credit_report_list_pdf')->name('customer-credit-report-export-to-pdf');
        Route::get('customer-credit-report-list-excel', 'customerCreditExportToExcel')->name('customer-credit-report-export-to-excel');
        Route::get('outside-customer-credit-report-list-pdf', 'outside_customer_credit_report_list_pdf')->name('outside-customer-credit-report-export-to-pdf');
        Route::get('outside-customer-credit-report-list-excel', 'outsideCustomerCreditExportToExcel')->name('outside-customer-credit-report-export-to-excel');

        Route::get('customer-due-report', 'customer_due_report_index')->name('customer-due-report.index');
        Route::post('customer-due-report-list', 'customerDueReportList')->name('customer-due-report.list');
        Route::get('customer-due-report-list-pdf', 'customer_due_report_list_pdf')->name('customer-due-report-export-to-pdf');
        Route::get('customer-due-report-list-excel', 'customerDueExportToExcel')->name('customer-due-report-export-to-excel');


        // Stock
        Route::get('stock-report', 'stock_report_index')->name('stock-report.index');
        Route::post('stock-report-list', 'stock_report_list')->name('stock-report.list');
        Route::get('stock-report-list-pdf',  'stock_report_list_pdf')->name("stock-report-export-to-pdf");
        Route::get('stock-report-list-excel',  'stockExportToExcel')->name("stock-report-export-to-excel");

        //original stock
        Route::get('original-stock-report', 'original_stock_report_index')->name('original-stock-report.index');
        Route::post('original-stock-report-list', 'original_stock_report_list')->name('original-stock-report.list');
        Route::get('original-stock-report-list-pdf',  'original_stock_report_list_pdf')->name("original-stock-report-export-to-pdf");
        Route::get('original-stock-report-list-excel',  'originalStockExportToExcel')->name("original-stock-report-export-to-excel");


        Route::get('refill-receipts-excel',  'refillReceiptReportListExcel')->name("receipt-report-export-to-excel");
        // Commission
        Route::get('commission-report', 'commission_report_index')->name('commission-report.index');
        Route::post('commission-report-list', 'commission_report_list')->name('commission-report.list');
        Route::get('commission-report-list-pdf',  'commission_report_list_pdf')->name("commission-report-export-to-pdf");
        Route::get('commission-report-list-excel',  'commissionExportToExcel')->name("commission-report-export-to-excel");
        // Customer Payment Details Report
        Route::get('customer-payment-report', 'payment_report_index')->name('customer-payment-report.index');
        Route::post('customer-payment-report-list', 'payment_report_list')->name('customer-payment-report.list');
        Route::get('customer-payment-report-list-pdf', 'payment_report_list_pdf')->name("customer-payment-report-export-to-pdf");
        Route::get('customer-payment-report-list-excel', 'paymentExportToExcel')->name("customer-payment-report-export-to-excel");
        // Supplier Payment Details Report

        Route::get('supplier-credit-report', 'supplier_credit_report_index')->name('supplier-credit-report.index');
        Route::post('supplier-credit-report-list', 'supplier_credit_report_list')->name('supplier-credit-report.list');
        Route::get('supplier-credit-report-list-pdf', 'supplier_credit_report_list_pdf')->name('supplier-credit-report-export-to-pdf');
        Route::get('supplier-credit-report-list-excel', 'supplierCreditExportToExcel')->name('supplier-credit-report-export-to-excel');


        Route::get('supplier-payment-report', 'supplier_payment_report_index')->name('supplier-payment-report.index');
        Route::post('supplier-payment-report-list', 'supplier_payment_report_list')->name('supplier-payment-report.list');
        Route::get('supplier-payment-report-list-pdf', 'supplier_payment_report_list_pdf')->name("supplier-payment-report-export-to-pdf");
        Route::get('supplier-payment-report-list-excel', 'supplierPaymentExportToExcel')->name("supplier-payment-report-export-to-excel");
    });


    // Product Wise Sales Report
    Route::controller(ProductWiseSalesReportController::class)->group(function () {
        Route::get('product-wise-sales-report', 'index')->name('product-wise-sales-report.index');
        Route::post('product-wise-sales-report/list', 'getProductWiseSalesReport')->name('product-wise-sales-report.list');
        Route::get('product-wise-sales-report/pdf', 'exportPDF')->name('product-wise-sales-report.pdf');
        Route::get('product-wise-sales-report/excel', 'exportExcel')->name('product-wise-sales-report.excel');
    });

    Route::controller(EditCustomerOpeningBalanceController::class)->group(function () {
        Route::get('edit-opening-balance-payment', 'index')->name('edit-opening-balance-payment.index');
        Route::get('opening-balance-payments', 'getOpeningBalancePayments')->name('opening.balance.payments');
        Route::post('verify-password', 'verifyPassword')->name('verify.password');
        Route::post('update-opening-payment', 'updateOpeningPayment')->name('update.order.payment');
    });

    // Expense Management
    Route::controller(ExpenseController::class)->group(function () {
        Route::get('expenses', 'index')->name('expenses.index');
    });

    // Payroll Management
    Route::controller(PayrollController::class)->group(function () {
        Route::get('payroll', 'index')->name('payroll.index');
        Route::get('commission', 'commission_index')->name('commission.index');

        Route::get('advance', 'advance_index')->name('advance.index');
    });


    Route::controller(EmployeeController::class)->group(function () {
        Route::get('employees', 'index')->name('employee.index');
    });
    Route::controller(AttendanceController::class)->group(function () {
        Route::get('attendance', 'index')->name('attendance.index');
        Route::get('monthly_attendance', 'monthly_attendance_index')->name('monthly_attendance.index');
    });
    Route::controller(LeaveController::class)->group(function () {
        Route::get('leave-management', 'index')->name('leaves.index');
    });
    // Accounting Reports
    Route::controller(AccountingReportController::class)->group(function () {
        Route::get('accounting-reports', 'index')->name('accounting-reports.index');

        Route::get('accounts-entry', 'accountsEntry')->name('accounting-report.accountsEntry');
    });

    // Accounting Reports
    Route::controller(FinancialYearController::class)->group(function () {
        Route::get('financial_years', 'index')->name('financial_years.index');
    });

    // Accounting Reports
    Route::controller(OpeningBalanceController::class)->group(function () {
        Route::get('opening_balance', 'index')->name('opening_balance.index');
    });

    // voucher
    Route::controller(VoucherController::class)->group(function () {
        Route::get('vouchers', 'index')->name('vouchers.index');
    });
});


Route::get('invoice-print/{order_code}', [InvoicePrintController::class, 'print_invoice'])
    ->name('invoice.print');
