<?php

namespace App\Services;

use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\OrderPayment;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Products;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\StockMovement;
use App\Models\VehicleLoadStock;
use App\Models\Customer;
use App\Models\OpeningBalance;
use App\Models\StockOriginal;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportService
{
    /**
     * Normalize payment mode names
     * "TRANSFER SAB" and "BANK TRANSFER SAB" are treated as the same
     * "TRANSFER ALRAJHI" and "BANK TRANSFER ALRAJHI" are treated as the same
     */
    protected function normalizePaymentMode($mode)
    {
        $mode = strtoupper(trim($mode ?? ''));

        switch ($mode) {

            case 'CASH':
                return 'cash';

            case 'BANK':
                return 'bank';

            case 'CARD':
                return 'card';

            case 'ONLINE':
                return 'online';

            case 'ALRAJHI':
            case 'TRANSFER ALRAJHI':
            case 'BANK TRANSFER ALRAJHI':
                return 'transfer alrajhi';

            case 'SAB':
            case 'TRANSFER SAB':
            case 'BANK TRANSFER SAB':
                return 'transfer sab';

            case 'POS ALRAJHI':
                return 'pos alrajhi';

            case 'POS SAB':
                return 'pos sab';

            default:
                return strtolower($mode);
        }
    }

    // Purchase
    public function purchaseReportList($data = null, $platform = 'web')
    {
        $result = Purchase::with('supplier', 'purchaseItem.product', 'purchasePayment');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('purchase_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('purchase_date', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('purchase_date', '<=', $endDate . ' 23:59:59');
            } else {
            }

            if (!empty($data->purchase_no)) {
                $result->where('purchase_no', 'like', '%' . $data->purchase_no . '%');
            }

            if (!empty($data->supplier_id)) {
                $result->where('supplier_id', $data->supplier_id);
            }

            if (!empty($data->product_id)) {
                $result->whereHas('purchaseItem.product', function ($q) use ($data) {
                    $q->where('id', $data->product_id);
                });
            }

            if (!empty($data->payment_mode)) {
                $result->whereHas('purchasePayment', function ($q) use ($data) {
                    $q->where('payment_mode', $data->payment_mode);
                })->with(['purchasePayment' => function ($q) use ($data) {
                    $q->where('payment_mode', $data->payment_mode);
                }]);
            }


            $result = $result;

            // Summary calculations
            $res = $result->get();

            $summary = [
                'cash' => 0,
                'bank_transfer_alrajhi' => 0,
                'bank_transfer_sab' => 0,
                'pos_alrajhis' => 0,
                'pos_sab' => 0,
                // 'credit' => 0,
            ];

            $totalPurchaseAmount = 0;

            foreach ($res as $purchase) {

                $totalPurchaseAmount += $purchase->purchaseItem->sum('total');

                foreach ($purchase->purchasePayment as $payment) {

                    $mode = $this->normalizePaymentMode($payment->payment_mode);
                    $amount = (float) $payment->amount_taken;

                    switch ($mode) {

                        case 'cash':
                            $summary['cash'] += $amount;
                            break;

                        case 'transfer alrajhi':
                            $summary['bank_transfer_alrajhi'] += $amount;
                            break;

                        case 'transfer sab':
                            $summary['bank_transfer_sab'] += $amount;
                            break;

                        case 'pos alrajhi':
                            $summary['pos_alrajhis'] += $amount;
                            break;

                        case 'pos sab':
                            $summary['pos_sab'] += $amount;
                            break;

                            // case 'credit':
                            //     $summary['credit'] += $amount;
                            //     break;
                    }
                }
            }


            $totalPurchaseAmount = $res->sum(function ($purchase) {
                return $purchase->purchaseItem->sum('total');
            });

            $totalQuantity = $res->sum(function ($purchase) {
                return $purchase->purchaseItem->sum('qty');
            });

            $totalPurchases = $res->count();

            $result = $result->orderBy('created_at', 'DESC')
                ->paginate(config('settings.pagination.perpage.admin'));

            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;
            $totalBalanceAmount = $res->sum(function ($purchase) {
                return (float) $purchase->balance_amount;
            });
            $totalRefundFromOrderBalance = $res->sum(function ($purchase) {
                return (float) $purchase->refund_amount_from_order_balance;
            });
            $totalRefundAmount = $res->sum(function ($purchase) {
                return (float) $purchase->total_refund_amount;
            });
            $response['totals'] = [
                'total_amount' => $totalPurchaseAmount,
                'cash' => $summary['cash'],
                'bank_transfer_alrajhi' => $summary['bank_transfer_alrajhi'],
                'bank_transfer_sab' => $summary['bank_transfer_sab'],
                'pos_alrajhis' => $summary['pos_alrajhis'],
                'pos_sab' => $summary['pos_sab'],
                'totalRefundFromOrderBalance' => $totalRefundFromOrderBalance,
                'totalRefundAmount' => $totalRefundAmount,
                'credit' => $totalBalanceAmount,
                // 'credit' => $summary['credit'],
            ];

            return response()->json($response);
        } else {
        }
    }

    public function purchaseReportListPDF($data = null, $platform = 'web')
    {
        $result = Purchase::with('supplier', 'purchaseItem.product', 'purchasePayment');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('purchase_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('purchase_date', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('purchase_date', '<=', $endDate . ' 23:59:59');
            } else {
            }

            if (!empty($data->purchase_no)) {
                $result->where('purchase_no', 'like', '%' . $data->purchase_no . '%');
            }

            if (!empty($data->supplier_id)) {
                $result->where('supplier_id', $data->supplier_id);
            }

            if (!empty($data->product_id)) {
                $result->whereHas('purchaseItem.product', function ($q) use ($data) {
                    $q->where('id', $data->product_id);
                });
            }

            if (!empty($data->payment_mode)) {
                $result->whereHas('purchasePayment', function ($q) use ($data) {
                    $q->where('payment_mode', $data->payment_mode);
                })->with(['purchasePayment' => function ($q) use ($data) {
                    $q->where('payment_mode', $data->payment_mode);
                }]);
            }

            $suplierName = '';
            if ($data->supplier_id) {
                $suplier = Supplier::find($data->supplier_id);
                if ($suplier) {
                    $suplierName = $suplier->supplier_name;
                }
            }

            $productName = '';
            if ($data->product_id) {
                $product = Products::find($data->product_id);
                if ($product) {
                    $productName = $product->product_name;
                }
            }
            $purchases = $result->orderBy('created_at', 'DESC')->limit(300)->get();
            $file_name = 'purchase_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.purchase_pdf',
                [
                    'purchases' => $purchases,
                    'purchase_no' => $data->purchase_no,
                    'supplier' => $suplierName,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'productName' => $productName,
                    'payment_mode' => $data->payment_mode,
                ]
            )->setPaper('a4', 'landscape');
        }
        return $pdf->download($file_name);
    }



    // Order
    public function orderReportList($data = null, $platform = 'web')
    {

        $result = Order::with('orderItems.product', 'customer', 'users', 'orderBy', 'deliveryBy')->where('order_status', '!=', 'draft');
        if ($data->customer_type == 'in') {
            $result->where('is_outside', 0);
        } else {
            $result->where('is_outside', 1);
        }

        if ($platform == 'web') {

            $result = $this->applyFilterOrderReport($result, $data);
            // NEW: calculate totals before pagination
            $res = $result->get();


            $totalQty = $res->sum('total_quantity');
            $totalFreeQty = $res->sum(function ($order) {
                return $order->orderItems->sum('free_quantity');
            });
            $totalAmount = $res->sum('total_amount');

            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;

            $response['totals'] = [
                'total_quantity' => $totalQty,
                'total_free_quantity' => $totalFreeQty,
                'total_amount' => $totalAmount,
            ];

            return response()->json($response);
        } else {
        }
    }
    public function orderReportListPDF($data = null, $platform = 'web')
    {
        $result = Order::with('orderItems.product', 'customer', 'orderBy')->where('order_status', '!=', 'draft');
        if ($data->customer_type == 'in') {
            $result->where('is_outside', 0);
        } else {
            $result->where('is_outside', 1);
        }
        if ($platform == 'web') {

            $result = $this->applyFilterOrderReport($result, $data);
            //dd($data);
            $results = $result->orderBy('created_at', 'DESC')->limit(200)->get();
            $file_name = 'order_report_' . date('Y_m_d_H_i_s') . '.pdf';
            // dd($data->customer_name);

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.order_pdf',
                [
                    'results' => $results,
                    'customer_name' => $data->customer_name ?? $data->display_customer,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'order' => $data->order,
                    'order_status' => $data->order_status ?? null,
                    "customer_type" => $data->customer_type ?? null,

                    // 'request' => $request
                ]
            );

            return $pdf->download($file_name);
        }
    }


    public function applyFilterOrderReport($result, $data)
    {
        $startDate = $data->start_date;
        $endDate = $data->end_date;
        if ($endDate != null && $startDate != null) {
            $result->whereBetween('order_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } else if ($endDate == null && $startDate != null) {
            $result->where('order_date', '>=', $startDate . ' 00:00:00');
        } else if ($endDate != null && $startDate == null) {
            $result->where('order_date', '<=', $endDate . ' 23:59:59');
        } else {
        }

        if (!empty($data->order)) {
            $result->where(function ($q) use ($data) {
                $q->where('order_type', $data->order)
                    ->orWhere('order_no', 'LIKE', "%{$data->order}%")
                    ->orWhereHas('orderBy', function ($sub) use ($data) {
                        $sub->where('name', 'LIKE', "%{$data->order}%");
                    });
            });
        }

        if (!empty($data->customer_name)) {


            $result->where(function ($query) use ($data) {

                $query->where(function ($q) use ($data) {
                    $q->where('order_type', '!=', 'Outside Sale')
                        ->whereHas('customer', function ($customer) use ($data) {
                            $customer->where('customer_name', 'LIKE', "%{$data->customer_name}%");
                        });
                });

                $query->orWhere(function ($q) use ($data) {
                    $q->where('order_type', 'Outside Sale')
                        ->whereHas('users', function ($user) use ($data) {
                            $user->where('name', 'LIKE', "%{$data->customer_name}%");
                        });
                });
            });
        }

        if (!empty($data->order_status)) {
            $result->where('order_status', $data->order_status);
        }

        return $result;
    }



    // Open Close
    public function openCloseReportList($data = null, $platform = 'web')
    {
        // dd($data);

        $result = VehicleLoadStock::with('vehicleLoadStockItems.product', 'vehicle', 'user')
            ->where('load_status',  'approved');

        $totalLoad = 0;
        $balanceLoad = 0;

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('created_at', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('created_at', '<=', $endDate . ' 23:59:59');
            } else {
            }

            if (!empty($data->stock_code)) {
                $result->where('v_stock_code', 'like', '%' . $data->stock_code . '%');
            }

            if (!empty($data->vehicle_id)) {
                $result->where('vehicle_id', $data->vehicle_id);
            }

            $res = $result;

            $res = $res->get();

            $totalLoad = $res->sum(function ($stock) {
                return $stock->vehicleLoadStockItems->sum('total_qty');
            });

            $balanceLoad = $res->sum(function ($stock) {
                return $stock->vehicleLoadStockItems->sum('available_qty');
            });

            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;

            $response['totals'] = [
                'totalLoad' => $totalLoad,
                'balanceLoad' => $balanceLoad,

            ];
            return response()->json($response);
        } else {
        }
    }

    public function openCloseReportListPDF($data = null, $platform = 'web')
    {
        // dd($data);
        $result = VehicleLoadStock::with('vehicleLoadStockItems.product', 'vehicle', 'user')
            ->where('load_status',  'approved');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('created_at', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('created_at', '<=', $endDate . ' 23:59:59');
            } else {
            }

            if (!empty($data->stock_code)) {
                $result->where('v_stock_code', 'like', '%' . $data->stock_code . '%');
            }

            if (!empty($data->vehicle_id)) {
                $result->where('vehicle_id', $data->vehicle_id);
            }
            $VehicleName = '';
            if (!empty($data->vehicle_id)) {
                $vehicle = Vehicle::find($data->vehicle_id);
                $VehicleName = $vehicle ? $vehicle->vehicle_name : 'N/A';
            }
            $results = $result->orderBy('created_at', 'DESC')->limit(300)->get();
            $file_name = 'open_close_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.open_close_pdf',
                [
                    'results' => $results,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'stock_code' => $data->stock_code,
                    'vehicleName' => $VehicleName,
                    // 'request' => $request
                ]
            );

            return $pdf->download($file_name);
        }
    }
    // Sales
    public function salesReportList($data = null, $platform = 'web')
    {

        $result = Order::with('orderItems.product', 'customer', 'users', 'orderBy', 'payments', 'deliveryBy')->where('order_status',  'delivered');
        $this->applySalesCustomerTypeFilter($result, $data->customer_type ?? 'Company');
        $totalQty = 0;
        $totalFreeQty = 0;
        $totalAmount = 0;
        $totalPaidAmount = 0;
        $totalRefundAmount = 0;
        $totalRefundOrderBalance = 0;
        $totalBalanceAmount = 0;
        if ($platform == 'web') {

            $result = $this->applyFiltersForSalesReport($result, $data);
            $res = $result->get();

            $totalQty = $res->sum('total_quantity');
            $totalFreeQty = $res->sum(function ($order) {
                return $order->orderItems->sum('free_quantity');
            });
            $totalAmount = $res->sum('total_amount');
            $totalPaidAmount = $res->sum('paid_amount');
            $totalRefundAmount = $res->sum('total_refunded_amount');
            $totalRefundOrderBalance = $res->sum('total_refund_from_order_balance');
            $totalBalanceAmount = $res->sum('balance_amount');


            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;
            $response['totals'] = [
                'total_quantity' => $totalQty,
                'total_free_quantity' => $totalFreeQty,
                'total_amount' => $totalAmount,
                'total_paid_amount' => $totalPaidAmount,
                'total_refund_amount' => $totalRefundAmount,
                'total_refund_order_balance' => $totalRefundOrderBalance,
                'total_balance_amount' => $totalBalanceAmount,
            ];
            return response()->json($response);
        } else {
        }
    }



    public function applyFiltersForSalesReport($result, $data)
    {

        // Filter by delivery status
        if (!empty($data->delivery_status)) {
            $result->where('order_status', $data->delivery_status);
        }

        $startDate = $data->start_date;
        $endDate = $data->end_date;
        if ($endDate != null && $startDate != null) {
            // For delivered orders, filter by delivered_date
            // For requested/rejected orders, filter by created_at or order_date
            if (!empty($data->order_status) && $data->order_status == 'delivered') {
                $result->whereBetween('delivered_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else {
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('delivered_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('delivered_date')
                                ->whereBetween('order_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }
        }

        if (!empty($data->customer_name)) {


            $result->where(function ($query) use ($data) {

                $query->where(function ($q) use ($data) {
                    $q->where('order_type', '!=', 'Outside Sale')
                        ->whereHas('customer', function ($customer) use ($data) {
                            $customer->where('customer_name', 'LIKE', "%{$data->customer_name}%");
                        });
                });

                $query->orWhere(function ($q) use ($data) {
                    $q->where('order_type', 'Outside Sale')
                        ->whereHas('users', function ($user) use ($data) {
                            $user->where('name', 'LIKE', "%{$data->customer_name}%");
                        });
                });
            });
        }


        if (!empty($data->order)) {
            $result->where('order_no', $data->order);
        }

        if (!empty($data->payment_status)) {
            $result->where('payment_status', $data->payment_status);
        }

        // if (!empty($data->payment_mode)) {
        //     $result->whereHas('payments', function ($q) use ($data) {
        //         $q->where('payment_mode', $data->payment_mode);
        //     });
        // }

        if (!empty($data->payment_mode)) {
            $result->whereHas('payments', function ($q) use ($data) {
                $q->where('payment_mode', $data->payment_mode)
                    ->where('type', 'order');
            });
        }

        // if (!empty($data->salesman_id)) {
        //     $result->where(function ($q) use ($data) {
        //         $q->where('order_by', $data->salesman_id)
        //             ->orWhere('delivered_by', $data->salesman_id);
        //     });
        // }

        if (!empty($data->filter_order_by)) {
            $result->where('order_by', $data->filter_order_by);
        }
        if (!empty($data->filter_delivery_by)) {
            $result->where('delivered_by', $data->filter_delivery_by);
        }
        // if (!empty($data->employee_id)) {
        //     $result->where('delivered_by', $data->employee_id);
        // }

        if (!empty($data->employee_id)) {

            $result->whereHas('customer', function ($q) use ($data) {
                $q->where('employee_id', $data->employee_id);
            });
        }


        return $result;
    }

    /**
     * The web report uses Company/Outside while some clients use in/out.
     * Accept both forms so the customer-type filter always selects the right orders.
     */
    private function applySalesCustomerTypeFilter($query, $customerType)
    {
        $customerType = strtolower((string) $customerType);

        return $query->where('is_outside', in_array($customerType, ['in', 'company'], true) ? 0 : 1);
    }

    public function salesReportListPDF($data = null, $platform = 'web')
    {

        $result = Order::with('orderItems.product', 'customer', 'orderBy', 'payments')->where('order_status', 'delivered');
        $this->applySalesCustomerTypeFilter($result, $data->customer_type ?? 'Company');

        if ($platform == 'web') {

            $result = $this->applyFiltersForSalesReport($result, $data);

            $results = $result->orderBy('created_at', 'DESC')->limit(200)->get();
            $file_name = 'sales_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $startDateFormatted = 'N/A';
            $endDateFormatted   = 'N/A';

            if (!empty($data->start_date) && strtotime($data->start_date)) {
                $startDateFormatted = date('d M Y', strtotime($data->start_date));
            }

            if (!empty($data->end_date) && strtotime($data->end_date)) {
                $endDateFormatted = date('d M Y', strtotime($data->end_date));
            }

            $orderByName = 'All';
            if (!empty($data->filter_order_by)) {
                $orderByUser = \App\Models\User::find($data->filter_order_by);
                $orderByName = $orderByUser->name ?? 'All';
            }

            $deliveryByName = 'All';
            if (!empty($data->filter_delivery_by)) {
                $deliveryByUser = \App\Models\User::find($data->filter_delivery_by);
                $deliveryByName = $deliveryByUser->name ?? 'All';
            }



            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.sales_pdf',
                [
                    'results' => $results,
                    'startDate' => $startDateFormatted,
                    'endDate' => $endDateFormatted,
                    'invoice_no' => $data->invoice_no,
                    'customer_name' => $data->customer_name,
                    'payment_mode' => $data->payment_mode,
                    'payment_status' => $data->payment_status,
                    'customer_type' => $data->customer_type,
                    'is_outside' => $data->is_outside,
                    'order_by' => $orderByName,            // added
                    'delivery_by' => $deliveryByName,
                    // 'request' => $request
                ]
            )->setPaper('a4', 'landscape');

            return $pdf->download($file_name);
        }
    }
    // Stock
    public function stockReportList($data = null, $platform = 'web')
    {
        // dd($data);
        $result = StockMovement::with('product');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                // Filter by date field, fallback to created_at if date is null
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->salesman_id)) {
                $result->where('created_by', $data->salesman_id);
            }

            if (!empty($data->stock_movement_mode)) {
                $result->where('reason', $data->stock_movement_mode);
            }

            $this->applyStockCustomerTypeFilter($result, $data->customer_type ?? 'company');

            // Calculate totals from all filtered records (before pagination)
            // Build separate queries for totals to avoid affecting the main query
            $totalInwardQuery = StockMovement::query();
            $totalOutwardQuery = StockMovement::query();

            // Apply same filters to totals queries
            if ($endDate != null && $startDate != null) {
                $totalInwardQuery->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
                $totalOutwardQuery->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $totalInwardQuery->where('product_id', $data->product_id);
                $totalOutwardQuery->where('product_id', $data->product_id);
            }

            if (!empty($data->salesman_id)) {
                $totalInwardQuery->where('created_by', $data->salesman_id);
                $totalOutwardQuery->where('created_by', $data->salesman_id);
            }

            if (!empty($data->stock_movement_mode)) {
                $totalInwardQuery->where('reason', $data->stock_movement_mode);
                $totalOutwardQuery->where('reason', $data->stock_movement_mode);
            }

            $this->applyStockCustomerTypeFilter($totalInwardQuery, $data->customer_type ?? 'company');
            $this->applyStockCustomerTypeFilter($totalOutwardQuery, $data->customer_type ?? 'company');

            $totalInward = (int) $totalInwardQuery->where('movement_type', 'in')->sum('quantity');
            $totalOutward = (int) $totalOutwardQuery->where('movement_type', 'out')->sum('quantity');
            $closingBalance = $totalInward - $totalOutward;

            // Now paginate for the list
            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            // Calculate remaining quantity product-wise
            $balances = [];

            $productIds = $result->pluck('product_id')->unique();

            foreach ($productIds as $productId) {

                $stock = 0;

                $movements = StockMovement::where('product_id', $productId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($movements as $movement) {

                    if ($movement->movement_type == 'in') {
                        $stock += $movement->quantity;
                    } else {
                        $stock -= $movement->quantity;
                    }

                    $balances[$movement->id] = $stock;
                }
            }

            foreach ($result as $item) {
                $item->remaining_qty = $balances[$item->id] ?? 0;
            }
            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;
            $response['totals'] = [
                'total_inward' => $totalInward,
                'total_outward' => $totalOutward,
                'closing_balance' => $closingBalance,
            ];
            return response()->json($response);
        } else {
        }
    }

    // Stock
    public function originalStockReportList($data = null, $platform = 'web')
    {
        $result = StockOriginal::with('product');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->salesman_id)) {
                $result->where('created_by', $data->salesman_id);
            }

            if (!empty($data->stock_movement_mode)) {
                $result->where('movement_type', $data->stock_movement_mode);
            }

            $this->applyStockCustomerTypeFilter($result, $data->customer_type ?? 'company');

            // Helper closure to apply the same filters (except movement_type, which we set explicitly below)
            $applyCommonFilters = function ($query) use ($data, $startDate, $endDate) {
                if ($endDate != null && $startDate != null) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                            ->orWhere(function ($qq) use ($startDate, $endDate) {
                                $qq->whereNull('date')
                                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                            });
                    });
                }

                if (!empty($data->product_id)) {
                    $query->where('product_id', $data->product_id);
                }

                if (!empty($data->salesman_id)) {
                    $query->where('created_by', $data->salesman_id);
                }

                if (!empty($data->stock_movement_mode)) {
                    $query->where('movement_type', $data->stock_movement_mode);
                }

                $this->applyStockCustomerTypeFilter($query, $data->customer_type ?? 'company');

                return $query;
            };

            // Total Inward / Outward (quantity based)
            $totalInwardQuery = $applyCommonFilters(StockOriginal::query());
            $totalOutwardQuery = $applyCommonFilters(StockOriginal::query());

            $totalInward = (int) $totalInwardQuery->where('movement_type', 'in')->sum('quantity');
            $totalOutward = (int) $totalOutwardQuery->where('movement_type', 'out')->sum('quantity');
            $closingBalance = $totalInward - $totalOutward;

            // Total Opening Stock = SUM(quantity * cost_price) for movement_type = 'in'
            $openingStockQuery = $applyCommonFilters(StockOriginal::query());
            $totalOpeningStock = (float) $openingStockQuery
                ->where('movement_type', 'in')
                ->sum(DB::raw('quantity * cost_price'));

            // Total Closing Stock = SUM(quantity * cost_price) for movement_type = 'out'
            $closingStockQuery = $applyCommonFilters(StockOriginal::query());
            $totalClosingStock = (float) $closingStockQuery
                ->where('movement_type', 'out')
                ->sum(DB::raw('quantity * cost_price'));

            // Now paginate for the list
            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));

            // Calculate remaining quantity product-wise
            $balances = [];
            $productIds = $result->pluck('product_id')->unique();

            foreach ($productIds as $productId) {
                $stock = 0;

                $movements = StockOriginal::where('product_id', $productId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($movements as $movement) {
                    if ($movement->movement_type == 'in') {
                        $stock += $movement->quantity;
                    } else {
                        $stock -= $movement->quantity;
                    }
                    $balances[$movement->id] = $stock;
                }
            }

            foreach ($result as $item) {
                $item->remaining_qty = $balances[$item->id] ?? 0;
            }

            $response['pagination'] = generatePaginationData($result);
            $response['data'] = $result;
            $response['totals'] = [
                'total_inward' => $totalInward,
                'total_outward' => $totalOutward,
                'closing_balance' => $closingBalance,
                'total_opening_stock' => $totalOpeningStock,
                'total_closing_stock' => $totalClosingStock,
            ];
            return response()->json($response);
        } else {
        }
    }
    public function originalStockReportListPDF($data = null, $platform = 'web')
    {
        // dd($data);
        $result = StockOriginal::with('product');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;

            if ($endDate != null && $startDate != null) {
                // Filter by date field, fallback to created_at if date is null
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->salesman_id)) {
                $result->where('created_by', $data->salesman_id);
            }

            if (!empty($data->stock_movement_mode)) {
                $result->where('reason', $data->stock_movement_mode);
            }


            // $this->applyStockCustomerTypeFilter($result);

            $productName = '';
            if (!empty($data->product_id)) {
                $product = Products::find($data->product_id);
                $productName = $product ? $product->product_name : 'N/A';
            }

            $stocks = $result->orderBy('created_at', 'DESC')->limit(300)->get();
            // dd($stocks->first()->cost_price);
            $balances = [];

            $productIds = $stocks->pluck('product_id')->unique();

            foreach ($productIds as $productId) {

                $stock = 0;

                $movements = StockOriginal::where('product_id', $productId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($movements as $movement) {

                    if ($movement->movement_type == 'in') {
                        $stock += $movement->quantity;
                    } else {
                        $stock -= $movement->quantity;
                    }

                    $balances[$movement->id] = $stock;
                }
            }

            foreach ($stocks as $item) {
                $item->remaining_qty = $balances[$item->id] ?? 0;
            }
            $file_name = 'original_stock_report_' . date('Y_m_d_H_i_s') . '.pdf';
            $customerTypeLabel = ($data->customer_type ?? 'company') == 'company'
                ? 'Company Customers'
                : 'Outside Customers';


            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.original_stock_pdf',
                [
                    'stocks' => $stocks,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'productName' => $productName,
                    'customerTypeLabel' => $customerTypeLabel,
                    // 'request' => $request
                ]
            );

            return $pdf->download($file_name);
        }
    }
    /**
     * Outside-salesman stock allocations are recorded with these reasons.
     * All other movements belong to the company-customer stock report.
     */
    private function applyStockCustomerTypeFilter($query)
    {
        $outsideStockReasons = [
            'vehicle opening stock for outsider',
            'vehicle opening re-stock for outsider',
        ];

        // if (strtolower((string) $customerType) === 'outside') {
        //     return $query->whereIn('reason', $outsideStockReasons);
        // }

        // return $query->where(function ($reasonQuery) use ($outsideStockReasons) {
        //     $reasonQuery->whereNull('reason')
        //         ->orWhereNotIn('reason', $outsideStockReasons);
        // });
    }

    public function stockReportListPDF($data = null, $platform = 'web')
    {
        // dd($data);
        $result = StockMovement::with('product');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;

            if ($endDate != null && $startDate != null) {
                // Filter by date field, fallback to created_at if date is null
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->salesman_id)) {
                $result->where('created_by', $data->salesman_id);
            }

            if (!empty($data->stock_movement_mode)) {
                $result->where('reason', $data->stock_movement_mode);
            }


            $this->applyStockCustomerTypeFilter($result, $data->customer_type ?? 'company');

            $productName = '';
            if (!empty($data->product_id)) {
                $product = Products::find($data->product_id);
                $productName = $product ? $product->product_name : 'N/A';
            }

            $stocks = $result->orderBy('created_at', 'DESC')->limit(300)->get();
            $balances = [];

            $productIds = $stocks->pluck('product_id')->unique();

            foreach ($productIds as $productId) {

                $stock = 0;

                $movements = StockMovement::where('product_id', $productId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($movements as $movement) {

                    if ($movement->movement_type == 'in') {
                        $stock += $movement->quantity;
                    } else {
                        $stock -= $movement->quantity;
                    }

                    $balances[$movement->id] = $stock;
                }
            }

            foreach ($stocks as $item) {
                $item->remaining_qty = $balances[$item->id] ?? 0;
            }
            $file_name = 'stock_report_' . date('Y_m_d_H_i_s') . '.pdf';
            $customerTypeLabel = ($data->customer_type ?? 'company') == 'company'
                ? 'Company Customers'
                : 'Outside Customers';


            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.stock_pdf',
                [
                    'stocks' => $stocks,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'productName' => $productName,
                    'customerTypeLabel' => $customerTypeLabel,
                    // 'request' => $request
                ]
            );

            return $pdf->download($file_name);
        }
    }



    // Commission
    public function commissionReportList($data = null, $platform = 'web')
    {
        $result = CommissionHistory::with('product', 'orderBy', 'order');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;

            if ($endDate != null && $startDate != null) {
                // Filter by date field, fallback to created_at if date is null
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->employee_name)) {
                $result->whereHas('orderBy', function ($query) use ($data) {
                    $query->where('name', 'like', '%' . $data->employee_name . '%');
                });
            }

            if (!empty($data->order_code)) {
                $result->whereHas('order', function ($query) use ($data) {
                    $query->where('order_no', 'like', '%' . $data->order_code . '%');
                });
            }

            $result->where('commission_amount', '!=', 0);
            $total_commission = $result->sum('commission_amount');
            $total_records = $result->count();

            $result = $result->orderBy('date', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            $response['pagination'] = generatePaginationData($result);
            $response['total_commission'] = $total_commission;
            $response['total_records'] = $total_records;
            $response['data'] = $result;

            return response()->json($response);
        }
    }


    public function commissionReportListPDF($data = null, $platform = 'web')
    {
        // dd($data);
        $result = CommissionHistory::with('product', 'orderBy', 'order');

        if ($platform == 'web') {

            $startDate = $data->start_date;
            $endDate = $data->end_date;

            //  if ($endDate != null && $startDate != null) {
            //     // Filter by date field, fallback to created_at if date is null
            //     $result->where(function ($query) use ($startDate, $endDate) {
            //         $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            //             ->orWhere(function ($q) use ($startDate, $endDate) {
            //                 $q->whereNull('date')
            //                     ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            //             });
            //     });
            // }

            if ($endDate != null && $startDate != null) {
                $result->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('date')
                                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            } else if ($endDate == null && $startDate != null) {
                $result->where(function ($query) use ($startDate) {
                    $query->where(function ($q) use ($startDate) {
                        $q->whereNotNull('date')
                            ->where('date', '>=', $startDate . ' 00:00:00');
                    })->orWhere(function ($q) use ($startDate) {
                        $q->whereNull('date')
                            ->where('created_at', '>=', $startDate . ' 00:00:00');
                    });
                });
            } else if ($endDate != null && $startDate == null) {
                $result->where(function ($query) use ($endDate) {
                    $query->where(function ($q) use ($endDate) {
                        $q->whereNotNull('date')
                            ->where('date', '<=', $endDate . ' 23:59:59');
                    })->orWhere(function ($q) use ($endDate) {
                        $q->whereNull('date')
                            ->where('created_at', '<=', $endDate . ' 23:59:59');
                    });
                });
            } else {
            }

            if (!empty($data->product_id)) {
                $result->where('product_id', $data->product_id);
            }

            if (!empty($data->employee_name)) {
                $result->whereHas('orderBy', function ($query) use ($data) {
                    $query->where('name', 'like', '%' . $data->employee_name . '%');
                });
            }

            if (!empty($data->order_code)) {
                $result->whereHas('order', function ($query) use ($data) {
                    $query->where('order_no', 'like', '%' . $data->order_code . '%');
                });
            }

            $productName = '';
            if (!empty($data->product_id)) {
                $product = Products::find($data->product_id);
                $productName = $product ? $product->product_name : 'N/A';
            }

            $results = $result->orderBy('date', 'DESC')->orderBy('created_at', 'DESC')->limit(300)->get();

            $file_name = 'commission_report_' . date('Y_m_d_H_i_s') . '.pdf';
            // dd($data->employee_name);
            $pdf = Pdf::loadView(

                'admin.pages.report.pdf.commission_pdf',
                [
                    'results' => $results,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'productName' => $productName,
                    'employee' => $data->employee_name,
                    'orderCode' => $data->order_code ?? ''
                    // 'request' => $request
                ]
            );

            return $pdf->download($file_name);
        }
    }

    // Daily Sales Report - Order wise with per-mode amounts
    public function dailySalesReportList123($data = null, $platform = 'web')
    {
        try {
            $query = Order::with('payments', 'orderBy', 'deliveryBy')
                ->where('order_status', '!=', 'draft');

            $startDate = $data->start_date ?? null;
            $endDate = $data->end_date ?? null;
            if ($endDate && $startDate) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if (!empty($data->employee_id)) {
                $query->where(function ($q) use ($data) {
                    $q->where('order_by', $data->employee_id)
                        ->orWhere('delivered_by', $data->employee_id);
                });
            }



            $orders = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));

            $grandTotals = [
                'cash' => 0,
                'bank' => 0,
                'card' => 0,
                'online' => 0,
                'credit' => 0,
                'bank_transfer_alrajhi' => 0,
                'bank_transfer_sab' => 0,
                'pos_alrajhis' => 0,
                'pos_sab' => 0,
            ];

            $ordersTransformed = $orders->getCollection()->map(function ($order) use (&$grandTotals) {
                $byMode = [
                    'cash' => 0,
                    'bank' => 0,
                    'card' => 0,
                    'online' => 0,
                    'credit' => 0,
                    'bank_transfer_alrajhi' => 0,
                    'bank_transfer_sab' => 0,
                    'pos_alrajhis' => 0,
                    'pos_sab' => 0,
                ];
                foreach ($order->payments as $payment) {
                    $mode = $this->normalizePaymentMode($payment->payment_mode ?? '');
                    $amount = (float)($payment->paid_amount ?? 0);
                    // normalize mapping to detailed buckets
                    // Database values: "POS SAB", "POS ALRAJHI", "BANK TRANSFER SAB", "BANK TRANSFER ALRAJHI"
                    switch ($mode) {

                        case 'cash':
                            $result['cash'] += $amount;
                            break;

                        case 'bank':
                            $result['bank'] += $amount;
                            break;

                        case 'card':
                            $result['card'] += $amount;
                            break;

                        case 'online':
                            $result['online'] += $amount;
                            break;

                        case 'transfer alrajhi':
                            $result['bank_transfer_alrajhi'] += $amount;
                            break;

                        case 'transfer sab':
                            $result['bank_transfer_sab'] += $amount;
                            break;

                        case 'pos alrajhi':
                            $result['pos_alrajhis'] += $amount;
                            break;

                        case 'pos sab':
                            $result['pos_sab'] += $amount;
                            break;
                    }
                }
                // Credit amount from balance_amount field in order table
                $creditAmount = (float)($order->balance_amount ?? 0);
                $byMode['credit'] = $creditAmount;
                $grandTotals['credit'] += $creditAmount;

                return [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'order_by' => $order->orderBy->name ?? '-',
                    'delivered_by' => $order->deliveryBy->name ?? '-',
                    'cash' => $byMode['cash'],
                    'bank' => $byMode['bank'],
                    'card' => $byMode['card'],
                    'online' => $byMode['online'],
                    'credit' => $byMode['credit'],
                    'bank_transfer_alrajhi' => $byMode['bank_transfer_alrajhi'],
                    'bank_transfer_sab' => $byMode['bank_transfer_sab'],
                    'pos_alrajhis' => $byMode['pos_alrajhis'],
                    'pos_sab' => $byMode['pos_sab'],
                ];
            })->values();

            $response = [
                'orders' => $ordersTransformed,
                'grand_totals' => $grandTotals,
                'pagination' => generatePaginationData($orders),
            ];

            return successResponse($response, 'Daily sales report retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function dailySalesReportList($data = null, $platform = 'web')
    {
        try {

            $query = Order::with('payments', 'orderBy', 'deliveryBy')
                ->where(function ($q) {
                    $q->where('order_status', 'requested')
                        ->orWhere('order_status', 'delivered');
                });
            if ($data->employee_type == 'in') {
                $query = $query->where('is_outside', 0);
            } else {
                $query = $query->where('is_outside', 1);
            }
            $startDate = $data->start_date ?? null;
            $endDate = $data->end_date ?? null;

            if ($endDate && $startDate) {
                $query->whereBetween('delivered_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } elseif ($startDate) {
                $query->where('delivered_date', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('delivered_date', '<=', $endDate . ' 23:59:59');
            }

            if (!empty($data->employee_id)) {
                $query->where(function ($q) use ($data) {
                    $q->where('order_by', $data->employee_id)
                        ->orWhere('delivered_by', $data->employee_id);
                });
            }

            // Fetch ALL orders (for totals)
            $allOrders = (clone $query)->get();

            // Initialize totals
            $grandTotals = [
                'cash' => 0,
                'bank' => 0,
                'card' => 0,
                'online' => 0,
                'bank_transfer_alrajhi' => 0,
                'bank_transfer_sab' => 0,
                'pos_alrajhis' => 0,
                'pos_sab' => 0,
                'credit' => 0,
                'grand_total' => 0   // NEW → Will NOT include credit
            ];

            foreach ($allOrders as $order) {
                foreach ($order->payments as $payment) {
                    // dd($order->payments->pluck('payment_mode'));
                    $mode = $this->normalizePaymentMode($payment->payment_mode ?? '');
                    $amount = (float)($payment->paid_amount ?? 0);

                    switch ($mode) {
                        case 'transfer alrajhi':
                        case 'bank transfer alrajhi':
                        case 'transfer alrajhi_refund':
                        case 'bank transfer alrajhi_refund':
                            $grandTotals['bank_transfer_alrajhi'] += $amount;
                            break;
                        case 'transfer sab':
                        case 'bank transfer sab':
                        case 'transfer sab_refund':
                        case 'bank transfer sab_refund':
                            $grandTotals['bank_transfer_sab'] += $amount;
                            break;
                        case 'pos alrajhi':
                        case 'pos alrajhi_refund':
                            $grandTotals['pos_alrajhis'] += $amount;
                            break;
                        case 'pos sab':
                        case 'pos sab_refund':
                            $grandTotals['pos_sab'] += $amount;
                            break;
                        case 'card':
                        case 'card_refund':
                            $grandTotals['card'] += $amount;
                            break;
                        case 'cash':
                        case 'cash_refund':
                            $grandTotals['cash'] += $amount;
                            break;
                        case 'bank':
                        case 'bank_refund':
                            $grandTotals['bank'] += $amount;
                            break;
                        case 'online':
                        case 'online_refund':
                            $grandTotals['online'] += $amount;
                            break;
                    }
                }

                // Credit is *not* added to grand total — it is tracked separately
                $grandTotals['credit'] += (float)($order->balance_amount ?? 0);
            }

            // Calculate paid grand total
            $total =
                $grandTotals['cash'] +
                $grandTotals['bank'] +
                $grandTotals['card'] +
                $grandTotals['online'] +
                $grandTotals['bank_transfer_alrajhi'] +
                $grandTotals['bank_transfer_sab'] +
                $grandTotals['pos_alrajhis'] +
                $grandTotals['pos_sab'];

            $grandTotals['grand_total'] = $total;
            $grandTotals['grand_total_sales'] = $total + $grandTotals['credit'];

            // Paginate orders for listing
            $orders = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));

            $ordersTransformed = $orders->getCollection()->map(function ($order) {
                $result = [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'order_by' => $order->orderBy->name ?? '-',
                    'order_status' => $order->order_status ?? '-',
                    'delivered_by' => $order->deliveryBy->name ?? '-',
                    'cash' => 0,
                    'bank' => 0,
                    'card' => 0,
                    'online' => 0,
                    'credit' => (float)($order->balance_amount ?? 0),
                    'bank_transfer_alrajhi' => 0,
                    'bank_transfer_sab' => 0,
                    'pos_alrajhis' => 0,
                    'pos_sab' => 0,
                ];

                foreach ($order->payments as $payment) {
                    // dd($order->payments->pluck('payment_mode'));
                    // dd($payment->payment_mode, $this->normalizePaymentMode($payment->payment_mode));
                    $mode = $this->normalizePaymentMode($payment->payment_mode ?? '');
                    $amount = (float)($payment->paid_amount ?? 0);

                    switch ($mode) {
                        case 'transfer alrajhi':
                        case 'bank transfer alrajhi':
                            $result['bank_transfer_alrajhi'] += $amount;
                            break;
                        case 'transfer sab':
                        case 'bank transfer sab':
                            $result['bank_transfer_sab'] += $amount;
                            break;
                        case 'pos alrajhi':
                            $result['pos_alrajhis'] += $amount;
                            break;
                        case 'pos sab':
                            $result['pos_sab'] += $amount;
                            break;
                        case 'card':
                            $result['card'] += $amount;
                            break;
                        case 'cash':
                            $result['cash'] += $amount;
                            break;
                        case 'bank':
                            $result['bank'] += $amount;
                            break;
                        case 'online':
                            $result['online'] += $amount;
                            break;
                    }
                }

                return $result;
            })->values();

            return successResponse([
                'orders' => $ordersTransformed,
                'grand_totals' => $grandTotals,
                'pagination' => generatePaginationData($orders),
            ], 'Daily sales report retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }



    // Customer Credit Report
    // public function customerCreditReportList($data = null, $platform = 'web')
    // {
    //     try {
    //         $query = \App\Models\Customer::query();

    //         // Customer name filter
    //         if (!empty($data->customer_name)) {
    //             $query->where('customer_name', 'LIKE', "%{$data->customer_name}%");
    //         }

    //         // Customer code filter
    //         if (!empty($data->customer_code)) {
    //             $query->where('customer_code', 'LIKE', "%{$data->customer_code}%");
    //         }

    //         // Employee filter
    //         if (!empty($data->employee_id)) {
    //             $query->where('employee_id', $data->employee_id);
    //         }

    //         // Filter only customers with credit balance (balance_amount > 0)
    //         if ($platform == 'web') {
    //             $query->where('balance_amount', '>', 0);
    //         }

    //         // Employee Type filter
    //         if (!empty($data->employee_type)) {

    //             if ($data->employee_type == 'company') {
    //                 $query->where('is_outside', 0);
    //             }

    //             if ($data->employee_type == 'outside') {
    //                 $query->where('is_outside', 1);
    //             }
    //         }

    //         if ($platform == 'web') {
    //             // Calculate total credit amount for all pages (before pagination)
    //             $totalCreditAmount = (clone $query)->where('balance_amount', '>', 0)->sum('balance_amount');

    //             $result = $query->orderBy('balance_amount', 'DESC')
    //                 ->paginate(config('settings.pagination.perpage.admin'));

    //             $response['pagination'] = generatePaginationData($result);
    //             $response['data'] = $result;
    //             $response['total_credit_amount'] = $totalCreditAmount;
    //             return response()->json($response);
    //         } else {
    //             $result = $query->orderBy('balance_amount', 'DESC')->limit(300)->get();
    //             return successResponse($result, 'Customer credit report retrieved successfully.', 0, 200);
    //         }
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }



    // public function customerCreditReportList($data = null, $platform = 'web')
    // {
    //     try {

    //         $query = Customer::where('is_outside', 0);
    //         if (!empty($data->customer_name)) {
    //             $query->where('customer_name', 'LIKE', "%{$data->customer_name}%");
    //         }

    //         if (!empty($data->customer_code)) {
    //             $query->where('customer_code', 'LIKE', "%{$data->customer_code}%");
    //         }

    //         if (!empty($data->employee_id)) {
    //             $query->where('employee_id', $data->employee_id);
    //         }

    //         if ($platform == 'web') {
    //             $query->where('balance_amount', '>', 0);
    //         }



    //         if ($platform == 'web') {

    //             $totalCreditAmount = (clone $query)->sum('balance_amount');

    //             $result = $query
    //                 ->orderByDesc('balance_amount')
    //                 ->paginate(config('settings.pagination.perpage.admin'));

    //             return response()->json([
    //                 'pagination' => generatePaginationData($result),
    //                 'data' => $result,
    //                 'total_credit_amount' => $totalCreditAmount,
    //             ]);
    //         }

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Mobile Response
    //     |--------------------------------------------------------------------------
    //     */

    //         $result = $query
    //             ->orderByDesc('balance_amount')
    //             ->limit(300)
    //             ->get();
    //         dd($result);
    //         return successResponse(
    //             $result,
    //             'Customer credit report retrieved successfully.',
    //             0,
    //             200
    //         );
    //     } catch (\Exception $e) {

    //         return errorResponse(
    //             null,
    //             $e->getMessage(),
    //             100,
    //             500
    //         );
    //     }
    // }

    // public function customerDueReportList($data = null, $platform = 'web')
    // {

    //     $result = Order::with('customer', 'payments')
    //         ->where('order_status',  'delivered')
    //         ->where('payment_status', 'Partial')
    //         ->where('is_outside', 0);
    //     // ->where('order_type','!=','Outside Sale');


    //     $totalBalanceAmount = 0;
    //     if ($platform == 'web') {

    //         $result = $this->applyFiltersForSalesReport($result, $data);

    //         if (!empty($data->due_time) && $data->due_time == 'ninty') {
    //             $result->where('delivered_date', '<=', now()->subDays(90));
    //         }
    //         if (!empty($data->due_time) && $data->due_time == 'sixty') {
    //             $result->where('delivered_date', '<=', now()->subDays(60))
    //                 ->where('delivered_date', '>=', now()->subDays(89));
    //         }
    //         if (!empty($data->due_time) && $data->due_time == 'thirty') {

    //             $result->where('delivered_date', '<=', now()->subDays(30))
    //                 ->where('delivered_date', '>=', now()->subDays(59));
    //         }
    //         if (!empty($data->due_time) && $data->due_time == 'thirty_less') {

    //             $result->where('delivered_date', '>=', now()->subDays(29));
    //         }
    //         $res = $result->get();
    //         $totalBalanceAmount = $res->sum('balance_amount');


    //         $result = $result->orderBy('delivered_date', 'ASC')->paginate(config('settings.pagination.perpage.admin'));
    //         $response['pagination'] = generatePaginationData($result);
    //         $response['total_balance_amount'] = $totalBalanceAmount;
    //         $response['data'] = $result;

    //         $result->getCollection()->transform(function ($item) {


    //             $item->due_days = round(Carbon::parse($item->delivered_date)->diffInDays(now()));
    //             return $item;
    //         });

    //         return response()->json($response);
    //     } else {
    //     }
    // }

    public function customerDueReportList($data = null, $platform = 'web', $user_type = 'employee')
    {
        if (isset($data->sales_man_user_id)) {
            $user = User::find($data->sales_man_user_id);
            if ($user->user_type == 'outside_salesman') {
                $user_type = 'outside_salesman';
            } else {
                $user_type = 'employee';
            }
        }



        if ($user_type == 'employee') {
            $orderQuery = Order::with(['customer', 'payments'])
                ->where('order_status', 'delivered')
                ->where('payment_status', 'Partial')
                ->where('is_outside', 0);

            $orderQuery = $this->applyFiltersForSalesReport($orderQuery, $data);



            $openingBalanceQuery = OpeningBalance::with('customer')
                ->whereRaw('(amount - COALESCE(paid_amount, 0)) > 0');
            if (!empty($data->customer_name)) {

                $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                    $q->where('customer_name', 'LIKE', "%{$data->customer_name}%");
                });
            }

            if (!empty($data->employee_id)) {

                $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                    $q->where('employee_id', $data->employee_id);
                });
            }

            if (!empty($data->customer_code)) {

                $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                    $q->where('customer_code', 'LIKE', "%{$data->customer_code}%");
                });
            }

             if (!empty($data->customer_name)) {

                $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

                    $q->where('customer_name', 'LIKE', "%{$data->customer_name}%");
                });
            }


            $orders = $orderQuery->get()->map(function ($order) {

                $order->record_type = 'order';
                $order->due_date = $order->delivered_date;

                return $order;
            });

            $openingBalances = $openingBalanceQuery->get()->map(function ($opening) {

                return (object)[
                    'id' => 'OB-' . $opening->id,
                    'record_type' => 'opening_balance',

                    'order_no' => 'Opening Balance',

                    'customer' => $opening->customer,

                    'balance_amount' => $opening->amount - $opening->paid_amount,

                    'delivered_date' => $opening->date,

                    'due_date' => $opening->date,

                    'payments' => collect(),

                    'due_days' => abs(
                        Carbon::parse($opening->date)
                            ->startOfDay()
                            ->diffInDays(now()->startOfDay(), false)
                    ),
                ];
            });



            $result = $orders
                ->concat($openingBalances)
                ->sortBy('due_date')
                ->values();

            $today = now()->startOfDay();

            if (!empty($data->due_time)) {

                switch ($data->due_time) {

                    case 'ninty':
                        $result = $result->filter(function ($item) use ($today) {
                            return Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->lte($today->copy()->subDays(90));
                        });
                        break;

                    case 'sixty':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 60 && $days <= 89;
                        });
                        break;

                    case 'thirty':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 30 && $days <= 59;
                        });
                        break;

                    case 'thirty_less':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 0 && $days <= 29;
                        });
                        break;
                }
            }

            $result = $result->values();
            $result->transform(function ($item) {

                if (!isset($item->due_days)) {

                    $item->due_days = (int) abs(Carbon::parse($item->delivered_date)
                        ->diffInDays(now()));
                }

                return $item;
            });
            $totalBalanceAmount = $result->sum('balance_amount');



            if ($platform == 'web') {

                $page = request()->page ?? 1;

                $perPage = config('settings.pagination.perpage.admin');

                $items = $result->forPage($page, $perPage)->values();

                $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $result->count(),
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );

                return response()->json([
                    'pagination' => generatePaginationData($pagination),
                    'data' => $pagination,
                    'total_balance_amount' => $totalBalanceAmount,
                ]);
            }
            return successResponse(
                $result,
                'Customer due report retrieved successfully.',
                0,
                200
            );
        } else {
            $orderQuery = Order::with(['customer', 'payments'])
                ->where('order_status', 'delivered')
                ->where('payment_status', 'Partial')
                ->where('is_outside', 1);


            // $orderQuery = $this->applyFiltersForSalesReport($orderQuery, $data);

            if (!empty($data->employee_id)) {

                $orderQuery->whereHas('customer', function ($q) use ($data) {
                    $q->where('employee_id', $data->employee_id)->where('is_outside', 1);
                });
            }

            $orders = $orderQuery->get()->map(function ($order) {

                $order->record_type = 'order';
                $order->due_date = $order->delivered_date;

                return $order;
            });





            $result = $orders

                ->sortBy('due_date')
                ->values();

            $today = now()->startOfDay();

            if (!empty($data->due_time)) {

                switch ($data->due_time) {

                    case 'ninty':
                        $result = $result->filter(function ($item) use ($today) {
                            return Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->lte($today->copy()->subDays(90));
                        });
                        break;

                    case 'sixty':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 60 && $days <= 89;
                        });
                        break;

                    case 'thirty':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 30 && $days <= 59;
                        });
                        break;

                    case 'thirty_less':
                        $result = $result->filter(function ($item) use ($today) {
                            $days = Carbon::parse($item->due_date)
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 0 && $days <= 29;
                        });
                        break;
                }
            }

            $result = $result->values();
            $result->transform(function ($item) {

                if (!isset($item->due_days)) {

                    $item->due_days = (int) abs(Carbon::parse($item->delivered_date)
                        ->diffInDays(now()));
                }

                return $item;
            });
            $totalBalanceAmount = $result->sum('balance_amount');



            if ($platform == 'web') {

                $page = request()->page ?? 1;

                $perPage = config('settings.pagination.perpage.admin');

                $items = $result->forPage($page, $perPage)->values();

                $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $result->count(),
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );

                return response()->json([
                    'pagination' => generatePaginationData($pagination),
                    'data' => $pagination,
                    'total_balance_amount' => $totalBalanceAmount,
                ]);
            }
            return successResponse(
                $result,
                'Customer due report retrieved successfully.',
                0,
                200
            );
        }
    }

    // public function customerDueReportListPDF($data = null, $platform = 'web')
    // {
    //     $orderQuery = Order::with(['customer', 'payments'])
    //         ->where('order_status', 'delivered')
    //         ->where('payment_status', 'Partial')
    //         ->where('is_outside', 0);

    //     $orderQuery = $this->applyFiltersForSalesReport($orderQuery, $data);



    //     $openingBalanceQuery = OpeningBalance::with('customer')
    //         ->whereRaw('(amount - COALESCE(paid_amount, 0)) > 0');;
    //     if (!empty($data->customer_name)) {

    //         $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

    //             $q->where('customer_name', 'LIKE', "%{$data->customer_name}%");
    //         });
    //     }

    //     if (!empty($data->employee_id)) {

    //         $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

    //             $q->where('employee_id', $data->employee_id);
    //         });
    //     }

    //     if (!empty($data->customer_code)) {

    //         $openingBalanceQuery->whereHas('customer', function ($q) use ($data) {

    //             $q->where('customer_code', 'LIKE', "%{$data->customer_code}%");
    //         });
    //     }



    //     $orders = $orderQuery->get()->map(function ($order) {

    //         $order->record_type = 'order';
    //         $order->due_date = $order->delivered_date;

    //         return $order;
    //     });

    //     $openingBalances = $openingBalanceQuery->get()->map(function ($opening) {

    //         return (object)[
    //             'id' => 'OB-' . $opening->id,
    //             'record_type' => 'opening_balance',

    //             'order_no' => 'Opening Balance',

    //             'customer' => $opening->customer,

    //             'balance_amount' => $opening->amount - $opening->paid_amount,

    //             'delivered_date' => $opening->date,

    //             'due_date' => $opening->date,

    //             'payments' => collect(),

    //             'due_days' => abs(
    //                 Carbon::parse($opening->date)
    //                     ->startOfDay()
    //                     ->diffInDays(now()->startOfDay(), false)
    //             ),
    //         ];
    //     });



    //     $result = $orders
    //         ->concat($openingBalances)
    //         ->sortBy('due_date')
    //         ->values();

    //     $today = now()->startOfDay();

    //     if (!empty($data->due_time)) {

    //         switch ($data->due_time) {

    //             case 'ninty':
    //                 $result = $result->filter(function ($item) use ($today) {
    //                     return Carbon::parse($item->due_date)
    //                         ->startOfDay()
    //                         ->lte($today->copy()->subDays(90));
    //                 });
    //                 break;

    //             case 'sixty':
    //                 $result = $result->filter(function ($item) use ($today) {
    //                     $days = Carbon::parse($item->due_date)
    //                         ->startOfDay()
    //                         ->diffInDays($today);

    //                     return $days >= 60 && $days <= 89;
    //                 });
    //                 break;

    //             case 'thirty':
    //                 $result = $result->filter(function ($item) use ($today) {
    //                     $days = Carbon::parse($item->due_date)
    //                         ->startOfDay()
    //                         ->diffInDays($today);

    //                     return $days >= 30 && $days <= 59;
    //                 });
    //                 break;

    //             case 'thirty_less':
    //                 $result = $result->filter(function ($item) use ($today) {
    //                     $days = Carbon::parse($item->due_date)
    //                         ->startOfDay()
    //                         ->diffInDays($today);

    //                     return $days >= 0 && $days <= 29;
    //                 });
    //                 break;
    //         }
    //     }

    //     $result = $result->values();
    //     $result->transform(function ($item) {

    //         if (!isset($item->due_days)) {

    //             $item->due_days = (int) abs(Carbon::parse($item->delivered_date)
    //                 ->diffInDays(now()));
    //         }

    //         return $item;
    //     });


    //     $file_name = 'customer_due_report_' . date('Y_m_d_H_i_s') . '.pdf';
    //     $pdf = Pdf::loadView(
    //         'admin.pages.report.pdf.customer_due_pdf',
    //         [
    //             'results' => $result,
    //             'customer_name' => $data->customer_name ?? '',

    //         ]
    //     );

    //     return $pdf->download($file_name);
    // }

    public function customerDueReportListPDF($data = null, $platform = 'web')
    {
        $orderQuery = Order::with(['customer', 'payments'])
            ->where('order_status', 'delivered')
            ->where('payment_status', 'Partial')
            ->where('is_outside', 0);

        /*
    |--------------------------------------------------------------------------
    | Apply Order Filters
    |--------------------------------------------------------------------------
    */

        $orderQuery = $this->applyFiltersForSalesReport(
            $orderQuery,
            $data
        );

        /*
    |--------------------------------------------------------------------------
    | Opening Balance
    |--------------------------------------------------------------------------
    */

        $openingBalanceQuery = OpeningBalance::with('customer')
            ->whereRaw(
                '(amount - COALESCE(paid_amount, 0)) > 0'
            );

        /*
    | Customer Name
    */

        if (!empty($data->customer_name)) {

            $openingBalanceQuery->whereHas(
                'customer',
                function ($q) use ($data) {

                    $q->where(
                        'customer_name',
                        'LIKE',
                        '%' . $data->customer_name . '%'
                    );
                }
            );
        }

        /*
    | Employee
    */

        if (!empty($data->employee_id)) {

            $openingBalanceQuery->whereHas(
                'customer',
                function ($q) use ($data) {

                    $q->where(
                        'employee_id',
                        $data->employee_id
                    );
                }
            );
        }

        /*
    | Customer Code
    */

        if (!empty($data->customer_code)) {

            $openingBalanceQuery->whereHas(
                'customer',
                function ($q) use ($data) {

                    $q->where(
                        'customer_code',
                        'LIKE',
                        '%' . $data->customer_code . '%'
                    );
                }
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

        $orders = $orderQuery->get()->map(function ($order) {

            $order->record_type = 'order';

            $order->due_date = $order->delivered_date;

            /*
         * IMPORTANT:
         * This assumes balance_amount is already available
         * on the Order model.
         */

            return $order;
        });

        /*
    |--------------------------------------------------------------------------
    | Opening Balances
    |--------------------------------------------------------------------------
    */

        $openingBalances = $openingBalanceQuery->get()->map(
            function ($opening) {

                return (object) [

                    'id' => 'OB-' . $opening->id,

                    'record_type' => 'opening_balance',

                    'order_no' => 'Opening Balance',

                    'customer' => $opening->customer,

                    'balance_amount' =>
                    $opening->amount -
                        ($opening->paid_amount ?? 0),

                    'delivered_date' => $opening->date,

                    'due_date' => $opening->date,

                    'payments' => collect(),

                    'due_days' => (int) abs(
                        Carbon::parse($opening->date)
                            ->startOfDay()
                            ->diffInDays(
                                now()->startOfDay(),
                                false
                            )
                    ),
                ];
            }
        );

        /*
    |--------------------------------------------------------------------------
    | Combine Orders + Opening Balances
    |--------------------------------------------------------------------------
    */

        $result = $orders
            ->concat($openingBalances)
            ->sortBy('due_date')
            ->values();

        /*
    |--------------------------------------------------------------------------
    | Due Days Filter
    |--------------------------------------------------------------------------
    */

        $today = now()->startOfDay();

        if (!empty($data->due_time)) {

            switch ($data->due_time) {

                case 'ninty':

                    $result = $result->filter(
                        function ($item) use ($today) {

                            return Carbon::parse(
                                $item->due_date
                            )
                                ->startOfDay()
                                ->lte(
                                    $today->copy()->subDays(90)
                                );
                        }
                    );

                    break;

                case 'sixty':

                    $result = $result->filter(
                        function ($item) use ($today) {

                            $days = Carbon::parse(
                                $item->due_date
                            )
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 60 && $days <= 89;
                        }
                    );

                    break;

                case 'thirty':

                    $result = $result->filter(
                        function ($item) use ($today) {

                            $days = Carbon::parse(
                                $item->due_date
                            )
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 30 && $days <= 59;
                        }
                    );

                    break;

                case 'thirty_less':

                    $result = $result->filter(
                        function ($item) use ($today) {

                            $days = Carbon::parse(
                                $item->due_date
                            )
                                ->startOfDay()
                                ->diffInDays($today);

                            return $days >= 0 && $days <= 29;
                        }
                    );

                    break;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Calculate Due Days
    |--------------------------------------------------------------------------
    */

        $result = $result->values();

        $result->transform(function ($item) {

            if (!isset($item->due_days)) {

                $item->due_days = (int) abs(
                    Carbon::parse(
                        $item->delivered_date
                    )->diffInDays(now())
                );
            }

            return $item;
        });

        /*
    |--------------------------------------------------------------------------
    | Employee Name
    |--------------------------------------------------------------------------
    */

        $employeeName = '';

        if (!empty($data->employee_id)) {

            $employee = \App\Models\User::find(
                $data->employee_id
            );

            $employeeName = $employee
                ? $employee->name
                : 'N/A';
        }

        /*
    |--------------------------------------------------------------------------
    | Due Label
    |--------------------------------------------------------------------------
    */

        $dueLabel = '';

        if (!empty($data->due_time)) {

            switch ($data->due_time) {

                case 'ninty':
                    $dueLabel = '90 Days and Above';
                    break;

                case 'sixty':
                    $dueLabel = '60 - 89 Days';
                    break;

                case 'thirty':
                    $dueLabel = '30 - 59 Days';
                    break;

                case 'thirty_less':
                    $dueLabel = '0 - 29 Days';
                    break;

                default:
                    $dueLabel = $data->due_time;
                    break;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

        $file_name =
            'customer_due_report_' .
            date('Y_m_d_H_i_s') .
            '.pdf';

        $pdf = Pdf::loadView(
            'admin.pages.report.pdf.customer_due_pdf',
            [
                'results' => $result,

                'customer_name' =>
                $data->customer_name ?? '',

                'start_date' =>
                $data->start_date ?? '',

                'end_date' =>
                $data->end_date ?? '',

                'employee_name' =>
                $employeeName,

                'due_label' =>
                $dueLabel,
            ]
        );

        return $pdf->download($file_name);
    }
    public function customerCreditReportList($data = null, $platform = 'web')
    {
        try {

            /*
        |--------------------------------------------------------------------------
        | Normal Customers
        |--------------------------------------------------------------------------
        */

            $customers = Customer::query()
                ->where('is_outside', 0);

            if (!empty($data->customer_name)) {
                $customers->where('customer_name', 'LIKE', "%{$data->customer_name}%");
            }

            if (!empty($data->customer_code)) {
                $customers->where('customer_code', 'LIKE', "%{$data->customer_code}%");
            }

            if (!empty($data->employee_id)) {
                $customers->where('employee_id', $data->employee_id);
            }

            if ($platform == 'web') {
                $customers->where('balance_amount', '>', 0);
            }

            $customers = $customers->get();

            $customers->transform(function ($customer) {

                $customer->record_type = 'customer';

                return $customer;
            });


            /*
        |--------------------------------------------------------------------------
        | Outside Salesmen
        |--------------------------------------------------------------------------
        */

            $outsideSalesmen = User::query()
                ->select('users.*', 'employees.employee_code')
                ->join('employees', 'employees.user_id', '=', 'users.id')
                ->where('users.user_type', 'outside_salesman')
                ->where('employees.designation', 'outside_salesman');

            if (!empty($data->customer_name)) {

                $outsideSalesmen->where('users.name', 'LIKE', "%{$data->customer_name}%");
            }

            if (!empty($data->customer_code)) {

                $outsideSalesmen->where('employees.employee_code', 'LIKE', "%{$data->customer_code}%");
            }

            if (!empty($data->employee_id && $data->employee_id != 1)) {
                $outsideSalesmen = $outsideSalesmen->get();
                $outsideSalesmen = null;
            } else {
                $outsideSalesmen = $outsideSalesmen->get();
            }

            /*
        |--------------------------------------------------------------------------
        | Calculate Credit Balance
        |--------------------------------------------------------------------------
        */

            $balances = Order::where('order_type', 'Outside Sale')
                ->selectRaw('customer_id, SUM(balance_amount) balance')
                // ->where('order_status','delivered')
                ->groupBy('customer_id')
                ->pluck('balance', 'customer_id');
            // dd($balances);
            if ($outsideSalesmen != null) {

                $outsideSalesmen->transform(function ($salesman) use ($balances) {

                    $salesman->customer_name = $salesman->name;
                    $salesman->customer_code = $salesman->employee_code;
                    $salesman->balance_amount = $balances[$salesman->id] ?? 0;
                    $salesman->phone_number = $salesman->phone;
                    $salesman->record_type = 'outside_salesman';

                    return $salesman;
                });


                if ($platform == 'web') {

                    $outsideSalesmen = $outsideSalesmen->filter(function ($item) {

                        return $item->balance_amount > 0;
                    });
                }
            }


            /*
        |--------------------------------------------------------------------------
        | Merge
        |--------------------------------------------------------------------------
        */
            if ($outsideSalesmen) {
                $result = $customers
                    ->concat($outsideSalesmen)
                    // ->sortByDesc('balance_amount')
                    ->values();

                $totalCreditAmount = $result->sum('balance_amount');
            } else {
                $result = $customers->values();
                $totalCreditAmount = $result->sum('balance_amount');
            }


            /*
        |--------------------------------------------------------------------------
        | Web Response
        |--------------------------------------------------------------------------
        */

            if ($platform == 'web') {

                $page = request()->get('page', 1);

                $perPage = config('settings.pagination.perpage.admin');

                $items = $result->forPage($page, $perPage)->values();

                $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $result->count(),
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );

                return response()->json([
                    'pagination' => generatePaginationData($pagination),
                    'data' => $pagination,
                    'total_credit_amount' => $totalCreditAmount,
                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Mobile Response
        |--------------------------------------------------------------------------
        */

            return successResponse(
                $result->values(),
                'Customer credit report retrieved successfully.',
                0,
                200
            );
        } catch (\Exception $e) {

            return errorResponse(
                null,
                $e->getMessage(),
                100,
                500
            );
        }
    }
    public function outsideCustomerCreditReportList($data = null, $platform = 'web')
    {
        try {

            /*
        |--------------------------------------------------------------------------
        | Normal Customers
        |--------------------------------------------------------------------------
        */

            $customers = Customer::query()
                ->where('is_outside', 1);

            if (!empty($data->customer_name)) {
                $customers->where('customer_name', 'LIKE', "%{$data->customer_name}%");
            }

            if (!empty($data->customer_code)) {
                $customers->where('customer_code', 'LIKE', "%{$data->customer_code}%");
            }

            if (!empty($data->employee_id)) {
                $customers->where('employee_id', $data->employee_id);
            }

            if ($platform == 'web') {
                $customers->where('balance_amount', '>', 0);
            }

            $customers = $customers->get();

            $customers->transform(function ($customer) {

                $customer->record_type = 'customer';

                return $customer;
            });




            $balances = Order::where('order_type', 'Outside Sale')
                ->selectRaw('customer_id, SUM(balance_amount) balance')
                // ->where('order_status','delivered')
                ->groupBy('customer_id')
                ->pluck('balance', 'customer_id');







            $result = $customers
                ->values();

            $totalCreditAmount = $result->sum('balance_amount');



            /*
        |--------------------------------------------------------------------------
        | Web Response
        |--------------------------------------------------------------------------
        */

            if ($platform == 'web') {

                $page = request()->get('page', 1);

                $perPage = config('settings.pagination.perpage.admin');

                $items = $result->forPage($page, $perPage)->values();

                $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $result->count(),
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );

                return response()->json([
                    'pagination' => generatePaginationData($pagination),
                    'data' => $pagination,
                    'total_credit_amount' => $totalCreditAmount,
                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Mobile Response
        |--------------------------------------------------------------------------
        */

            return successResponse(
                $result->values(),
                'Customer credit report retrieved successfully.',
                0,
                200
            );
        } catch (\Exception $e) {

            return errorResponse(
                null,
                $e->getMessage(),
                100,
                500
            );
        }
    }
    /**
     * Base query shared by the screen and its exports so that each customer-type
     * filter returns the same records everywhere.
     */
    public function customerCreditReportQuery()
    {
        $customers = DB::table('customers')->select([
            'id',
            DB::raw('customer_name as customer_name'),
            DB::raw('customer_code as customer_code'),
            'employee_id',
            'balance_amount',
            'is_outside',
            'phone_number',
            DB::raw("'customer' as record_type"),
        ]);

        $outsideSalesmen = DB::table('users')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.customer_id', '=', 'users.id')
                    ->where('orders.order_type', 'Outside Sale');
            })
            ->leftJoin('employees', function ($join) {
                $join->on('employees.user_id', '=', 'users.id')
                    ->where('employees.designation', 'outside_salesman');
            })
            ->where('users.user_type', 'outside_salesman')
            ->select([
                'users.id',
                DB::raw('users.name as customer_name'),
                DB::raw('employees.employee_code as customer_code'),
                DB::raw('users.id as employee_id'),
                DB::raw('COALESCE(SUM(orders.balance_amount), 0) as balance_amount'),
                // DB::raw('0 as is_outside'),
                DB::raw('users.phone as phone_number'),
                DB::raw("'outside_salesman' as record_type"),
            ])
            ->groupBy(
                'users.id',
                'users.name',
                'employees.employee_code',
                'users.phone'
            );

        return DB::query()->fromSub(
            // $customers->unionAll($outsideSalesmen),
            $customers,
            'credit_report'
        );
    }

    public function customerCreditReportListPDF($data = null, $platform = 'web')
    {
        try {
            $query = $this->customerCreditReportQuery();

            // Customer name filter
            if (!empty($data->customer_name)) {
                $query->where('customer_name', 'LIKE', "%{$data->customer_name}%");
            }

            // Customer code filter
            if (!empty($data->customer_code)) {
                $query->where('customer_code', 'LIKE', "%{$data->customer_code}%");
            }

            // Employee filter
            if (!empty($data->employee_id)) {
                $query->where('employee_id', $data->employee_id);
            }

            // if (($data->employee_type ?? '') === 'company') {
            //     $query->where('is_outside', 0);
            // } elseif (($data->employee_type ?? '') === 'outside') {
            //     $query->where('is_outside', 1);
            // }

            // Filter only customers with credit balance
            $query->where('balance_amount', '>', 0);

            $results = $query->orderBy('balance_amount', 'DESC')->limit(300)->get();

            // Get employee name if filter is applied
            $employee_name = '';
            if (!empty($data->employee_id)) {
                $employee = \App\Models\User::find($data->employee_id);
                $employee_name = $employee ? $employee->name : '';
            }

            $file_name = 'customer_credit_report_' . date('Y_m_d_H_i_s') . '.pdf';
            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.customer_credit_pdf',
                [
                    'results' => $results,
                    'customer_name' => $data->customer_name ?? '',
                    'customer_code' => $data->customer_code ?? '',
                    'employee_name' => $employee_name,
                    // 'customer_type' => match ($data->employee_type ?? '') {
                    //     'company' => 'Company Customer',
                    //     'outside' => 'Outside Customer',
                    //     default => 'All',
                    // },
                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function outsideCustomerCreditReportListPDF($data = null, $platform = 'web')
    {
        try {
            $query = Customer::query()->where('is_outside', 1);

            // Customer name filter
            if (!empty($data->customer_name)) {
                $query->where('customer_name', 'LIKE', "%{$data->customer_name}%");
            }

            // Customer code filter
            if (!empty($data->customer_code)) {
                $query->where('customer_code', 'LIKE', "%{$data->customer_code}%");
            }

            // Employee filter
            if (!empty($data->employee_id)) {
                $query->where('employee_id', $data->employee_id);
            }



            // Filter only customers with credit balance
            $query->where('balance_amount', '>', 0);

            $results = $query->orderBy('balance_amount', 'DESC')->limit(300)->get();

            // Get employee name if filter is applied
            $employee_name = '';
            if (!empty($data->employee_id)) {
                $employee = \App\Models\User::find($data->employee_id);
                $employee_name = $employee ? $employee->name : '';
            }

            $file_name = 'outside_customer_credit_report_' . date('Y_m_d_H_i_s') . '.pdf';
            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.outside_customer_credit_pdf',
                [
                    'results' => $results,
                    'customer_name' => $data->customer_name ?? '',
                    'customer_code' => $data->customer_code ?? '',
                    'employee_name' => $employee_name,

                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // Employee Payment Collection Report
    public function employeePaymentCollectionReportList($data = null, $platform = 'web')
    {
        try {
            $query = OrderPayment::with('user')
                ->whereNotNull('user_id'); // Only get payments with valid user_id

            $startDate = $data->start_date ?? null;
            $endDate = $data->end_date ?? null;

            if ($endDate && $startDate) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            if (!empty($data->employee_id)) {
                $query->where('user_id', $data->employee_id);
            }

            // Get all payments for grand totals calculation
            $allPayments = (clone $query)->get();

            // Initialize grand totals
            $grandTotals = [
                'cash' => 0,
                'bank' => 0,
                'card' => 0,
                'online' => 0,
                'bank_transfer_alrajhi' => 0,
                'bank_transfer_sab' => 0,
                'pos_alrajhis' => 0,
                'pos_sab' => 0,
                'credit' => 0,
                'grand_total' => 0
            ];

            // Calculate grand totals from all payments
            foreach ($allPayments as $payment) {
                $mode = $this->normalizePaymentMode($payment->payment_mode ?? '');
                $amount = (float)($payment->paid_amount ?? 0);

                if ($amount <= 0) continue; // Skip zero or negative amounts

                switch ($mode) {
                    case 'transfer alrajhi':
                    case 'bank transfer alrajhi':
                        $grandTotals['bank_transfer_alrajhi'] += $amount;
                        break;
                    case 'transfer sab':
                    case 'bank transfer sab':
                        $grandTotals['bank_transfer_sab'] += $amount;
                        break;
                    case 'pos alrajhi':
                        $grandTotals['pos_alrajhis'] += $amount;
                        break;
                    case 'pos sab':
                        $grandTotals['pos_sab'] += $amount;
                        break;
                    case 'card':
                        $grandTotals['card'] += $amount;
                        break;
                    case 'cash':
                        $grandTotals['cash'] += $amount;
                        break;
                    case 'bank':
                        $grandTotals['bank'] += $amount;
                        break;
                    case 'online':
                        $grandTotals['online'] += $amount;
                        break;
                }
            }

            // Calculate grand total
            $grandTotals['grand_total'] =
                $grandTotals['cash'] +
                $grandTotals['bank'] +
                $grandTotals['card'] +
                $grandTotals['online'] +
                $grandTotals['bank_transfer_alrajhi'] +
                $grandTotals['bank_transfer_sab'] +
                $grandTotals['pos_alrajhis'] +
                $grandTotals['pos_sab'];

            // Get unique user IDs for pagination
            $userIds = (clone $query)->distinct()->pluck('user_id')->filter();

            // Paginate user IDs
            $perPage = config('settings.pagination.perpage.admin');
            $currentPage = request()->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $paginatedUserIds = $userIds->slice($offset, $perPage)->values();

            // Get payments for paginated users
            $payments = (clone $query)
                ->whereIn('user_id', $paginatedUserIds)
                ->get()
                ->groupBy('user_id');

            $employeesData = [];
            foreach ($paginatedUserIds as $userId) {
                $userPayments = $payments->get($userId, collect());

                if ($userPayments->isEmpty()) {
                    continue;
                }

                $firstPayment = $userPayments->first();
                $user = $firstPayment->user ?? null;

                if (!$user) {
                    continue;
                }

                $byMode = [
                    'cash' => 0,
                    'bank' => 0,
                    'card' => 0,
                    'online' => 0,
                    'bank_transfer_alrajhi' => 0,
                    'bank_transfer_sab' => 0,
                    'pos_alrajhis' => 0,
                    'pos_sab' => 0,
                    'total' => 0
                ];

                foreach ($userPayments as $payment) {
                    $mode = $this->normalizePaymentMode($payment->payment_mode ?? '');
                    $amount = (float)($payment->paid_amount ?? 0);

                    if ($amount <= 0) continue; // Skip zero or negative amounts

                    switch ($mode) {
                        case 'transfer alrajhi':
                        case 'bank transfer alrajhi':
                            $byMode['bank_transfer_alrajhi'] += $amount;
                            break;
                        case 'transfer sab':
                        case 'bank transfer sab':
                            $byMode['bank_transfer_sab'] += $amount;
                            break;
                        case 'pos alrajhi':
                            $byMode['pos_alrajhis'] += $amount;
                            break;
                        case 'pos sab':
                            $byMode['pos_sab'] += $amount;
                            break;
                        case 'card':
                            $byMode['card'] += $amount;
                            break;
                        case 'cash':
                            $byMode['cash'] += $amount;
                            break;
                        case 'bank':
                            $byMode['bank'] += $amount;
                            break;
                        case 'online':
                            $byMode['online'] += $amount;
                            break;
                    }
                }

                $byMode['total'] =
                    $byMode['cash'] +
                    $byMode['bank'] +
                    $byMode['card'] +
                    $byMode['online'] +
                    $byMode['bank_transfer_alrajhi'] +
                    $byMode['bank_transfer_sab'] +
                    $byMode['pos_alrajhis'] +
                    $byMode['pos_sab'];

                $employeesData[] = [
                    'user_id' => $userId,
                    'employee_name' => $user->name ?? '-',
                    'user_type' => $user->user_type ?? '-',
                    'cash' => $byMode['cash'],
                    'bank' => $byMode['bank'],
                    'card' => $byMode['card'],
                    'online' => $byMode['online'],
                    'bank_transfer_alrajhi' => $byMode['bank_transfer_alrajhi'],
                    'bank_transfer_sab' => $byMode['bank_transfer_sab'],
                    'pos_alrajhis' => $byMode['pos_alrajhis'],
                    'pos_sab' => $byMode['pos_sab'],
                    'total' => $byMode['total'],
                ];
            }

            // Create pagination data manually
            $total = $userIds->count();
            $lastPage = ceil($total / $perPage);
            $pagination = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => (int)$currentPage,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ];

            return successResponse([
                'employees' => $employeesData,
                'grand_totals' => $grandTotals,
                'pagination' => $pagination,
            ], 'Employee payment collection report retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // Get order details with items for modal
    public function getOrderDetails($data = null)
    {
        try {
            $orderId = $data->order_id ?? $data->id ?? null;

            if (!$orderId) {
                return errorResponse(null, 'Order ID is required', 100, 400);
            }

            $order = Order::with(['orderItems.product', 'customer', 'orderBy', 'deliveryBy'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return errorResponse(null, 'Order not found', 100, 404);
            }

            $orderItems = $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => [
                        'product_name' => $item->product->product_name ?? 'N/A',
                    ],
                    'quantity' => $item->quantity,
                    'free_quantity' => $item->free_quantity ?? 0,
                    'price' => (float)$item->price,
                    'total' => (float)$item->total,
                ];
            });

            $response = [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'order_items' => $orderItems,
            ];

            return successResponse($response, 'Order details retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // Payment Details Report
    public function paymentReportList($data = null, $platform = 'web')
    {
        $result = OrderPayment::with(['customer', 'order', 'user']);


        // Customer Type Filter
        if (($data->customer_type ?? 'in') == 'in') {
            // Company Customer (default)
            // $result->whereHas('order', function ($q) {
            //     $q->where('is_outside', 0);
            // })
            //     ->orWhere(function ($q) {
            //         $q->where('type', 'opening_balance')
            //             ->whereHas('customer', function ($customer) {
            //                 $customer->where('is_outside', 0);
            //             });
            //     });
            $result->where(function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('is_outside', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('type', 'opening_balance')
                            ->whereNull('order_id')
                            ->whereHas('customer', function ($customer) {
                                $customer->where('is_outside', 0);
                            });
                    });
            });
        } else {
            // Outside Customer
            $result->whereHas('order', function ($q) {
                $q->where('is_outside', 1);
            });
        }

        // $user = User::find($data->sales_man_user_id);

        // if ($user) {
        //     $userType = $user->user_type;

        //     if ($userType == 'outside_salesman') {

        //         $result->whereHas('order', function ($q) {
        //             $q->where('is_outside', 1);
        //         });
        //     }
        //     if (($data->customer_type ?? 'in') == 'in') {
        //         $result->where(function ($query) {
        //             $query->whereHas('order', function ($q) {
        //                 $q->where('is_outside', 0);
        //             })
        //                 ->orWhere(function ($q) {
        //                     $q->where('type', 'opening_balance')
        //                         ->whereHas('customer', function ($customer) {
        //                             $customer->where('is_outside', 0);
        //                         });
        //                 });
        //         });
        //     } else {
        //         $result->whereHas('order', function ($q) {
        //             $q->where('is_outside', 1);
        //         });
        //     }
        // }

        if (!empty($data->sales_man_user_id)) {

            $user = User::find($data->sales_man_user_id);

            if ($user && $user->user_type == 'outside_salesman') {

                $result->whereHas('order', function ($q) {
                    $q->where('is_outside', 1);
                });
            }
        }

        // Exclude credit payment mode
        $result->where(function ($q) {
            $q->where('payment_mode', '!=', 'credit')
                ->orWhereNull('payment_mode');
        });

        $startDate = $data->start_date;
        $endDate = $data->end_date;
        if ($endDate != null && $startDate != null) {
            $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } else if ($endDate == null && $startDate != null) {
            $result->where('payment_date', '>=', $startDate . ' 00:00:00');
        } else if ($endDate != null && $startDate == null) {
            $result->where('payment_date', '<=', $endDate . ' 23:59:59');
        }


        // if (!empty($data->customer_search)) {

        //     $result->where(function ($query) use ($data) {

        //         // Normal customer
        //         $query->where(function ($q) use ($data) {

        //             $q->whereHas('order', function ($order) {
        //                 $order->where('order_type', '!=', 'Outside Sale');
        //             });

        //             $q->whereHas('customer', function ($customer) use ($data) {
        //                 $customer->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
        //                     ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
        //             });
        //         })

        //             // Outside salesman
        //             ->orWhere(function ($q) use ($data) {

        //                 $q->whereHas('order', function ($order) use ($data) {

        //                     $order->where('order_type', 'Outside Sale')
        //                         ->whereHas('users', function ($user) use ($data) {
        //                             $user->where('id', DB::raw('order_payments.customer_id'))
        //                                 ->where('name', 'LIKE', '%' . $data->customer_search . '%');
        //                         });
        //                 });
        //             });
        //     });
        // }


        if (!empty($data->customer_search)) {

            $result->where(function ($query) use ($data) {

                // Normal company customer payments
                $query->where(function ($q) use ($data) {

                    $q->whereHas('order', function ($order) {
                        $order->where('order_type', '!=', 'Outside Sale');
                    });

                    $q->whereHas('customer', function ($customer) use ($data) {
                        $customer->where(function ($search) use ($data) {
                            $search->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
                                ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
                        });
                    });
                })

                    // Opening balance payments (no order_id)
                    ->orWhere(function ($q) use ($data) {

                        $q->where('type', 'opening_balance')
                            ->whereNull('order_id')
                            ->whereHas('customer', function ($customer) use ($data) {
                                $customer->where(function ($search) use ($data) {
                                    $search->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
                                        ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
                                });
                            });
                    })

                    // Outside salesman payments
                    ->orWhere(function ($q) use ($data) {

                        $q->whereHas('order', function ($order) use ($data) {

                            $order->where('order_type', 'Outside Sale')
                                ->whereHas('users', function ($user) use ($data) {
                                    $user->where('id', DB::raw('order_payments.customer_id'))
                                        ->where('name', 'LIKE', '%' . $data->customer_search . '%');
                                });
                        });
                    });
            });
        }

        if (!empty($data->employee_id)) {
            $result->where('user_id', $data->employee_id);
        }


        if (!empty($data->payment_mode))
            if ($data->payment_mode == 'TRANSFER ALRAJHI') {

                $result->where(function ($q) {
                    $q->where('payment_mode', 'TRANSFER ALRAJHI')
                        ->orWhere('payment_mode', 'ALRAJHI');
                });
            } else {

                $result->where('payment_mode', $data->payment_mode);
            }

        // Filter by payment type
        if (!empty($data->payment_type)) {

            if ($data->payment_type === 'opening_balance') {
                // Opening balance payments only

                $result->where(function ($q) {
                    $q->where('type', 'opening_balance');
                });
            } elseif ($data->payment_type === 'previous_payments') {
                // Previous payments (order created before payment date)
                $result->where(function ($q) {
                    $q->whereNotNull('order_id')
                        ->whereNotNull('payment_date')
                        ->whereHas('order', function ($orderQ) {
                            $orderQ->whereRaw('DATE(orders.delivered_date) < DATE(order_payments.payment_date)');
                        });
                });
            } elseif ($data->payment_type === 'order_payments') {


                $result->where(function ($q) {
                    $q->where('type', 'order');
                });
            }
        }


        if (!empty($data->search)) {
            $result->whereHas('order', function ($q) use ($data) {
                $q->where('order_no', 'like', '%' . $data->search . '%');
            })->orWhereHas('customer', function ($q) use ($data) {
                $q->where('customer_name', 'like', '%' . $data->search . '%')
                    ->orWhere('customer_code', 'like', '%' . $data->search . '%');
            });
        }

        $totalPaidAmount = 0;

        $res = $result;
        $res = $res->get();


        $totalPaidAmount = $res->sum('paid_amount');
        // if ($platform == 'web') {
        $result = $result->orderBy('payment_date', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
        $response['pagination'] = generatePaginationData($result);
        $response['data'] = $result;

        $response['total_paid_amount'] = $totalPaidAmount;

        return response()->json($response);
        // }
        // else{
        //     $result = $result->orderBy('created_at', 'DESC')->get();
        //     $response['data'] = $result;
        //     $response['total_paid_amount'] = $totalPaidAmount;

        //     return successResponse($response, 'Payment report retrieved successfully.', 0, 200);
        // }
    }

    //if for app this can use
    //  public function paymentReportList($data = null, $platform = 'web')
    // {
    //     $result = OrderPayment::with(['customer', 'order', 'user']);


    //     // Customer Type Filter
    //     if (($data->customer_type ?? 'in') == 'in') {
    //         // Company Customer (default)
    //         $result->whereHas('order', function ($q) {
    //             $q->where('is_outside', 0);
    //         });
    //     } else {
    //         // Outside Customer
    //         $result->whereHas('order', function ($q) {
    //             $q->where('is_outside', 1);
    //         });
    //     }

    //     $user = User::find($data->sales_man_user_id);

    //     if ($user) {
    //         $userType = $user->user_type;

    //         if ($userType == 'outside_salesman') {

    //             $result->whereHas('order', function ($q) {
    //                 $q->where('is_outside', 1);
    //             });
    //         }
    //         if (($data->customer_type ?? 'in') == 'in') {
    //             $result->where(function ($query) {
    //                 $query->whereHas('order', function ($q) {
    //                     $q->where('is_outside', 0);
    //                 })
    //                     ->orWhere(function ($q) {
    //                         $q->where('type', 'opening_balance')
    //                             ->whereHas('customer', function ($customer) {
    //                                 $customer->where('is_outside', 0);
    //                             });
    //                     });
    //             });
    //         } else {
    //             $result->whereHas('order', function ($q) {
    //                 $q->where('is_outside', 1);
    //             });
    //         }
    //     } 



    //     // Exclude credit payment mode
    //     $result->where(function ($q) {
    //         $q->where('payment_mode', '!=', 'credit')
    //             ->orWhereNull('payment_mode');
    //     });

    //     $startDate = $data->start_date;
    //     $endDate = $data->end_date;
    //     if ($endDate != null && $startDate != null) {
    //         $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //     } else if ($endDate == null && $startDate != null) {
    //         $result->where('payment_date', '>=', $startDate . ' 00:00:00');
    //     } else if ($endDate != null && $startDate == null) {
    //         $result->where('payment_date', '<=', $endDate . ' 23:59:59');
    //     }


    //     if (!empty($data->customer_search)) {

    //         $result->where(function ($query) use ($data) {

    //             // Normal customer
    //             $query->where(function ($q) use ($data) {

    //                 $q->whereHas('order', function ($order) {
    //                     $order->where('order_type', '!=', 'Outside Sale');
    //                 });

    //                 $q->whereHas('customer', function ($customer) use ($data) {
    //                     $customer->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
    //                         ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
    //                 });
    //             })

    //                 // Outside salesman
    //                 ->orWhere(function ($q) use ($data) {

    //                     $q->whereHas('order', function ($order) use ($data) {

    //                         $order->where('order_type', 'Outside Sale')
    //                             ->whereHas('users', function ($user) use ($data) {
    //                                 $user->where('id', DB::raw('order_payments.customer_id'))
    //                                     ->where('name', 'LIKE', '%' . $data->customer_search . '%');
    //                             });
    //                     });
    //                 });
    //         });
    //     }

    //     if (!empty($data->employee_id)) {
    //         $result->where('user_id', $data->employee_id);
    //     }


    //     if (!empty($data->payment_mode))
    //         if ($data->payment_mode == 'TRANSFER ALRAJHI') {

    //             $result->where(function ($q) {
    //                 $q->where('payment_mode', 'TRANSFER ALRAJHI')
    //                     ->orWhere('payment_mode', 'ALRAJHI');
    //             });
    //         } else {

    //             $result->where('payment_mode', $data->payment_mode);
    //         }

    //     // Filter by payment type
    //     if (!empty($data->payment_type)) {
    //         if ($data->payment_type === 'opening_balance') {
    //             // Opening balance payments only
    //             $result->where(function ($q) {
    //                 $q->where('type', 'opening_balance');
    //             });
    //         } elseif ($data->payment_type === 'previous_payments') {
    //             // Previous payments (order created before payment date)
    //             $result->where(function ($q) {
    //                 $q->whereNotNull('order_id')
    //                     ->whereNotNull('payment_date')
    //                     ->whereHas('order', function ($orderQ) {
    //                         $orderQ->whereRaw('DATE(orders.delivered_date) < DATE(order_payments.payment_date)');
    //                     });
    //             });
    //         } elseif ($data->payment_type === 'order_payments') {
    //             // Order payments (has order_id and not opening balance)
    //             // $result->whereNotNull('order_id')
    //             //     ->where(function ($q) {
    //             //         $q->where('type', '!=', 'opening_balance')
    //             //             ->orWhereNull('type');
    //             //     });
    //             $result->where(function ($q) {
    //                 $q->where('type', 'order');
    //             });
    //         }
    //     }


    //     if (!empty($data->search)) {
    //         $result->whereHas('order', function ($q) use ($data) {
    //             $q->where('order_no', 'like', '%' . $data->search . '%');
    //         })->orWhereHas('customer', function ($q) use ($data) {
    //             $q->where('customer_name', 'like', '%' . $data->search . '%')
    //                 ->orWhere('customer_code', 'like', '%' . $data->search . '%');
    //         });
    //     }

    //     $totalPaidAmount = 0;

    //     $res = $result;
    //     $res = $res->get();


    //     $totalPaidAmount = $res->sum('paid_amount');
    //     // if ($platform == 'web') {
    //     $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
    //     $response['pagination'] = generatePaginationData($result);
    //     $response['data'] = $result;

    //     $response['total_paid_amount'] = $totalPaidAmount;

    //     return response()->json($response);
    //     // }
    //     // else{
    //     //     $result = $result->orderBy('created_at', 'DESC')->get();
    //     //     $response['data'] = $result;
    //     //     $response['total_paid_amount'] = $totalPaidAmount;

    //     //     return successResponse($response, 'Payment report retrieved successfully.', 0, 200);
    //     // }
    // }
    public function paymentReportListPDF($data = null, $platform = 'web')
    {

        $result = OrderPayment::with(['customer', 'order', 'user']);

        if ($platform == 'web') {
            // Keep the PDF customer-type results identical to the screen report.
            if (($data->customer_type ?? 'in') === 'in') {
                $result->whereHas('order', function ($query) {
                    $query->where('is_outside', 0);
                });
            } else {
                $result->whereHas('order', function ($query) {
                    $query->where('is_outside', 1);
                });
            }

            // Exclude credit payment mode
            $result->where(function ($q) {
                $q->where('payment_mode', '!=', 'credit')
                    ->orWhereNull('payment_mode');
            });

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('payment_date', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('payment_date', '<=', $endDate . ' 23:59:59');
            }

            // if (!empty($data->customer_search)) {
            //     $result->whereHas('customer', function ($q) use ($data) {
            //         $q->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
            //             ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
            //     });
            // }


            if (!empty($data->customer_search)) {

                $result->where(function ($query) use ($data) {

                    // Normal customer
                    $query->where(function ($q) use ($data) {

                        $q->whereHas('order', function ($order) {
                            $order->where('order_type', '!=', 'Outside Sale');
                        });

                        $q->whereHas('customer', function ($customer) use ($data) {
                            $customer->where('customer_name', 'LIKE', '%' . $data->customer_search . '%')
                                ->orWhere('customer_code', 'LIKE', '%' . $data->customer_search . '%');
                        });
                    })

                        // Outside salesman
                        ->orWhere(function ($q) use ($data) {

                            $q->whereHas('order', function ($order) use ($data) {

                                $order->where('order_type', 'Outside Sale')
                                    ->whereHas('users', function ($user) use ($data) {
                                        $user->where('id', DB::raw('order_payments.customer_id'))
                                            ->where('name', 'LIKE', '%' . $data->customer_search . '%');
                                    });
                            });
                        });
                });
            }


            if (!empty($data->employee_id)) {
                $result->where('user_id', $data->employee_id);
            }

            // Filter by payment type
            if (!empty($data->payment_type)) {
                if ($data->payment_type === 'opening_balance') {
                    // Opening balance payments only
                    $result->where(function ($q) {
                        $q->where('type', 'opening_balance');
                    });
                } elseif ($data->payment_type === 'previous_payments') {
                    // Previous payments (order created before payment date)
                    $result->where(function ($q) {
                        $q->whereNotNull('order_id')
                            ->whereNotNull('payment_date')
                            ->whereHas('order', function ($orderQ) {
                                $orderQ->whereRaw('DATE(orders.delivered_date) < DATE(order_payments.payment_date)');
                            });
                    });
                } elseif ($data->payment_type === 'order_payments') {
                    // Order payments (has order_id and not opening balance)
                    $result->whereNotNull('order_id')
                        ->where(function ($q) {
                            $q->where('type', '!=', 'opening_balance')
                                ->orWhereNull('type');
                        });
                }
            }

            if (!empty($data->search)) {
                $result->whereHas('order', function ($q) use ($data) {
                    $q->where('order_no', 'like', '%' . $data->search . '%');
                })->orWhereHas('customer', function ($q) use ($data) {
                    $q->where('customer_name', 'like', '%' . $data->search . '%')
                        ->orWhere('customer_code', 'like', '%' . $data->search . '%');
                });
            }

            $customerName = !empty($data->customer_search) ? $data->customer_search : 'All';

            // Get employee name if filter is applied
            $employeeName = 'All';
            if (!empty($data->employee_id)) {
                $employee = User::find($data->employee_id);
                $employeeName = $employee ? $employee->name : 'N/A';
            }

            $payments = $result->orderBy('created_at', 'DESC')->limit(200)->get();

            $file_name = 'payment_details_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.payment_pdf',
                [
                    'payments' => $payments,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'customerName' => $customerName,
                    'employeeName' => $employeeName,
                    'customerType' => ($data->customer_type ?? 'in') === 'in'
                        ? 'Company Customer'
                        : 'Outside Customer',
                ]
            );

            return $pdf->download($file_name);
        }
    }

    // Supplier Payment Details Report
    public function supplierPaymentReportList($data = null, $platform = 'web')
    {
        $result = PurchasePayment::with(['purchase.supplier', 'purchase', 'supplier'])->where('deleted_at', null);

        if ($platform == 'web') {

            //$result = $this->applyFiltersForSupplierPayment($result, $data);
            // Exclude credit payment mode
            $result->where(function ($q) {
                $q->where('payment_mode', '!=', 'credit')
                    ->orWhereNull('payment_mode');
            });

            if (!empty($data->payment_mode)) {
                $result->where('payment_mode', 'like', '%' . $data->payment_mode . '%');
            }

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('payment_date', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('payment_date', '<=', $endDate . ' 23:59:59');
            }

            // if (!empty($data->supplier_id)) {
            //     $result->whereHas('purchase', function ($q) use ($data) {
            //         $q->where('supplier_id', $data->supplier_id);
            //     });
            // }

            if (!empty($data->supplier_id)) {
                $result->where('supplier_id', $data->supplier_id);
            }

            if (!empty($data->search)) {
                $result->whereHas('purchase', function ($q) use ($data) {
                    $q->where('purchase_no', 'like', '%' . $data->search . '%');
                })->orWhereHas('purchase.supplier', function ($q) use ($data) {
                    $q->where('supplier_name', 'like', '%' . $data->search . '%')
                        ->orWhere('supplier_code', 'like', '%' . $data->search . '%');
                });
            }


            $total_paid_amount = $result->sum('amount_taken');

            $result = $result->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
            $response['pagination'] = generatePaginationData($result);
            $response['total_paid_amount'] = $total_paid_amount;
            $response['data'] = $result;
            return response()->json($response);
        }
    }

    public function supplierPaymentReportListPDF($data = null, $platform = 'web')
    {
        $result = PurchasePayment::with(['purchase.supplier', 'purchase']);
        //return $data;
        if ($platform == 'web') {

            // Exclude credit payment mode
            $result->where(function ($q) {
                $q->where('payment_mode', '!=', 'credit')
                    ->orWhereNull('payment_mode');
            });

            $startDate = $data->start_date;
            $endDate = $data->end_date;
            if ($endDate != null && $startDate != null) {
                $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            } else if ($endDate == null && $startDate != null) {
                $result->where('payment_date', '>=', $startDate . ' 00:00:00');
            } else if ($endDate != null && $startDate == null) {
                $result->where('payment_date', '<=', $endDate . ' 23:59:59');
            }

            if (!empty($data->supplier_id)) {
                $result->whereHas('purchase', function ($q) use ($data) {
                    $q->where('supplier_id', $data->supplier_id);
                });
            }

            if (!empty($data->search)) {
                $result->whereHas('purchase', function ($q) use ($data) {
                    $q->where('purchase_no', 'like', '%' . $data->search . '%');
                })->orWhereHas('purchase.supplier', function ($q) use ($data) {
                    $q->where('supplier_name', 'like', '%' . $data->search . '%')
                        ->orWhere('supplier_code', 'like', '%' . $data->search . '%');
                });
            }

            $supplierName = 'All';
            if (!empty($data->supplier_id)) {
                $supplier = Supplier::find($data->supplier_id);
                $supplierName = $supplier ? $supplier->supplier_name . ' [' . $supplier->supplier_code . ']' : 'N/A';
            }

            $payments = $result->orderBy('created_at', 'DESC')->limit(400)->get();
            //return $payments;
            $file_name = 'supplier_payment_details_report_' . date('Y_m_d_H_i_s') . '.pdf';

            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.supplier_payment_pdf',
                [
                    'payments' => $payments,
                    'startDate' => $data->start_date,
                    'endDate' => $data->end_date,
                    'supplierName' => $supplierName,
                ]
            );

            return $pdf->download($file_name);
        }
    }

    // public function applyFiltersForSupplierPayment($result, $data){

    //     // Exclude credit payment mode
    //         $result->where(function ($q) {
    //             $q->where('payment_mode', '!=', 'credit')
    //                 ->orWhereNull('payment_mode');
    //         });

    //         $startDate = $data->start_date;
    //         $endDate = $data->end_date;
    //         if ($endDate != null && $startDate != null) {
    //             $result->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //         } else if ($endDate == null && $startDate != null) {
    //             $result->where('payment_date', '>=', $startDate . ' 00:00:00');
    //         } else if ($endDate != null && $startDate == null) {
    //             $result->where('payment_date', '<=', $endDate . ' 23:59:59');
    //         }

    //         if (!empty($data->supplier_id)) {
    //             $result->whereHas('purchase', function ($q) use ($data) {
    //                 $q->where('supplier_id', $data->supplier_id);
    //             });
    //         }

    //         if (!empty($data->search)) {
    //             $result->whereHas('purchase', function ($q) use ($data) {
    //                 $q->where('purchase_no', 'like', '%' . $data->search . '%');
    //             })->orWhereHas('purchase.supplier', function ($q) use ($data) {
    //                 $q->where('supplier_name', 'like', '%' . $data->search . '%')
    //                     ->orWhere('supplier_code', 'like', '%' . $data->search . '%');
    //             });
    //         }
    //         return $result;
    // }



    // Customer Credit Report
    public function supplierCreditReportList($data = null, $platform = 'web')
    {
        try {
            $query = \App\Models\Supplier::query();

            // Supplier name filter
            if (!empty($data->supplier_name)) {
                $query->where('supplier_name', 'LIKE', "%{$data->supplier_name}%");
            }

            // Supplier code filter
            if (!empty($data->supplier_code)) {
                $query->where('supplier_code', 'LIKE', "%{$data->supplier_code}%");
            }



            // Filter only Supplier with credit balance (balance_amount > 0)
            if ($platform == 'web') {
                $query->where('balance', '>', 0);
            }

            if ($platform == 'web') {
                // Calculate total credit amount for all pages (before pagination)
                $totalCreditAmount = (clone $query)->where('balance', '>', 0)->sum('balance');

                $result = $query->orderBy('balance', 'DESC')
                    ->paginate(config('settings.pagination.perpage.admin'));

                $response['pagination'] = generatePaginationData($result);
                $response['data'] = $result;
                $response['total_credit_amount'] = $totalCreditAmount;
                return response()->json($response);
            } else {
                $result = $query->orderBy('balance', 'DESC')->limit(300)->get();
                return successResponse($result, 'Supplier credit report retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function supplierCreditReportListPDF($data = null, $platform = 'web')
    {
        try {
            $query = \App\Models\Supplier::query();

            // Supplier name filter
            if (!empty($data->supplier_name)) {
                $query->where('supplier_name', 'LIKE', "%{$data->supplier_name}%");
            }

            // Supplier code filter
            if (!empty($data->supplier_code)) {
                $query->where('supplier_code', 'LIKE', "%{$data->supplier_code}%");
            }



            // Filter only suppliers with credit balance
            $query->where('balance', '>', 0);

            $results = $query->orderBy('balance', 'DESC')->limit(300)->get();



            $file_name = 'supplier_credit_report_' . date('Y_m_d_H_i_s') . '.pdf';
            $pdf = Pdf::loadView(
                'admin.pages.report.pdf.supplier_credit_pdf',
                [
                    'results' => $results,
                    'supplier_name' => $data->supplier_name ?? '',
                    'supplier_code' => $data->supplier_code ?? '',

                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
