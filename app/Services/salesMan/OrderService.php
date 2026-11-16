<?php

namespace App\Services\salesMan;

use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\DeliveredOrder;
use App\Models\FinancialYear;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\StockOriginal;
use App\Models\User;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use App\Services\CommissionManagement\CommissionManagementService;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService extends CommissionManagementService
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }

    // this list order requested from sales executive
    public function getOrdersRequestList($request)
    {
        try {
            $orders = Order::with(['orderItems.product', 'orderBy', 'deliveryBy', 'customer'])
                ->where('order_status', 'requested')
                ->where('delivered_by', $request->sales_man_user_id)
                ->latest()->get();

            // Format response
            $response = $orders->map(function ($order) {

                $customer = [
                    'id' => $order->customer->id,
                    'code' => $order->customer->customer_code,
                    'customer_name' => $order->customer->customer_name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone_number,
                    'credit_balance_amount' => $order->customer->balance_amount,
                    'national_address_no' => $order->customer->national_address_no,
                    'city' => $order->customer->city,
                    'apartment_number' => $order->customer->apartment_number,
                    'room_number' => $order->customer->room_number,
                    'address' => $order->customer->address,
                ];

                return [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'total_quantity' => $order->total_quantity,
                    'total_amount' => $order->total_amount,
                    'paid_amount' => $order->paid_amount,
                    'balance_amount' => $order->balance_amount,
                    'payment_status_db' => $order->payment_status,
                    'order_status' => $order->order_status,
                    // "payment_status" => $order->payment_status,
                    'payment_status' => $order->paid_amount == 0 ? 'Unpaid' : $order->payment_status,
                    'order_date' => $order->created_at ? $order->created_at->format('d-m-Y H:i') : null,
                    'customer_id' => $order->customer_id,
                    // "customer_details"    => $order->customer,
                    'customer_details' => $customer,
                    'order_by' => $order->order_by,
                    'delivery_by' => $order->delivered_by,
                ];
            });

            return successResponse($response, 'Order items fetched successfully', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // this list the order items and order details of order request
    public function getOrderRequestDetails($request)
    {
        try {
            $order = Order::with(['orderItems.product', 'orderBy', 'deliveryBy', 'customer'])
                ->where('order_status', 'requested')
                ->where('id', $request->order_id)
                ->latest()->first();

            $order_items = $order->orderItems->map(function ($item) {
                return [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'free_quantity' => $item->free_quantity,
                    'price' => $item->price,
                    'tax_percentage' => $item->tax_percentage,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                ];
            });

            $customer = [
                'id' => $order->customer->id,
                'code' => $order->customer->customer_code,
                'name' => $order->customer->customer_name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone_number,
                'credit_balance_amount' => $order->customer->balance_amount,
                'national_address_no' => $order->customer->national_address_no,
                'city' => $order->customer->city,
                'apartment_number' => $order->customer->apartment_number,
                'room_number' => $order->customer->room_number,
                'address' => $order->customer->address,
            ];

            $delivery_by_details = [
                'id' => $order->deliveryBy->id,
                'name' => $order->deliveryBy->name,
                'username' => $order->deliveryBy->username,
                'email' => $order->deliveryBy->email,
                'phone' => $order->deliveryBy->phone,
                'user_type' => $order->deliveryBy->user_type,
            ];

            $order_by_details = [
                'id' => $order->orderBy->id,
                'name' => $order->orderBy->name,
                'username' => $order->orderBy->username,
                'email' => $order->orderBy->email,
                'phone' => $order->orderBy->phone,
                'user_type' => $order->orderBy->user_type,
            ];

            $response = [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'total_quantity' => $order->total_quantity,
                'total_amount' => $order->total_amount,
                'balance_amount' => $order->balance_amount,
                'paid_amount' => $order->paid_amount,
                'order_status' => $order->order_status,
                // "payment_status" => $order->payment_status,
                'payment_status' => $order->paid_amount == 0 ? 'Unpaid' : $order->payment_status,
                'order_date' => $order->created_at ? $order->created_at->format('d-m-Y H:i') : null,
                'customer_id' => $order->customer_id,
                'customer_details' => $customer,
                'order_by' => $order->order_by,
                // "order_by_details"   => $order_by_details,
                'delivery_by' => $order->delivered_by,
                // "delivery_by_details"   => $delivery_by_details,
                'order_items' => $order_items,
            ];

            return successResponse($response, 'Order items fetched successfully', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // this mark as delivery the order that requested a sales executive
    public function deliveryOrder($request)
    {
        DB::beginTransaction();
        try {
            // 1. Find order with items
            $order = Order::with('orderItems.product')
                ->where('id', $request['order_id'])
                // ->where('customer_id', $request['customer_id'])
                ->where('delivered_by', $request['sales_man_user_id'])
                ->first();

            if (! $order) {
                return errorResponse(null, 'Order not found', 100, 200);
            }

            // 2. reduced stock item from vehicle
            $this->deductOrderItemsStock($order, $request);


            // Determine date for commission
            $commissionDate = null;
            if (! empty($request->delivered_date)) {
                // If request has date, combine with current time
                $requestedDate = $request->delivered_date;
                $currentTime = Carbon::now()->format('H:i:s');
                $finalDateTime = Carbon::parse($requestedDate . ' ' . $currentTime);
                $commissionDate = $finalDateTime->format('Y-m-d H:i:s');
            } else {
                // Otherwise, take current date & time
                $finalDateTime = Carbon::now();
                $commissionDate = $finalDateTime->format('Y-m-d H:i:s');
            }

            $financialYear = FinancialYear::whereDate('start_date', '<=', $finalDateTime)
                ->whereDate('end_date', '>=', $finalDateTime)
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
            Log::channel('commission_history')->info('------------ deliveryOrder ---------------');
            Log::channel('commission_history')->info('before handleDeliveryVehicleCommission', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'commissionDate' => $commissionDate,
            ]);

            // 3. this handle vehicle delivery commission for sales man
            $this->handleDeliveryVehicleCommission($order, $request, $commissionDate);

            Log::channel('commission_history')->info('------------ deliveryOrder ---------------');

            // 4. Combine requested date + current time
            // Example: request['order_date'] = '2025-10-24'

            // Update fields
            $order->delivered_date = $finalDateTime;

            $order->order_status = 'delivered'; // make sure this column exists in your orders table
            $order->save();


            // find the customer and update if customer have credit balance
            $customer = Customer::where('id', $order->customer_id)->first();

            if (! $customer) {
                return errorResponse(null, 'Customer not found', 100, 200);
            }

            $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                'date' => $finalDateTime ?? Carbon::now(),
                'amount' => $order->total_amount,
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
            if ($order->balance_amount > 0) {


                $customer->balance_amount = $customer->balance_amount + $order->balance_amount;
                $customer->save();

                $creditPayment = CreditPayment::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'amount' => $order->balance_amount,
                    'date' => $finalDateTime,
                ]);
            }

            DB::commit();

            return successResponse($order, 'Order delivered successfully', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    // sales man directly take order and delivery
    public function salesManDeliverWithInvoice($request)
    {

        DB::beginTransaction();
        try {
            // sales_man_user_id,order_id,payment_modes

            // 1. Find order

            $order = Order::with('orderItems.product')
                ->where('id', $request['order_id'])
                ->first();

            if (! $order) {
                return errorResponse(null, 'Order not found', 100, 200);
            }

            generateAutoCode($order, 'order_no', 'AI_ORDER_CODE');

            //find salesman type


            // 2. reduced stock item from vehicle
            $this->deductOrderItemsStock($order, $request);


            // 4. Combine requested date + current time
            // Example: request['order_date'] = '2025-10-24'
            if (! empty($request->order_date)) {
                // If request has date, combine with current time
                $requestedDate = $request->order_date;
                $currentTime = Carbon::now()->format('H:i:s');
                $finalDateTime = Carbon::parse($requestedDate . ' ' . $currentTime);
            } else {
                // Otherwise, take current date & time
                $finalDateTime = Carbon::now();
            }
            Log::channel('commission_history')->info('------------passed to handleSalesManPayments  ---------------');


            // 3. Handle payments (separate function)
            $this->handleSalesManPayments($order, $request, $finalDateTime);


            // Update fields
            $order->order_date = $finalDateTime;
            $order->delivered_date = $finalDateTime;
            // $order->payment_method=$request->payment_modes?? 'N/A';

            // 4. Mark order delivered
            $order->order_status = 'delivered';
            $order->delivered_by = $request['sales_man_user_id'];
            $order->save();

            DB::commit();

            return successResponse($order, 'Order invoiced & delivered successfully', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    private function handleSalesManPayments($order, $request, $requested_date = null)
    {


        if (empty($request['payment_modes'])) {
            return;
        }
        $customer = Customer::find($order->customer_id);


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
        $paymentBreakdown = []; // Track each payment mode separately
        $salesmanType = User::find($request->sales_man_user_id);
        $customerCreditBalance = 0;
        $total_refunded_amount = 0;
        $paymentStatus = 'Partial';

        if ($salesmanType->user_type != 'outside_salesman') {
            $jo = $this->journalEntryService->createSalesCreditEntry([
                'date' => $requested_date ?? Carbon::now(),
                'amount' => $order->total_amount,
                'reference_type' => 'App\\Models\\Order',
                'reference_id' => $order->id,
                'description' => 'Order - ' . $order->order_no,
                'customer_id' => $customer ? $customer->id : '',
                'customer_name' => $customer ? $customer->customer_name : '',

                'customer_type' => 'customer',
                'user_id' => $customer ? $customer->user_id : '',

                'customer_code' => $customer ? $customer->customer_code : null,
            ]);
            if (!$jo) {
                return errorResponse(null, 'Failed to create journal entry', 500);
            }
        }
        // if ($customer->balance_amount < 0) {
        //     $customer->balance_amount += $order->balance_amount + $order->total_refunded_amount;
        //     $customer->save();
        // }
        $remaining_total_amount = $order->balance_amount ?? 0;
        $totalPayment = 0;
        foreach ($request['payment_modes'] as $payment) {
            $paid = $payment['paid_amount'] ?? 0;
            $payment_mode = $payment['payment_mode'] ?? 'Cash';

            if (strtolower($payment_mode) !== 'credit') {
                // amount to apply to this order (don't exceed remaining total)
                $applied = min($paid, $remaining_total_amount);

                $remaining_total_amount -= $applied;
                $totalPayment += $applied;
                $order->paid_amount += $applied;
                $order->balance_amount -= $applied;
                $order->save();

                if ($applied > 0) {
                    $orderPayment = OrderPayment::create([
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'user_id' => $request['sales_man_user_id'],
                        'total_amount' => $order->total_amount,
                        'paid_amount' => $applied,
                        'balance_amount' => max(0, $remaining_total_amount),
                        'payment_mode' => $payment_mode,
                        'payment_date' => $requested_date,
                        'financial_year_id'  => $financialYear->id,
                    ]);


                    if ($salesmanType->user_type != 'outside_salesman') {

                        $ret1 = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $requested_date,
                            'amount' => $applied,
                            'reference_type' => 'App\\Models\\OrderPayment',
                            'reference_id' => $orderPayment->id,
                            'description' => 'Order Payment received - Order ' . $order->order_no,
                            'customer_id' => $customer ? $customer->id : '',
                            'customer_name' => $customer ? $customer->customer_name : '',
                            'payment_mode' => $payment_mode,
                            'user_id' => $customer ? $customer->user_id : '',
                            'customer_type' => 'customer',
                            'customer_code' => $customer ? $customer->customer_code : null,
                        ]);
                        if (!$ret1) {
                            return errorResponse(null, 'Failed to create journal entry', 500);
                        }
                    }
                }
                Log::channel('commission_history')->info('------------order payment created in handleSalesManPayments  ---------------');

                // if user overpays, keep the extra aside for later use
                $extra = $paid - $applied;
                if ($extra > 0) {
                    $totalPayment += $extra;
                    // Store extra by payment mode
                    $paymentBreakdown[] = [
                        'payment_mode' => $payment_mode,
                        'amount' => $extra,
                    ];
                }
                Log::channel('commission_history')->info('------------passing to createSalesPaymentEntry  ---------------');
                Log::channel('commission_history')->info('------------returned from createSalesPaymentEntry  ---------------');
            }
            // else {
            //     $amount = $payment['paid_amount'] ?? 0;
            //     if ($customer->balance_amount < 0) {

            //         $customerCreditBalance = $customer->balance_amount * (-1);
            //         if ($customerCreditBalance > $amount) {
            //             $total_refunded_amount = $amount;

            //             // $paymentStatus = 'Paid';
            //         } else {
            //             $total_refunded_amount = $customerCreditBalance;

            //             // $paymentStatus = 'Partial';
            //         }
            //         $customer->balance_amount += $total_refunded_amount;
            //         $customer->save();
            //     } else {
            //         $paymentStatus = 'Partial';
            //         // $total_refunded_amount = 0;
            //     }
            // }
        }

        // $order->update([
        //     'paid_amount' => min($totalPayment, $order->total_amount),
        //     'balance_amount' => max(0, $order->total_amount - min($totalPayment, $order->total_amount) - $order->total_refunded_amount),
        //     // 'payment_status' => $totalPayment >= $order->total_amount ? 'Paid' : 'Partial',
        //     // 'total_refunded_amount' => $total_refunded_amount,
        //     // 'payment_status' => $paymentStatus
        // ]);

        // if any balance remains, it must be credit
        if ($order->balance_amount > 0) {
            // CREDIT handling
            $creditPayment = CreditPayment::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'amount' => $order->balance_amount,
                'date' => $requested_date,
            ]);
            $paymentStatus = 'Partial';
            $customer->balance_amount += $order->balance_amount + $order->total_refunded_amount;

            $customer->save();
            $salesmanType = User::find($request->sales_man_user_id);
        } else {
            $paymentStatus = 'Paid';
        }
        $order->update([
            'payment_status' => $paymentStatus
        ]);
        $salesmanType = User::find($request->sales_man_user_id);
        if ($salesmanType->user_type != 'outside_salesman') {
            $this->handleSalesManCommission($order, $request, $requested_date);
        }
        //  Pass payment breakdown instead of single mode and extraPaid
        if (! empty($paymentBreakdown)) {
            $this->applyExtraPaymentToPreviousOrders(
                $order->customer_id,
                $request['sales_man_user_id'],
                $paymentBreakdown, //  Pass array of payment modes with amounts
                $requested_date
            );
        }
    }

    // this reduced items or quantity of stock from vehicle
    private function deductOrderItemsStock($order, $request)
    {
        $vehicleLoadStock = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])
            ->where('day_status', 'started')
            ->where('load_status', 'approved')
            ->latest('created_at')
            ->first();

        if (! $vehicleLoadStock) {
            DB::rollBack();

            return errorResponse(null, 'No active vehicle load stock found for salesman', 100, 200);
        }

        foreach ($order->orderItems as $item) {
            $stockItem = VehicleLoadStockItem::where('product_id', $item->product_id)
                ->where('vehicle_load_stock_id', $vehicleLoadStock->id)
                ->lockForUpdate()
                ->first();

            if (! $stockItem) {
                DB::rollBack();
                throw new Exception("No stock found for product_id: {$item->product->product_name}");
            }

            $total_qty = $item->quantity + $item->free_quantity;

            if ($stockItem->available_qty < $total_qty) {
                DB::rollBack();
                throw new Exception("Not enough stock for product_id: {$item->product->product_name}");
            }

            $stockItem->available_qty -= $total_qty;
            $stockItem->save();

            $salesmanType = User::find($request->sales_man_user_id);
            if ($salesmanType->user_type != 'outside_salesman') {
                $cost_price = StockOriginal::where('movement_type', 'in')
                    ->where('product_id', $item->product_id)
                    ->avg('cost_price');
            }
            // if (! empty($request->order_date)) {
            //     // If request has date, combine with current time
            //     $requestedDate = $request->order_date;
            //     $currentTime = Carbon::now()->format('H:i:s');
            //     $finalDateTime = Carbon::parse($requestedDate . ' ' . $currentTime);
            // } else {
            //     // Otherwise, take current date & time
            //     $finalDateTime = Carbon::now();
            // }
            if (! empty($request->delivered_date)) {
                // If request has date, combine with current time
                $requestedDate = $request->delivered_date;
                $currentTime = Carbon::now()->format('H:i:s');
                $finalDateTime = Carbon::parse($requestedDate . ' ' . $currentTime);
                $commissionDate = $finalDateTime->format('Y-m-d H:i:s');
            } else {
                // Otherwise, take current date & time
                $finalDateTime = Carbon::now();
                $commissionDate = $finalDateTime->format('Y-m-d H:i:s');
            }
            if ($salesmanType->user_type != 'outside_salesman') {
                StockOriginal::create([
                    'ref_id'       => $order->id,
                    'ref_no'       => $order->order_no,
                    'product_id'   => $item->product->id,
                    'quantity'     => $total_qty,
                    'cost_price' => $cost_price,
                    'movement_type' => 'out',
                    'reason'       => 'Sale',
                    'date' => $finalDateTime,
                    'created_by'   => $order->user_id,
                ]);
            }
        }

        // Deduct stock for each order item
        DeliveredOrder::create([
            'order_id' => $order->id,
            'created_by' => $request['sales_man_user_id'],
        ]);
    }

    // if pending payment of order, sales can pay the balance amount
    public function payPendingOrderAmount123($request)
    {
        try {
            DB::beginTransaction();

            $order = Order::with('orderItems')->where('id', $request->order_id)->first();

            if (! $order) {
                return errorResponse(null, 'Order Not Found', 100, 200);
            }

            $vehicle_load = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])->where('load_status', 'approved')
                ->where('day_status', 'started')
                ->latest()
                ->first();

            if (! $vehicle_load) {
                return errorResponse(null, 'Vehicle Not Loaded', 100, 200);
            }

            // check vehicle have that product qty or not
            foreach ($order->orderItems as $item) {
                $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (! $vehicle_load_stock_item) {
                    return errorResponse(null, 'Product Not Found In Vehicle', 100, 200);
                }

                if ($vehicle_load_stock_item->available_qty < $item['quantity']) {

                    return errorResponse(null, 'Product does not have enough stock in vehicle', 100, 200);
                } else {
                    // return errorResponse(null, "Product Not Found In Vehicle", 100, 200);
                }
            }

            // Format date (before commission calculation)
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
            if ($request['payment_amount'] > 0) {
                $order->balance_amount -= $request['payment_amount'];
                $order->paid_amount += $request['payment_amount'];

                if ($order->balance_amount <= 0) {

                    if ($order->order_by == $order->delivered_by) {
                        // this for sales_man
                        // $this->handleDeliveryVehicleCommission($order, $request);
                    } else {
                        // this for salesExecutive
                        $this->handlePendingPaymentCommission($order, $request, $requested_date);
                    }

                    $order->payment_status = 'Paid';
                } else {
                    $order->payment_status = 'Partial';
                }

                $order->save();

                OrderPayment::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'user_id' => $request['sales_man_user_id'],
                    'total_amount' => $order->total_amount ?? 0,
                    'paid_amount' => $request['payment_amount'] ?? 0,
                    'balance_amount' => $order->balance_amount ?? 0,
                    'payment_mode' => $request['payment_mode'] ?? 'Cash',
                    'payment_date' => $requested_date,
                    'financial_year_id'  => $financialYear->id,
                ]);
            }

            DB::commit();

            return successResponse($order, 'Payment recorded successfully', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function payPendingOrderAmount($request)
    {
        try {
            DB::beginTransaction();

            $order = Order::with('orderItems')->where('id', $request->order_id)->first();

            if (! $order) {
                return errorResponse(null, 'Order Not Found', 100, 200);
            }
            $customer = Customer::where('id', $order->customer_id)->first();
            $vehicle_load = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])
                ->where('load_status', 'approved')
                ->where('day_status', 'started')
                ->latest()
                ->first();

            if (! $vehicle_load) {
                return errorResponse(null, 'Vehicle Not Loaded', 100, 200);
            }

            // -----------------------------------------
            // VALIDATE STOCK
            // -----------------------------------------
            foreach ($order->orderItems as $item) {
                $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (! $vehicle_load_stock_item) {
                    return errorResponse(null, 'Product Not Found In Vehicle', 100, 200);
                }

                if ($vehicle_load_stock_item->available_qty < $item['quantity']) {
                    return errorResponse(null, 'Product does not have enough stock in vehicle', 100, 200);
                }
            }

            // =====================================================================
            //  DETECT REQUEST TYPE (OLD: payment_amount + payment_mode)
            //                        (NEW: payment_modes[])
            // =====================================================================
            $isMultiple = isset($request['payment_modes']) && is_array($request['payment_modes']);

            // Calculate total payment amount
            // if ($isMultiple) {
            //     $totalPayment = collect($request['payment_modes'])->sum('payment_amount');
            // } else {
            //     $totalPayment = $request['payment_amount'] ?? 0;
            // }

            $totalPayment = 0;

            // =====================================================================
            //  CALCULATE TOTAL PAYMENT (excluding Credit)
            // =====================================================================
            if ($isMultiple) {
                foreach ($request['payment_modes'] as $p) {
                    if (strtolower($p['payment_mode']) === 'credit') {
                        continue;
                    }
                    $totalPayment += $p['payment_amount'];
                }
            } else {
                if (isset($request['payment_mode']) && strtolower($request['payment_mode']) !== 'credit') {
                    $totalPayment = $request['payment_amount'];
                }
            }

            // Format date (before commission calculation)
            $requested_date = isset($request['date'])
                ? Carbon::parse($request['date'])->format('Y-m-d H:i:s')
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
            // =====================================================================
            //  PROCESS PAYMENT
            // =====================================================================
            if ($totalPayment > 0) {
                $runningBalance = $order->balance_amount;

                $order->balance_amount -= $totalPayment;
                $order->paid_amount += $totalPayment;


                // Full paid or partial update
                if ($order->balance_amount <= 0) {
                    $order->payment_status = 'Paid';
                } else {
                    $order->payment_status = 'Partial';
                }

                // Calculate commission
                if ($order->order_by == $order->delivered_by) {
                    // $this->handleDeliveryVehicleCommission($order, $request);
                } else {


                    $request['payment_amount'] = $totalPayment;

                    $this->handlePendingPaymentCommission($order, $request, $requested_date);
                }



                // =====================================================================
                //  INSERT PAYMENT RECORDS
                // =====================================================================


                $customer_type = $customer->is_outside == 0 ? 'inside' : 'outside';
                if ($isMultiple) {

                    // Insert multiple payment modes
                    foreach ($request['payment_modes'] as $p) {
                        $runningBalance -= $p['payment_amount'];

                        if (strtolower($p['payment_mode']) === 'credit') {
                            continue; // skip creating payment entry
                        }

                        $orderPayment = OrderPayment::create([
                            'order_id' => $order->id,
                            'customer_id' => $order->customer_id,
                            'user_id' => $request['sales_man_user_id'],
                            'total_amount' => $order->total_amount,
                            'paid_amount' => $p['payment_amount'],
                            'balance_amount' => $runningBalance,
                            'payment_mode' => $p['payment_mode'],
                            'payment_date' => $requested_date,
                            'financial_year_id'  => $financialYear->id,
                        ]);

                        // $remaining_total_amount = $order->total_amount ?? 0;
                        // $totalPayment = 0;
                        // $paid = $p['payment_amount'] ?? 0;
                        // $applied = min($paid, $remaining_total_amount);

                        // $remaining_total_amount -= $applied;
                        // $totalPayment += $applied;

                        // $order->paid_amount = $applied;
                        // $order->balance_amount = max(0, $remaining_total_amount);
                        // $order->payment_status = $totalPayment >= $order->total_amount ? "Paid" : "Partial";
                        // $order->save();

                        if ($customer_type == 'inside') {
                            $jo = $this->journalEntryService->createSalesPaymentEntry([
                                'date' => $requested_date,
                                'amount' => $p['payment_amount'],
                                'reference_type' => 'App\\Models\\LaterOrderPayment',
                                'reference_id' => $orderPayment->id,
                                'description' => 'Payment received - Order ' . $order->order_no,
                                'customer_id' => $customer ? $customer->id : '',
                                'customer_name' => $customer ? $customer->customer_name : '',
                                'payment_mode' => $p['payment_mode'],
                                'user_id' => $customer ? $customer->id : '',
                                'customer_type' => $customer_type,
                                'customer_code' => $customer ? $customer->customer_code : null,
                            ]);
                            if (!$jo) {
                                return errorResponse(null, 'Failed to create journal entry', 500);
                            }
                        }
                    }
                } else {

                    // OLD FORMAT (Backwards Compatible)
                    $orderPayment = OrderPayment::create([
                        'order_id' => $order->id,
                        'customer_id' => $order->customer_id,
                        'user_id' => $request['sales_man_user_id'],
                        'total_amount' => $order->total_amount,
                        'paid_amount' => $request['payment_amount'],
                        'balance_amount' => $order->balance_amount,
                        'payment_mode' => $request['payment_mode'],
                        'payment_date' => $requested_date,
                        'financial_year_id'  => $financialYear->id,

                    ]);

                    // $remaining_total_amount = $order->total_amount ?? 0;
                    // $totalPayment = 0;
                    // $paid = $p['payment_amount'] ?? 0;
                    // $applied = min($paid, $remaining_total_amount);

                    // $remaining_total_amount -= $applied;
                    // $totalPayment += $applied;

                    // $order->paid_amount = $applied;
                    // $order->balance_amount = max(0, $remaining_total_amount);
                    // $order->payment_status = $totalPayment >= $order->total_amount ? "Paid" : "Partial";
                    // $order->save();

                    if ($customer_type == 'inside') {
                        $jo = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $requested_date,
                            'amount' => $request['payment_amount'],
                            'reference_type' => 'App\\Models\\LaterOrderPayment',
                            'reference_id' => $orderPayment->id,
                            'description' => 'Payment received - Order ' . $order->order_no,
                            'customer_id' => $customer ? $customer->id : '',
                            'customer_name' => $customer ? $customer->customer_name : '',
                            'payment_mode' => $request['payment_mode'],
                            'user_id' => $customer ? $customer->id : '',
                            'customer_type' => $customer_type,
                            'customer_code' => $customer ? $customer->customer_code : null,
                        ]);
                        if (!$jo) {
                            return errorResponse(null, 'Failed to create journal entry', 500);
                        }
                    }
                }

                // $customer->balance_amount -= $totalPayment;
                // $customer->save();

                $order->save();
                DB::commit();
            }

            DB::commit();

            return successResponse($order, 'Payment recorded successfully', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    // Reject order by sales man
    public function rejectOrder($request)
    {
        try {
            DB::beginTransaction();

            // Validate request
            if (empty($request->order_id)) {
                return errorResponse(null, 'Order ID is required', 100, 400);
            }

            // Find the order
            $order = Order::find($request->order_id);

            if (! $order) {
                return errorResponse(null, 'Order not found', 100, 404);
            }

            // Check if order is already rejected
            if ($order->order_status == 'rejected') {
                return errorResponse(null, 'Order has already been rejected', 100, 400);
            }

            // Check if order is already delivered
            if ($order->order_status == 'delivered') {
                return errorResponse(null, 'Order has already been delivered', 100, 400);
            }

            // Check if sales executive collected payment for this order
            // If paid_amount > 0, order cannot be rejected
            if (! empty($order->paid_amount) && floatval($order->paid_amount) > 0) {
                DB::rollBack();

                return errorResponse(null, 'Cannot reject order. Payment has already been collected by sales executive.', 100, 400);
            }

            // Update order status to rejected
            $order->order_status = 'rejected';
            $order->save();

            DB::commit();

            return successResponse($order, 'Order rejected successfully', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function checkSalesManVehicleLoadHaveProductOrNot($request, $order)
    {
        try {

            if (! $order) {
                return errorResponse(null, 'failed to take order', 100, 200);
            }

            // check vehicle loaded or not for that
            $vehicle_load = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])->where('load_status', 'approved')
                ->where('day_status', 'started')
                ->latest()
                ->first();

            if (! $vehicle_load) {
                return errorResponse(null, 'Vehicle Not Loaded', 100, 200);
            }

            // check vehicle have that product qty or not
            foreach ($order->orderItems as $item) {
                $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (! $vehicle_load_stock_item) {
                    return errorResponse(null, 'Product Not Found In Vehicle', 100, 200);
                }

                // dd($vehicle_load_stock_item->available_qty < $item['quantity']);

                if ($vehicle_load_stock_item->available_qty < $item['quantity']) {

                    // dd("hgii");
                    return errorResponse(null, 'Product does not have enough stock in vehicle', 100, 200);
                } else {
                    // dd("Suiii");
                    // return errorResponse(null, "Product Not Found In Vehicle", 100, 200);
                }
            }

            // dd("final");
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
