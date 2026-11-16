<?php

namespace App\Services\salesExecutive;

use App\Models\AssignedSpecialPrice;
use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\DeliveredOrder;
use App\Models\FinancialYear;
use App\Models\Incentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Products;
use App\Models\SpecialPrice;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use App\Services\CommissionManagement\CommissionManagementService;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService extends CommissionManagementService
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }


    // this update product price
    public function updateProductPrice($request)
    {
        try {

            DB::beginTransaction();

            // 1. Check if special price exists for product & price
            $specialPrice = SpecialPrice::where('product_id', $request['product_id'])
                ->where('price', $request['price'])
                ->first();

            if (!$specialPrice) {
                // Create new special price
                $specialPrice = SpecialPrice::create([
                    'product_id' => $request['product_id'],
                    'price' => $request['price'],
                    'salesman_commission' => 0, // default or pass dynamically
                    'executive_commission' => 0,
                    'delivery_vehicle_commission' => 0,
                    'status' => 1,
                    // 'created_by' => $request['user_id'],
                ]);
            }

            // 2. Check if assigned special price exists for customer & product
            $assignedPrice = AssignedSpecialPrice::where('customer_id', $request['customer_id'])
                ->where('product_id', $request['product_id'])
                ->first();

            if ($assignedPrice) {
                // Delete old one (or update if you prefer)
                $assignedPrice->delete();
            }

            // 3. Create new assigned special price
            AssignedSpecialPrice::create([
                'customer_id' => $request['customer_id'],
                'product_id' => $request['product_id'],
                'special_price' => $request['price'],
                'special_price_id' => $specialPrice->id,
                'status' => 1,
                // 'created_by' => $request['user_id'],
            ]);

            DB::commit();

            return successResponse(null, "Product special price updated successfully", 0);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // this store invoice to draft
    public function storeInvoiceDraft($request)
    {
        try {
            DB::beginTransaction();


            $order = Order::create([
                "customer_id" => $request['customer_id'] ?? null,
                // "order_no" => "",
                "commission_type_id" => $request['commission_type_id'] ?? null,
                "order_status" => "draft",
            ]);

            if (!$order) {
                DB::rollBack();
                return errorResponse(null, "Failed to take order", 100, 200);
            }

            $order->order_no = $order->id;
            $order->save();

            // generateAutoCode($order, 'order_no', 'AI_ORDER_CODE');

            $response = $this->processOderItems($request, $order);

            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // this update existing invoice draft
    public function updateInvoiceDraft($request)
    {
        try {
            // fetch order
            $order = Order::where('id', $request['order_id'])->where('order_status', 'draft')->first();


            if (!$order) {
                return errorResponse(null, "Draft order not found", 100, 200);
            }


            if ($request->commission_type_id) {
                $order->commission_type_id = $request->commission_type_id;
                $order->save();
            }

            //  Clear existing items before re-adding
            $order->orderItems()->delete();


            $response = $this->processOderItems($request, $order);

            DB::commit();
            return $response;

            // return successResponse($order, 'Draft updated successfully', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function processOderItems($request, $order)
    {

        try {
            $total_amount = 0;
            $total_quantity = 0.0;

            foreach ($request['products'] as $item) {
                // skip this iteration  if qty is 0,
                if ($item['qty'] == 0 && $item['free_qty'] == 0) {
                    continue;
                }

                $product = Products::where('id', $item['product_id'])->first();


                if (!$product) {
                    DB::rollBack();
                    return errorResponse($order, "product not found", 100, 200);
                }

                if ($request['user_id']) {
                    $user = User::find($request['user_id']);

                    if ($user && in_array(strtolower($user->user_type), ['salesman', 'sales_man'])) {
                        // fetch latest vehicle for that user
                        $vehicle_load = VehicleLoadStock::where('user_id', $user->id)->where('load_status', 'approved')
                            ->where('day_status', 'started')
                            ->latest('id')   // or ->orderBy('created_at', 'desc')
                            ->first();

                        if ($vehicle_load) {

                            $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
                                ->where('product_id', $item['product_id'])
                                ->first();

                            if (!$vehicle_load_stock_item || $vehicle_load_stock_item->available_qty < $item['qty']) {
                                DB::rollBack();
                                return successResponse($order, "Product '{$product->product_name}' does not have enough stock in vehicle", 100, 200);
                            }
                        } else {
                            DB::rollBack();
                            return errorResponse(null, "Vehicle Not Loaded", 100, 200);
                        }
                    }
                }

                // if customer have special price that product, then take special price, otherwise take product price

                $assigned_product = AssignedSpecialPrice::where('customer_id', $request['customer_id'])->where('product_id', $item['product_id'])->where('status', 1)->orderBy('created_at', 'desc')->first();


                // $free_qty = 0;

                if ($assigned_product) {
                    $selling_price = $assigned_product->special_price;
                } else {
                    $selling_price = $product->selling_price;

                    // if ($item['qty'] >= 20) {
                    //     $free_qty = intdiv($item['qty'], 20);
                    // }
                }

                $logged_user = User::find($request['user_id']);

                if ($logged_user->user_type == 'outside_salesman') {

                    $selling_price = $item['selling_price'];
                }


                // total price of product
                $total_product_amount = $selling_price * $item['qty'];

                // total amount of all products
                $total_amount = $total_amount + $total_product_amount;

                // total quantity
                $total_quantity = $total_quantity + $item['qty'];

                OrderItem::create([
                    "order_id" => $order->id,
                    "product_id" => $item['product_id'],
                    "quantity" => $item['qty'] ?? 0,
                    "free_quantity" => $item['free_qty'] ?? 0,
                    "price" => $selling_price ?? 0,
                    "tax_percentage" => 0,
                    "tax_amount" => 0,
                    "total" => $total_product_amount ?? 0,
                ]);
            }

            // Update order after processing all products
            $customer = Customer::find($request['customer_id']);
            if (!$customer) {
                DB::rollBack();
                return errorResponse(null, 'Customer not found', 100, 200);
            }
            $logged_user = User::find($request['user_id']);
            if ($logged_user->user_type == 'outside_salesman') {
                $is_outside = 1;
            } else {
                $is_outside = 0;
            }
            $order->update([
                "total_amount" => $total_amount,
                "paid_amount" => 0,
                "balance_amount" => $total_amount,
                "total_quantity" => $total_quantity,
                "order_type" => $customer->customer_type,
                "order_by" => $request['user_id'],
                "is_outside" => $is_outside,

            ]);


            return successResponse($order, 'Draft updated successfully', 0, 200);
        } catch (Exception $th) {
            DB::rollBack();
            return errorResponse(null, $th->getMessage(), 100, 500);
        }
    }



    // this list invoice draft items list for taking invoice confirmation
    public function listInvoiceItems($request)
    {
        try {
            $order = Order::with(['orderItems.product', 'customer'])->where('id', $request['order_id'])->where('order_by', $request['user_id'])->first();

            if (!$order) {
                return errorResponse(null, 'Order not found', 100, 200);
            }

            // Format response
            $items = $order->orderItems->map(function ($item) {
                return [
                    "item_id" => $item->id,
                    "product_id" => $item->product_id,
                    "product_name" => $item->product->product_name ?? 'Unknown',
                    "quantity" => $item->quantity,
                    "free_quantity" => $item->free_quantity,
                    "price" => $item->price,
                    "tax_percentage" => $item->tax_percentage,
                    "tax_amount" => $item->tax_amount,
                    "total" => $item->total,
                ];
            });
            // dd($items);
            $customer = [
                "id" => $order->customer->id,
                "code" => $order->customer->customer_code,
                "name" => $order->customer->customer_name,
                "email" => $order->customer->email,
                "phone" => $order->customer->phone_number,
                "credit_balance_amount" => $order->customer->balance_amount,
            ];

            $response = [
                "order_id" => $order->id,
                "order_no" => $order->order_no,
                "customer_id" => $order->customer_id,
                "customer_details" => $customer,
                "total_quantity" => $order->total_quantity,
                "amount" => $order->total_amount,
                "credit_balance_amount" => $order->customer->balance_amount,
                // "total_amount" => $order->total_amount + $order->customer->balance_amount,
                // "credit_balance_amount" => 0,
                "total_amount" => $order->total_amount,
                "order_status" => $order->order_status,
                "order_items" => $items
            ];

            return successResponse($response, 'Order items fetched successfully', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // this change order_status draft to requested
    public function storeInvoiceRequest($request)
    {
        try {

            $order = Order::with('orderItems')->where('id', $request['order_id'])->first();

            if (!$order) {
                return errorResponse(null, "failed to take order", 100, 200);
            }


            generateAutoCode($order, 'order_no', 'AI_ORDER_CODE');


            // check vehicle loaded or not for that
            // $vehicle_load = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])->where('load_status', 'approved')
            //     ->where('day_status', 'started')
            //     ->latest()
            //     ->first();

            // if (!$vehicle_load) {
            //     return errorResponse(null, "Vehicle Not Loaded", 100, 200);
            // }

            // check vehicle have that product qty or not
            // foreach ($order->orderItems as $item) {
            //     $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
            //         ->where('product_id', $item['product_id'])
            //         ->first();

            //     if (!$vehicle_load_stock_item) {
            //         return errorResponse(null, "Product Not Found In Vehicle", 100, 200);
            //     }


            //     if ($vehicle_load_stock_item->available_qty < $item['quantity']) {
            //         return errorResponse(null, "Product does not have enough stock in vehicle", 100, 200);
            //     }
            // }



            // 4. Combine requested date + current time
            // Example: request['order_date'] = '2025-10-24'
            if ($request->order_date) {
                // If request has date, combine with current time
                $requestedDate = $request->order_date;
                $currentTime = Carbon::now()->format('H:i:s');
                $finalDateTime = Carbon::parse($requestedDate . ' ' . $currentTime);
            } else {
                // Otherwise, take current date & time
                $finalDateTime = Carbon::now();
            }


            // Update field

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

            $order->update([
                "order_date" => $finalDateTime,
                "order_status" => "requested",
                "delivered_by" => $request['sales_man_user_id'],
                "financial_year_id" => $financialYear->id,
            ]);

            // Handle payments (separate function)
            $this->handleSalesExecutivePayments($order, $request, $finalDateTime);
            $order->refresh();


            return successResponse($order, 'ordered successfully', 0, 200);
        } catch (Exception $e) {

            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function checkSalesManVehicleLoadHaveProductOrNot($request, $order)
    {
        try {

            if (!$order) {
                return errorResponse(null, "failed to take order", 100, 200);
            }

            // check vehicle loaded or not for that
            $vehicle_load = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])->where('load_status', 'approved')
                ->where('day_status', 'started')
                ->latest()
                ->first();

            if (!$vehicle_load) {
                return errorResponse(null, "Vehicle Not Loaded", 100, 200);
            }




            // check vehicle have that product qty or not
            foreach ($order->orderItems as $item) {
                $vehicle_load_stock_item = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicle_load->id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$vehicle_load_stock_item) {
                    return errorResponse(null, "Product Not Found In Vehicle", 100, 200);
                }

                // dd($vehicle_load_stock_item->available_qty < $item['quantity']);

                if ($vehicle_load_stock_item->available_qty < $item['quantity']) {

                    // dd("hgii");
                    return errorResponse(null, "Product does not have enough stock in vehicle", 100, 200);
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

    // this commission for sales executive who taking order

    private function handleSalesExecutivePayments($order, $request, $requested_date = null)
    {
        Log::info('Payment Modes', $request['payment_modes']);
        if (empty($request['payment_modes'])) {
            return;
        }


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

        $customer = Customer::find($order->customer_id);


        $customerCreditBalance = 0;
        $total_refunded_amount = 0;
        $paymentStatus = 'Partial';


        if ($customer->balance_amount < 0) {

            $customerCreditBalance = $customer->balance_amount * (-1);
            if ($customerCreditBalance > $order->balance_amount) {
                $total_refunded_amount = $order->balance_amount;
                $order->total_refunded_amount += $total_refunded_amount;
                $order->balance_amount -= $total_refunded_amount;

                $paymentStatus = 'Paid';
            } else {
                $total_refunded_amount = $customerCreditBalance;
                $order->total_refunded_amount += $total_refunded_amount;
                $order->balance_amount -= $total_refunded_amount;

                $paymentStatus = 'Partial';
            }

            $order->save();
        }
        $remaining_total_amount = $order->total_amount - $order->total_refunded_amount ?? 0;
        $totalPayment = 0;
        foreach ($request['payment_modes'] as $payment) {

            $paid = $payment['paid_amount'] ?? 0;
            $payment_mode = $payment['payment_mode'] ?? 'Cash';


            if (strtolower($payment_mode) !== 'credit') {
                // amount to apply to this order (don't exceed remaining total)
                $applied = min($paid, $remaining_total_amount);

                $remaining_total_amount -= $applied;
                $totalPayment += $applied;

                $orderPayment = OrderPayment::create([
                    'order_id'       => $order->id,
                    'customer_id'    => $order->customer_id,
                    'user_id'        => $request['user_id'],
                    'total_amount'   => $order->total_amount, // always actual order total
                    'paid_amount'    => $applied,             // applied only what fits
                    'balance_amount' => max(0, $remaining_total_amount),
                    'payment_mode'   => $payment_mode,
                    'payment_date'   => $requested_date ?? Carbon::now(),
                    'financial_year_id'  => $financialYear->id,
                ]);

                $order->paid_amount += $applied;
                $order->balance_amount -= max(0, $remaining_total_amount);
                $order->save();
                // $order->payment_status = $totalPayment >= $order->balance-amount ? "Paid" : "Partial";
                // $order->save();


                $jo1 = $this->journalEntryService->createSalesPaymentEntry([
                    'date' => $requested_date ?? Carbon::now(),
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
                // // dd($jo);
                if (!$jo1) {
                    return errorResponse(null, 'Failed to create journal entry', 500);
                }
                // if user overpays, keep the extra aside for later use
                $extra = $paid - $applied;
                if ($extra > 0) {
                    $totalPayment += $extra; // so we can apply later
                }
            }
            // else {
            //     $amount = $payment['paid_amount'] ?? 0;

            //     if ($customer->balance_amount < 0) {

            //         $customerCreditBalance = $customer->balance_amount * (-1);
            //         if ($customerCreditBalance > $amount) {
            //             $total_refunded_amount = $amount;
            //             $customer->balance_amount += $total_refunded_amount;

            //             $paymentStatus = 'Paid';
            //         } else {
            //             $total_refunded_amount = $customerCreditBalance;
            //             $customer->balance_amount += $amount;

            //             $paymentStatus = 'Partial';
            //         }

            //         // $customer->save();
            //     } else {
            //         $paymentStatus = 'Partial';
            //         // $total_refunded_amount = 0;
            //     }
            // }
        }

        // update current order
        // $order->update([
        //     'paid_amount'    => min($totalPayment, $order->total_amount),
        //     'balance_amount' => max(0, $order->total_amount - min($totalPayment, $order->total_amount)),
        //     // 'payment_status' => $totalPayment >= $order->total_amount ? "Paid" : "Partial",
        // ]);
        if ($order->balance_amount > 0) {
            $paymentStatus = 'Partial';
        } else {
            $paymentStatus = 'Paid';
        }
        $order->update([
            'payment_status' => $paymentStatus
        ]);

        // Calculate commission for current order
        $this->handleSalesExecutiveCommission($order, $request);


        // If extra paid, apply to previous orders
        if ((float)$totalPayment > (float)$order->total_amount - $total_refunded_amount) {

            $extraPaid = (float)$totalPayment - (float)$order->total_amount;
            $this->applyExtraPaymentToPreviousOrders(
                $order->customer_id,
                $request['user_id'],
                $extraPaid,
                $request['payment_modes'][0]
            );
        }
    }


    // private function handleSalesExecutivePayments($order, $request, $requested_date = null)
    // {
    //     if (empty($request['payment_modes'])) {
    //         return;
    //     }

    //     $remaining_total_amount = $order->total_amount ?? 0;
    //     $totalPayment = 0;
    //     $paymentBreakdown = []; // ✅ Track each payment mode separately

    //     $customer = Customer::find($order->customer_id);

    //     // $requested_date = isset($request['date'])
    //     //         ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
    //     //         : now();

    //     foreach ($request['payment_modes'] as $payment) {
    //         $paid = $payment['paid_amount'] ?? 0;
    //         $payment_mode = $payment['payment_mode'] ?? 'Cash';
    //         // Skip credit mode here (handled as balance)
    //         if ($payment_mode != 'credit') {


    //             // if (strtolower($payment_mode) !== 'credit') {
    //             // amount to apply to this order (don't exceed remaining total)
    //             $applied = min($paid, $remaining_total_amount);

    //             $remaining_total_amount -= $applied;
    //             $totalPayment += $applied;

    //             if ($applied > 0) {
    //                 $orderPayment = OrderPayment::create([
    //                     'order_id'       => $order->id,
    //                     'customer_id'    => $order->customer_id,
    //                     'user_id'        => $request['user_id'],
    //                     'total_amount'   => $order->total_amount,
    //                     'paid_amount'    => $applied,
    //                     'balance_amount' => max(0, $remaining_total_amount),
    //                     'payment_mode'   => $payment_mode,
    //                     'payment_date'   => $requested_date
    //                 ]);
    //             }

    //             // if user overpays, keep the extra aside for later use
    //             $extra = $paid - $applied;
    //             if ($extra > 0) {
    //                 $totalPayment += $extra;
    //                 // Store extra by payment mode
    //                 $paymentBreakdown[] = [
    //                     'payment_mode' => $payment_mode,
    //                     'amount' => $extra
    //                 ];
    //             }
    //             // }


    //             // Create journal entry for payment received
    //             $this->journalEntryService->createSalesPaymentEntry([
    //                 'date' => $requested_date,
    //                 'amount' => $applied,
    //                 'reference_type' => 'App\\Models\\OrderPayment',
    //                 'reference_id' => $orderPayment->id,
    //                 'description' => 'Payment received - Order ' . $order->order_no,
    //                 'customer_id' => $customer ? $customer->id : '',
    //                 'customer_name' => $customer ? $customer->customer_name : '',
    //                 'payment_mode' => $payment_mode,
    //                 'user_id' => $request['user_id'],

    //                 'customer_code' => $customer ? $customer->customer_code : null,
    //             ]);
    //         } else {
    //             // CREDIT handling
    //             CreditPayment::create([
    //                 'order_id'    => $order->id,
    //                 'customer_id' => $order->customer_id,
    //                 'amount'      => $paid,
    //             ]);

    //             $customer->balance_amount = $paid;
    //             // $customer->balance_amount += $paid;
    //             $customer->save();
    //         }
    //     }

    //     // update current order
    //     $order->update([
    //         'paid_amount'    => min($totalPayment, $order->total_amount),
    //         'balance_amount' => max(0, $order->total_amount - min($totalPayment, $order->total_amount)),
    //         'payment_status' => $totalPayment >= $order->total_amount ? "Paid" : "Partial",
    //     ]);

    //     // if any balance remains, it must be credit
    //     if ($order->balance_amount > 0) {
    //         // CreditPayment::create([
    //         //     'order_id'    => $order->id,
    //         //     'customer_id' => $order->customer_id,
    //         //     'amount'      => $order->balance_amount,
    //         // ]);

    //         // $customer->balance_amount += $order->balance_amount;
    //         // $customer->save();
    //     }

    //     // Calculate commission for current order
    //     $this->handleSalesExecutiveCommission($order, $request, $requested_date);

    //     // Pass payment breakdown instead of single mode
    //     if (!empty($paymentBreakdown)) {
    //         $this->applyExtraPaymentToPreviousOrders(
    //             $order->customer_id,
    //             $request['user_id'],
    //             $paymentBreakdown,
    //             $requested_date
    //         );
    //     }
    // }
    private function handleSalesExecutivePayments123($order, $request)
    {
        if (empty($request['payment_modes'])) {
            return;
        }

        $remaining_total_amount = $order->total_amount ?? 0;
        $totalPayment = 0;


        $customer = Customer::find($order->customer_id);

        $requested_date = isset($request['date'])
            ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
            : now();

        foreach ($request['payment_modes'] as $payment) {
            $paid = $payment['paid_amount'] ?? 0;
            $payment_mode = $payment['payment_mode'] ?? 'Cash';

            if (strtolower($payment_mode) !== 'credit') {
                // amount to apply to this order (don't exceed remaining total)
                $applied = min($paid, $remaining_total_amount);

                $remaining_total_amount -= $applied;
                $totalPayment += $applied;

                OrderPayment::create([
                    'order_id'       => $order->id,
                    'customer_id'    => $order->customer_id,
                    'user_id'        => $request['user_id'],
                    'total_amount'   => $order->total_amount, // always actual order total
                    'paid_amount'    => $applied,             // applied only what fits
                    'balance_amount' => max(0, $remaining_total_amount),
                    'payment_mode'   => $payment_mode,
                    'payment_date'   => $requested_date ?? Carbon::now(),

                ]);

                // if user overpays, keep the extra aside for later use
                $extra = $paid - $applied;
                if ($extra > 0) {
                    $totalPayment += $extra; // so we can apply later
                }
            }
        }

        // update current order
        $order->update([
            'paid_amount'    => min($totalPayment, $order->total_amount),
            'balance_amount' => max(0, $order->total_amount - min($totalPayment, $order->total_amount)),
            'payment_status' => $totalPayment >= $order->total_amount ? "Paid" : "Partial",
        ]);

        // if any balance remains, it must be credit
        if ($order->balance_amount > 0) {
            // CREDIT handling
            $creditPayment =  CreditPayment::create([
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'amount'      => $order->balance_amount,
                'date'        => $requested_date,
            ]);

            $customer->balance_amount += $order->balance_amount;
            $customer->save();

            // $jo = $this->journalEntryService->createSalesEntry([
            //     'date' => $requested_date,
            //     'amount' => $order->balance_amount,
            //     'reference_type' => 'App\\Models\\CreditPayment',
            //     'reference_id' => $creditPayment->id,
            //     'description' => 'Credit Payment received - Order ' . $order->order_no,
            //     'customer_id' => $customer ? $customer->id : '',
            //     'customer_name' => $customer ? $customer->customer_name : '',
            //     'payment_mode' => $payment_mode,
            //     'customer_type' => 'customer',
            //     'user_id' => $customer ? $customer->user_id : '',

            //     'customer_code' => $customer ? $customer->customer_code : null,
            // ]);
            // if (!$jo) {
            //     return errorResponse(null, 'Failed to create journal entry', 500);
            // }
        }




        // Calculate commission for current order
        $this->handleSalesExecutiveCommission($order, $request, $requested_date);

        // If extra paid, apply to previous orders
        if ($totalPayment > $order->total_amount) {

            $extraPaid = $totalPayment - $order->total_amount;
            $this->applyExtraPaymentToPreviousOrdersSinglePayment(
                $order->customer_id,
                $request['user_id'],
                $extraPaid,
                $request['payment_modes'][0],
                $requested_date
            );
        }
    }






    // calculate commission, both paid and partial payment
    // private function handleSalesExecutiveCommission($order, $request)
    // {

    //     $orderItems = $order->orderItems;
    //     foreach ($orderItems as $item) {
    //         $commissionMaster = CommissionMaster::where('user_id', $request['user_id'])
    //             ->where('product_id', $item->product_id)
    //             ->first();

    //         // if customer have special price that product, then take special price, otherwise take product price
    //         $assigned_product = AssignedSpecialPrice::with('specialPrice')->where('customer_id', $request['customer_id'])->where('product_id', $item['product_id'])->orderBy('created_at', 'desc')->first();

    //         if ($assigned_product) {
    //             $commissionAmount = $item->quantity * $assigned_product->specialPrice->executive_commission;
    //         } else {
    //             $commissionAmount = $item->quantity * $item->product->executive_commission;
    //         }

    //         if ($commissionMaster) {
    //             $commissionMaster->update([
    //                 'commission_amount' => $commissionMaster->commission_amount + $commissionAmount,
    //                 'updated_by' => $request['user_id'],
    //             ]);
    //         } else {
    //             $commissionMaster =  CommissionMaster::create([
    //                 'user_id' => $request['user_id'],
    //                 'product_id' => $item->product_id,
    //                 'commission_amount' => $commissionAmount,
    //                 'created_by' => $request['user_id'],
    //             ]);
    //         }

    //         // Update Incentives Table (total per user)
    //         $incentive = Incentive::where('user_id', $request['user_id'])->first();

    //         if ($incentive) {
    //             $incentive->update([
    //                 'total_commission' => $incentive->total_commission + $commissionAmount,
    //             ]);
    //         } else {
    //             Incentive::create([
    //                 'user_id' => $request['user_id'],
    //                 'total_commission' => $commissionAmount,
    //             ]);
    //         }


    //         CommissionHistory::create([
    //             'user_id' => $request['user_id'],
    //             'commision_master_id' => $commissionMaster->id,
    //             'product_id' => $item->product_id,
    //             'product_quantity' => $item->quantity,
    //             'product_price' => $item->price,
    //             'total_amount' => $item->total,
    //             'commission_amount' => $commissionAmount,
    //             'created_by' => $request['user_id'],
    //         ]);
    //     }
    // }


    // private function handleSalesExecutiveCommission($order, $request)
    // {
    //     $orderItems = $order->orderItems;

    //     foreach ($orderItems as $item) {

    //         // if customer has special price for that product
    //         $assigned_product = AssignedSpecialPrice::with('specialPrice')
    //             ->where('customer_id', $request['customer_id'])
    //             ->where('product_id', $item['product_id'])
    //             ->orderBy('created_at', 'desc')
    //             ->first();

    //         if ($assigned_product) {
    //             $commissionRate = $assigned_product->specialPrice->executive_commission;
    //         } else {
    //             $commissionRate = $item->product->executive_commission;
    //         }

    //         //  Base commission amount (full order)
    //         $commissionAmount = $item->quantity * $commissionRate;

    //         //  If order is partial, recalculate proportionally
    //         if (strtolower($order->payment_status) === 'partial' && $order->total_amount > 0) {
    //             $paidRatio = $order->paid_amount / $order->total_amount; // e.g., 50 / 100 = 0.5
    //             $commissionAmount = $commissionAmount * $paidRatio;
    //         }


    //         // $commissionMaster = CommissionMaster::where('user_id', $request['user_id'])
    //         //     ->where('product_id', $item->product_id)
    //         //     ->first();

    //         // // Update CommissionMaster
    //         // if ($commissionMaster) {
    //         //     $commissionMaster->update([
    //         //         'commission_amount' => $commissionMaster->commission_amount + $commissionAmount,
    //         //         'updated_by' => $request['user_id'],
    //         //     ]);
    //         // } else {
    //         //     $commissionMaster = CommissionMaster::create([
    //         //         'user_id' => $request['user_id'],
    //         //         'product_id' => $item->product_id,
    //         //         'commission_amount' => $commissionAmount,
    //         //         'created_by' => $request['user_id'],
    //         //     ]);
    //         // }

    //         // // Update Incentives Table (total per user)
    //         // $incentive = Incentive::where('user_id', $request['user_id'])->first();

    //         // if ($incentive) {
    //         //     $incentive->update([
    //         //         'total_commission' => $incentive->total_commission + $commissionAmount,
    //         //     ]);
    //         // } else {
    //         //     Incentive::create([
    //         //         'user_id' => $request['user_id'],
    //         //         'total_commission' => $commissionAmount,
    //         //     ]);
    //         // }

    //         // // Commission history
    //         // CommissionHistory::create([
    //         //     'user_id' => $request['user_id'],
    //         //     'commision_master_id' => $commissionMaster->id,
    //         //     'product_id' => $item->product_id,
    //         //     'product_quantity' => $item->quantity,
    //         //     'product_price' => $item->price,
    //         //     'total_amount' => $item->total,
    //         //     'commission_amount' => $commissionAmount,
    //         //     'created_by' => $request['user_id'],
    //         // ]);

    //         $this->saveDataToCommissionMaster($item, $commissionAmount, $request['user_id']);
    //     }
    // }


    public function getOrdersReport($request)
    {
        try {

            $perPage = $request->per_page ?? 25;
            $userType = strtolower($request->user_type);

            // Salesman + Outside Salesman
            if (in_array($userType, ['salesman', 'sales_man', 'outside_salesman'])) {

                $query = DeliveredOrder::with([
                    'order.orderItems.product',
                    'order.customer'
                ])->where('created_by', $request->user_id)
                    ->whereHas('order');

                // Customer Filter
                if (!empty($request->customer_id)) {
                    $query->whereHas('order', function ($q) use ($request) {
                        $q->where('customer_id', $request->customer_id);
                    });
                }

                // Date Filter
                if (!empty($request->start_date) && !empty($request->end_date)) {
                    $query->whereBetween('created_at', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59'
                    ]);
                }

                $orders = $query->paginate($perPage);

                $response = $orders->through(function ($deliveredOrder) {

                    // if (!$deliveredOrder->order) {
                    //     return null;
                    // }

                    $deliveredOrder->delivered_date =
                        $deliveredOrder->order->delivered_date
                        ? Carbon::parse($deliveredOrder->order->delivered_date)->format('d M Y h:i a')
                        : optional($deliveredOrder->order->created_at)->format('d M Y h:i a');

                    return $deliveredOrder;
                })->filter()->values();
            } else {

                $query = Order::with([
                    'orderItems.product',
                    'orderBy',
                    'deliveryBy',
                    'customer'
                ])
                    ->where('order_status', '!=', 'draft')
                    ->where('order_by', $request->user_id)
                    ->where('customer_id', '!=', $request->user_id)
                    ->latest();

                $this->applyFilters($query, $request);

                if (!empty($request->order_status)) {
                    $query->where('order_status', $request->order_status);
                }

                $orders = $query->paginate($perPage);

                $response = $orders->through(function ($order) {

                    $orderItems = $order->orderItems->map(function ($item) {

                        return [
                            "item_id" => $item->id,
                            "product_id" => $item->product_id,
                            "product_name" => $item->product->product_name ?? 'Unknown',
                            "quantity" => $item->quantity,
                            "free_quantity" => $item->free_quantity,
                            "price" => $item->price,
                            "tax_percentage" => $item->tax_percentage,
                            "tax_amount" => $item->tax_amount,
                            "total" => $item->total,
                        ];
                    });

                    $customer = [
                        "id" => $order->customer?->id,
                        "code" => $order->customer?->customer_code,
                        "name" => $order->customer?->customer_name,
                        "email" => $order->customer?->email,
                        "phone" => $order->customer?->phone_number,
                        "credit_balance_amount" => $order->customer?->balance_amount,
                    ];

                    $delivery_by_details = [
                        "id" => $order->deliveryBy->id,
                        "name" => $order->deliveryBy->name,
                        "username" => $order->deliveryBy->username,
                        "email" => $order->deliveryBy->email,
                        "phone" => $order->deliveryBy->phone,
                        "user_type" => $order->deliveryBy->user_type,
                    ];

                    $order_by_details = [
                        "id" => $order->orderBy->id,
                        "name" => $order->orderBy->name,
                        "username" => $order->orderBy->username,
                        "email" => $order->orderBy->email,
                        "phone" => $order->orderBy->phone,
                        "user_type" => $order->orderBy->user_type,
                    ];

                    $orderDate = !empty($order->order_date)
                        ? Carbon::parse($order->order_date)->format('d M Y h:i a')
                        : optional($order->created_at)->format('d M Y h:i a');

                    return [
                        "order_id" => $order->id,
                        "order_no" => $order->order_no,
                        "total_quantity" => $order->total_quantity,
                        "total_amount" => $order->total_amount,
                        "payment_status" => $order->payment_status,
                        "order_status" => $order->order_status,
                        "order_date" => $orderDate,
                        "customer_id" => $order->customer_id,
                        "customer_details" => $customer,
                        "order_by" => $order->order_by,
                        "delivery_by" => $order->delivered_by,
                        "order_items" => $orderItems
                    ];
                });
            }

            return successResponse($response, 'Order items fetched successfully', 0, 200);
        } catch (Exception $e) {

            return errorResponse([
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], $e->getMessage(), 100, 500);
        }
    }


    // public function getOrdersReport($request)
    // {
    //     try {


    //         $perPage = $request->per_page ?? 25;


    //         $query = Order::with(['orderItems.product', 'orderBy', 'deliveryBy', 'customer'])
    //             ->where('order_status', '!=', 'draft')
    //             ->where('order_by', $request['user_id'])
    //             ->latest();

    //         $orders = $this->applyFilters($query, $request);

    //         if (!empty($request['order_status'])) {
    //             $query->where('order_status', $request['order_status']);
    //         }

    //         $orders = $query->paginate($perPage);

    //         // Format response
    //         $response = $orders->map(function ($order) {

    //             $order_items = $order->orderItems->map(function ($item) {
    //                 return [
    //                     "item_id" => $item->id,
    //                     "product_id" => $item->product_id,
    //                     "product_name" => $item->product->product_name ?? 'Unknown',
    //                     "quantity" => $item->quantity,
    //                     "free_quantity" => $item->free_quantity,
    //                     "price" => $item->price,
    //                     "tax_percentage" => $item->tax_percentage,
    //                     "tax_amount" => $item->tax_amount,
    //                     "total" => $item->total,
    //                 ];
    //             });

    //             $customer = [
    //                 "id" => $order->customer->id,
    //                 "code" => $order->customer->customer_code,
    //                 "name" => $order->customer->customer_name,
    //                 "email" => $order->customer->email,
    //                 "phone" => $order->customer->phone_number,
    //                 "credit_balance_amount" => $order->customer->balance_amount,
    //             ];

    //             $delivery_by_details = [
    //                 "id" => $order->deliveryBy->id,
    //                 "name" => $order->deliveryBy->name,
    //                 "username" => $order->deliveryBy->username,
    //                 "email" => $order->deliveryBy->email,
    //                 "phone" => $order->deliveryBy->phone,
    //                 "user_type" => $order->deliveryBy->user_type,
    //             ];

    //             $order_by_details = [
    //                 "id" => $order->orderBy->id,
    //                 "name" => $order->orderBy->name,
    //                 "username" => $order->orderBy->username,
    //                 "email" => $order->orderBy->email,
    //                 "phone" => $order->orderBy->phone,
    //                 "user_type" => $order->orderBy->user_type,
    //             ];


    //             $order_date = !empty($order->order_date)
    //                 ? Carbon::parse($order->order_date)->format('d M Y h:i a')
    //                 : Carbon::parse($order->created_at)->format('d M Y h:i a');

    //             return [
    //                 "order_id" => $order->id,
    //                 "order_no" => $order->order_no,
    //                 "total_quantity" => $order->total_quantity,
    //                 "total_amount" => $order->total_amount,
    //                 "order_status" => $order->order_status,
    //                 // "order_date" => $order->created_at ? $order->created_at->format('d M Y h:i a') : null,
    //                 "order_date" => $order_date ?? null,


    //                 "customer_id" => $order->customer_id,
    //                 "customer_details" => $customer,
    //                 "order_by" => $order->order_by,
    //                 // "order_by_details"   => $order_by_details,
    //                 "delivery_by" => $order->delivered_by,
    //                 // "delivery_by_details"   => $delivery_by_details,
    //                 "order_items" => $order_items
    //             ];
    //         });



    //         return successResponse($response, 'Order items fetched successfully', 0, 200);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    private function applyFilters($query, $request)
    {
        // Sales Executive filter
        if (!empty($request->sales_executive_user_id)) {
            $query->where('order_by', $request->sales_executive_user_id);
        }

        // Sales Man filter
        if (!empty($request->sales_man_user_id)) {
            $query->where('delivered_by', $request->sales_man_user_id);
        }


        if (!empty($request->only_not_delivered) && $request->only_not_delivered == true) {
            $query->where('order_status', 'requested');
        }



        // Customer filter
        if (!empty($request->customer_id)) {
            $query->where('customer_id', $request->customer_id);
        }

        // Date Range filter
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            // $query->whereBetween('order_date', [$start, $end]);
            $query = applyDateFilter($query, $start, $end, 'order_date');
        }

        // Limit
        if (!empty($request->limit) && is_numeric($request->limit)) {
            $query->take((int) $request->limit);
        }


        return $query->get();
    }
}
