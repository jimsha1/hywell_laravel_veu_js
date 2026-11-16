<?php

namespace App\Http\Controllers\admin\reports;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ProductWiseSalesRepositoryInterface;
use App\Exports\ProductWiseSalesExcelGenerate;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductWiseSalesReportController extends Controller
{
    private ProductWiseSalesRepositoryInterface $productWiseSalesRepository;

    public function __construct(ProductWiseSalesRepositoryInterface $productWiseSalesRepository)
    {
        $this->productWiseSalesRepository = $productWiseSalesRepository;
    }

    public function index()
    {
        return view('admin.pages.report.productWiseSalesReport');
    }


    public function getProductWiseSalesReport(Request $request)
    {
        try {
            $result = $this->productWiseSalesRepository->getProductWiseSalesReport($request);

            return response()->json([
                'pagination' => $result['pagination'] ?? [],
                'data'       => $result['data'] ?? [],
                'totals'     => $result['totals'] ?? [
                    'total_quantity' => 0,
                    'total_free_quantity' => 0,
                    'total_returned_quantity' => 0,
                ],
            ]);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Export PDF report
     */
    public function exportPDF(Request $request)
    {
        return $this->generatePDF($request);
    }

    /**
     * Export Excel report
     */
    public function exportExcel(Request $request)
    {
        return $this->generateExcel($request);
    }

    /**
     * Generate PDF report
     */
    private function generatePDF(Request $request)
    {
        try {
            // Set export_type to pdf
            $request->merge(['export_type' => 'pdf']);

            $result = $this->productWiseSalesRepository->getProductWiseSalesReport($request);

            $file_name = 'product_wise_sales_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $startDateFormatted = 'N/A';
            $endDateFormatted = 'N/A';

            if (!empty($request->start_date) && strtotime($request->start_date)) {
                $startDateFormatted = date('d M Y', strtotime($request->start_date));
            }

            if (!empty($request->end_date) && strtotime($request->end_date)) {
                $endDateFormatted = date('d M Y', strtotime($request->end_date));
            }

            // Get names for display
            $customer_name = $request->customer_id ? \App\Models\Customer::find($request->customer_id)?->customer_name : null;
            $delivery_man_name = $request->delivered_by ? \App\Models\User::find($request->delivered_by)?->name : null;

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.product_wise_sales_pdf',
                [
                    'results' => $result['data'] ?? collect([]),
                    'startDate' => $startDateFormatted,
                    'endDate' => $endDateFormatted,
                    'product_name' => $request->product_name ?? '',
                    'category_id' => $request->category_id ?? null,
                    'product_id' => $request->product_id ?? null,
                    'customer_name' => $customer_name,
                    'delivery_man_name' => $delivery_man_name,
                    'order_by' => $request->order_by ?? null,
                    $request->is_outside
                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel report
     */
    private function generateExcel(Request $request)
    {
        try {
            return Excel::download(
                new ProductWiseSalesExcelGenerate(
                    $request->product_id,
                  
                    $request->start_date,
                    $request->end_date,
                   
                    $request->customer_id,
                    $request->delivered_by,
                    $request->order_by,
                    $request->is_outside
                ),
                'product_wise_sales_report_' . date('Y_m_d_H_i_s') . '.xlsx'
            );
        } catch (Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Error generating Excel: ' . $e->getMessage());
        }
    }
}
