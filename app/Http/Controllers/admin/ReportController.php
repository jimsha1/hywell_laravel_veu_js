<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Exports\StockExcelGenerate;
use App\Exports\CommissionExcelGenerate;
use App\Exports\PurchaseExcelGenerate;
use App\Exports\SalesExcelGenerate;
use App\Exports\OrderExcelGenerate;
use App\Exports\OpenCloseExcelGenerate;
use App\Exports\CustomerCreditExcelGenerate;
use App\Exports\CustomerDueExcelGenerate;
use App\Exports\OriginalStockExcelGenerate;
use App\Exports\OutsideCustomerCreditExcelGenerate;
use App\Exports\PaymentExcelGenerate;
use App\Exports\SupplierCreditExcelGenerate;
use App\Exports\SupplierPaymentExcelGenerate;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Exception;
use function PHPUnit\Framework\returnArgument;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $ReportService)
    {
        $this->reportService = $ReportService;
    }
    // Purchase
    public function purchase_report_index()
    {
        return view('admin.pages.report.purchaseReport');
    }
    public function purchase_report_list(Request $request)
    {
        return $this->reportService->purchaseReportList($request);
    }
    public function purchase_report_list_pdf(Request $request)
    {
        return $this->reportService->purchaseReportListPDF($request);
    }
    public function purchaseExportToExcel(Request $request)
    {
        return Excel::download(
            new PurchaseExcelGenerate(
                $request->supplier_id,
                $request->start_date,
                $request->end_date,
                $request->purchase_no,
                $request->product_id,
                $request->payment_mode,
            ),
            'purchase_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
    // Order
    public function order_report_index()
    {
        return view('admin.pages.report.orderReport');
    }
    public function order_report_list(Request $request)
    {
        return $this->reportService->orderReportList($request);
    }
    public function order_report_list_pdf(Request $request)
    {
        return $this->reportService->orderReportListPDF($request);
    }
    public function orderExportToExcel(Request $request)
    {

        return Excel::download(
            new OrderExcelGenerate(
                $request->customer_name,
                $request->start_date,
                $request->end_date,
                $request->order,
                $request->order_status,
                $request->customer_type,
            ),
            'order_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
    //Open Close
    public function open_close_report_index()
    {
        return view('admin.pages.report.openCloseReport');
    }
    public function open_close_report_list(Request $request)
    {
        return $this->reportService->openCloseReportList($request);
    }
    public function open_close_report_list_pdf(Request $request)
    {
        return $this->reportService->openCloseReportListPDF($request);
    }
    public function openCloseExportToExcel(Request $request)
    {
        return Excel::download(
            new OpenCloseExcelGenerate(
                $request->vehicle_id,
                $request->start_date,
                $request->end_date,
                $request->stock_code,

            ),
            'open_close_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
    //Sales
    public function sales_report_index()
    {
        return view('admin.pages.report.salesReport');
    }
    public function customer_due_report_index()
    {
        return view('admin.pages.report.customerDueReport');
    }
    public function sales_report_list(Request $request)
    {
        return $this->reportService->salesReportList($request);
    }
    public function customerDueReportList(Request $request)
    {
        return $this->reportService->customerDueReportList($request);
    }
    public function sales_report_list_pdf(Request $request)
    {
        return $this->reportService->salesReportListPDF($request);
    }
    public function salesExportToExcel(Request $request)
    {
        // dd(  $request->payment_mode);
        return Excel::download(
            new SalesExcelGenerate(
                $request->order,
                $request->start_date,
                $request->end_date,
                $request->customer_name,
                $request->payment_mode,
                $request->payment_status,
                $request->delivery_status,
                $request->filter_order_by,
                $request->filter_delivery_by,
                $request->customer_type,
                $request->is_outside
            ),
            'sales_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
    // Stock
    public function stock_report_index()
    {
        return view('admin.pages.report.stockReport');
    }
    public function stock_report_list(Request $request)
    {
        return $this->reportService->stockReportList($request);
    }


    public function stock_report_list_pdf(Request $request)
    {
        return $this->reportService->stockReportListPDF($request);
    }

    public function stockExportToExcel(Request $request)
    {
        return Excel::download(
            new StockExcelGenerate(
                $request->product_id,
                $request->salesman_id,
                $request->stock_movement_mode,
                $request->start_date,
                $request->end_date,
                $request->customer_type,
            ),
            'stock_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }


    public function original_stock_report_index()
    {
        return view('admin.pages.report.originalStockReport');
    }
    public function original_stock_report_list(Request $request)
    {
        return $this->reportService->originalStockReportList($request);
    }

    public function original_stock_report_list_pdf(Request $request)
    {
        return $this->reportService->originalStockReportListPDF($request);
    }

    public function originalStockExportToExcel(Request $request)
    {
        return Excel::download(
            new OriginalStockExcelGenerate(
                $request->product_id,
                $request->salesman_id,
                $request->stock_movement_mode,
                $request->start_date,
                $request->end_date,
                $request->customer_type,
            ),
            'original_stock_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }

    // Commission
    public function commission_report_index()
    {
        return view('admin.pages.report.commissionReport');
    }
    public function commission_report_list(Request $request)
    {
        return $this->reportService->commissionReportList($request);
    }
    public function commission_report_list_pdf(Request $request)
    {
        return $this->reportService->commissionReportListPDF($request);
    }
    public function commissionExportToExcel(Request $request)
    {
        return Excel::download(
            new CommissionExcelGenerate(
                $request->product_id,
                $request->start_date,
                $request->end_date,
                $request->employee_name,
                $request->order_code
            ),
            'commission_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }

    // Daily Sales Report
    public function daily_sales_report_index()
    {
        return view('admin.pages.report.daily_sales_report');
    }

    public function daily_sales_report_list(Request $request)
    {
        return $this->reportService->dailySalesReportList($request);
    }

    // Customer Credit Report
    public function customer_credit_report_index()
    {
        return view('admin.pages.report.customer_credit_report');
    }

    public function outside_customer_credit_report_index()
    {
        return view('admin.pages.report.outside_customer_credit_report');
    }
    public function customer_credit_report_list(Request $request)
    {
        return $this->reportService->customerCreditReportList($request);
    }

    public function outside_customer_credit_report_list(Request $request)
    {
        return $this->reportService->outsideCustomerCreditReportList($request);
    }
    public function customer_credit_report_list_pdf(Request $request)
    {
        return $this->reportService->customerCreditReportListPDF($request);
    }

    public function outside_customer_credit_report_list_pdf(Request $request)
    {
        return $this->reportService->outsideCustomerCreditReportListPDF($request);
    }
    public function customerCreditExportToExcel(Request $request)
    {
        return Excel::download(
            new CustomerCreditExcelGenerate(
                $request->customer_name,
                $request->customer_code,
                $request->employee_id,
                $request->employee_type
            ),
            'customer_credit_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
    public function outsideCustomerCreditExportToExcel(Request $request)
    {
        return Excel::download(
            new OutsideCustomerCreditExcelGenerate(
                $request->customer_name,
                $request->customer_code,
                $request->employee_id,
                $request->employee_type
            ),
            'outside_customer_credit_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }

    // Employee Payment Collection Report
    public function employee_payment_collection_report_index()
    {
        return view('admin.pages.report.employee_payment_collection_report');
    }

    public function employee_payment_collection_report_list(Request $request)
    {
        return $this->reportService->employeePaymentCollectionReportList($request);
    }

    public function get_order_details(Request $request)
    {
        return $this->reportService->getOrderDetails($request);
    }

    // Payment Details Report
    public function payment_report_index()
    {
        return view('admin.pages.report.paymentReport');
    }

    public function payment_report_list(Request $request)
    {
        return $this->reportService->paymentReportList($request);
    }

    public function payment_report_list_pdf(Request $request)
    {
        return $this->reportService->paymentReportListPDF($request);
    }

    public function paymentExportToExcel(Request $request)
    {
        return Excel::download(
            new PaymentExcelGenerate(
                // $request->customer_id,
                $request->customer_search,
                $request->start_date,
                $request->end_date,
                $request->employee_id,
                $request->payment_type,
                $request->customer_type,
            ),
            'payment_details_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }

    // Supplier Credit Report
    public function supplier_credit_report_index()
    {
        return view('admin.pages.report.supplier_credit_report');
    }

    public function supplier_credit_report_list(Request $request)
    {
        return $this->reportService->supplierCreditReportList($request);
    }

    public function supplier_credit_report_list_pdf(Request $request)
    {
        return $this->reportService->supplierCreditReportListPDF($request);
    }

    public function supplierCreditExportToExcel(Request $request)
    {
        return Excel::download(
            new SupplierCreditExcelGenerate(
                $request->supplier_name,
                $request->supplier_code
            ),
            'supplier_credit_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }


    public function supplier_payment_report_index()
    {
        return view('admin.pages.report.supplierPaymentReport');
    }

    public function supplier_payment_report_list(Request $request)
    {
        return $this->reportService->supplierPaymentReportList($request);
    }

    public function supplier_payment_report_list_pdf(Request $request)
    {
        return $this->reportService->supplierPaymentReportListPDF($request);
    }

    public function supplierPaymentExportToExcel(Request $request)
    {
        return Excel::download(
            new SupplierPaymentExcelGenerate(
                $request->supplier_id,
                $request->start_date,
                $request->end_date,
            ),
            'supplier_payment_details_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }


    public function customer_due_report_list_pdf(Request $request)
    {
        return $this->reportService->customerDueReportListPDF($request);
    }


    public function customerDueExportToExcel(Request $request)
    {

        return Excel::download(
            new CustomerDueExcelGenerate(
                $request


            ),
            'customer_due_report_' . date('Y_m_d_H_i_s') . '.xlsx'
        );
    }
}
