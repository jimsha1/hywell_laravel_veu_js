<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AutoIncrementMaster;
use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\DeletedOrderPayment;
use App\Models\FinancialYear;
use App\Models\GlobalTax;
use App\Models\Incentive;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Products;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\User;
use App\Services\CommissionManagement\CommissionManagementService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminOrderService extends CommissionManagementService
{
    private static function getStockValue($operator, $date, $product_id)
    {
        return DB::table('stock_originals')
            ->selectRaw("
            SUM(CASE WHEN movement_type='in' THEN quantity ELSE 0 END) -
            SUM(CASE WHEN movement_type='out' THEN quantity ELSE 0 END)
            as stock
        ")
            ->whereDate('date', $operator, $date)
            ->whereNull('deleted_at')
            ->where('product_id', $product_id)
            ->value('stock') ?? 0;
    }
    public function admin_order_store($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $productRows  = json_decode($data->cart, true) ?? [];
            $paymentRows  = json_decode($data->payment_rows, true) ?? [];

            // =========================
            // STOCK VALIDATION
            // =========================

            $orderDate = isset($data->order_date) && !empty($data->order_date)
                ? Carbon::parse($data->order_date)->format('Y-m-d H:i:s')
                : Carbon::now()->format('Y-m-d H:i:s');



            foreach ($productRows as $row) {

                $qty = (float)($row['qty'] ?? 0);
                $freeQty = (float)($row['free_qty'] ?? 0);
                $requiredQty = $qty + $freeQty;

                $product = Products::find($row['id']);

                if (!$product) {
                    DB::rollBack();
                    return errorResponse(null, 'Product not found.', 100, 404);
                }


                if ($product->total_stock < $requiredQty) {

                    // dd($product->total_stock);
                    DB::rollBack();

                    return errorResponse(
                        null,
                        $product->product_name .
                            ' available stock is only ' .
                            $product->total_stock .
                            '. Requested qty is ' .
                            $requiredQty,
                        100,
                        400
                    );
                }

                $closingStock = self::getStockValue('<=', $orderDate, $product->id);
                if ($closingStock < $requiredQty) {
                    DB::rollBack();
                    return errorResponse(
                        null,
                        $product->product_name .
                            ' available stock is only ' .
                            $closingStock .
                            '. Requested qty is ' .
                            $requiredQty,
                        100,
                        400
                    );
                }
            }


            $totalAmount = 0;
            $totalQty = 0;

            foreach ($productRows as $row) {
                $qty = (float)$row['qty'];
                $price = (float)$row['price'];
                $totalQty += $qty;
                $totalAmount += $qty * $price;
            }

            $paidAmount = 0;
            $paymentStatus = 'Paid';

            $customer = Customer::find($data->customer_id);
            $customerCreditBalance = 0;
            $total_refunded_amount = 0;
            foreach ($paymentRows as $row) {
                $mode = strtolower($row['payment_mode']);
                $amount = (float)$row['amount_taken'];


                if ($mode === 'credit') {

                    if ($customer->balance_amount < 0) {
                        $customerCreditBalance = $customer->balance_amount * (-1);
                        if ($customerCreditBalance > $amount)
                            $total_refunded_amount = $amount;
                        else
                            $total_refunded_amount = $customerCreditBalance;
                    } else {
                        $paymentStatus = 'Partial';
                    }
                } else {
                    $paidAmount += $amount;
                }
            }

            $balanceAmount = $totalAmount - $paidAmount - $total_refunded_amount;
            $orderNo = $this->generateOrderCode();
            $customer = Customer::find($data->customer_id);

            if ($balanceAmount > 0) {
                $paymentStatus = 'Partial';
            }

            // Handle direct sale logic
            $isDirectSale = isset($data->is_direct_sale) && $data->is_direct_sale == 1;


            $financialYear = FinancialYear::whereDate('start_date', '<=', $orderDate)
                ->whereDate('end_date', '>=', $orderDate)
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
            // For direct sale: order is taken and delivered by admin, status is 'delivered', dates are same
            if ($isDirectSale) {
                $deliveredBy = $data->user_id ?? 1; // Admin user
                $orderStatus = Order::ORDER_STATUS_DELIVERED;
                $deliveredDate = $orderDate; // Same as order date
            } else {
                $deliveredBy = $data->salesman_id;
                $orderStatus = Order::ORDER_STATUS_REQUESTED;
                $deliveredDate = null;
            }

            $financialYear = FinancialYear::where('start_date', '<=', $orderDate)
                ->where('end_date', '>=', $orderDate)
                ->first();
            $order = Order::create([
                'customer_id'        => $data->customer_id,
                'order_no'           => $orderNo,
                'total_quantity'     => $totalQty,
                'total_amount'       => $totalAmount,
                'paid_amount'        => $paidAmount,
                'balance_amount'     => $balanceAmount,
                'total_refunded_amount' => $total_refunded_amount,
                'order_status'       => $orderStatus,
                'payment_status'     => $paymentStatus,
                'order_type'         => $customer->customer_type,
                'invoice_type'       => 'web',
                'commission_type_id' => $data->commission_type,
                'order_by'           => $data->user_id ?? 1,
                'delivered_by'       => $deliveredBy,
                'order_date'         => $orderDate,
                'financial_year_id'  => $financialYear->id,
                'delivered_date'      => $deliveredDate,
            ]);

            $customer->balance_amount += $total_refunded_amount;
            $customer->save();
            //stock update
            foreach ($productRows as $row) {
                $qty = (float)$row['qty'];
                $freeQty = (float)$row['free_qty'] ?? 0;
                $price = (float)$row['price'];
                $total = ($qty + $freeQty) * $price;
                $total_qty = $qty + $freeQty;

                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $row['id'],
                    'quantity'      => $qty,
                    'free_quantity' => $freeQty,
                    'price'         => $price,
                    'tax_percentage' => 0,
                    'tax_amount'    => 0,
                    'total'         => $total,
                ]);

                // Decrease product stock, if direct sale
                if ($isDirectSale) {
                    $product = Products::find($row['id']);

                    if (!$product) {
                        return errorResponse(null, 'Product not found.', 100, 404);
                    }

                    if ($product->total_stock < $total_qty) {
                        return errorResponse(null, 'Product is out of stock for ' . $product->product_name . '.', 100, 400);
                    }

                    $product->decrement('total_stock', $total_qty);

                    StockMovement::create([
                        'product_id' => $row['id'],
                        'movement_type' => 'out',
                        'amount' => $total,
                        'quantity' => $total_qty,
                        'reason' => 'direct order',
                        'ref_id'       => $order->id,
                        'ref_no'       => $order->order_no,
                        'date' => $deliveredDate ?? Carbon::now(),
                    ]);



                    $costOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $row['id'])->sum('cost_price');
                    $countOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $row['id'])->count();
                    if ($countOfPrevPurchases <= 0) {
                        $cost_price = Products::where('id', $row['id'])->first()->unit_price;
                    } else {
                        $cost_price = $costOfPrevPurchases / $countOfPrevPurchases;
                    }


                    StockOriginal::create([
                        'ref_id'       => $order->id,
                        'ref_no'       => $order->order_no,
                        'product_id'   => $row['id'],
                        'quantity'     => $total_qty,
                        'cost_price' => $cost_price,
                        'movement_type' => 'out',
                        'reason'       => 'Sale',
                        'date' => $deliveredDate ?? Carbon::now(),
                        'created_by'   => $data->user_id,
                    ]);
                }
            }


            // $customer = Customer::where('id', $data->customer_id)->first();

            foreach ($paymentRows as $row) {
                $amountTaken = (float) $row['amount_taken'];
                $paymentMode = strtolower($row['payment_mode']);

                if ($paymentMode === 'credit') {


                    continue;
                } else {

                    $orderPayment = OrderPayment::create([
                        'order_id'       => $order->id,
                        'customer_id'    => $data->customer_id,
                        'user_id'        => $data->user_id,
                        'total_amount'   => $totalAmount,
                        'paid_amount'    => $amountTaken,
                        'balance_amount' => $balanceAmount,
                        'payment_mode'   => $row['payment_mode'],
                        'payment_date'   => $orderDate,
                        'financial_year_id'  => $financialYear->id,
                        'description' => $data->description ?? null,
                    ]);

                    $journalEntry1 = $this->journalEntryService->createSalesPaymentEntry([
                        'date' => $orderDate ?? Carbon::now(),
                        'amount' => $amountTaken,
                        'reference_type' => 'App\\Models\\OrderPayment',
                        'reference_id' => $orderPayment->id,
                        'description' => 'Order Payment - Order ' . $order->order_no,
                        'customer_id' => $customer ? $customer->id : '',
                        'customer_name' => $customer ? $customer->customer_name : '',
                        'payment_mode' => $paymentMode,
                        'customer_type' => 'customer',
                        'user_id' => $customer ? $customer->user_id : '',

                        'customer_code' => $customer ? $customer->customer_code : null,
                    ]);
                    if (!$journalEntry1) {
                        return errorResponse(null, 'Failed to create journal entry2', 500);
                    }
                }
            }

            if ($isDirectSale) {
                $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                    'date' => $orderDate ?? Carbon::now(),
                    'amount' => $totalAmount,
                    'reference_type' => 'App\\Models\\Order',
                    'reference_id' => $order->id,
                    'description' => 'Order ' . $order->order_no,
                    'customer_id' => $customer ? $customer->id : '',
                    'customer_name' => $customer ? $customer->customer_name : '',

                    'customer_type' => 'customer',
                    'user_id' => $customer ? $customer->user_id : '',

                    'customer_code' => $customer ? $customer->customer_code : null,
                ]);
                if (!$journalEntry) {
                    return errorResponse(null, 'Failed to create journal entry', 500);
                }
            }

            // if the sale is direct sale, record credit payment and update customer balance,
            // otherwise this is handled in delivered order process
            if ($isDirectSale && $balanceAmount > 0) {
                $creditPayment = CreditPayment::create([
                    'order_id'    => $order->id,
                    'customer_id' => $data->customer_id,
                    'amount'      => $balanceAmount,
                    'date' => $orderDate ?? Carbon::now(),
                ]);




                $customer->balance_amount = $customer->balance_amount + $balanceAmount;
                $customer->save();

                // if ($customer_type == 'inside') {
                //     $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                //         'date' => $orderDate ?? Carbon::now(),
                //         'amount' => $balanceAmount,
                //         'reference_type' => 'App\\Models\\CreditPayment',
                //         'reference_id' => $creditPayment->id,
                //         'description' => 'Credit Payment - Order ' . $order->order_no,
                //         'customer_id' => $customer ? $customer->id : '',
                //         'customer_name' => $customer ? $customer->customer_name : '',
                //         // 'payment_mode' => $paymentMode,
                //         'customer_type' => $customer_type,
                //         'user_id' => $customer ? $customer->user_id : '',

                //         'customer_code' => $customer ? $customer->customer_code : null,
                //     ]);
                //     if (!$journalEntry) {
                //         return errorResponse(null, 'Failed to create journal entry2', 500);
                //     }
                // }
            }

            DB::commit();
            return successResponse($order, 'Order created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function generateOrderCode()
    {
        $auto_invoice_code = AutoIncrementMaster::where('auto_code', 'AI_ORDER_CODE')->first();
        $currentValue = $auto_invoice_code->auto_increment_general_value;
        $auto_invoice_code->auto_increment_general_value = $currentValue + 1;
        $auto_invoice_code->save();

        return $auto_invoice_code->auto_prefix . $currentValue;
    }
    public function getAllAdminOrder($data, $platform = 'web')
    {
        try {
            $query = Order::with('customer', 'deliveryBy', 'orderItems.product', 'payments')
                ->where('order_type', '!=', 'Outside Sale');

            // Only filter by invoice_type for web platform if needed
            if (isset($data->invoice_type_filter) && $data->invoice_type_filter) {
                $query->where('invoice_type', $data->invoice_type_filter);
            } else if ($platform == 'web') {
                // Default behavior: show web orders for web platform
                $query->where('invoice_type', 'web');
            }

            if ($data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($cat) use ($searchTerm) {
                            $cat->where('customer_name', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('delivery_by', function ($cat) use ($searchTerm) {
                            $cat->where('name', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }


            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'admin_order'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Admin Order retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Admin Order retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getOrderDetailsWithPayments($orderId)
    {
        try {
            $order = Order::with(['customer', 'orderItems.product', 'payments.user'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return errorResponse(null, 'Order not found.', 404, 404);
            }

            return successResponse($order, 'Order details retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function verifyPasswordAndGetOrder($orderId, $password, $userId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return errorResponse(null, 'User not found.', 404, 404);
            }

            if (!Hash::check($password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            $order = Order::with(['customer', 'orderItems.product', 'payments.user'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return errorResponse(null, 'Order not found.', 404, 404);
            }

            return successResponse($order, 'Password verified successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }





    public function getAllOrdersForPaymentEdit($data, $platform = 'web')
    {
        try {
            $query = Order::with('customer', 'deliveryBy', 'orderItems.product', 'payments')
                ->whereIn('order_status', [Order::ORDER_STATUS_REQUESTED, Order::ORDER_STATUS_DELIVERED])->where('is_returned',  '0');

            if ($data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($cat) use ($searchTerm) {
                            $cat->where('customer_name', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('deliveryBy', function ($cat) use ($searchTerm) {
                            $cat->where('name', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'orders' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Orders retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Orders retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function updateAllOrderPaymentsCopy($request)
    {
        try {
            DB::beginTransaction();

            // Verify password
            $user = User::find($request->user_id);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            $order = Order::find($request->order_id);
            if (!$order) {
                return errorResponse(null, 'Order not found.', 404, 404);
            }

            $payments = $request->payments ?? [];

            // Calculate total paid amount first to validate
            $newTotalPaid = 0;
            foreach ($payments as $paymentData) {
                $paidAmount = (float)($paymentData['paid_amount'] ?? 0);
                $newTotalPaid += $paidAmount;
            }

            // Validate: Total paid cannot exceed order total amount
            if ($newTotalPaid > $order->total_amount) {
                return errorResponse(null, 'Total paid amount cannot exceed order total amount.', 400, 400);
            }

            // Get old total paid for this order only (before update)
            $oldTotalPaidForThisOrder = OrderPayment::where('order_id', $order->id)->sum('paid_amount');

            // First, delete all existing payments for this order
            OrderPayment::where('order_id', $order->id)->delete();

            // Now create/update all payments from the request
            foreach ($payments as $paymentData) {
                $paidAmount = (float)($paymentData['paid_amount'] ?? 0);

                if ($paidAmount > 0) {
                    OrderPayment::create([
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'user_id' => $request->user_id,
                        'total_amount' => $order->total_amount,
                        'paid_amount' => $paidAmount,
                        'balance_amount' => 0, // Will be recalculated below
                        'payment_mode' => $paymentData['payment_mode'] ?? 'Cash',
                        'payment_date' => isset($paymentData['payment_date'])
                            ? Carbon::parse($paymentData['payment_date'])->format('Y-m-d H:i:s')
                            : now(),
                        'description' => $paymentData['description'] ?? null,
                    ]);
                }
            }

            // Recalculate order totals for this order only
            $actualTotalPaid = OrderPayment::where('order_id', $order->id)->sum('paid_amount');
            $order->paid_amount = $actualTotalPaid;
            $order->balance_amount = $order->total_amount - $actualTotalPaid;
            $order->payment_status = $order->balance_amount <= 0 ? 'Paid' : 'Partial';
            $order->save();

            // Update balance_amount for each payment (for consistency)
            $runningBalance = $order->total_amount;
            $orderPayments = OrderPayment::where('order_id', $order->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($orderPayments as $payment) {
                $runningBalance -= $payment->paid_amount;
                $payment->balance_amount = max(0, $runningBalance);
                $payment->save();
            }

            // Update customer balance - only adjust by the difference for this order
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    // Calculate the difference in paid amount for this order
                    $difference = $actualTotalPaid - $oldTotalPaidForThisOrder;

                    // Update customer balance by the difference only
                    $customer->balance_amount -= $difference;
                    $customer->save();
                }
            }

            DB::commit();

            // Return updated order with payments
            $updatedOrder = Order::with(['customer', 'orderItems.product', 'payments.user'])
                ->where('id', $order->id)
                ->first();

            return successResponse($updatedOrder, 'Payments updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function updateAllOrderPayments($request)
    {

        $log = Log::channel('update_order_payments');
        try {
            // DB::beginTransaction();
            // Verify password
            $user = User::find($request->user_id);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            $order = Order::find($request->order_id);
            if (!$order) {
                return errorResponse(null, 'Order not found.', 404, 404);
            }

            $log->info("[ order_id = {$order->id} ] | order : " . $order);

            $payments = $request->payments ?? [];
            $log->info(
                "[ order_id = {$order->id} ] | updating payments",
                ['payments' => $payments]
            );
            // Calculate total paid amount first to validate
            $newTotalPaid = 0;
            foreach ($payments as $paymentData) {

                $financialYear = FinancialYear::whereDate('start_date', '<=', $paymentData['payment_date'])
                    ->whereDate('end_date', '>=', $paymentData['payment_date'])
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
                $paidAmount = (float)($paymentData['paid_amount'] ?? 0);
                $newTotalPaid += $paidAmount;
            }

            // Validate: Total paid cannot exceed order total amount
            if ($newTotalPaid > $order->total_amount) {
                return errorResponse(null, 'Total paid amount cannot exceed order total amount.', 400, 400);
            }

            // Get old total paid for this order only (before update)
            $oldTotalPaidForThisOrder = OrderPayment::where('order_id', $order->id)
                ->where('type', 'order')->sum('paid_amount');

            // Get all existing payments before deleting them
            $existingPayments = OrderPayment::where('order_id', $order->id)->get();
            // Store deleted payments in deleted_order_payments table before deleting

            $deletedPaymentIds = [];
            foreach ($existingPayments as $payment) {

                $deletedPayment = DeletedOrderPayment::create([
                    'order_payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'customer_id' => $payment->customer_id,
                    'user_id' => $payment->user_id,
                    'total_amount' => $payment->total_amount,
                    'paid_amount' => $payment->paid_amount,
                    'balance_amount' => $payment->balance_amount,
                    'payment_mode' => $payment->payment_mode,
                    'type' => $payment->type,
                    'payment_date' => $payment->payment_date,
                    'description' => $payment->description,
                    'updated_by' => $payment->updated_by,
                    'deleted_by' => $request->user_id, // User who deleted/edited the payment
                    'deleted_at' => Carbon::now(),
                ]);

                $journalEntries = JournalEntry::where('reference_type', 'App\\Models\\OrderPayment')
                    ->where('reference_id', $payment->id)
                    ->where('status', 'posted')
                    ->get();

                foreach ($journalEntries as $entry) {

                    // Mark original entry as reversed after reversal succeeds.
                    $entry->status = 'reversed';
                    $entry->save();
                }
                // $customer = Customer::where('id', $payment->customer_id)->first();
                // $deletedPaymentIds[] = $payment->id;
            }

            // First, delete all existing payments for this order
            OrderPayment::where('order_id', $order->id)->delete();



            // Now create/update all payments from the request
            foreach ($payments as $paymentData) {
                $paidAmount = (float)($paymentData['paid_amount'] ?? 0);


                if ($paidAmount > 0) {

                    $customer = Customer::find($order->customer_id);
                    $orderPayment = OrderPayment::create([
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'user_id' => $paymentData['user_id'] ?? $request->user_id, // Employee who collected payment
                        'total_amount' => $order->total_amount,
                        'paid_amount' => $paidAmount,
                        'balance_amount' => 0, // Will be recalculated below
                        'payment_mode' => $paymentData['payment_mode'] ?? 'Cash',
                        'type' => 'order',
                        'payment_date' => isset($paymentData['payment_date'])
                            ? Carbon::parse($paymentData['payment_date'])->format('Y-m-d H:i:s')
                            : now(),
                        'description' => $paymentData['description'] ?? null,
                        'updated_by' => $request->user_id, // User who edited
                        'financial_year_id'  => $financialYear->id,
                    ]);


                    $ret1 = $this->journalEntryService->createSalesPaymentEntry([
                        'date' => isset($paymentData['payment_date'])
                            ? Carbon::parse($paymentData['payment_date'])->format('Y-m-d H:i:s')
                            : now(),
                        'amount' => $paidAmount,
                        'reference_type' => 'App\\Models\\OrderPayment',
                        'reference_id' => $orderPayment->id,
                        'description' => 'Order Payment received - Order ' . $order->order_no,
                        'customer_id' => $customer ? $customer->id : '',
                        'customer_name' => $customer ? $customer->customer_name : '',
                        'payment_mode' => $paymentData['payment_mode'],
                        'user_id' => $customer ? $customer->user_id : '',
                        'customer_type' => 'customer',
                        'customer_code' => $customer ? $customer->customer_code : null,
                    ]);
                    if (!$ret1) {
                        return errorResponse(null, 'Failed to create journal entry', 500);
                    }


                    // $customer = Customer::where('id', $payment->customer_id)->first();

                    // if ($orderPayment->payment_mode == 'cash') {
                    //     $changed_account = 3;
                    // } else if ($orderPayment->payment_mode == 'TRANSFER ALRAJHI') {
                    //     $changed_account = 30;
                    // } else if ($orderPayment->payment_mode == 'TRANSFER SAB') {
                    //     $changed_account = 31;
                    // } else if ($orderPayment->payment_mode == 'POS ALRAJHI') {
                    //     $changed_account = 32;
                    // } else if ($orderPayment->payment_mode == 'POS SAB') {
                    //     $changed_account = 33;
                    // }


                    // foreach ($deletedPaymentIds as $paymentId) {

                    //     $existingJournalEntries = JournalEntry::where('reference_type', 'App\Models\OrderPayment')
                    //         ->where('reference_id', $paymentId)->get();
                    //     if ($existingJournalEntries) {
                    //         foreach ($existingJournalEntries as $entry) {

                    //             // $entry->entry_date = isset($paymentData['payment_date'])
                    //             //     ? Carbon::parse($paymentData['payment_date'])->format('Y-m-d H:i:s')
                    //             //     : now();
                    //             // $entry->reference_id = $orderPayment->id;
                    //             $entry->status = 'reversed';
                    //             $entry->save();
                    //             // $existingDebitReverseJournalEntry = JournalEntryLine::where('journal_entry_id', $entry->id)
                    //             //     ->where('type', 'debit')->first();

                    //             // $existingDebitReverseJournalEntry->amount = $paidAmount;
                    //             // $existingDebitReverseJournalEntry->type = 'credit';
                    //             // $existingDebitReverseJournalEntry->account_id = $changed_account;
                    //             // $existingDebitReverseJournalEntry->save();

                    //             // $existingCreditReverseJournalEntry = JournalEntryLine::where('journal_entry_id', $entry->id)
                    //             //     ->where('type', 'credit')->first();

                    //             // $existingCreditReverseJournalEntry->amount = $paidAmount;
                    //             // $existingCreditReverseJournalEntry->type = 'debit';
                    //             // // $existingCreditReverseJournalEntry->account_id = $changed_account;
                    //             // $existingCreditReverseJournalEntry->save();
                    //             // $this->journalEntryService->editOrderPaymentEntry($entry);

                    //             // $journal = $this->journalEntryService->createReverseSalesPaymentEntry([
                    //             //     'date' => isset($paymentData['payment_date'])
                    //             //         ? Carbon::parse($paymentData['payment_date'])->format('Y-m-d H:i:s')
                    //             //         : now(),
                    //             //     'amount' => $paidAmount,
                    //             //     'reference_type' => 'App\\Models\\ReverseOrderPayment',
                    //             //     'reference_id' => $orderPayment->id,
                    //             //     'description' => 'Payment Sale Reversed- Order ' . $order->order_no,
                    //             //     'customer_id' => $customer ? $customer->id : '',
                    //             //     'customer_name' => $customer ? $customer->customer_name : '',
                    //             //     'payment_mode' => $orderPayment->payment_mode,
                    //             //     'user_id' => $customer->user_id,
                    //             //     'customer_code' => $customer ? $customer->customer_code : null,
                    //             // ]);
                    //         }
                    //     }
                    // }
                }
            }

            // Recalculate order totals for this order only
            $actualTotalPaid = OrderPayment::where('order_id', $order->id)
                ->where('type', 'order')->sum('paid_amount');
            $order->paid_amount = $actualTotalPaid;
            $order->balance_amount = $order->total_amount - $actualTotalPaid;
            $order->payment_status = $order->balance_amount <= 0 ? 'Paid' : 'Partial';
            $order->updated_by = $request->user_id; // User who edited
            $order->save();

            // Update balance_amount for each payment (for consistency)
            $runningBalance = $order->total_amount;
            $orderPayments = OrderPayment::where('order_id', $order->id)
                ->where('type', 'order')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($orderPayments as $payment) {
                $runningBalance -= $payment->paid_amount;
                $payment->balance_amount = max(0, $runningBalance);
                $payment->save();
            }

            $log->info("[ order_id = {$order->id} ] oldTotalPaidForThisOrder : " . $oldTotalPaidForThisOrder . " actualTotalPaid : " . $actualTotalPaid . " order status : " . $order->order_status);
            if ($order->order_status === 'delivered') {
                if ($order->customer_id) {
                    $customer = Customer::find($order->customer_id);
                    if ($customer) {
                        $log->info("[ order_id = {$order->id} ] customer_id : " . $customer->id . " customer_balance_amount before : " . $customer->balance_amount);
                        // Calculate the difference in paid amount for this order
                        $difference = $actualTotalPaid - $oldTotalPaidForThisOrder;

                        // Update customer balance by the difference only
                        $customer->balance_amount -= $difference;
                        $customer->save();

                        $log->info("[ order_id = {$order->id} ] customer_id : " . $customer->id . " customer_balance_amount aafter : " . $customer->balance_amount);
                    }
                }
            }

            // Reverse old commissions for this order
            $log->info("[ order_id = {$order->id} ] Reversing old commissions for this order");
            $this->reverseCommissionsForOrder($order->id);

            // Determine the date to use for commission history
            // Use the earliest payment date from new payments, or fall back to order date
            $date = null;
            if (!empty($payments)) {
                $paymentDates = [];
                foreach ($payments as $paymentData) {
                    if (isset($paymentData['payment_date']) && $paymentData['payment_date']) {
                        $paymentDates[] = Carbon::parse($paymentData['payment_date']);
                    }
                }
                if (!empty($paymentDates)) {
                    $date = min($paymentDates)->format('Y-m-d H:i:s');
                }
            }


            // Fall back to order date if no payment date found
            if (!$date && $order->order_date) {
                $date = Carbon::parse($order->order_date)->format('Y-m-d H:i:s');
            }

            // Recalculate commissions based on new paid amount
            $order->refresh(); // Refresh order to get latest data
            $order->load('orderItems'); // Load order items for commission calculation
            $log->info("[ order_id = {$order->id} ] Recalculating commissions for this order");
            $this->recalculateCommissionsForOrder($order, $actualTotalPaid, $date);

            // Update customer balance - only adjust by the difference for this order


            $log->log('info', "[ order_id = {$order->id} ] order payments updated successfully");
            // DB::commit();

            // Return updated order with payments
            $updatedOrder = Order::with(['customer', 'orderItems.product', 'payments.user'])
                ->where('id', $order->id)
                ->first();

            return successResponse($updatedOrder, 'Payments updated successfully.', 0, 200);
        } catch (Exception $e) {
            // DB::rollBack();
            $log->info("[ order_id = {$order->id} ] | Error | " . $e->getMessage());
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Reverse all commissions for a specific order
     */
    protected function reverseCommissionsForOrder($orderId)
    {
        try {
            // Get all commission history entries for this order
            $commissionHistories = CommissionHistory::where('order_id', $orderId)->get();

            foreach ($commissionHistories as $history) {
                // Reverse CommissionMaster
                $commissionMaster = CommissionMaster::find($history->commision_master_id);
                if ($commissionMaster) {
                    $newAmount = $commissionMaster->commission_amount - $history->commission_amount;
                    if ($newAmount <= 0) {
                        // $commissionMaster->delete();
                        $commissionMaster->update([
                            'commission_amount' => 0,
                        ]);
                    } else {
                        $commissionMaster->update([
                            'commission_amount' => $newAmount,
                        ]);
                    }
                }

                // Reverse Incentive
                $incentive = Incentive::where('user_id', $history->user_id)->first();
                if ($incentive) {
                    $newTotal = $incentive->total_commission - $history->commission_amount;
                    if ($newTotal <= 0) {
                        $incentive->update(['total_commission' => 0]);
                    } else {
                        $incentive->update([
                            'total_commission' => $newTotal,
                        ]);
                    }
                }

                // Delete commission history
                $history->delete();
            }
        } catch (Exception $e) {
            Log::error('Error reversing commissions for order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recalculate commissions for an order based on new paid amount
     */
    protected function recalculateCommissionsForOrder($order, $newPaidAmount, $date = null)
    {
        try {
            if ($newPaidAmount <= 0) {
                return; // No payment, no commission
            }

            $orderItems = $order->orderItems;
            if (!$orderItems || $orderItems->isEmpty()) {
                return;
            }

            // Determine which commission method to use based on order type
            $isSalesManOrder = ($order->order_by === $order->delivered_by);



            if ($isSalesManOrder) {
                $request['sales_man_user_id'] = $order->order_by;
                $this->handleSalesManCommission($order, $request, $date);
            } else {
                $request['user_id'] = $order->order_by;
                $this->handleSalesExecutiveCommission($order, $request, $date);

                if ($order->order_status === 'delivered' && $order->delivered_by) {
                    $request['sales_man_user_id'] = $order->delivered_by;
                    $this->handleDeliveryVehicleCommission($order, $request, $date);
                }
            }
        } catch (Exception $e) {
            Log::error('Error recalculating commissions for order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate and save commission for a single item
     */
    protected function calculateAndSaveCommissionForItem($item, $order, $paidAmount, $userId, $type = 'executive', $date = null)
    {
        try {
            // Get commission rate
            $commissionRate = $this->getCommissionRate($order, $item, $type);

            // Full possible commission for this item (if fully paid)
            $fullCommission = $item->quantity * $commissionRate;

            // Ratio of how much of this order got paid
            $paidRatio = $order->total_amount > 0 ? ($paidAmount / $order->total_amount) : 0;

            // Final commission for this payment
            $commissionAmount = $fullCommission * $paidRatio;

            if ($commissionAmount > 0) {
                $this->saveDataToCommissionMaster($item, $commissionAmount, $userId, $order, $date);
            }
        } catch (Exception $e) {
            Log::error('Error calculating commission for item: ' . $e->getMessage());
            throw $e;
        }
    }
}
