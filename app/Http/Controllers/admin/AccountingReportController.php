<?php

namespace App\Http\Controllers\admin;

use App\Helpers\ReportHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnPayment;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use App\Services\JournalEntryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Console\PurgeCommand;

class AccountingReportController extends Controller
{
    protected JournalEntryService $journalEntryService;


    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }
    /**
     * Display the accounting reports page
     */
    public function index()
    {
        return view('admin.pages.accounting_reports.index');
    }

    /**
     * Get Trial Balance
     */
    public function getTrialBalance(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $financialYearId = $request->financialYearId;

            $data = ReportHelper::getTrialBalance($startDate, $endDate, $financialYearId);

            if (isset($data['message'])) {
                return errorResponse(null, $data['message'], 100, 200);
            }
            return successResponse($data, 'Trial Balance retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Balance Sheet
     */
    public function getBalanceSheet(Request $request)
    {

        try {

            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $financialYearId = $request->financialYearId;
            $data = ReportHelper::getBalanceSheet($endDate, $startDate, $financialYearId);
            if (isset($data['message'])) {
                return errorResponse(null, $data['message'], 100, 200);
            }
            return successResponse($data, 'Balance Sheet retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Income Statement (Profit & Loss)
     */
    public function getIncomeStatement(Request $request)
    {
        try {
            $financialYearId = $request->financialYearId;
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $data = ReportHelper::getIncomeStatement($startDate, $endDate, $financialYearId);
            if (isset($data['message'])) {
                return errorResponse(null, $data['message'], 100, 200);
            }
            return successResponse($data, 'Income Statement retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Day Book
     */
    public function getDayBook(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate= $request->end_date;
            $financialYearId = $request->financialYearId;
            $data = ReportHelper::getDayBook($startDate, $endDate, $financialYearId);
            return successResponse($data, 'Day Book retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Cash Book
     */
    public function getCashBook(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $accountCode = $request->account_code ?? null; // Allow selecting specific cash/bank account
            $data = ReportHelper::getCashBook($startDate, $endDate, $accountCode);
            return successResponse($data, 'Cash Book retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get General Ledger for an account
     */
    public function getGeneralLedger(Request $request)
    {
        try {
            $request->validate([
                'account_id' => 'required|exists:accounts,id',
            ]);

            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $financialYearId = $request->financialYearId;

            $data = ReportHelper::getGeneralLedger(
                (int)$request->account_id,
                $startDate,
                $endDate,
                $financialYearId
            );
            return successResponse($data, 'General Ledger retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Journal Entries Report
     */
    public function getJournalEntries(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();
            $status = $request->status ?? 'posted';

            $data = ReportHelper::getJournalEntriesReport($startDate, $endDate, $status);
            return successResponse($data, 'Journal Entries retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get Chart of Accounts
     */
    public function getChartOfAccounts(Request $request)
    {
        try {
            $includeInactive = $request->boolean('include_inactive', false);
            $data = ReportHelper::getChartOfAccounts($includeInactive);
            return successResponse($data, 'Chart of Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get list of accounts for dropdown
     */
    public function getAccountsList()
    {
        try {
            $accounts = Account::with('accountType')
                ->active()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'account_type_id']);
            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
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
            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    // public function accountsEntry()
    // {
    //     //purchase entry

    //     $allPurchases = Purchase::where('accounts_posted', false)->get();

    //     foreach ($allPurchases as $purchase) {


    //         $supplier = Supplier::find($purchase->supplier_id);

    //         $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)
    //             ->where('deleted_at', NULL)
    //             ->get();

    //         foreach ($purchaseItems as $item) {

    //             $this->journalEntryService->createPurchaseEntry([
    //                 'date' => $purchase->purchase_date,
    //                 'net_amount' => $item->total,
    //                 'reference_type' => 'App\\Models\\Purchase',
    //                 'reference_id' => $purchase->id,
    //                 'description' => 'Purchase - ' . $purchase->purchase_no,
    //                 'supplier_id' => $supplier->id,
    //                 'supplier_name' => $supplier->supplier_name,
    //                 'supplier_code' => $supplier->supplier_code,
    //                 'user_id' => $purchase->created_by,
    //             ]);

    //             $item->update(['accounts_posted' => true]);
    //             $cost_price = $item->total / $item->qty;
    //             StockOriginal::create([
    //                 'ref_id'       => $purchase->id,
    //                 'ref_no'       => $purchase->purchase_no,
    //                 'product_id'   => $item->product_id,
    //                 'quantity'     => $item->qty,
    //                 'cost_price' => $cost_price,
    //                 'movement_type' => 'in',
    //                 'reason'       => 'Purchase',
    //                 'date' => $purchase->purchase_date,
    //                 'created_by'   => $purchase->created_by,
    //             ]);
    //         }
    //         $purchasePayments = PurchasePayment::where('purchase_id', $purchase->id)->get();
    //         foreach ($purchasePayments as $payment) {
    //             if ($payment->payment_mode != 'credit') {
    //                 $this->journalEntryService->createPurchasePaymentEntry([
    //                     'date' => $payment->payment_date,
    //                     'amount' => $payment->amount_taken,
    //                     'reference_type' => 'App\\Models\\PurchasePayment',
    //                     'reference_id' => $payment->id,
    //                     'description' => 'Payment - ' . $purchase->purchase_no,
    //                     'supplier_id' => $supplier->id,
    //                     'supplier_name' => $supplier->supplier_name,
    //                     'supplier_code' => $supplier->supplier_code,
    //                     'payment_mode' => $payment->payment_mode,
    //                     'user_id' => $purchase->created_by,
    //                 ]);
    //                 $payment->update(['accounts_posted' => true]);
    //             }
    //         }

    //         $purchase->update(['accounts_posted' => true]);
    //     }




    //     // sales return
    //     // $allSalesReturn = SalesReturn::with(['customer', 'returnItems', 'returnPayments'])
    //     //     ->where('return_status', 'approved')->get();

    //     // foreach ($allSalesReturn as $salesReturn) {

    //     //     $customer = $salesReturn->customer;
    //     //     foreach ($salesReturn->returnPayments as $payment) {

    // $this->journalEntryService->createSalesReturnEntry([
    //     'date' => $payment->refund_date,
    //     'reference_type' => 'App\\Models\\SalesReturn',
    //     'reference_id' => $payment->sales_return_id,
    //     'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $salesReturn->customer->customer_name,
    //     'amount' => $payment->refund_amount,
    //     'user_id' => $salesReturn->approved_by,
    //     'return_no' => $salesReturn->return_no ?? $salesReturn->id,
    //     'customer_id' => $customer->id,
    //     'customer_name' => $customer->customer_name,
    //     'refund_mode' => $payment->refund_mode,
    // ]);
    //     //     }

    //     //     // foreach ($salesReturn->returnItems as $returnItem) {
    //     //     //     $totalReturnQty = $returnItem->return_quantity + $returnItem->free_quantity;

    //     //     //     $soldCostPrice = StockOriginal::where('ref_id', $salesReturn->order_id)
    //     //     //         ->where('product_id', $returnItem->product_id)->first();
    //     //     //     if ($soldCostPrice) {
    //     //     //         $cost_price = $soldCostPrice->cost_price;
    //     //     //         $originalStock = StockOriginal::create([
    //     //     //             'ref_id'       => $salesReturn->id,
    //     //     //             'ref_no'       =>  $salesReturn->return_no ?? $salesReturn->order->order_no,
    //     //     //             'product_id'   => $returnItem->product_id,
    //     //     //             'quantity'     => $totalReturnQty,
    //     //     //             'cost_price' => $cost_price,
    //     //     //             'movement_type' => 'in',
    //     //     //             'reason'       => 'Sales Return',
    //     //     //             'date' => $salesReturn->return_date,
    //     //     //             'created_by'   => $salesReturn->created_by,
    //     //     //         ]);
    //     //     //     }
    //     //     // }
    //     // }

    //     //customer opening balance

    //     // $openingBalances = OpeningBalance::get();
    //     // foreach ($openingBalances as $balance) {
    //     //     $account = Account::where('reference_id', $balance->customer_id)->where('reference_type', 'customer')->first();
    //     //     $this->journalEntryService->createOpeningBalanceEntry([
    //     //         'date' => $balance->date,
    //     //         'amount' => $balance->amount,
    //     //         'reference_type' => 'App\\Models\\CustomerOpeningBalance',
    //     //         'reference_id' => $balance->id,
    //     //         'description' => 'Customer opening balance entry',
    //     //         'account' => $account,
    //     //         'user_id' => 1,

    //     //     ]);
    //     // }

    //     // $payments = OrderPayment::where('accounts_posted', false)
    //     //     ->where('type', 'order')
    //     //     ->where('payment_mode', '!=', 'Credit')
    //     //     ->get();
    //     // foreach ($payments as $payment) {

    //     //     $customer = Customer::where('id', $payment->customer_id)->first();
    //     //     $this->journalEntryService->createSalesPaymentEntry([
    //     //         'date' => $payment->payment_date,
    //     //         'amount' => $payment->paid_amount,
    //     //         'reference_type' => 'App\\Models\\OrderPayment',
    //     //         'reference_id' => $payment->id,
    //     //         'description' => 'Payment received - Order ' . $payment->order_id,
    //     //         'customer_id' => $customer->id ?? '',
    //     //         'customer_name' => $customer->customer_name ?? '',
    //     //         'payment_mode' => $payment->payment_mode,
    //     //         'user_id' => $customer->id,
    //     //         'customer_code' => $customer->customer_code ?? null,
    //     //     ]);

    //     //     $payment->update(['accounts_posted' => true]);
    //     // }

    //     dd('ok');
    // }


    // public function accountsEntry()
    // {

    // $suppliersOpeningBalances = SupplierOpeningBalance::get();

    // foreach ($suppliersOpeningBalances as $supplierBalance) {

    //     $supplier = Supplier::find($supplierBalance->supplier_id);
    //     $account = Account::find($supplier->account_id);
    //     $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
    //         'date' => "2025-08-31",
    //         // 'date' => Carbon::now(),
    //         'amount' => $supplierBalance->amount,
    //         'reference_type' => 'App\\Models\\SupplierOpeningBalance',
    //         'reference_id' => $supplierBalance->id,
    //         'description' => 'Supplier opening balance entry',
    //         'account' => $account,
    //         'user_id' => 1,

    //     ]);
    // }

    // $customersOpeningBalances = OpeningBalance::get();

    // foreach ($customersOpeningBalances as $customerBalance) {
    //     $customer = Customer::find($customerBalance->customer_id);

    //     $account = Account::find($customer->account_id);

    //     $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
    //         'date' => "2025-08-31",
    //         // 'date' => $requested_date,
    //         'amount' => $customerBalance->amount,
    //         'reference_type' => 'App\\Models\\CustomerOpeningBalance',
    //         'reference_id' => $customerBalance->id,
    //         'description' => 'Customer opening balance entry',
    //         'account' => $account,
    //         'user_id' => 1,

    //     ]);
    // }



    // $purchases = Purchase::where('purchase_date', "2025-01-01")->get();

    // foreach ($purchases as $purchase) {
    //     $financialYear = FinancialYear::find(2);
    //     $purchase->financial_year_id = 2;
    //     $purchase->save();
    //     $supplier = Supplier::find($purchase->supplier_id);

    //     $jo = $this->journalEntryService->createPurchaseEntry([
    //         'date' => $purchase->purchase_date,
    //         'total_amount' => $purchase->total,
    //         'reference_type' => 'App\\Models\\Purchase',
    //         'reference_id' => $purchase->id,
    //         'description' => 'Payment - ' . $purchase->purchase_no,
    //         'supplier_id' => $supplier->id,
    //         'supplier_name' => $supplier->supplier_name,
    //         'supplier_code' => $supplier->supplier_code,
    //         'financialYear' => $financialYear,
    //         'user_id' => 1,
    //     ]);
    //     if(!$jo){
    //         dd($purchase->id);
    //     }
    // }
    // dd('purchase entry completed');


    // $sales = Order::where('order_date', '<', "2026-01-01")
    //     ->where('order_status', 'delivered')
    //     ->where('id','>',2662)
    //     ->get();

    // foreach ($sales as $order) {
    //     $financialYear = FinancialYear::find(2);
    //     $order->financial_year_id = 2;
    //     $order->save();
    //     $customer = Customer::find($order->customer_id);
    // $journalEntry = $this->journalEntryService->createSalesCreditEntry([
    //     'date' => $order->order_date,
    //     'amount' => $order->total_amount,
    //     'reference_type' => 'App\\Models\\Order',
    //     'reference_id' => $order->id,
    //     'description' => 'Order ' . $order->order_no,
    //     'customer_id' => $customer ? $customer->id : '',
    //     'customer_name' => $customer ? $customer->customer_name : '',
    //     'financialYear' => $financialYear,
    //     'customer_type' => 'customer',
    //     'user_id' => $customer ? $customer->user_id : '',

    //     'customer_code' => $customer ? $customer->customer_code : null,
    // ]);
    // }
    // dd('sales entry completed');


    // $orderPayments = OrderPayment::where('payment_date', '<', '2026-01-01')
    // ->where('payment_date', '>=', '2025-12-01')
    // ->where('type', 'order')
    // ->where('id','!=',9983)
    // ->where('id','>',3117)
    // ->get();

    // foreach ($orderPayments as $payment) {
    //     $financialYear = FinancialYear::find(2);
    //     $payment->financial_year_id = 2;
    //     $payment->save();
    //     $customer = Customer::find($payment->customer_id);
    //     $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
    //         'date' => $payment->payment_date,
    //         'amount' => $payment->paid_amount,
    //         'reference_type' => 'App\\Models\\OrderPayment',
    //         'reference_id' => $payment->id,
    //         'description' => 'Order Payment - Order ' . $payment->id,
    //         'customer_id' => $customer ? $customer->id : '',
    //         'customer_name' => $customer ? $customer->customer_name : '',
    //         'payment_mode' => $payment->payment_mode,
    //         'customer_type' => 'customer',
    //         'user_id' => $customer ? $customer->user_id : '',

    //         'customer_code' => $customer ? $customer->customer_code : null,
    //     ]);
    // }
    // dd('order payements entry completed');


    //  $orderPayments = OrderPayment::where('payment_date', '<', '2026-01-01')

    // ->where('type', 'opening_balance')
    // // ->where('id','!=',9983)
    // ->where('id','>',1885)
    // ->get();

    // foreach ($orderPayments as $payment) {
    //     $financialYear = FinancialYear::find(2);
    //     $payment->financial_year_id = 2;
    //     $payment->save();
    //     $customer = Customer::find($payment->customer_id);
    //     $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
    //         'date' => $payment->payment_date,
    //         'amount' => $payment->paid_amount,
    //         'reference_type' => 'App\\Models\\OrderPayment',
    //         'reference_id' => $payment->id,
    //         'description' => 'Order Payment - Order ' . $payment->id,
    //         'customer_id' => $customer ? $customer->id : '',
    //         'customer_name' => $customer ? $customer->customer_name : '',
    //         'payment_mode' => $payment->payment_mode,
    //         'customer_type' => 'customer',
    //         'user_id' => $customer ? $customer->user_id : '',

    //         'customer_code' => $customer ? $customer->customer_code : null,
    //     ]);
    // }
    // dd('order payements entry completed');



    //  $salesReturns = SalesReturn::where('return_date', '<', '2026-01-01')
    // ->where('payment_date', '>=', '2025-12-01')
    // ->where('type', 'order')
    // ->where('id','!=',9983)
    // ->where('id','>',3117)
    // ->get();

    // foreach ($salesReturns as $return) {
    //     $financialYear = FinancialYear::find(2);
    //     $payment->financial_year_id = 2;
    //     $payment->save();
    //     $customer = Customer::find($payment->customer_id);
    // $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
    //     'date' => $payment->payment_date,
    //     'amount' => $payment->paid_amount,
    //     'reference_type' => 'App\\Models\\OrderPayment',
    //     'reference_id' => $payment->id,
    //     'description' => 'Order Payment - Order ' . $payment->id,
    //     'customer_id' => $customer ? $customer->id : '',
    //     'customer_name' => $customer ? $customer->customer_name : '',
    //     'payment_mode' => $payment->payment_mode,
    //     'customer_type' => 'customer',
    //     'user_id' => $customer ? $customer->user_id : '',

    //     'customer_code' => $customer ? $customer->customer_code : null,
    // ]);
    // }
    // dd('order payements entry completed');


    // $stockMovements = StockMovement::where('date', '<', '2026-01-01')
    //     ->where('deleted_at', NULL)
    //     // ->whereIn('reason', ['purchase', 'sales', 'sales_return', 'direct order'])
    //     ->where('reason', 'direct order')
    //     ->orderBy('date')->get();
    // foreach ($stockMovements as $movement) {

    //     $orderItems = OrderItem::where('order_id', $movement->ref_id)->get();
    //     foreach ($orderItems as $item) {
    //         $order = Order::find($item->order_id);
    //         $cost_price = StockOriginal::where('movement_type', 'in')
    //             ->where('product_id', $item->product_id)
    //             ->where('date', '<', $order->order_date)
    //             ->avg('cost_price');

    //         StockOriginal::create([
    //             'ref_id'       => $order->id,
    //             'ref_no'       => $order->purchase_no,
    //             'product_id'   => $item->product_id,
    //             'quantity'     => $item->qty,
    //             'cost_price' => $cost_price,
    //             'movement_type' => 'out',
    //             'reason'       => 'Sale',
    //             'date' => $order->order_date,
    //             'created_by'   => $order->user_id ?? 1,
    //         ]);
    //     }
    // }
    //     dd('finished');
    // }



    public function accountsEntry()
    {
        $abc = 0;
        if ($abc == 0) {

            $suppliersOpeningBalances = SupplierOpeningBalance::get();

            foreach ($suppliersOpeningBalances as $supplierBalance) {

                $supplier = Supplier::find($supplierBalance->supplier_id);
                $account = Account::find($supplier->account_id);
                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => "2025-08-31",
                    // 'date' => Carbon::now(),
                    'amount' => $supplierBalance->amount,
                    'reference_type' => 'App\\Models\\SupplierOpeningBalance',
                    'reference_id' => $supplierBalance->id,
                    'description' => 'Supplier opening balance entry',
                    'account' => $account,
                    'user_id' => 1,

                ]);
            }

            $customersOpeningBalances = OpeningBalance::get();

            foreach ($customersOpeningBalances as $customerBalance) {
                $customer = Customer::find($customerBalance->customer_id);

                $account = Account::find($customer->account_id);

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => "2025-08-31",
                    // 'date' => $requested_date,
                    'amount' => $customerBalance->amount,
                    'reference_type' => 'App\\Models\\CustomerOpeningBalance',
                    'reference_id' => $customerBalance->id,
                    'description' => 'Customer opening balance entry',
                    'account' => $account,
                    'user_id' => 1,

                ]);
            }
            $abc = 1;
        }
        dd('jj');

        // $transactions = collect();

        // $purchases = Purchase::where('deleted_at', NULL)
        //     // ->where('purchase_date', '>=', "2025-09-02")
        //     ->where('purchase_date', '<', "2025-09-02")
        //     ->where('is_accounted', 0)
        //     ->get()->map(function ($purchase) {
        //         return [
        //             'date' => $purchase->purchase_date,
        //             'type' => 'purchase',
        //             'id' => $purchase->id,
        //             'priority' => 1,
        //             'model' => $purchase,
        //         ];
        //     });

        // $transactions = $transactions->merge($purchases);



        // $sales = Order::where('order_status', 'delivered')
        //     ->where('is_returned', 0)
        //     // ->where('delivered_date', '>=', "2025-09-02")
        //     ->where('delivered_date', '<', "2025-09-02")
        //     ->where('is_outside', 0)
        //     ->where('is_accounted', 0)
        //     ->get()
        //     ->map(function ($sale) {
        //         return [
        //             'date' => $sale->delivered_date,
        //             'type' => 'sale',
        //             'id' => $sale->id,
        //             'priority' => 2,
        //             'model' => $sale,
        //         ];
        //     });
        // $transactions = $transactions->merge($sales);


        // $payments = OrderPayment::where('payment_date', '<', "2025-09-02")

        //     // ->where('payment_date', '>=', "2025-09-02")
        //     ->where('is_accounted', 0)
        //     ->get()->map(function ($payment) {
        //         return [
        //             'date' => $payment->payment_date,
        //             'type' => 'payment',
        //             'id' => $payment->id,
        //             'priority' => 3,

        //             'model' => $payment,
        //         ];
        //     });

        // $transactions = $transactions->merge($payments);


        $returns = SalesReturn::where('return_date', '<', "2025-09-03")
            ->where('return_date', '>=', "2025-09-02")
            ->where('is_accounted', 0)
            ->get()->map(function ($return) {
                return [
                    'date' => $return->return_date,
                    'type' => 'sales_return',
                    'id' => $return->id,
                    'model' => $return,
                ];
            });

        $transactions = $transactions->merge($returns);

        // $transactions = $transactions->sortBy(function ($transaction) {
        //     return $transaction['date'];
        // })->values();

        // $transactions = $transactions->sortBy([
        //     ['date', 'asc'],
        //     ['priority', 'asc'],
        //     ['id', 'asc'],
        // ])->values();


        // DB::beginTransaction();
        // try {


        //     foreach ($transactions as $transaction) {

        //         switch ($transaction['type']) {

        //             case 'purchase':

        //                 $purchase = $transaction['model'];

        //                 $supplier = Supplier::find($purchase->supplier_id);

        //                 $journalEntry = $this->journalEntryService->createPurchaseEntry([
        //                     'date' => $purchase->purchase_date,
        //                     'total_amount' => $purchase->total,
        //                     'reference_type' => 'App\\Models\\Purchase',
        //                     'reference_id' => $purchase->id,
        //                     'description' => 'Payment - ' . $purchase->purchase_no,
        //                     'supplier_id' => $supplier->id,
        //                     'supplier_name' => $supplier->supplier_name,
        //                     'supplier_code' => $supplier->supplier_code,

        //                     'user_id' => 1,
        //                 ]);


        //                 $purchase->is_accounted = 1;

        //                 $purchase->financial_year_id = $journalEntry->financial_year_id;
        //                 $purchase->save();

        //                 $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)
        //                     ->where('deleted_at', NULL)->get();

        //                 foreach ($purchaseItems as $item) {

        //                     StockOriginal::create([
        //                         'ref_id'       => $purchase->id,
        //                         'ref_no'       => $purchase->purchase_no,
        //                         'product_id'   => $item->product_id,
        //                         'quantity'     => $item->qty,
        //                         'cost_price' => $item->unit_price,
        //                         'movement_type' => 'in',
        //                         'reason'       => 'Purchase',
        //                         'date' => $purchase->purchase_date,
        //                         'created_by'   => $purchase->user_id,
        //                     ]);

        //                     $item->is_accounted = 1;
        //                     $item->save();
        //                 }
        //                 break;

        //             case 'sale':

        //                 $order = $transaction['model'];

        //                 $customer = Customer::find($order->customer_id);
        //                 $journalEntry = $this->journalEntryService->createSalesCreditEntry([
        //                     'date' => $order->delivered_date,
        //                     'amount' => $order->total_amount,
        //                     'reference_type' => 'App\\Models\\Order',
        //                     'reference_id' => $order->id,
        //                     'description' => 'Order ' . $order->order_no,
        //                     'customer_id' => $customer ? $customer->id : '',
        //                     'customer_name' => $customer ? $customer->customer_name : '',

        //                     'customer_type' => 'customer',
        //                     'user_id' => $customer ? $customer->user_id : '',

        //                     'customer_code' => $customer ? $customer->customer_code : null,
        //                 ]);

        //                 $order->is_accounted = 1;
        //                 $order->financial_year_id = $journalEntry->financial_year_id;
        //                 $order->save();
        //                 $orderItems = OrderItem::where('order_id', $order->id)
        //                     ->get();
        //                 foreach ($orderItems as $item) {

        //                     $cost_price = StockOriginal::where('movement_type', 'in')
        //                         ->where('product_id', $item->product_id)
        //                         ->avg('cost_price');

        //                     StockOriginal::create([
        //                         'ref_id'       => $order->id,
        //                         'ref_no'       => $order->order_no,
        //                         'product_id'   => $item->product_id,
        //                         'quantity'     => $item->quantity,
        //                         'cost_price' => $cost_price,
        //                         'movement_type' => 'out',
        //                         'reason'       => 'Sale',
        //                         'date' => $order->order_date,
        //                         'created_by'   => $order->user_id,
        //                     ]);
        //                     $item->is_accounted = 1;
        //                     $item->save();
        //                 }
        //                 break;

        //             case 'payment':

        //                 $payment = $transaction['model'];
        //                 $customer = Customer::find($payment->customer_id);

        //                 $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
        //                     'date' => $payment->payment_date,
        //                     'amount' => $payment->paid_amount,
        //                     'reference_type' => 'App\\Models\\OrderPayment',
        //                     'reference_id' => $payment->id,
        //                     'description' => 'Order Payment - Order ' . $payment->id,
        //                     'customer_id' => $customer ? $customer->id : '',
        //                     'customer_name' => $customer ? $customer->customer_name : '',
        //                     'payment_mode' => $payment->payment_mode,
        //                     'customer_type' => 'customer',
        //                     'user_id' => $customer ? $customer->user_id : '',

        //                     'customer_code' => $customer ? $customer->customer_code : null,
        //                 ]);
        //                 $payment->is_accounted = 1;
        //                 $payment->financial_year_id = $journalEntry->financial_year_id;
        //                 $payment->save();
        //                 break;

        //                 // case 'sales_return':

        //                 $salesReturn = $transaction['model'];
        //                 $customer = Customer::find($salesReturn->customer_id);
        //                 $payment = SalesReturnPayment::where('sales_return_id', $salesReturn->id)->first();

        //                 $journalEntry = $this->journalEntryService->createSalesReturnEntry([
        //                     'date' => $salesReturn->return_date,
        //                     'reference_type' => 'App\\Models\\SalesReturn',
        //                     'reference_id' => $salesReturn->id,
        //                     'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $salesReturn->customer->customer_name,
        //                     'amount' => $salesReturn->total_amount,
        //                     'user_id' => $salesReturn->approved_by,
        //                     'return_no' => $salesReturn->return_no ?? $salesReturn->id,
        //                     'customer_id' => $customer->id,
        //                     'customer_name' => $customer->customer_name,
        //                     'refund_mode' => $payment->refund_mode,
        //                 ]);
        //                 $salesReturn->is_accounted = 1;
        //                 $salesReturn->financial_year_id = $journalEntry->financial_year_id;
        //                 $salesReturn->save();
        //                 break;
        //         }
        //     }

        //     DB::commit();
        //     dd('finished succesfully');
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     dd('failed' . $e);
        // }
    }
}
