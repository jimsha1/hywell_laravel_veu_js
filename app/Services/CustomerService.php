<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AssignedSpecialPrice;
use App\Models\AutoIncrementMaster;
use App\Models\Customer;
use App\Models\CustomerDispenser;
use App\Models\CustomerProduct;
use App\Models\Despenser;
use App\Models\OpeningBalance;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Products;
use App\Models\SpecialPrice;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\CreditPayment;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\SalesReturn;
use App\Models\SalesReturnPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\CommissionManagement\CommissionManagementService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CustomerService extends CommissionManagementService
{

    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }
    // Customer section
    public function customer_store($data = null, $platform = 'web')
    {
        // dd($data);
        try {
            DB::beginTransaction();

            if ($data->phone_number) {
                $existingUser = User::where('phone', $data->phone_number)->first();
                if ($existingUser) {
                    return errorResponse(null, 'Customer already exists.', 100, 200);
                }
            }

            if ($data->email) {
                $existingUser = User::where('email', $data->email)->first();
                if ($existingUser) {
                    return errorResponse(null, 'Customer already exists.', 100, 200);
                }
            }

            $user = User::create([
                'name' => $data->customer_name,
                'username' => $data->customer_name,
                'email' => $data->email ?? '',
                'user_type' => 'customer',
                'phone' => $data->phone_number,
                'password' => Hash::make('123456'),
                'password_without_hash' => 123456,
            ]);

            // $user->assignRole($role->name);
            $customer = Customer::where("phone_number", $data->phone_number)->first();

            if ($customer) {
                return errorResponse(null, 'Customer already exists.', 100, 200);
            }

            $openingBalance = (float) ($data->opening_balance ?? 0);

            $requested_date = isset($data->registration_date)
                ? \Carbon\Carbon::parse($data->registration_date)->format('Y-m-d H:i:s')
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }

            $logged_in_user = User::find($data->logged_user_id);
            $logged_in_user_type = $logged_in_user->user_type;

            if ($logged_in_user_type == 'outside_salesman') {
                $is_outside = 1;
            } else {
                $is_outside = 0;
            }
            $customer = Customer::Create([
                'user_id' => $user->id,
                'customer_code' => $this->genarateCusCode(),
                'customer_name' => $data->customer_name,
                'customer_name_ar' => $data->customer_name_ar ?? '',
                'customer_type' => $data->customer_type,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'commercial_reg_no' => $data->commercial_reg_no,
                'vat_reg_no' => $data->vat_reg_no,
                'national_address_no' => $data->national_address_no,
                'country' => $data->country_name,
                'city' => $data->city,
                'account_no' => $data->account_no,
                'cr_no' => $data->cr_no,
                'postal_code' => $data->postal_code,
                'remarks' => $data->remarks,
                'address' => $data->address,
                'status' => $data->status ?? 1,
                'registration_date' => $requested_date,
                'created_by' => $data->logged_user_id,
                'balance_amount' => $openingBalance > 0 ? $openingBalance : 0,
                'employee_id' => !empty($data->employee_id) ? $data->employee_id : $data->logged_user_id,
                'is_outside' => $is_outside,
            ]);

            if ($is_outside != 1) {

                $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_CUSTOMER_LEDGER_CODE')->first();
                $next = $codeAuto->auto_increment_general_value;
                $code = 'C' . $next;
                $parent = Account::where('name', 'Accounts Receivable(Debitors)')->first();
                $account = Account::create([
                    'account_type_id' => 1,
                    'name' => $customer->customer_name . '_' . $customer->customer_code,
                    'parent_id' => $parent->id,
                    'description' => $customer->customer_name . '_' . $customer->customer_code . ' receivable',
                    'opening_balance' => 0,
                    'reference_id' => $customer->id,
                    'reference_type' => 'customer',
                    'is_active' => 1,
                    'is_system' => 1,
                    'code' => $code
                ]);
                $next = $next + 1;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();


                $customer->account_id = $account->id;
                $customer->save();

                if ($openingBalance > 0) {

                    $opening_balance = OpeningBalance::create([
                        'customer_id' => $customer->id,
                        'amount' => $openingBalance,
                        'description' => 'opening balance',
                        'date' => $requested_date,
                    ]);


                    $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                        'date' => "2025-08-31",
                        // 'date' => $requested_date,
                        'amount' => $openingBalance,
                        'reference_type' => 'App\\Models\\CustomerOpeningBalance',
                        'reference_id' => $opening_balance->id,
                        'description' => 'Customer opening balance entry',
                        'account' => $account,
                        'user_id' => $data->logged_user_id,

                    ]);

                    // $currentBalance = $account->opening_balance;
                    // $account->opening_balance = $currentBalance - $openingBalance;
                    // $account->save();
                    if (!$journalEntry) {

                        return errorResponse(null, 'Failed to create journal entry for the supplier opening balance', 500);
                    }
                }
            }

            DB::commit();

            return successResponse($customer, 'Customer created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function genarateCusCode()
    {
        $auto_cus_code = AutoIncrementMaster::where('auto_code', 'AI_CUSTOMER_CODE')->first();
        $currentValue = $auto_cus_code->auto_increment_general_value;
        $auto_cus_code->auto_increment_general_value = $currentValue + 1;
        $auto_cus_code->save();

        return $auto_cus_code->auto_prefix . $currentValue;
    }
    public function customer_update($data = null, $platform = 'web')
    {
        // dd($data);
        try {
            DB::beginTransaction();

            // Find the customer
            $customer = Customer::find($data->id);
            if (!$customer) {
                return errorResponse(null, 'Customer not found', 404, 404);
            }
            // Find the user
            $user = User::find($customer->user_id);
            if (!$user) {
                return errorResponse(null, 'Associated user not found', 404, 404);
            }

            // Update User table
            $user->update([
                'name' => $data->customer_name ?? $user->name,
                'email' => $data->email ?? $user->email,
                'user_type' => 'customer',
            ]);


            $requested_date = isset($data['registration_date'])
                ? \Carbon\Carbon::parse($data['registration_date'])->format('Y-m-d H:i:s')
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }


            $customer->update([
                'customer_name' => $data->customer_name,
                'customer_name_ar' => $data->customer_name_ar,
                'customer_type' => $data->customer_type,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'commercial_reg_no' => $data->commercial_reg_no,
                'vat_reg_no' => $data->vat_reg_no,
                'national_address_no' => $data->national_address_no,
                'country' => $data->country,
                'city' => $data->city,
                'account_no' => $data->account_no,
                'cr_no' => $data->cr_no,
                'postal_code' => $data->postal_code,
                'remarks' => $data->remarks,
                'address' => $data->address,
                'status' => $data->status,
                'registration_date' => $requested_date,
                'updated_by' => $data->logged_user_id,
                'employee_id' => !empty($data->employee_id) ? $data->employee_id : null,
            ]);
            // Update customer ledger account name
            $account = Account::where('reference_type', 'customer')
                ->where('reference_id', $customer->id)
                ->first();

            if ($account) {
                $account->update([
                    'name' => $customer->customer_name . '_' . $customer->customer_code,
                    'description' => $customer->customer_name . '_' . $customer->customer_code . ' receivable',
                ]);
            }

            $openingBalance = (float) ($data->opening_balance ?? 0);

            if ($openingBalance > 0) {
                $customer->balance_amount += $openingBalance;
                $customer->save();

                $opening_balance = OpeningBalance::create([
                    'customer_id' => $customer->id,
                    'amount' => $openingBalance,
                    'description' => 'Added opening balance',
                    'date' => $requested_date,
                ]);


                $account = Account::where('reference_type', 'customer')->where('reference_id', $data->id)->first();

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => "2025-08-31",
                    // 'date' => $requested_date,
                    'amount' => $openingBalance,
                    'reference_type' => 'App\\Models\\CustomerOpeningBalance',
                    'reference_id' => $opening_balance->id,
                    'description' => 'Customer opening balance entry',
                    'account' => $account,
                    'user_id' => $data->logged_user_id,

                ]);
                if (!$journalEntry) {

                    return errorResponse(null, 'Failed to create journal entry for the supplier opening balance', 500);
                }
                // $currentBalance = $account->opening_balance;
                // $account->opening_balance = $currentBalance - $openingBalance;
                // $account->save();
            }

            DB::commit();
            return successResponse(null, 'Customer updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function getAllCustomer($data, $platform = 'web')
    {
        try {
            $query = Customer::with('createdBy', 'open_balance', 'employee');


            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('customer_name', 'LIKE', "%{$data->search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$data->search}%")
                        ->orWhere('email', 'LIKE', "%{$data->search}%")
                        ->orWhere('customer_code', 'LIKE', "%{$data->search}%");
                });
            }
            if ($data->start_date) {
                $query->whereDate('registration_date', '>=', $data->start_date);
            }
            if ($data->end_date) {
                $query->whereDate('registration_date', '<=', $data->end_date);
            }
            if ($data->added_by) {
                $query->whereHas('createdBy', function ($q) use ($data) {
                    $q->where('name', 'LIKE', "%{$data->added_by}%");
                });
            }
            if ($data->employee_id) {
                $query->where('employee_id', $data->employee_id);
            }




            if ($platform == 'web') {

                if ($data->customer_type == 'in') {
                    $query->where('is_outside', 0);
                } else {

                    $query->where('is_outside', 1);
                }
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'customers' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Customers retrieved successfully.', 0, 200);
            } else {

                if (!empty($data->logged_user_id)) {
                    $query->where('employee_id', $data->logged_user_id);
                } else {
                    $query->where('is_outside', 0);
                }
                $result = $query->orderBy('created_at', 'DESC')->where('status', 1)->get();

                $response = [
                    'customers' => $result,
                ];

                return successResponse($response, 'Customers retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }



    public function customerDelete($data)
    {
        try {
            DB::beginTransaction();
            $customer = Customer::findOrFail($data->id);
            if ($customer) {
                $customer->deleted_by = $data->user_id;
                $customer->save();
                $customer->delete();
                DB::commit();
                return successResponse(null, 'Customer deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Customer not found', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    // Assigned Product
    public function assign_product($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $special_price = SpecialPrice::where('id', $data->special_price)->first();

            $assignedProduct = AssignedSpecialPrice::updateOrCreate(
                [
                    'id' => $data->id,
                ],
                [
                    'customer_id'   => $data->customer_id,
                    'product_id'    => $data->product_id,
                    'special_price' => $special_price->price,
                    'special_price_id' => $data->special_price,
                    'status'        => $data->special_price_status,
                    $data->id ? 'updated_by' : 'created_by' => $data->logged_user_id,
                ]
            );

            DB::commit();

            $message = $data->id ? 'Special Price Updated Successfully.' : 'Special Price Assigned Successfully.';
            $errorCode = 0;

            return $platform === 'web' ? successResponse($assignedProduct, $message, $errorCode, 200) : apiResponse($errorCode, $message, $assignedProduct);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function assignProductList($data, $platform = 'web')
    {
        // dd($data);
        try {
            $query = AssignedSpecialPrice::with('product')->where('customer_id', $data->customer_id);

            if ($data->search) {
                $query->where('customer_name', 'LIKE', "%{$data->search}%");
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'special_prices' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Assigned Special Price retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Assigned Special Price retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function assignProductDelete($id)
    {
        try {
            DB::beginTransaction();
            $assignProduct = AssignedSpecialPrice::findOrFail($id);
            if ($assignProduct) {
                $assignProduct->delete();
                DB::commit();
                return successResponse(null, 'Assign Product deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Assign Product not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function getCustomerDetails($request)
    {
        try {
            DB::beginTransaction();
            $customer = Customer::with('customerOrders', 'assignedSpecialPrice', 'assignedSpecialPrice.product')->find($request->customer_id);

            if (!$customer) {
                return errorResponse(null, "customer not found", 100, 200);
            }

            $totalAmount = $customer->customerOrders->sum('total_amount');
            $customer->total_order_amount = $totalAmount;

            return successResponse($customer, 'customer successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function customerDetailList($request)
    {
        try {
            DB::beginTransaction();
            $perPage = $request->per_page ?? 10;
            $ordersPage = $request->orders_page ?? 1;
            $specialPricePage = $request->special_price_page ?? 1;
            $paymentsPage = $request->payments_page ?? 1;

            $customer = Customer::find($request->customer_id);

            if (!$customer) {
                return errorResponse(null, "customer not found", 100, 200);
            }

            //fetch paginated customer orders
            $customerOrders = $customer->customerOrders()
                ->paginate($perPage, ['*'], 'orders_page', $ordersPage);

            //fetch paginated special prices with product
            $assignedSpecialPrice = $customer->assignedSpecialPrice()
                ->with('product')
                ->paginate($perPage, ['*'], 'special_price_page', $specialPricePage);

            //fetch paginated payment details
            $customerPayments = OrderPayment::where('customer_id', $customer->id)
                ->with(['user', 'order'])
                ->where('payment_mode', '!=', 'credit')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'payments_page', $paymentsPage);

            $totalAmount = $customer->customerOrders()->sum('total_amount');
            $customer->total_order_amount = $totalAmount;

            $responseData = [
                'customer' => $customer,
                'customer_orders' => $customerOrders->items(),
                'assigned_special_price' => $assignedSpecialPrice->items(),
                'customer_payments' => $customerPayments->items(),
                'orders_pagination' => [
                    'current_page' => $customerOrders->currentPage(),
                    'last_page' => $customerOrders->lastPage(),
                    'per_page' => $customerOrders->perPage(),
                    'total' => $customerOrders->total(),
                ],
                'special_price_pagination' => [
                    'current_page' => $assignedSpecialPrice->currentPage(),
                    'last_page' => $assignedSpecialPrice->lastPage(),
                    'per_page' => $assignedSpecialPrice->perPage(),
                    'total' => $assignedSpecialPrice->total(),
                ],
                'payments_pagination' => [
                    'current_page' => $customerPayments->currentPage(),
                    'last_page' => $customerPayments->lastPage(),
                    'per_page' => $customerPayments->perPage(),
                    'total' => $customerPayments->total(),
                ]
            ];

            return successResponse($responseData, 'customer successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function payCreditBalance($request)
    {
        try {
            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            $description = $request['description'] ?? null;
            $data = $this->applyExtraPaymentToPreviousOrdersSinglePayment($request['customer_id'], $request['user_id'], $request['amount'], $request, $requested_date, $description);

            return successResponse(null, 'credit payed successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function payCreditBalanceMultiplePayment($request)
    {

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $userId = $request->user_id;
            $paymentModes = $request->payment_modes ?? [];

            if (empty($paymentModes)) {
                DB::rollBack();
                return errorResponse(null, 'Payment modes are required.', 100, 400);
            }

            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }

            // Format payment breakdown for applyExtraPaymentToPreviousOrders
            $paymentBreakdown = [];
            foreach ($paymentModes as $payment) {
                $paymentMode = $payment['payment_mode'] ?? '';
                $amount = (float)($payment['amount'] ?? 0);

                if (strtolower($paymentMode) === 'credit') {
                    continue;
                }

                if (!empty($paymentMode) && $amount > 0) {
                    $paymentBreakdown[] = [
                        'payment_mode' => $paymentMode,
                        'amount' => $amount
                    ];

                    if (empty($paymentBreakdown)) {
                        DB::rollBack();
                        return errorResponse(null, 'No valid payment amount provided.', 100, 400);
                    }
                    // $this->journalEntryService->createSalesPaymentEntry([
                    //     'date' => $requested_date ?? now()->toDateString(),
                    //     'amount' => $amount,
                    //     'reference_type' => 'App\\Models\\CustomerCreditPayment',
                    //     'reference_id' => $customer->id,
                    //     'customer_id' => $customer->id,
                    //     'description' => 'Credit payment received from customer',
                    //     'customer_name' => $customer->customer_name ?? '',
                    //     'payment_mode' => $paymentMode,
                    //     'user_id' => $userId ?? 1,
                    // ]);
                }
            }

            // Apply payments to previous orders
            $description = $request->description ?? null;
            $this->applyExtraPaymentToPreviousOrders($customer->id, $userId, $paymentBreakdown, $requested_date, $description);

            // Refresh customer to get updated balance
            $customer->refresh();
            Log::info('CONTROLLER RESPONSE', [
                'customer_id' => $customer->id,
                'balance' => $customer->balance_amount
            ]);



            DB::commit();

            return successResponse([
                'customer_id' => $customer->id,
                'remaining_balance' => $customer->balance_amount
            ], 'Credit payment processed successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getCustomerTransactionLedger($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            // Get all orders
            $orders = Order::where('customer_id', $customerId)
                ->whereIn('order_status', ['requested', 'delivered'])
                ->get();

            // Get all payments (excluding credit)
            $payments = OrderPayment::where('customer_id', $customerId)
                ->where(function ($q) {
                    $q->where('payment_mode', '!=', 'credit')
                        ->orWhereNull('payment_mode');
                })
                ->whereIn('type', ['order', 'opening_balance'])
                ->with(['order', 'user'])
                ->orderBy('payment_date', 'asc')
                ->get();

            // Get all credit payments
            // $creditPayments = CreditPayment::where('customer_id', $customerId)
            //     ->orderBy('created_at', 'asc')
            //     ->get();

            // Combine all transactions into a single array
            $transactions = [];
            $runningBalance = 0;

            // Add opening balance (if exists)
            $openingBalance = OpeningBalance::where('customer_id', $customerId)->first();
            if ($openingBalance && $openingBalance->amount != 0) {
                $transactions[] = [
                    'date' => $openingBalance->date ?? now(),
                    'id' => '',
                    'type' => 'Opening Balance',
                    'debit' => $openingBalance->amount > 0 ? number_format($openingBalance->amount, 2) : '0.00',
                    'credit' => $openingBalance->amount < 0 ? number_format(abs($openingBalance->amount), 2) : '0.00',
                    'balance' => number_format($openingBalance->amount, 2),
                    'description' => 'Opening Balance',
                    'sort_date' => $openingBalance->date ?? now(),
                ];
                $runningBalance = $openingBalance->amount;
            }

            // Add orders (Sales Bill) - Debit
            foreach ($orders as $order) {
                $transactions[] = [
                    'date' => $order->order_date,
                    'id' => $order->id,
                    'type' => 'Sales Bill',
                    'debit' => number_format($order->total_amount ?? 0, 2),
                    'credit' => '0.00',
                    'balance' => number_format($runningBalance + ($order->total_amount ?? 0), 2),
                    'description' => 'Sale Bill # ' . $order->order_no,
                    'sort_date' => $order->order_date,
                ];
                $runningBalance += ($order->total_amount ?? 0);
            }

            // Add payments (Receipts/Incoming Payments) - Credit
            foreach ($payments as $payment) {
                $orderNo = $payment->order ? $payment->order->order_no : '';
                $description = 'Payment';

                if ($orderNo) {
                    // Check if this is final payment by comparing with order balance
                    // if ($payment->order && $payment->order->payment_status === 'Paid') {
                    //     $description = 'Final Payment Sale Bill # ' . $orderNo;
                    // } else {
                    //     $description = 'Partial Payment Sale Bill # ' . $orderNo;
                    // }
                    if ($payment->balance_amount == 0) {
                        $description = 'Final Payment Sale Bill # ' . $orderNo;
                    } else {
                        $description = 'Partial Payment Sale Bill # ' . $orderNo;
                    }
                } else {
                    // Check if this is opening balance payment
                    if ($payment->type === 'opening_balance') {
                        $description = 'Opening Balance Payment';
                    }
                }

                $transactions[] = [
                    'date' => $payment->payment_date,
                    'id' => $payment->id,
                    'type' => 'Receipts/Incoming Payments',
                    'debit' => '0.00',
                    'credit' => number_format($payment->paid_amount ?? 0, 2),
                    'balance' => number_format($runningBalance - ($payment->paid_amount ?? 0), 2),
                    'description' => $description,
                    'sort_date' => $payment->payment_date,
                ];
                $runningBalance -= ($payment->paid_amount ?? 0);
            }

            // Add credit payments - Credit
            // foreach ($creditPayments as $creditPayment) {
            //     $order = null;
            //     if ($creditPayment->order_id) {
            //         $order = Order::find($creditPayment->order_id);
            //     }
            //     $orderNo = $order ? $order->order_no : '';
            //     $transactions[] = [
            //         'date' => $creditPayment->created_at,
            //         'id' => $creditPayment->id,
            //         'type' => 'Credit Payment',
            //         'debit' => '0.00',
            //         'credit' => number_format($creditPayment->amount ?? 0, 2),
            //         'balance' => number_format($runningBalance - ($creditPayment->amount ?? 0), 2),
            //         'description' => $orderNo ? 'Credit Payment Sale Bill # ' . $orderNo : 'Credit Payment',
            //         'sort_date' => $creditPayment->created_at,
            //     ];
            //     $runningBalance -= ($creditPayment->amount ?? 0);
            // }

            // Get approved sales returns
            $approvedSalesReturns = SalesReturn::where('customer_id', $customerId)
                ->where('return_status', 'approved')
                ->with('order')
                ->get();

            // Get sales return payments for approved returns
            $salesReturnPayments = SalesReturnPayment::whereIn('sales_return_id', $approvedSalesReturns->pluck('id'))
                ->orderBy('refund_date', 'asc')
                ->get();

            // Add sales return payments (Refunds) - Credit
            foreach ($salesReturnPayments as $payment) {
                $salesReturn = $approvedSalesReturns->firstWhere('id', $payment->sales_return_id);
                $returnNo = $salesReturn ? ($salesReturn->return_no ?? 'SR-' . $salesReturn->id) : 'N/A';
                $orderNo = $salesReturn && $salesReturn->order ? $salesReturn->order->order_no : '';

                // Format refund mode for display
                $refundModeRaw = $payment->refund_mode ?? 'cash';
                $refundMode = 'Cash'; // Default

                if ($refundModeRaw === 'order_balance') {
                    $refundMode = 'Order Balance';
                } elseif ($refundModeRaw === 'cash' || $refundModeRaw == config('constants.CASH')) {
                    $refundMode = 'Cash';
                } elseif ($refundModeRaw === 'online' || $refundModeRaw == config('constants.ONLINE')) {
                    $refundMode = 'Online';
                } elseif ($refundModeRaw == config('constants.BANK_TRANSFER_ALRAJHI')) {
                    $refundMode = 'Bank Transfer Alrajhi';
                } elseif ($refundModeRaw == config('constants.BANK_TRANSFER_SAB')) {
                    $refundMode = 'Bank Transfer SAB';
                } elseif ($refundModeRaw == config('constants.POS_ALRAJHIS')) {
                    $refundMode = 'POS Alrajhis';
                } elseif ($refundModeRaw == config('constants.POS_SAB')) {
                    $refundMode = 'POS SAB';
                } else {
                    // Fallback: format the raw value
                    $refundMode = ucwords(str_replace('_', ' ', strtolower($refundModeRaw)));
                }

                $description = 'Sales Return Refund - ' . $returnNo;
                if ($orderNo) {
                    $description .= ' (Order: ' . $orderNo . ')';
                }
                $description .= ' - ' . $refundMode;



                // Only reduce balance if refund mode is 'order_balance'
                $isOrderBalance = ($refundModeRaw === 'order_balance');

                if ($isOrderBalance) {
                    $runningBalance -= ($payment->refund_amount ?? 0);
                }

                $transactions[] = [
                    'date' => $payment->refund_date ?? $payment->created_at,
                    'id' => $payment->id,
                    'type' => 'Sales Return Refund',
                    'debit' => '0.00',
                    'credit' => number_format($payment->refund_amount ?? 0, 2),
                    'balance' => number_format($runningBalance, 2),
                    'description' => $description,
                    'sort_date' => $payment->refund_date ?? $payment->created_at,
                    'is_order_balance' => $isOrderBalance, // Flag to track if this affects balance
                ];
            }

            // Sort all transactions by date
            usort($transactions, function ($a, $b) {
                return $a['sort_date'] <=> $b['sort_date'];
            });

            // Recalculate running balance in correct order
            $finalBalance = 0;
            if ($openingBalance) {
                $finalBalance = $openingBalance->amount;
            }

            $sales_return = SalesReturn::where('customer_id', $customerId)->where('return_status', 'approved')->get();

            foreach ($transactions as &$transaction) {
                if ($transaction['type'] === 'Opening Balance') {
                    $finalBalance = str_replace(',', '', $transaction['balance']);
                } elseif ($transaction['type'] === 'Sales Bill') {
                    $finalBalance += floatval(str_replace(',', '', $transaction['debit']));
                    $transaction['balance'] = number_format($finalBalance, 2);
                } elseif ($transaction['type'] === 'Receipts/Incoming Payments' || $transaction['type'] === 'Credit Payment') {
                    $finalBalance -= floatval(str_replace(',', '', $transaction['credit']));
                    $transaction['balance'] = number_format($finalBalance, 2);
                } elseif ($transaction['type'] === 'Sales Return Refund') {
                    // Only reduce balance if refund mode is 'order_balance'
                    if (isset($transaction['is_order_balance']) && $transaction['is_order_balance'] === true) {
                        $finalBalance -= floatval(str_replace(',', '', $transaction['credit']));
                    }
                    $transaction['balance'] = number_format($finalBalance, 2);
                }
            }



            // Calculate totals
            $currentOpeningBalance = $openingBalance->amount ?? 0;
            $totalOrderAmount = $orders->sum('total_amount') ?? 0;
            $totalPaidAmount = $payments->sum('paid_amount') ?? 0; // Only actual payments, not credit
            $totalSalesReturnAmount = $sales_return->sum('total_amount') ?? 0;

            // Calculate total order_balance refunds only (these affect customer balance)
            $totalOrderBalanceRefunds = SalesReturnPayment::whereIn('sales_return_id', $approvedSalesReturns->pluck('id'))
                ->where('refund_mode', 'order_balance')
                ->sum('refund_amount') ?? 0;

            $balanceAmount = $currentOpeningBalance + $totalOrderAmount - $totalPaidAmount - $totalOrderBalanceRefunds;

            return [
                'customer' => $customer,
                'transactions' => $transactions,
                'opening_balance' => number_format($currentOpeningBalance, 2),
                'final_balance' => number_format($finalBalance, 2),
                'total_order_amount' => number_format($totalOrderAmount, 2),
                'total_paid_amount' => number_format($totalPaidAmount, 2),
                'total_sales_return_amount' => number_format($totalSalesReturnAmount, 2),
                'balance_amount' => number_format($balanceAmount, 2),
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function generateCustomerTransactionPDF($customerId)
    {
        try {
            $data = $this->getCustomerTransactionLedger($customerId);

            $file_name = 'customer_transaction_ledger_' . $data['customer']->customer_code . '_' . date('Y_m_d_H_i_s') . '.pdf';

            $pdf = Pdf::loadView(
                'admin.pages.customer.pdf.customer_transaction_ledger',
                [
                    'customer' => $data['customer'],
                    'transactions' => $data['transactions'],
                    'opening_balance' => $data['opening_balance'],
                    'final_balance' => $data['final_balance'],
                    'total_order_amount' => $data['total_order_amount'],
                    'total_paid_amount' => $data['total_paid_amount'],
                    'total_sales_return_amount' => $data['total_sales_return_amount'],
                    'balance_amount' => $data['balance_amount'],
                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function generateCustomerListPDF($request)
    {
        try {
            $query = Customer::with('createdBy', 'open_balance', 'employee');

            $query = $this->applyCustomerFilters($query, $request);

            $customers = $query->orderBy('created_at', 'DESC')->limit(300)->get();

            $file_name = 'customer_list_' . date('Y_m_d_H_i_s') . '.pdf';

            // Get employee name if filter is applied
            $employeeName = '';
            if ($request->employee_id) {
                $employee = User::find($request->employee_id);
                $employeeName = $employee ? $employee->name : '';
            }

            $pdf = Pdf::loadView(
                'admin.pages.customer.pdf.customer_list',
                [
                    'customers' => $customers,
                    'filters' => [
                        'search' => $request->search ?? '',
                        'start_date' => $request->start_date ?? '',
                        'end_date' => $request->end_date ?? '',
                        'added_by' => $request->added_by ?? '',
                        'employee_id' => $request->employee_id ?? '',
                        'employee_name' => $employeeName,
                    ],
                ]
            );

            return $pdf->download($file_name);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function applyCustomerFilters($query, $request)
    {
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', "%{$request->search}%")
                    ->orWhere('phone_number', 'LIKE', "%{$request->search}%")
                    ->orWhere('email', 'LIKE', "%{$request->search}%")
                    ->orWhere('customer_code', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->start_date) {
            $query->whereDate('registration_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('registration_date', '<=', $request->end_date);
        }

        if ($request->added_by) {
            $query->whereHas('createdBy', function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->added_by}%");
            });
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        return $query;
    }


    public function outsideSalesmanList($data, $platform = 'web')
    {
        try {
            $query = User::where('user_type', 'outside_salesman');

            if ($platform == 'web') {

                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'salesmen' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Salesmen retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->where('status', 1)->get();

                $response = [
                    'salesmen' => $result,
                ];

                return successResponse($response, 'Salesmen retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function getOutsideSalesmanBalance($data)
    {



        $employee = Employee::where('user_id', $data['selectedSalesmanId'])->first();


        $balanceAmount = (float) $employee->balance_amount;


        return successResponse($balanceAmount, 'Salesmen balance retrieved successfully.', 0, 200);
    }

    public function payOutsideCreditBalanceMultiplePayment($request)
    {
        try {
            DB::beginTransaction();

            // $salesman = Employee::where('user_id',$request->salesman_id)->first();

            $salesman = User::find($request->salesman_id);

            $balance_amount = Order::where('order_type', 'Outside Sale')->where('customer_id', $salesman->id)->sum('balance_amount');

            $userId = $request->user_id;
            $paymentModes = $request->payment_modes ?? [];

            if (empty($paymentModes)) {
                DB::rollBack();
                return errorResponse(null, 'Payment modes are required.', 100, 400);
            }

            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            // Format payment breakdown for applyExtraPaymentToPreviousOrders
            $paymentBreakdown = [];
            foreach ($paymentModes as $payment) {
                $paymentMode = $payment['payment_mode'] ?? '';
                $amount = (float)($payment['amount'] ?? 0);

                if (strtolower($paymentMode) === 'credit') {
                    continue;
                }

                if (!empty($paymentMode) && $amount > 0) {
                    $paymentBreakdown[] = [
                        'payment_mode' => $paymentMode,
                        'amount' => $amount
                    ];

                    if (empty($paymentBreakdown)) {
                        DB::rollBack();
                        return errorResponse(null, 'No valid payment amount provided.', 100, 400);
                    }
                }
            }

            // Apply payments to previous orders
            $description = $request->description ?? null;
            $this->applyPaidAmountToPrevOrders($salesman->id, $userId, $paymentBreakdown, $requested_date, $description);

            // Refresh salesman to get updated balance
            $salesman->refresh();
            Log::info('CONTROLLER RESPONSE', [
                'salesman_id' => $salesman->id,
                'balance' => $balance_amount
            ]);



            DB::commit();

            return successResponse([
                'salesman_id' => $salesman->id,
                'remaining_balance' => $balance_amount
            ], 'Credit payment processed successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    protected function applyPaidAmountToPrevOrders($salesmanId, $userId, $paymentBreakdown, $date = null, $description = null)
    {

        try {

            Log::info('========== CREDIT PAYMENT START ==========');

            Log::info('REQUEST DATA', [
                'customer_id' => $salesmanId,
                'user_id' => $userId,
                'payment_breakdown' => $paymentBreakdown,
                'date' => $date,
                'description' => $description
            ]);

            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            $salesman = User::find($salesmanId);
            $balance_amount = Order::where('order_type', 'Outside Sale')->where('customer_id', $salesmanId)->sum('balance_amount');

            Log::info('CUSTOMER BEFORE PAYMENT', [
                'customer_id' => $salesman?->id,
                'customer_name' => $salesman?->name,
                'balance_amount' => $balance_amount
            ]);


            // Get previous unpaid orders
            $previousOrders = Order::with('orderItems')
                ->where('customer_id', $salesmanId)
                ->where('order_type', 'Outside Sale')
                ->whereRaw('LOWER(payment_status) != ?', ['paid'])
                ->where('order_status', 'delivered')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($previousOrders as $prevOrder) {
                if (empty($paymentBreakdown)) break;

                // $remaining = $prevOrder->total_amount - $prevOrder->paid_amount;

                $remaining = $prevOrder->balance_amount;

                if ($remaining <= 0) continue;

                // Apply each payment mode proportionally
                foreach ($paymentBreakdown as $key => &$payment) {

                    if ($payment['amount'] <= 0) continue;

                    $applied = min($payment['amount'], $remaining);

                    $prevOrder->update([
                        'paid_amount' => $prevOrder->paid_amount + $applied,
                        'balance_amount' => $remaining - $applied,
                        'payment_status' => ($remaining - $applied) <= 0 ? "Paid" : "Partial",
                    ]);

                    // Save with correct payment mode
                    $orderPayment = OrderPayment::create([
                        'order_id' => $prevOrder->id,
                        'customer_id' => $salesmanId,
                        'user_id' => $userId ?? 1,
                        'total_amount' => $prevOrder->total_amount,
                        'paid_amount' => $applied,
                        'balance_amount' => $prevOrder->balance_amount,
                        'payment_mode' => $payment['payment_mode'], // Correct mode
                        'payment_date' => $date ?? now(),
                        'description' => $description,
                        'financial_year_id'  => $financialYear->id,
                    ]);

                    $salesman = User::find($salesmanId);

                    // Create journal entry for credit payment received
                    $jo = $this->journalEntryService->createSalesPaymentEntry([
                        'date' => $date ?? now()->toDateString(),
                        'amount' => $applied,
                        'reference_type' => 'App\\Models\\OrderPayment',
                        'reference_id' => $orderPayment->id,
                        'customer_id' => $salesmanId,
                        'customer_type' => 'outside',
                        'description' => 'Credit payment - Order ' . $prevOrder->order_no,
                        'customer_name' => $salesman->name ?? '',
                        'payment_mode' => $payment['payment_mode'],
                        'user_id' => $userId ?? 1,
                    ]);
                    if (!$jo) {
                        return errorResponse(null, 'Failed to create journal entry', 500);
                    }

                    $employee = Employee::where('user_id', $salesmanId)->first();
                   
                    $employee->balance_amount -= $applied;
                    $employee->save();

                    // Reduce from this payment mode
                    $payment['amount'] -= $applied;
                    $remaining -= $applied;

                    if ($remaining <= 0) break;
                }
              
                // Remove exhausted payment modes
                $paymentBreakdown = array_filter($paymentBreakdown, function ($p) {
                    return $p['amount'] > 0;
                });
                $paymentBreakdown = array_values($paymentBreakdown); // reindex

            }


            // // Handle leftover amount
            // if (!empty($paymentBreakdown)) {

            //     $salesman = User::find($salesmanId);

            //     $balance_amount = Order::where('order_type', 'Outside Sale')->where('customer_id', $salesmanId)->sum('balance_amount');

            //     foreach ($paymentBreakdown as $payment) {
            //         if ($payment['amount'] > 0) {
            //             $orderPayment = OrderPayment::create([
            //                 'customer_id' => $salesmanId,
            //                 'user_id' => $userId ?? 1,
            //                 'total_amount' => $balance_amount,
            //                 'paid_amount' => $payment['amount'],
            //                 'balance_amount' => $balance_amount - $payment['amount'],
            //                 'type' => "extra_payment",
            //                 'payment_mode' => $payment['payment_mode'],
            //                 'payment_date' => $date ?? now(),
            //                 'description' => $description,
            //             ]);

            //             // Create journal entry for extra payment
            //             $jo = $this->journalEntryService->createSalesPaymentEntry([
            //                 'date' => $date ?? now()->toDateString(),
            //                 'amount' => $payment['amount'],
            //                 'reference_type' => 'App\\Models\\OrderPayment',
            //                 'reference_id' => $orderPayment->id,
            //                 'description' => 'Extra payment from customer',
            //                 'customer_id' => $salesmanId,
            //                 'customer_name' => $salesman->name ?? '',
            //                 'customer_type' => 'outside',
            //                 'payment_mode' => $payment['payment_mode'],
            //                 'user_id' => $userId ?? 1,
            //             ]);
            //             if (!$jo) {
            //                 return errorResponse(null, 'Failed to create journal entry', 500);
            //             }
            //             // $salesman->balance_amount -= $payment['amount'];
            //             Log::info('EXTRA PAYMENT APPLIED', [
            //                 'payment_mode' => $payment['payment_mode'],
            //                 'amount' => $payment['amount'],
            //                 'balance_after' => $balance_amount
            //             ]);
            //         }
            //     }
            //     // $salesman->save();
            //     // $salesman->refresh();

            //     Log::info('SALESMAN AFTER PAYMENT', [
            //         'salesman_id' => $salesman->id,
            //         'final_balance' => $balance_amount
            //     ]);

            //     Log::info('========== CREDIT PAYMENT END ==========');
            // }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
