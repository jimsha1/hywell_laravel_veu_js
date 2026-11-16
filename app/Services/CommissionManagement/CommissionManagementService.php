<?php

namespace App\Services\CommissionManagement;

use App\Models\AssignedSpecialPrice;
use App\Models\CommisionTypeMaster;
use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\CommissionTypeItem;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\Incentive;
use App\Models\OpeningBalance;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\JournalEntryService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


/**
 * Service responsible for managing commission calculation and adjustments
 * for Sales Executives and Salesmen based on payments received.
 */

class CommissionManagementService
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }

    /**
     *  ------------------- Start ------------------------------
     *  This Manage the order payments and commissions
     *  ------------------- Start ------------------------------
     */




    // calculate commission, both paid and partial payment

    protected function handleSalesExecutiveCommission($order, $request, $date = null)
    {


        try {
            $orderItems = $order->orderItems;

            foreach ($orderItems as $item) {

                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($request['customer_id'], $item, 'executive');

                $commissionRate = $this->getCommissionRate($order, $item, 'executive');

                //  Base commission amount (full order)
                $commissionAmount = $item->quantity * $commissionRate;

                //  If order is partial, recalculate proportionally
                if (strtolower($order->payment_status) === 'partial' && $order->total_amount > 0) {
                    $paidRatio = $order->paid_amount / $order->total_amount; // e.g., 50 / 100 = 0.5
                    $commissionAmount = $commissionAmount * $paidRatio;
                }

                $this->saveDataToCommissionMaster($item, $commissionAmount, $request['user_id'], $order, $date);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // this commission for sales man, when sales executive take order
    protected function handleDeliveryVehicleCommission($order, $request, $date = null)
    {
        try {
            $orderItems = $order->orderItems;
            foreach ($orderItems as $item) {

                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($order->customer_id, $item, 'delivery_vehicle');

                $commissionRate = $this->getCommissionRate($order, $item, 'delivery_vehicle');

                //  Base commission amount (full order)
                $commissionAmount = $item->quantity * $commissionRate;

                //  If order is partial, recalculate proportionally
                // if (strtolower($order->payment_status) === 'partial' && $order->total_amount > 0) {
                //     $paidRatio = $order->paid_amount / $order->total_amount; // e.g., 50 / 100 = 0.5
                //     $commissionAmount = $commissionAmount * $paidRatio;
                // }

                Log::channel('commission_history')->info("------------ handleDeliveryVehicleCommission ---------------");

                Log::channel('commission_history')->info("Commission Details", [
                    'order_id' => $order->id ?? null,
                    'product_id' => $item->product_id,
                    'commission_amount' => $commissionAmount
                ]);


                $this->saveDataToCommissionMaster($item, $commissionAmount, $request['sales_man_user_id'], $order, $date);
            }
        } catch (Exception $e) {
            Log::channel('commission_history')->info("------------ handleDeliveryVehicleCommission ---------------", [
                'error' => $e->getMessage()
            ]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // this Commission for the Sales Executive when a customer settles pending (credit) payments
    protected function handlePendingPaymentCommission($order, $request, $date = null)
    {
        try {
            $orderItems = $order->orderItems;
            foreach ($orderItems as $item) {


                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($order->customer_id, $item, 'executive');

                $commissionRate = $this->getCommissionRate($order, $item, 'executive');

                //  Base commission amount (full order)
                $commissionAmount = $item->quantity * $commissionRate;

                //  If order is partial, recalculate proportionally
                if (strtolower($order->payment_status) === 'partial' && $order->total_amount > 0) {
                    $paidRatio = $request['payment_amount'] / $order->total_amount; // e.g., 50 / 100 = 0.5
                    $commissionAmount = $commissionAmount * $paidRatio;
                }


                $this->saveDataToCommissionMaster($item, $commissionAmount, $order->order_by, $order, $date);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // this commission for sales man, who take order and delivery
    protected function handleSalesManCommission($order, $request, $date = null)
    {

        try {
            // managing the commission
            $orderItems = $order->orderItems;
            foreach ($orderItems as $item) {

                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($order->customer_id, $item, 'salesman');
                Log::channel('commission_history')->info("------------ to getCommissionRate---------------");

                $commissionRate = $this->getCommissionRate($order, $item, 'salesman');

                //  Base commission amount (full order)
                $commissionAmount = $item->quantity * $commissionRate;


                //  If order is partial, recalculate proportionally
                if (strtolower($order->payment_status) === 'partial' && $order->total_amount > 0) {
                    $paidRatio = $order->paid_amount / $order->total_amount; // e.g., 50 / 100 = 0.5
                    $commissionAmount = $commissionAmount * $paidRatio;
                }
                Log::channel('commission_history')->info(
                    "------------ from getCommissionRate---------------",
                    [
                        'order_id' => $order->id ?? null,
                        'product_id' => $item->product_id,

                        'amount' => $commissionAmount,

                    ]
                );

                $this->saveDataToCommissionMaster($item, $commissionAmount, $request['sales_man_user_id'], $order, $date);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    //

    /**
     *  ------------------- End ------------------------------
     *  Manage the order payments and commissions
     *  ------------------- End ------------------------------
     */


    /**
     *  ------------------- Start ------------------------------
     *  This Manage the previous order payments and commissions
     *  ------------------- Start ------------------------------
     */

    //


    // Apply extra payment (amount paid more than current order) , this pay previous order payments, credit payment
    protected function applyExtraPaymentToPreviousOrdersSinglePayment($customerId, $userId, $extraPaid, $paymentMode, $date = null, $description = null)
    {

        try {


            // DB::beginTransaction();
            // if customer have opening balance, pay that first
            $openeing_balance = 0;
            $openeing_total_amount = OpeningBalance::where('customer_id', $customerId)
                ->sum('amount');

            $openeing_paid_amount = OpeningBalance::where('customer_id', $customerId)
                ->sum('paid_amount');


            $openeing_balance = $openeing_total_amount - $openeing_paid_amount;

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
            if ($openeing_balance > 0) {


                if ($extraPaid <= 0) {
                    return;
                }

                $applied = min($extraPaid, $openeing_balance);

                // Update Opening Balance
                OpeningBalance::where('customer_id', $customerId)
                    ->increment('paid_amount', $applied);


                $customer = Customer::find($customerId);
                if ($customer->is_outside == 0) {
                    $customer_type = 'inside';
                } else {
                    $customer_type = 'outside';
                }
                $orderPayment = OrderPayment::create([
                    // 'order_id' => $prevOrder->id,
                    'customer_id' => $customerId,
                    'user_id' => $userId,
                    'total_amount' => $customer->balance_amount,
                    'paid_amount' => $applied,
                    'balance_amount' => $customer->balance_amount - $applied,
                    'type' => "opening_balance",
                    'payment_mode' => $paymentMode->payment_mode ?? "Cash", // or "Adjustment"
                    'payment_date' => $date ?? now(),
                    'description' => $description,
                    'financial_year_id'  => $financialYear->id,
                ]);

                if ($customer_type == 'inside') {
                    // Create journal entry for opening balance payment
                    // $ret = $this->journalEntryService->createSalesPaymentEntry([
                    //     'date' => $date ?? now()->toDateString(),
                    //     'amount' => $applied,
                    //     'reference_type' => 'App\\Models\\OrderPayment',
                    //     'reference_id' => $orderPayment->id,
                    //     'description' => 'Opening balance payment from customer',
                    //     'customer_id' => $customerId,
                    //     'customer_type' => $customer_type,
                    //     'customer_name' => $customer->customer_name ?? '',
                    //     'payment_mode' => $paymentMode->payment_mode ?? "Cash",
                    //     'user_id' => $userId,
                    // ]);

                    // if (!$ret) {
                    //     return errorResponse(null, 'Failed to create journal entry', 500);
                    // }
                }
                $customer->balance_amount -= $applied;
                $customer->save();


                // Deduct from extraPaid
                $extraPaid -= $applied;
            }


            if ($extraPaid <= 0) {

                return;
            }


            // Get previous unpaid orders of customer (excluding current order)

            $previousOrders = Order::with('orderItems')
                ->where('customer_id', $customerId)
                ->whereRaw('LOWER(payment_status) != ?', ['paid'])
                ->where('order_status', 'delivered')
                ->orderBy('created_at', 'asc')
                ->get();
            Log::info('PREVIOUS ORDERS COUNT', [
                'count' => $previousOrders->count()
            ]);

            foreach ($previousOrders as $prevOrder) {
                if ($extraPaid <= 0) break;

                // $remaining = $prevOrder->total_amount - $prevOrder->paid_amount;
                $remaining = $prevOrder->balance_amount;

                if ($remaining <= 0) continue; // already settled

                // Payment applied to this order,
                $applied = min($extraPaid, $remaining);


                $prevOrder->update([
                    'paid_amount' => $prevOrder->paid_amount + $applied,
                    'balance_amount' => $remaining - $applied,
                    'payment_status' => ($remaining - $applied) <= 0 ? "Paid" : "Partial",
                ]);

                // Save applied payment record
                $orderPayment = OrderPayment::create([
                    'order_id' => $prevOrder->id,
                    'customer_id' => $customerId,
                    'user_id' => $userId,
                    'total_amount' => $prevOrder->total_amount,
                    'paid_amount' => $applied,
                    'balance_amount' => $prevOrder->balance_amount,
                    'payment_mode' => $paymentMode->payment_mode ?? "Cash", // or "Adjustment"
                    'payment_date' => $date ?? now(),
                    'description' => $description,
                    'financial_year_id'  => $financialYear->id,
                ]);

                $customer = Customer::find($customerId);
                $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';

                if ($customer_type == 'inside') {
                    // Create journal entry for credit payment received
                    // $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
                    //     'date' => $date ?? now()->toDateString(),
                    //     'amount' => $applied,
                    //     'reference_type' => 'App\\Models\\OrderPayment',
                    //     'reference_id' => $orderPayment->id,
                    //     'description' => 'Credit payment - Order ' . $prevOrder->order_no,
                    //     'customer_id' => $customerId,
                    //     'customer_name' => $customer->customer_name ?? '',
                    //     'customer_type' => $customer_type,
                    //     'payment_mode' => $paymentMode->payment_mode ?? "Cash",
                    //     'user_id' => $userId,
                    // ]);
                    // if (!$journalEntry) {
                    //     return errorResponse(null, 'Failed to create journal entry', 500);
                    // }
                }
                $customer->balance_amount -= $applied;
                $customer->save();


                // Calculate commission for this previous order

                if ($prevOrder->order_by === $prevOrder->delivered_by) {
                    // if the sales man take order and delivery , then calculate the commission
                    $this->handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customerId, $date);
                } else {
                    // if the sales executive take order and sales man delivery , then calculate the commission
                    $this->handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customerId, $date);
                }


                // Deduct from extraPaid
                $extraPaid -= $applied;
            }


            // If still leftover, update customer's balance
            if ($extraPaid > 0) {

                $customer = Customer::find($customerId);
                $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';
                // OrderPayment::create([
                //     // 'order_id' => $prevOrder->id,
                //     'customer_id' => $customerId,
                //     'user_id' => $userId,
                //     'total_amount' => $customer->balance_amount,
                //     'paid_amount' => $extraPaid,
                //     'balance_amount' => $customer->balance_amount - $extraPaid,
                //     'type' => "opening_balance",
                //     'payment_mode' => $paymentMode->payment_mode ?? "Cash", // or "Adjustment"
                //     'payment_date' => $date ?? now(),
                //     'description' => $description,
                // ]);

                $orderPayment = OrderPayment::create([
                    // 'order_id' => $prevOrder->id,
                    'customer_id' => $customerId,
                    'user_id' => $userId,
                    'total_amount' => $customer->balance_amount,
                    'paid_amount' => $extraPaid,
                    'balance_amount' => $customer->balance_amount - $extraPaid,
                    'type' => "extra_payment",
                    'payment_mode' => $paymentMode->payment_mode ?? "Cash", // or "Adjustment"
                    'payment_date' => $date ?? now(),
                    'description' => $description,
                    'financial_year_id'  => $financialYear->id,
                ]);
                if ($customer_type == 'inside') {
                    // Create journal entry for extra payment
                    // $jo = $this->journalEntryService->createSalesPaymentEntry([
                    //     'date' => $date ?? now()->toDateString(),
                    //     'amount' => $extraPaid,
                    //     'reference_type' => 'App\\Models\\OrderPayment',
                    //     'reference_id' => $orderPayment->id,
                    //     'customer_id' => $customerId,
                    //     'description' => 'Extra payment from customer',
                    //     'customer_name' => $customer->customer_name ?? '',
                    //     'payment_mode' => $paymentMode->payment_mode ?? "Cash",
                    //     'user_id' => $userId,
                    //     'customer_type' => 'customer',
                    // ]);
                    // if (!$jo) {
                    //     return errorResponse(null, 'Failed to create journal entry', 500);
                    // }
                }
                $customer->balance_amount -= $extraPaid;
                $customer->save();
            }
            // DB::commit();
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }



    protected function applyExtraPaymentToPreviousOrders($customerId, $userId, $paymentBreakdown, $date = null, $description = null)
    {
        try {

            Log::info('========== CREDIT PAYMENT START ==========');

            Log::info('REQUEST DATA', [
                'customer_id' => $customerId,
                'user_id' => $userId,
                'payment_breakdown' => $paymentBreakdown,
                'date' => $date,
                'description' => $description
            ]);


            $customer = Customer::find($customerId);

            Log::info('CUSTOMER BEFORE PAYMENT', [
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->customer_name,
                'balance_amount' => $customer?->balance_amount
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
            // if customer have opening balance, pay that first
            $openeing_balance = 0;
            $openeing_total_amount = OpeningBalance::where('customer_id', $customerId)
                ->sum('amount');

            $openeing_paid_amount = OpeningBalance::where('customer_id', $customerId)
                ->sum('paid_amount');


            $openeing_balance = $openeing_total_amount - $openeing_paid_amount;

            if ($openeing_balance > 0) {
                foreach ($paymentBreakdown as $key => &$payment) {
                    if ($payment['amount'] <= 0) continue;

                    $applied = min($payment['amount'], $openeing_balance);

                    // Update Opening Balance
                    OpeningBalance::where('customer_id', $customerId)
                        ->increment('paid_amount', $applied);


                    $customer = Customer::find($customerId);
                    $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';

                    $orderPayment = OrderPayment::create([
                        // 'order_id' => $prevOrder->id,
                        'customer_id' => $customerId,
                        'user_id' => $userId,
                        'total_amount' => $customer->balance_amount,
                        'paid_amount' => $applied,
                        'balance_amount' => $customer->balance_amount - $applied,
                        'type' => "opening_balance",
                        'payment_mode' => $payment['payment_mode'] ?? "Cash", // or "Adjustment"
                        'payment_date' => $date ?? now(),
                        'description' => $description,
                        'financial_year_id'  => $financialYear->id,
                    ]);
                    if ($customer_type == 'inside') {
                        // Create journal entry for opening balance payment
                        $jo = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $date ?? now()->toDateString(),
                            'amount' => $applied,
                            'reference_type' => 'App\\Models\\OrderPayment',
                            'reference_id' => $orderPayment->id,
                            'customer_id' => $customerId,
                            'description' => 'Opening balance payment from customer',
                            'customer_type' => 'customer',
                            'customer_name' => $customer->customer_name ?? '',
                            'payment_mode' => $payment['payment_mode'] ?? "Cash",
                            'user_id' => $userId,
                        ]);
                        if (!$jo) {
                            return errorResponse(null, 'Failed to create journal entry', 500);
                        }
                    }
                    $customer->balance_amount -= $applied;
                    $customer->save();
                    Log::info('OPENING BALANCE PAYMENT APPLIED', [
                        'applied_amount' => $applied,
                        'new_balance' => $customer->balance_amount
                    ]);
                    // Log::info('ORDER PAYMENT APPLIED', [
                    //     'order_id' => $prevOrder->id,
                    //     'applied_amount' => $applied,
                    //     'customer_balance_after' => $customer->balance_amount
                    // ]);

                    $openeing_balance -= $applied;
                    $payment['amount'] -= $applied;
                }
            }


            // Get previous unpaid orders
            $previousOrders = Order::with('orderItems')
                ->where('customer_id', $customerId)
                ->whereRaw('LOWER(payment_status) != ?', ['paid'])
                ->where('order_status', 'delivered')
                ->orderBy('created_at', 'asc')
                ->get();
            Log::info('PREVIOUS ORDERS COUNT', [
                'count' => $previousOrders->count()
            ]);

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
                        'customer_id' => $customerId,
                        'user_id' => $userId ?? 1,
                        'total_amount' => $prevOrder->total_amount,
                        'paid_amount' => $applied,
                        'balance_amount' => $prevOrder->balance_amount,
                        'payment_mode' => $payment['payment_mode'], // Correct mode
                        'payment_date' => $date ?? now(),
                        'description' => $description,
                        'financial_year_id'  => $financialYear->id,
                    ]);

                    $customer = Customer::find($customerId);
                    $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';
                    if ($customer_type == 'inside') {
                        // Create journal entry for credit payment received
                        $jo = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $date ?? now()->toDateString(),
                            'amount' => $applied,
                            'reference_type' => 'App\\Models\\OrderPayment',
                            'reference_id' => $orderPayment->id,
                            'customer_id' => $customerId,
                            'customer_type' => 'customer',
                            'description' => 'Credit payment - Order ' . $prevOrder->order_no,
                            'customer_name' => $customer->customer_name ?? '',
                            'payment_mode' => $payment['payment_mode'],
                            'user_id' => $userId ?? 1,
                        ]);
                        if (!$jo) {
                            return errorResponse(null, 'Failed to create journal entry', 500);
                        }
                    }
                    Log::info('BEFORE CUSTOMER SAVE', [
                        'customer_balance_before' => $customer->balance_amount,
                        'applied' => $applied,
                    ]);

                    $customer->balance_amount -= $applied;
                    $customer->save();
                    Log::info('AFTER CALCULATION', [
                        'customer_balance_after' => $customer->balance_amount,
                    ]);


                    // $customer->refresh();

                    Log::info('AFTER SAVE', [
                        'db_balance' => $customer->balance_amount,
                    ]);

                    // Calculate commission
                    if ($prevOrder->order_by === $prevOrder->delivered_by) {
                        $this->handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customerId, $date);
                    } else {
                        $this->handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customerId, $date);
                    }

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

            // Handle leftover amount
            if (!empty($paymentBreakdown)) {
                $customer = Customer::find($customerId);
                $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';
                foreach ($paymentBreakdown as $payment) {
                    if ($payment['amount'] > 0) {
                        $orderPayment = OrderPayment::create([
                            'customer_id' => $customerId,
                            'user_id' => $userId ?? 1,
                            'total_amount' => $customer->balance_amount,
                            'paid_amount' => $payment['amount'],
                            'balance_amount' => $customer->balance_amount - $payment['amount'],
                            'type' => "extra_payment",
                            'payment_mode' => $payment['payment_mode'],
                            'payment_date' => $date ?? now(),
                            'description' => $description,
                            'financial_year_id'  => $financialYear->id,
                        ]);
                        if ($customer_type == 'inside') {
                            // Create journal entry for extra payment
                            $jo = $this->journalEntryService->createSalesPaymentEntry([
                                'date' => $date ?? now()->toDateString(),
                                'amount' => $payment['amount'],
                                'reference_type' => 'App\\Models\\OrderPayment',
                                'reference_id' => $orderPayment->id,
                                'description' => 'Extra payment from customer',
                                'customer_id' => $customerId,
                                'customer_type' => 'customer',
                                'customer_name' => $customer->customer_name ?? '',
                                'payment_mode' => $payment['payment_mode'],
                                'user_id' => $userId ?? 1,
                            ]);
                            if (!$jo) {
                                return errorResponse(null, 'Failed to create journal entry', 500);
                            }
                        }
                        $customer->balance_amount -= $payment['amount'];
                        $customer->save();
                        Log::info('EXTRA PAYMENT APPLIED', [
                            'payment_mode' => $payment['payment_mode'],
                            'amount' => $payment['amount'],
                            'balance_after' => $customer->balance_amount
                        ]);
                    }
                }
                $customer->save();
                // $customer->refresh();

                Log::info('CUSTOMER AFTER PAYMENT', [
                    'customer_id' => $customer->id,
                    'final_balance' => $customer->balance_amount
                ]);

                Log::info('========== CREDIT PAYMENT END ==========');
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // if the sales executive take order and sales man delivery , then calculate the commission
    // this calculate salesExecutive and sales_man commission
    protected function handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customerId, $date = null)
    {
        try {
            $orderItems = $prevOrder->orderItems;

            foreach ($orderItems as $item) {

                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($prevOrder->customer_id, $item, 'executive');

                $commissionRate = $this->getCommissionRate($prevOrder, $item, 'executive');

                // Full possible commission for this item (if fully paid)
                $fullCommission = $item->quantity * $commissionRate;

                // Ratio of how much of this order got paid in THIS applied portion
                $paidRatio = $applied / $prevOrder->total_amount;

                // Final commission for this applied payment
                $commissionAmount = $fullCommission * $paidRatio;

                // --- Save in CommissionMaster ---
                $this->saveDataToCommissionMaster($item, $commissionAmount, $prevOrder->order_by, $prevOrder, $date);

                // if ($prevOrder->order_status === 'delivered') {

                //     // $commissionRate = $this->getCommissionRate($prevOrder->customer_id, $item, 'delivery_vehicle');

                //     $commissionRate = $this->getCommissionRate($prevOrder, $item, 'delivery_vehicle');

                //     // Full possible commission for this item (if fully paid)
                //     $fullCommission = $item->quantity * $commissionRate;

                //     // Ratio of how much of this order got paid in THIS applied portion
                //     $paidRatio = $applied / $prevOrder->total_amount;

                //     // Final commission for this applied payment
                //     $commissionAmount = $fullCommission * $paidRatio;
                //     // store sales man commission
                //     $this->saveDataToCommissionMaster($item, $commissionAmount, $prevOrder->delivered_by, $prevOrder);
                // }
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // if the sales man take order and delivery , then calculate the commission
    protected function handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customerId, $date = null)
    {
        try {
            $orderItems = $prevOrder->orderItems;

            foreach ($orderItems as $item) {


                // if customer have special price that product, then take special price, otherwise take product price
                // $commissionRate = $this->getCommissionRate($prevOrder->customer_id, $item, 'salesman');

                $commissionRate = $this->getCommissionRate($prevOrder, $item, 'salesman');

                // Full possible commission for this item (if fully paid)
                $fullCommission = $item->quantity * $commissionRate;

                // Ratio of how much of this order got paid in THIS applied portion
                $paidRatio = $applied / $prevOrder->total_amount;

                // Final commission for this applied payment
                $commissionAmount = $fullCommission * $paidRatio;

                // --- Save in CommissionMaster ---
                if ($prevOrder->order_status === 'delivered') {
                    // store sales man commission
                    $this->saveDataToCommissionMaster($item, $commissionAmount, $prevOrder->delivered_by, $prevOrder, $date);
                }
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    /**
     *  ------------------- End ------------------------------
     *  Manage the previous order payments and commissions
     *  ------------------- End ------------------------------
     */

    //


    // this store the data into CommissionMaster, Incentive, CommissionHistory table
    protected function saveDataToCommissionMaster($item, $commissionAmount, $userId, $order = null, $date = null)
    {
        try {

            // DB::beginTransaction();

            $commissionAmount = round($commissionAmount, 2);

            if ($commissionAmount <= 0) {
                return;
            }
            Log::channel('commission_history')->info("------------saveDataToCommissionMaster---------------");

            Log::channel('commission_history')->info('Commission History Details', [
                'order_id' => $order->id ?? null,
                'product_id' => $item->product_id,
                'user_id' => $userId,
                'amount' => $commissionAmount,
                'date' => $date ?? "No date provided",
            ]);


            $commissionMaster = CommissionMaster::where('user_id', $userId)
                ->where('product_id', $item->product_id)
                ->first();

            if ($commissionMaster) {
                $commissionMaster->update([
                    'commission_amount' => $commissionMaster->commission_amount + $commissionAmount,
                    'updated_by' => $userId,
                ]);
            } else {
                $commissionMaster = CommissionMaster::create([
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                    'commission_amount' => $commissionAmount,
                    'created_by' => $userId,
                ]);
            }

            // --- Incentive update ---
            $incentive = Incentive::where('user_id', $userId)->first();
            if ($incentive) {
                $incentive->update([
                    'total_commission' => $incentive->total_commission + $commissionAmount,
                ]);
            } else {
                Incentive::create([
                    'user_id' => $userId,
                    'total_commission' => $commissionAmount,
                ]);
            }

            // --- History ---
            $commission = CommissionHistory::create([
                'user_id' => $userId,
                'commision_master_id' => $commissionMaster->id,
                'order_id' => $order->id ?? null,
                'product_id' => $item->product_id,
                'product_quantity' => $item->quantity,
                'product_price' => $item->price,
                'total_amount' => $item->total,
                'commission_amount' => $commissionAmount,
                'created_by' => $userId,
                'date' => $date ?? $order->order_date ?? now(),
            ]);


            $employee = Employee::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($employee) {
                $employee->increment('commission', $commissionAmount);
                $employee->save();
            } else {
                Log::warning('Employee not found for commission update', [
                    'user_id' => $userId,
                    'order_id' => $order->id ?? null,
                    'commission_amount' => $commissionAmount,
                ]);
            }

            // $journalEntry = $this->journalEntryService->createCommissionEntry([
            //     'date' => $date ?? $order->order_date ?? now(),
            //     'reference_type' => 'App\\Models\\Commission',
            //     'reference_id' => $commission->id,
            //     'description' => 'Commission payable: ' . $commission->id,
            //     'amount' => $commissionAmount,
            //     'user_id' => Auth::id(),
            //     'payroll_description' => 'Commission',
            //     'employee' => $employee->id,

            // ]);
            // DB::commit();

            Log::channel('commission_history')->info('Commission History updated');

            return;
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('commission_history')->error('Error in saving Commission History', [
                'error' => $e->getMessage(),
            ]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // if customer have special price that product, then take special price, otherwise take product price
    // protected function getCommissionRate($customerId, $item, $type = 'executive')
    // {
    //     try {
    //         $assigned = AssignedSpecialPrice::with('specialPrice')
    //             ->where('customer_id', $customerId)
    //             ->where('product_id', $item->product_id)
    //             ->latest()
    //             ->first();

    //         // Use assigned price if exists, otherwise fallback to item price
    //         $priceSource = $assigned?->specialPrice ?? $item->specialPrice;

    //         switch ($type) {
    //             case 'executive':
    //                 // return $priceSource->executive_commission ?? 0;
    //                 return $item->specialPrice->executive_commission ?? 0;
    //             case 'salesman':
    //                 return $priceSource->salesman_commission ?? 0;

    //             case 'delivery_vehicle':
    //                 // return $priceSource->executive_commission ?? 0;
    //                 return $item->specialPrice->executive_commission ?? 0;
    //             default:
    //                 return $priceSource->executive_commission ?? 0;
    //         }
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    // protected function getCommissionRate($order, $item, $type = 'executive')
    // {
    //     try {
    //         $commissionRate = 0;

    //         $commission_type_id = $order->commission_type_id;

    //         if(!$commission_type_id){
    //             return 0;
    //         }

    //         $commission_type = CommisionTypeMaster::where('id',  $commission_type_id)->first();
    //         if ($commission_type) {
    //             switch ($type) {
    //                 case 'executive':
    //                     $commissionRate = $commission_type->salesexecutive_commission;
    //                     break;
    //                 case 'salesman':
    //                     $commissionRate = $commission_type->salesman_commission;
    //                     break;
    //                 case 'delivery_vehicle':
    //                     $commissionRate = $commission_type->delivery_vehicle_commission;
    //                     break;
    //                 default:
    //                     $commissionRate = 0;
    //             }
    //         }
    //         return $commissionRate;
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    protected function getCommissionRate($order, $item, $type = 'executive')
    {


        try {
            $commissionRate = 0;



            // take commission_type from order table
            $commission_type_id = $order->commission_type_id;

            if (!$commission_type_id) {
                return 0;
            }

            // get commission type master
            $commission_type = CommisionTypeMaster::where('id',  $commission_type_id)->first();


            if (!$commission_type) {
                return 0;
            }

            // get product type
            $product_type = $item->product->product_type;

            if (!$product_type) {
                return 0;
            }

            // get commission type item
            $commission_type_item = CommissionTypeItem::where('commission_type_master_id', $commission_type_id)
                ->where('product_type', $product_type)
                ->first();


            if ($commission_type_item) {
                switch ($type) {
                    case 'executive':

                        $commissionRate = $commission_type_item->executive_commission;

                        break;
                    case 'salesman':
                        $commissionRate = $commission_type_item->salesman_commission;

                        break;
                    case 'delivery_vehicle':
                        $commissionRate = $commission_type_item->delivery_vehicle_commission;

                        break;
                    default:

                        $commissionRate = 0;
                }

                return $commissionRate;
            } else {
                return 0;
            }
        } catch (Exception $e) {

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
