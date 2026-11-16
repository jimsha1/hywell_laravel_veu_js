<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerCreditBalance;
use App\Models\CustomerCreditHistory;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Products;
use App\Models\SalesReturn;
use App\Models\SalesReturnPayment;
use App\Models\User;
use App\Models\VehicleLoadStock;
// use App\Models\Wallet;
// use App\Models\WalletHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\FinancialYear;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Normalize payment mode names
     * "TRANSFER SAB" and "BANK TRANSFER SAB" are treated as the same
     * "TRANSFER ALRAJHI" and "BANK TRANSFER ALRAJHI" are treated as the same
     */
    protected function normalizePaymentMode($mode)
    {
        $mode = strtolower(trim($mode ?? ''));

        if ($mode === 'alrajhi') {
            return 'transfer alrajhi';
        }

        if ($mode === 'sab') {
            return 'transfer sab';
        }

        if ($mode === 'bank transfer sab') {
            return 'transfer sab';
        }

        if ($mode === 'bank transfer alrajhi') {
            return 'transfer alrajhi';
        }

        return $mode;
    }

    public function getDashboardData($request)
    {
        try {

            $response = [

                'processedCountData' => $this->getProcessedCountData($request),
                'monthlyPaymentSales' => $this->getMonthlyPaymentSales(),
                'monthlySalesData' => $this->getMonthlySalesData(),
                'weeklySalesData' => $this->getWeeklySalesData(),
                'yearlySalesData' => $this->getYearlySalesData(),

            ];

            return successResponse($response, 'Dashboard data retrieved successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    // public function getSalesManList($request)
    // {
    //    try {
    //         $sales_man = User::with('todayOpenedVehicleLoadStocks.vehicleLoadStockItems','todaySalesManSales')
    //         ->whereIn('user_type', ['sales_man', 'salesman'])->where('status', 1)->orderBy('created_at', 'desc')->get();
    //         return successResponse($sales_man, 'Sales Man List', 0, 200);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }


    // public function getSalesManList($request)
    // {
    //     try {
    //         $sales_man = User::with([
    //                 'todayOpenedVehicleLoadStocks.vehicleLoadStockItems',
    //                 'todaySalesManSales'
    //             ])
    //             ->whereIn('user_type', ['sales_man', 'salesman'])
    //             ->where('status', 1)
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         $sales_man->map(function ($salesman) {
    //             // Safely get total vehicle quantity
    //             $salesman->total_vehicle_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('total_qty') ?? 0;


    //             // Total sales amount (float)
    //             // $salesman->total_sales_amount = $salesman->todaySalesManSales->sum(function ($order) {
    //             //     return (float) $order->total_amount;
    //             // });

    //             // Total sales quantity
    //            $salesman->total_available_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('available_qty') ?? 0;

    //             return $salesman;
    //         });

    //         return successResponse($sales_man, 'Sales Man List', 0, 200);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    public function getSalesManList()
    {
        try {
            $sales_man = User::with([
                'todayOpenedVehicleLoadStocks.vehicleLoadStockItems',
                'todaySalesManSales'
            ])
                ->whereIn('user_type', ['sales_man', 'salesman'])
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            $sales_man->map(function ($salesman) {
                // Total vehicle quantity
                $salesman->total_vehicle_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('total_qty') ?? 0;

                // Total available quantity
                $salesman->total_available_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('available_qty') ?? 0;

                $salesman->total_sold_quantity = $salesman->total_vehicle_quantity - $salesman->total_available_quantity;
                //  Total sales amount (float)
                $salesman->today_sales_amount = $salesman->todaySalesManSales?->sum(function ($order) {
                    return (float) $order->total_amount;
                }) ?? 0;



                return $salesman;
            });

            return successResponse($sales_man, 'Sales Man List', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getGodownStockList($request)
    {
        // try {
        //     $products = Products::where('status',1)->orderBy('created_at', 'desc')
        //         ->get()
        //         ->map(function ($product, $index) {
        //             return [
        //                 'id' => $product->id,
        //                 'product_name' => $product->product_name,
        //                 'uom' => $product->product_uom,
        //                 'unit_price' => (float) $product->unit_price,
        //                 'selling_price' => (float) $product->selling_price,
        //                 'total_stock' => (int) $product->total_stock,
        //             ];
        //         });

        //     return successResponse($products, 'Godown Stock List', 0, 200);
        // } catch (\Exception $e) {
        //     return errorResponse(null, $e->getMessage(), 100, 500);
        // }
        try {
            $products = Products::where('status', 1)
                ->when($request->product_name, function ($query, $productName) {
                    $query->where('product_name', 'LIKE', '%' . $productName . '%');
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'uom' => $product->product_uom,
                        'unit_price' => (float) $product->unit_price,
                        'selling_price' => (float) $product->selling_price,
                        'total_stock' => (int) $product->total_stock,
                    ];
                });

            return successResponse($products, 'Godown Stock List', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getVehicleStockList($request)
    {
        try {
            $sales_man = User::with([
                'todayOpenedVehicleLoadStocks.vehicle',
                'todayOpenedVehicleLoadStocks.vehicleLoadStockItems.product',
                'todaySalesManSales'
            ])
                ->whereIn('user_type', ['sales_man', 'salesman', 'outside_salesman'])
                ->where('status', 1);

            if (isset($request->user_id)) {
                $sales_man = $sales_man->where('id', $request->user_id);
            }
            $sales_man = $sales_man->orderBy('created_at', 'desc')
                ->get();

            $sales_man->map(function ($salesman) {
                // Total vehicle quantity
                $salesman->total_vehicle_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('total_qty') ?? 0;

                // Total available quantity
                $salesman->total_available_quantity = $salesman->todayOpenedVehicleLoadStocks?->vehicleLoadStockItems?->sum('available_qty') ?? 0;

                //  Total sales amount (float)
                $salesman->today_sales_amount = $salesman->todaySalesManSales?->sum(function ($order) {
                    return (float) $order->total_amount;
                }) ?? 0;



                return $salesman;
            });

            return successResponse($sales_man, 'Vehicle Stock List', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function getProcessedCountData($request)
    {

        $selectedDate = $request->input(
            'date_filter',
            Carbon::today()->toDateString()
        );

        $date = Carbon::parse($selectedDate)->toDateString();
        $currentMonthSales = Order::where('order_status', '!=', 'draft')
            ->whereMonth('delivered_date', Carbon::now()->month)
            ->whereYear('delivered_date', Carbon::now()->year)
            ->where('is_outside', 0)
            ->sum('total_amount');

        $currentMonthSalesReturn = SalesReturn::where('return_status', 'approved')
            ->whereMonth('return_date', Carbon::now()->month)
            ->whereYear('return_date', Carbon::now()->year)
            ->sum('total_amount');

        $currentMonthSales = $currentMonthSales - $currentMonthSalesReturn;

        $currentMonthCustomers = Customer::whereMonth('registration_date', Carbon::now()->month)
            ->whereYear('registration_date', Carbon::now()->year)->where('is_outside', 0)->count();


        $todayOrdersCount = Order::where('order_status', 'delivered')
            ->where('is_outside', 0)
            // ->whereDate('delivered_date', Carbon::today())
            ->whereDate('delivered_date', $date)
            ->count();


        $newCustomers = Customer::
            // whereDate('registration_date', Carbon::today())
            whereDate('registration_date', $date)
            ->where('is_outside', 0)->count();




        // Get all today's payments grouped by payment mode
        // $todayPaymentsRaw = OrderPayment::with('order')
        //     ->whereHas('order', function ($q) {
        //         $q->whereDate('delivered_date', Carbon::today())
        //             ->where('is_outside', 0);
        //     })
        //     ->whereNotNull('payment_mode')
        //     ->where('payment_mode', '!=', 'credit')
        //     ->where('type', 'order')
        //     ->selectRaw('LOWER(TRIM(payment_mode)) as payment_mode, SUM(paid_amount) as total')
        //     ->groupBy('payment_mode')
        //     ->get();

        $todayPaymentsRaw = OrderPayment::with('order')
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('delivered_date', $date)
                    ->where('is_outside', 0);
            })
            ->whereNotNull('payment_mode')
            ->where('payment_mode', '!=', 'credit')
            ->where('type', 'order')
            ->selectRaw('LOWER(TRIM(payment_mode)) as payment_mode, SUM(paid_amount) as total')
            ->groupBy('payment_mode')
            ->get();
        // Normalize payment modes and merge duplicates
        $todayPayments = collect();
        foreach ($todayPaymentsRaw as $item) {
            $normalizedMode = $this->normalizePaymentMode($item->payment_mode);
            if ($todayPayments->has($normalizedMode)) {
                $existing = $todayPayments->get($normalizedMode);
                $existing->total += $item->total;
            } else {
                $item->payment_mode = $normalizedMode;
                $todayPayments->put($normalizedMode, $item);
            }
        }

        $cashItem = $todayPayments->get('cash');
        $cardItem = $todayPayments->get('card');
        $onlineItem = $todayPayments->get('online');
        $bankTransferAlrajhiItem = $todayPayments->get('transfer alrajhi') ?? $todayPayments->get('bank transfer alrajhi');
        $bankTransferSabItem = $todayPayments->get('transfer sab') ?? $todayPayments->get('bank transfer sab');
        $posAlrajhisItem = $todayPayments->get('pos alrajhi');
        $posSabItem = $todayPayments->get('pos sab');

        $todayCashSales = $cashItem ? (float)($cashItem->total ?? 0) : 0;
        $todayCardSales = $cardItem ? (float)($cardItem->total ?? 0) : 0;
        $todayOnlineSales = $onlineItem ? (float)($onlineItem->total ?? 0) : 0;
        $todayBankTransferAlrajhi = $bankTransferAlrajhiItem ? (float)($bankTransferAlrajhiItem->total ?? 0) : 0;
        $todayBankTransferSab = $bankTransferSabItem ? (float)($bankTransferSabItem->total ?? 0) : 0;
        $todayPosAlrajhis = $posAlrajhisItem ? (float)($posAlrajhisItem->total ?? 0) : 0;
        $todayPosSab = $posSabItem ? (float)($posSabItem->total ?? 0) : 0;

        // Calculate credit sales (balance amount from orders)
        // $todayOrderSales = Order::where('order_status', '!=', 'draft')
        //     ->whereDate('delivered_date', Carbon::today())
        //     ->where('is_outside', 0)
        //     ->selectRaw('SUM(total_amount) as total_amount, SUM(paid_amount) as paid_amount')
        //     ->first();


        $todayOrderSales = Order::where('order_status', '!=', 'draft')
            ->whereDate('delivered_date', $date)
            ->where('is_outside', 0)
            ->selectRaw('SUM(total_amount) as total_amount, SUM(paid_amount) as paid_amount')
            ->first();

        // $todayTotalSales = Order::where('order_status', '!=', 'draft')
        //     ->whereDate('delivered_date', Carbon::today())
        //     ->where('is_outside', 0)
        //     ->sum('total_amount');

        // $todayTotalSalesReturn = SalesReturn::where('return_status', 'approved')
        //     ->whereDate('return_date', Carbon::today())
        //     ->sum('total_amount');


        $todayTotalSales = Order::where('order_status', '!=', 'draft')
            ->whereDate('delivered_date', $date)
            ->where('is_outside', 0)
            ->sum('total_amount');

        $todayTotalSalesReturn = SalesReturn::where('return_status', 'approved')
            ->whereDate('return_date', $date)
            ->sum('total_amount');


        $todayNetSales = $todayTotalSales - $todayTotalSalesReturn;

        // $todaySalesReturnPaymentsRaw = SalesReturnPayment::whereDate('refund_date', Carbon::today())
        //     ->selectRaw('LOWER(TRIM(refund_mode)) as refund_mode, SUM(refund_amount) as total')
        //     ->groupBy('refund_mode')
        //     ->get();

        $todaySalesReturnPaymentsRaw = SalesReturnPayment::whereDate('refund_date', $date)
            ->selectRaw('LOWER(TRIM(refund_mode)) as refund_mode, SUM(refund_amount) as total')
            ->groupBy('refund_mode')
            ->get();

        $todaySalesReturnPayments = collect();
        foreach ($todaySalesReturnPaymentsRaw as $item) {
            $normalizedMode = $this->normalizePaymentMode($item->refund_mode);
            if ($todaySalesReturnPayments->has($normalizedMode)) {
                $existing = $todaySalesReturnPayments->get($normalizedMode);
                $existing->total += $item->total;
            } else {
                $item->refund_mode = $normalizedMode;
                $todaySalesReturnPayments->put($normalizedMode, $item);
            }
        }
        $todayCreditSales = (float)(($todayOrderSales->total_amount ?? 0) - ($todayOrderSales->paid_amount ?? 0) - ($todayTotalSalesReturn ?? 0));
        $cashReturnItem = $todaySalesReturnPayments->get('cash');
        $bankTransferAlrajhiReturnItem = $todaySalesReturnPayments->get('transfer alrajhi') ?? $todaySalesReturnPayments->get('bank transfer alrajhi');
        $bankTransferSabReturnItem = $todaySalesReturnPayments->get('transfer sab') ?? $todaySalesReturnPayments->get('bank transfer sab');
        $posAlrajhiReturnItem = $todaySalesReturnPayments->get('pos alrajhi');
        $posSabReturnItem = $todaySalesReturnPayments->get('pos sab');
        $orderBalanceReturnItem = $todaySalesReturnPayments->get('order_balance');
        $onlineReturnItem = $todaySalesReturnPayments->get('online');
        $creditReturnItem = $todaySalesReturnPayments->get('credit');

        $todayCashSalesReturn = $cashReturnItem ? (float)($cashReturnItem->total ?? 0) : 0;
        $todayBankAlrajhiSalesReturn = ($bankTransferAlrajhiReturnItem ? (float)($bankTransferAlrajhiReturnItem->total ?? 0) : 0)
            + ($posAlrajhiReturnItem ? (float)($posAlrajhiReturnItem->total ?? 0) : 0);
        $todayBankSabSalesReturn = ($bankTransferSabReturnItem ? (float)($bankTransferSabReturnItem->total ?? 0) : 0)
            + ($posSabReturnItem ? (float)($posSabReturnItem->total ?? 0) : 0);
        $todayOrderBalanceSalesReturn = $orderBalanceReturnItem ? (float)($orderBalanceReturnItem->total ?? 0) : 0;
        $todayOnlineSalesReturn = $onlineReturnItem ? (float)($onlineReturnItem->total ?? 0) : 0;
        $todayCreditSalesReturn = $creditReturnItem ? (float)($creditReturnItem->total ?? 0) : 0;
        // Net sales after sales return deduction

        $todayCashSales = $todayCashSales - $todayCashSalesReturn;

        $todayBankAlrajhi = ($todayBankTransferAlrajhi + $todayPosAlrajhis)
            - $todayBankAlrajhiSalesReturn;

        $todayBankSab = ($todayBankTransferSab + $todayPosSab)
            - $todayBankSabSalesReturn;

        $todayCreditSales = $todayCreditSales - $todayCreditSalesReturn;

        $todaySales = [
            'todayTotalSales' => $todayNetSales,
            'todayCashSales' => $todayCashSales,
            'todayBankAlrajhi' => $todayBankAlrajhi,
            'todayBankSab' => $todayBankSab,
            'todayBankTransferAlrajhi' => $todayBankTransferAlrajhi,
            'todayBankTransferSab' => $todayBankTransferSab,
            'todayPosAlrajhis' => $todayPosAlrajhis,
            'todayPosSab' => $todayPosSab,
            'todayCreditSales' => $todayCreditSales,
            'todaySalesReturn' => $todayTotalSalesReturn,
        ];

        $todaySalesReturnData = [
            'todayTotalSalesReturn' => $todayTotalSalesReturn,
            'todayCashSalesReturn' => $todayCashSalesReturn,
            'todayBankAlrajhiSalesReturn' => $todayBankAlrajhiSalesReturn,
            'todayBankSabSalesReturn' => $todayBankSabSalesReturn,
            // 'todayOrderBalanceSalesReturn' => $todayOrderBalanceSalesReturn,
            // 'todayOnlineSalesReturn' => $todayOnlineSalesReturn,
            'todayCreditSalesReturn' => $todayCreditSalesReturn,
        ];

        return  [
            'currentMonthSales' => $currentMonthSales,
            'currentMonthCustomers' => $currentMonthCustomers,
            'todayOrdersCount' => $todayOrdersCount,
            'todaySales' => $todaySales,
            'todaySalesReturnData' => $todaySalesReturnData,
            'newCustomers' => $newCustomers,
            // 'totalAdvanceAmount' => $totalAdvanceAmount,
            // 'totalAdvanceWalletAmount' => $totalAdvanceWalletAmount,
            // 'totalOutstandingWalletAmount' => $totalOutstandingWalletAmount,
            // 'totalOutstandingAmount' => $totalOutstandingAmount,
            // 'totalAdvancedCreditAmount' => $totalAdvancedCreditAmount,
            // 'totalOutstandingCreditAmount' => $totalOutstandingCreditAmount,
            // // 'todayWalletRechargeAmount' => $todayWalletRechargeAmount,
            // 'todayCreditPaymentAmount' => $todayCreditPaymentAmount,

        ];
    }
    // public function getExpenseData()
    // {

    //     $currentMonthExpense = Expense::whereMonth('expense_date', Carbon::now()->month)
    //         ->whereYear('expense_date', Carbon::now()->year)
    //         ->where('status', 'approved')
    //         ->sum('amount');


    //     $todayTotalExpenses = Expense::where('status', '=', 'approved')
    //         ->whereDate('expense_date', Carbon::today())
    //         ->where('status', 'approved')
    //         ->sum('amount');


    //     $todayCashExpense = Expense::where('status', '=', 'approved')
    //         ->whereDate('expense_date', Carbon::today())
    //         ->where('payment_mode', 'CASH')
    //         ->sum('amount');
    //     $todayBankAlrajhi = Expense::where('status', '=', 'approved')
    //         ->whereDate('expense_date', Carbon::today())
    //         ->where('payment_mode', 'ALRAJHI')
    //         ->sum('amount');
    //     $todayBankSab = Expense::where('status', '=', 'approved')
    //         ->whereDate('expense_date', Carbon::today())
    //         ->where('payment_mode', 'SAB')
    //         ->sum('amount');

    //     // $allExpenses = Expense::where('status', '=', 'pending')
    //     //     ->get();

    //     $allExpenses = Expense::orderByRaw("
    //      CASE
    //           WHEN status = 'pending' THEN 1
    //           WHEN status = 'approved' THEN 2
    //           WHEN status = 'rejected' THEN 3
    //       END
    //      ")
    //         ->orderBy('created_at', 'desc')
    //         ->limit(10)
    //         ->get();
    //     $todayExpense = [
    //         'todayTotalExpenses' => $todayTotalExpenses,

    //         'todayCashExpense' => $todayCashExpense,

    //         'todayBankAlrajhi' => $todayBankAlrajhi,
    //         'todayBankTransferSab' => $todayBankSab,

    //         'pendingExpenses' => $allExpenses,


    //     ];


    //     return  [
    //         'currentMonthExpense' => $currentMonthExpense,

    //         'todayExpense' => $todayExpense,

    //     ];
    // }

    public function getExpenseData($request)
    {
        try {

            $currentMonthExpense = Voucher::where('voucher_type', 'payment')
                ->where('voucher_status', 'approved')
                ->whereMonth('voucher_date', Carbon::now()->month)
                ->whereYear('voucher_date', Carbon::now()->year)
                ->with('entries')
                ->get()
                ->sum(function ($voucher) {
                    return $voucher->entries->sum('credit');
                });
            $selectedDate = $request->input(
                'date_filter',
                Carbon::today()->toDateString()
            );

            $date = Carbon::parse($selectedDate)->toDateString();
            $todayTotalExpenses = Voucher::where('voucher_type', 'payment')
                ->where('voucher_status', 'approved')
                ->whereDate('voucher_date', $date)
                ->with('entries')
                ->get()
                ->sum(function ($voucher) {
                    return $voucher->entries->sum('credit');
                });

            $todayCashExpense = Voucher::where('voucher_type', 'payment')
                ->where('voucher_status', 'approved')
                ->whereDate('voucher_date', Carbon::today())
                ->whereHas('entries.account', function ($q) {
                    $q->where('name', 'Cash');
                })
                ->with('entries')
                ->get()
                ->sum(function ($voucher) {
                    return $voucher->entries->sum('credit');
                });

            $todayBankAlrajhi = Voucher::where('voucher_type', 'payment')
                ->where('voucher_status', 'approved')
                ->whereDate('voucher_date', Carbon::today())
                ->whereHas('entries.account', function ($q) {
                    $q->where('name', 'ALRAJHI');
                })
                ->with('entries')
                ->get()
                ->sum(function ($voucher) {
                    return $voucher->entries->sum('credit');
                });

            $todayBankSab = Voucher::where('voucher_type', 'payment')
                ->where('voucher_status', 'approved')
                ->whereDate('voucher_date', Carbon::today())
                ->whereHas('entries.account', function ($q) {
                    $q->where('name', 'SAB');
                })
                ->with('entries')
                ->get()
                ->sum(function ($voucher) {
                    return $voucher->entries->sum('credit');
                });

            $allExpenses = Voucher::query()
                ->where('voucher_type', 'payment')
                ->with(['entries.account.accountType'])
                ->orderByRaw("
                CASE
                    WHEN voucher_status = 'pending' THEN 1
                    WHEN voucher_status = 'approved' THEN 2
                    WHEN voucher_status = 'rejected' THEN 3
                END
            ")
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            $todayExpense = [
                'todayTotalExpenses' => $todayTotalExpenses,
                'todayCashExpense' => $todayCashExpense,
                'todayBankAlrajhi' => $todayBankAlrajhi,
                'todayBankTransferSab' => $todayBankSab,
                'pendingExpenses' => $allExpenses,
            ];

            return successResponse([
                'currentMonthExpense' => $currentMonthExpense,
                'todayExpense' => $todayExpense,
            ], 'Expenses retrieved successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function getMonthlyPaymentSales()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Get all monthly payments grouped by payment mode
        $monthlyPaymentsRaw = OrderPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->whereHas('order', function ($q) {
                $q->where('is_outside', 0)
                    ->where('order_status', Order::ORDER_STATUS_DELIVERED);
            })
            ->whereNotNull('payment_mode')
            ->where('payment_mode', '!=', 'credit')
            ->where('type', 'order')
            ->selectRaw('LOWER(TRIM(payment_mode)) as payment_mode, SUM(paid_amount) as total')
            ->groupBy('payment_mode')
            ->get();

        // Normalize payment modes and merge duplicates
        $monthlyPayments = collect();
        foreach ($monthlyPaymentsRaw as $item) {
            $normalizedMode = $this->normalizePaymentMode($item->payment_mode);
            if ($monthlyPayments->has($normalizedMode)) {
                $existing = $monthlyPayments->get($normalizedMode);
                $existing->total += $item->total;
            } else {
                $item->payment_mode = $normalizedMode;
                $monthlyPayments->put($normalizedMode, $item);
            }
        }

        $monthlyCashItem = $monthlyPayments->get('cash');
        $monthlyCardItem = $monthlyPayments->get('card');
        $monthlyOnlineItem = $monthlyPayments->get('online');
        $monthlyBankTransferAlrajhiItem = $monthlyPayments->get('transfer alrajhi') ?? $monthlyPayments->get('bank transfer alrajhi');
        $monthlyBankTransferSabItem = $monthlyPayments->get('transfer sab') ?? $monthlyPayments->get('bank transfer sab');
        $monthlyPosAlrajhisItem = $monthlyPayments->get('pos alrajhi');
        $monthlyPosSabItem = $monthlyPayments->get('pos sab');

        $monthlyCashSales = $monthlyCashItem ? (float)($monthlyCashItem->total ?? 0) : 0;
        $monthlyCardSales = $monthlyCardItem ? (float)($monthlyCardItem->total ?? 0) : 0;
        $monthlyOnlineSales = $monthlyOnlineItem ? (float)($monthlyOnlineItem->total ?? 0) : 0;
        $monthlyBankTransferAlrajhi = $monthlyBankTransferAlrajhiItem ? (float)($monthlyBankTransferAlrajhiItem->total ?? 0) : 0;
        $monthlyBankTransferSab = $monthlyBankTransferSabItem ? (float)($monthlyBankTransferSabItem->total ?? 0) : 0;
        $monthlyPosAlrajhis = $monthlyPosAlrajhisItem ? (float)($monthlyPosAlrajhisItem->total ?? 0) : 0;
        $monthlyPosSab = $monthlyPosSabItem ? (float)($monthlyPosSabItem->total ?? 0) : 0;

        // Calculate credit sales (balance amount from orders)
        $monthlyOrderSales = Order::where('order_status', Order::ORDER_STATUS_DELIVERED)
            ->where('is_outside', 0)
            ->whereBetween('delivered_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(total_amount) as total_amount, SUM(paid_amount) as paid_amount')
            ->first();

        $monthlyCreditSales = (float)(($monthlyOrderSales->total_amount ?? 0) - ($monthlyOrderSales->paid_amount ?? 0));

        $monthlyTotalSales = OrderPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->whereHas('order', function ($q) {
                $q->where('is_outside', 0)->where('order_status', Order::ORDER_STATUS_DELIVERED);
            })
            ->where('payment_mode', '!=', 'credit')
            ->where('type', 'order')
            ->sum('paid_amount');

        return [
            'monthlyCashSales' => $monthlyCashSales,
            'monthlyCardSales' => $monthlyCardSales,
            'monthlyOnlineSales' => $monthlyOnlineSales,
            'monthlyBankTransferAlrajhi' => $monthlyBankTransferAlrajhi,
            'monthlyBankTransferSab' => $monthlyBankTransferSab,
            'monthlyPosAlrajhis' => $monthlyPosAlrajhis,
            'monthlyPosSab' => $monthlyPosSab,
            'monthlyCreditSales' => $monthlyCreditSales,
            'monthlyTotalSales' => $monthlyTotalSales,
        ];
    }



    public function getMonthlySalesData()
    {
        try {
            $monthlySales = Order::where('order_status', '!=', 'draft')
                ->where('is_outside', 0)
                ->selectRaw('MONTH(delivered_date) as month, SUM(CAST(total_amount AS DECIMAL(10,2))) as total_amount')
                ->whereYear('delivered_date', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total_amount', 'month')
                ->map(fn($value) => (float) $value);
            $months = [
                1 => "Jan",
                2 => "Feb",
                3 => "Mar",
                4 => "Apr",
                5 => "May",
                6 => "June",
                7 => "July",
                8 => "Aug",
                9 => "Sep",
                10 => "Oct",
                11 => "Nov",
                12 => "Dec",
            ];
            $salesByMonth = array_fill_keys(array_values($months), 0);
            foreach ($monthlySales as $monthNumber => $total) {
                $monthName = $months[$monthNumber];
                $salesByMonth[$monthName] = $total;
            }
            return $salesByMonth;
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }



    public function getWeeklySalesData()
    {
        try {
            // Fetch sales data from DB, grouping by weekday number (1=Sunday ... 7=Saturday)
            $weeklySales = Order::where('order_status', '!=', 'draft')
                ->where('is_outside', 0)
                ->selectRaw('DAYOFWEEK(delivered_date) as day, SUM(CAST(total_amount AS DECIMAL(10,2))) as total_amount')
                ->whereBetween('delivered_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->pluck('total_amount', 'day') // Make it associative: [day => total_amount]
                ->map(fn($value) => (float) $value); // Ensure float values

            // Day mapping (short form), reordered starting from Monday
            $days = [
                2 => 'Mon',
                3 => 'Tue',
                4 => 'Wed',
                5 => 'Thu',
                6 => 'Fri',
                7 => 'Sat',
                1 => 'Sun',
            ];

            // Initialize with zero values for all days
            $weeklyOrders = [];
            foreach ($days as $dayNumber => $shortName) {
                $weeklyOrders[] = [
                    'x' => $shortName,
                    'y' => $weeklySales[$dayNumber] ?? 0.0
                ];
            }

            return $weeklyOrders;
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }


    public function getYearlySalesData()
    {
        try {
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function getEmployeeCommission()
    {
        try {
            $currentMonthStart = now()->startOfMonth();
            $currentMonthEnd = now()->endOfMonth();
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $commissions = User::whereIn('user_type', ['salesman', 'sales_executive'])
                // ->withSum(['commissionHistories as total_commission' => function ($query) use ($currentMonthStart, $currentMonthEnd) {
                //         $query->whereBetween('date', [$currentMonthStart, $currentMonthEnd]);
                //     }], 'commission_amount')
                //     ->withSum(['paid_commissions as paid' => function ($query) use ($currentMonthStart, $currentMonthEnd) {
                //         $query->whereBetween('date', [$currentMonthStart, $currentMonthEnd]);
                //     }], 'paid_amount')
                ->get()
                ->map(function ($user) use ($currentMonthStart, $currentMonthEnd, $currentMonth, $currentYear) {
                    $historySum = \App\Models\CommissionHistory::where('user_id', $user->id)
                        ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                        ->sum('commission_amount') ?: 0;

                    $employee = \App\Models\Employee::where('user_id', $user->id)->first();
                    $givenSum = 0;
                    if ($employee) {
                        $givenSum = \App\Models\CommissionPayroll::where('employee_id', $employee->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->where('status', 'paid')
                            ->sum('commission') ?: 0;
                    }

                    return [
                        'username' => $user->name,
                        'total_commission' => (float)$historySum - (float)$givenSum,
                        'given_commission' => (float)$givenSum
                    ];
                });

            // $paidcommissions = User::whereIn('user_type', ['salesman', 'sales_executive'])
            //     ->withSum(['paid_commissions as paid' => function ($query) use ($currentMonthStart, $currentMonthEnd) {
            //         $query->whereBetween('date', [$currentMonthStart, $currentMonthEnd]);
            //     }], 'paid_amount')
            //     // ->select('id', 'name')
            //     ->get()


            //     ->map(function ($user) {
            //         return [

            //             'paid_amount' => $user->paid ?? 0
            //         ];
            //     });

            return response()->json([
                'status' => 'success',
                'data' => $commissions,

            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 100
            ], 500);
        }
    }


    public function getHomePageData($request)
    {

        try {

            $user = User::find($request->user_id);
            $response = [
                'salesData' => $this->getProcessedCountData($request),
                'expenseData' => $this->getExpenseData($request),
                'salesManList' => $this->getSalesManList(),
                'employeeListWithCommission' => $this->getEmployeeCommission(),
                'getGodownStockList' => $this->getGodownStockList($request),
                'getVehicleStockList' => $this->getVehicleStockList($request),
                'cashBankAccountsList' => $this->getCashBankAccountsList(),
                'totalCreditAmount' => $this->getTotalCreditAmount(),
                'totalExpenseAmount' => $this->getExpenseAmount(),
                'monthlyPaymentSales' => $this->getMonthlyPaymentSales(),
                'monthlySalesData' => $this->getMonthlySalesData(),
                'weeklySalesData' => $this->getWeeklySalesData(),
                'yearlySalesData' => $this->getYearlySalesData(),
                'user' => $user,


            ];

            return successResponse($response, 'Dashboard data retrieved successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    // public function getTotalCreditAmount()
    // {

    //     $accounts = Account::where('account_type_id', 2)
    //         ->active()->get();


    //     $totalCredit = 0;

    //     $accounts->transform(function ($account) use (&$totalCredit) {


    //         $totalCredit += $account->getCurrentBalance();
    //     });


    //     return successResponse($totalCredit, 'total credit payment retrieved', 0);
    // }


    public function getTotalCreditAmount()
    {

        $financialYear = FinancialYear::where('is_active', 1)->where('is_closed', 0)->first();
        $liabilities = self::getAccountsByType(
            'LIABILITY',
            $financialYear->end_date,
            $financialYear->start_date,
            $financialYear->id
        );



        $totalCredit = 0;


        foreach ($liabilities as $account) {
            $totalCredit += (float) $account['balance'];
        }

        return successResponse($totalCredit, 'total credit payment retrieved', 0);
    }

    public function getExpenseAmount()
    {

        $financialYear = FinancialYear::where('is_active', 1)->where('is_closed', 0)->first();


        $totalExpenses = Voucher::where('voucher_type', 'payment')->where('voucher_status', 'pending')->sum('amount');



        return successResponse($totalExpenses, 'total expenses retrieved', 0);
    }

    protected static function getAccountsByType(
        string $typeCode,
        ?string $date = null,
        ?string $startDate = null,
        ?int $financialYearId = null
    ): array {
        $date = $date ?? now()->toDateString();

        /*
    |--------------------------------------------------------------------------
    | Get Account Type
    |--------------------------------------------------------------------------
    */

        $accountType = AccountType::where('code', $typeCode)->first();

        if (!$accountType) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Get Accounts
    |--------------------------------------------------------------------------
    */

        $accounts = Account::query()
            ->select([
                'id',
                'code',
                'name',
                'parent_id',
                'account_type_id',
                'opening_balance',
                'opening_balance_date',
            ])
            ->where('account_type_id', $accountType->id)
            ->where('code', '!=', 'A1001')
            ->active()
            ->orderBy('code')
            ->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Account IDs
    |--------------------------------------------------------------------------
    */

        $accountIds = $accounts->pluck('id')->toArray();

        /*
    |--------------------------------------------------------------------------
    | Get Debit / Credit Totals in ONE query
    |--------------------------------------------------------------------------
    */

        $journalTotals = DB::table('journal_entry_lines as jel')
            ->join(
                'journal_entries as je',
                'je.id',
                '=',
                'jel.journal_entry_id'
            )
            ->whereIn('jel.account_id', $accountIds)
            ->where('je.status', 'posted')
            ->whereDate('je.entry_date', '<=', $date)

            ->when($financialYearId, function ($query) use ($financialYearId) {
                $query->where(
                    'je.financial_year_id',
                    $financialYearId
                );
            })

            ->select(
                'jel.account_id',

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'debit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS debit_total
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'credit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS credit_total
            ")
            )

            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        /*
    |--------------------------------------------------------------------------
    | Build Result
    |--------------------------------------------------------------------------
    */

        $result = [];

        foreach ($accounts as $account) {

            $totals = $journalTotals->get($account->id);

            $debitTotal = $totals
                ? (float) $totals->debit_total
                : 0;

            $creditTotal = $totals
                ? (float) $totals->credit_total
                : 0;

            /*
        |--------------------------------------------------------------------------
        | Calculate Balance
        |--------------------------------------------------------------------------
        */

            $balance = $debitTotal - $creditTotal;

            /*
        |--------------------------------------------------------------------------
        | Credit Normal Account
        |--------------------------------------------------------------------------
        */

            if ($accountType->isCreditNormal()) {
                $balance = $creditTotal - $debitTotal;
            }

            /*
        |--------------------------------------------------------------------------
        | Opening Balance
        |--------------------------------------------------------------------------
        */

            if (
                $account->opening_balance_date &&
                $account->opening_balance_date <= $date
            ) {
                $balance += (float) $account->opening_balance;
            }

            /*
        |--------------------------------------------------------------------------
        | Only return accounts having balance
        |--------------------------------------------------------------------------
        */

            if (
                abs($balance) > 0.0001 ||
                (float) $account->opening_balance != 0
            ) {

                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => round($balance, 2),
                    'parent_id' => $account->parent_id,
                ];
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Free memory
    |--------------------------------------------------------------------------
    */

        unset($accounts);
        unset($accountIds);
        unset($journalTotals);

        return $result;
    }
    public function getCashBankAccountsList()
    {
        try {

            $parent = Account::where('code', '1100')->first();
            $accounts = Account::with('accountType')
                ->active()
                ->orderBy('code')
                ->where('parent_id', $parent->id)
                ->get(['id', 'code', 'name', 'account_type_id']);

            $accounts->transform(function ($account) {

                $account->current_balance = $account->getCurrentBalance();
                return $account;
            });

            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function getPendingExpenses($request)
    {
        try {

            $perPage = $request->input('per_page', 10);

            $query = Voucher::query()
                ->where('voucher_type', 'payment')
                // ->where('voucher_status', 'pending')
                ->with(['entries.account.accountType']);

            if ($request->filled('start_date')) {
                $query->whereDate('voucher_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('voucher_date', '<=', $request->end_date);
            }

            if ($request->filled('voucher_status')) {
                $query->where('voucher_status', $request->voucher_status);
            }

            $pendingExpenses = $query
                ->orderBy('voucher_date', 'desc')
                ->paginate($perPage);

            return successResponse(
                $pendingExpenses,
                'Pending expenses retrieved successfully',
                0
            );
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
}
