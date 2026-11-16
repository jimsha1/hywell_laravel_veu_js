<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\CommissionTypeItem;
use App\Models\CommisionTypeMaster;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\Incentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Products;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnPayment;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\User;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use App\Models\VehicleLoadItemDetail;
use App\Services\CommissionManagement\CommissionManagementService;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class SalesReturnService extends CommissionManagementService
{
    protected JournalEntryService $journalEntryService;

    protected StockService $stockService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
        $this->stockService = app(StockService::class);
    }


    /**
     * Create a sales return record (warehouse step).
     * This only creates SalesReturn and SalesReturnItems and increments returned_quantity
     * and does NOT change order totals or order_item.quantity.
     */


    public function createSalesReturn($data)
    {
        try {
            DB::beginTransaction();

            Log::channel('sales_return')->info('---------------- createSalesReturn --------------');
            Log::channel('sales_return')->info(
                'creating sales return',
                [
                    'order_id' => $data->order_id,
                    'user_id' => $data->user_id,
                    'return_items' => $data->return_items
                ]
            );

            // 1) Validate order & status
            $order = Order::with('orderItems')->lockForUpdate()->find($data->order_id);
            if (!$order) {
                Log::channel('sales_return')->info('Order not found', ['order_id' => $data->order_id]);
                DB::rollBack();
                return errorResponse(null, 'Order not found.', 404, 404);
            }

            if ($order->order_status !== 'delivered') {
                DB::rollBack();
                Log::channel('sales_return')->info('Only delivered orders can be returned', ['order_id' => $data->order_id]);
                return errorResponse(null, 'Only delivered orders can be returned.', 400, 400);
            }

            if (SalesReturn::where('order_id', $order->id)->whereNotIn('return_status', ['approved', 'rejected'])->exists()) {
                DB::rollBack();
                Log::channel('sales_return')->info('Please Accept or Reject the Existing Sales return of this order', ['order_id' => $data->order_id]);
                return errorResponse(null, 'Please Accept or Reject the Existing Sales return of this order.', 400, 400);
            }

            // 2) Auth check (prefer token-based auth; this preserves your password check)
            if (isset($data->user_id) && isset($data->password)) {
                $user = User::find($data->user_id);
                if (!$user || !Hash::check($data->password, $user->password)) {
                    DB::rollBack();
                    return errorResponse(null, 'Invalid password.', 401, 401);
                }
            }

            $returnItems = json_decode($data->return_items, true) ?? [];
            if (empty($returnItems)) {
                Log::channel('sales_return')->info('No items to return', ['data' => $data]);
                DB::rollBack();
                return errorResponse(null, 'No items to return.', 400, 400);
            }

            // 3) Validate each item, compute totals but DO NOT mutate order totals
            $totalQty = 0;
            $totalAmount = 0;
            $validatedItems = [];

            foreach ($returnItems as $item) {
                $orderItem = OrderItem::with('product')->where('id', $item['order_item_id'])->lockForUpdate()->first();
                if (!$orderItem) {
                    DB::rollBack();
                    return errorResponse(null, 'Order item not found.', 404, 404);
                }

                // Ensure product matches
                if (isset($item['product_id']) && $item['product_id'] != $orderItem->product_id) {
                    DB::rollBack();
                    return errorResponse(null, 'Product mismatch for order item ID ' . $orderItem->id, 400, 400);
                }

                $returnQty = (int)$item['return_quantity'];
                $returnFreeQty = (int)($item['free_quantity'] ?? 0);

                if ($returnQty <= 0 && $returnFreeQty <= 0) {
                    DB::rollBack();
                    return errorResponse(null, 'Return quantity or free quantity must be > 0', 400, 400);
                }

                // Already returned (approved) qty
                $alreadyReturned = (int)$orderItem->returned_quantity; // we track returned_quantity on order_items
                $maxReturnable = $orderItem->quantity - $alreadyReturned;

                if ($returnQty > $maxReturnable) {
                    DB::rollBack();
                    $productName = $orderItem->product ? $orderItem->product->product_name : 'Product ID ' . $orderItem->product_id;
                    return errorResponse(null, "Return quantity ({$returnQty}) cannot exceed ordered quantity ({$orderItem->quantity}) for {$productName}. Maximum returnable: {$maxReturnable}.", 400, 400);
                }

                // Validate free quantity
                $maxReturnableFree = (int)$orderItem->free_quantity;
                if ($returnFreeQty > $maxReturnableFree) {
                    DB::rollBack();
                    $productName = $orderItem->product ? $orderItem->product->product_name : 'Product ID ' . $orderItem->product_id;
                    return errorResponse(null, "Return free quantity ({$returnFreeQty}) cannot exceed free quantity ({$maxReturnableFree}) for {$productName}.", 400, 400);
                }

                $price = (float)$orderItem->price;
                $lineTotal = $returnQty * $price;

                $totalQty += $returnQty;
                $totalAmount += $lineTotal;

                $validatedItems[] = [
                    'order_item' => $orderItem,
                    'product_id' => $orderItem->product_id,
                    'return_quantity' => $returnQty,
                    'free_quantity' => $returnFreeQty,
                    'price' => $price,
                    'total' => $lineTotal,
                ];
            }

            // 4) Determine return type (full or partial) using original order totals (no mutation)
            $orderTotalQty = $order->orderItems->sum('quantity');
            $returnType = ($totalQty >= $orderTotalQty) ? 'full' : 'partial';

            // $financialYear = FinancialYear::whereDate('start_date', '<=', $data->return_date)
            //     ->whereDate('end_date', '>=', $data->return_date)
            //     ->first();

            // if (!$financialYear) {
            //     // throw new Exception('No financial year found for the selected date.');
            //     DB::rollBack();
            //     return errorResponse(null, 'No financial year found for the selected date.', 500);
            // }
            // if ($financialYear->is_closed == 1) {
            //     DB::rollBack();
            //     return errorResponse(null, 'Financial year closed for the selected date.', 500);
            // }

            // 5) Create the SalesReturn parent (status pending)
            $salesReturn = SalesReturn::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'return_no' => null, // generate later (finance) or use helper
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
                'return_status' => 'pending', // pending (warehouse created)
                'return_type' => $returnType,
                'return_reason' => $data->return_reason ?? null,
                'returned_by' => $data->returned_by ?? $data->user_id ?? null,
                'return_date' => isset($data->return_date) ? Carbon::parse($data->return_date) : Carbon::now(),
            ]);

            generateAutoCode($salesReturn,  'return_no', 'AI_SALES_RETURN_CODE');

            // 6) Create SalesReturnItems and increment order_items.returned_quantity
            foreach ($validatedItems as $it) {
                $orderItem = $it['order_item'];
                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'order_item_id' => $orderItem->id,
                    'product_id' => $it['product_id'],
                    'return_quantity' => $it['return_quantity'],
                    'free_quantity' => $it['free_quantity'] ?? 0,
                    'price' => $it['price'],
                    'total' => $it['total'],
                ]);
            }

            Log::channel('sales_return')->info('Sales return created successfully', ['sales_return_id' => $salesReturn->id]);

            DB::commit();
            return successResponse($salesReturn->load('returnItems', 'order', 'customer'), 'Sales return created successfully.', 0, 200);
        } catch (Exception $e) {

            Log::channel('sales_return')->error('Error creating sales return', ['error' => $e->getMessage()]);
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Approve sales return (finance step). This approves the return,
     * restores stock (vehicle/godown), creates refund payments (if requested),
     * creates stock movements, and updates optional summary fields.
     */
    // public function approveSalesReturn($data)
    // {
    //     try {
    //         DB::beginTransaction();

    //         Log::channel('sales_return')->info('---------------- approveSalesReturn --------------');
    //         Log::channel('sales_return')->info('approving sales return', [
    //             'return_id' => $data->return_id,
    //             'user_id' => $data->user_id,
    //             'return_destination' => $data->return_destination,
    //             'salesman_id' => $data->salesman_id,
    //             'refund_rows' => $data->refund_rows
    //         ]);



    //         $salesReturn = SalesReturn::with('returnItems', 'order')->lockForUpdate()->find($data->return_id);
    //         if (!$salesReturn) {
    //             DB::rollBack();
    //             Log::channel('sales_return')->info('Sales return not found', ['return_id' => $data->return_id]);
    //             return errorResponse(null, 'Sales return not found.', 404, 404);
    //         }

    //         if ($salesReturn->return_status === 'approved') {
    //             DB::rollBack();
    //             return errorResponse(null, 'Return already approved.', 400, 400);
    //         }

    //         // check the  order alreadey retunred once then block the apporval
    //         $order = Order::with('orderItems')->lockForUpdate()->find($salesReturn->order_id);
    //         // if ($order->is_returned == 1) {
    //         //     DB::rollBack();
    //         //     return errorResponse(null, 'Sales already returned.', 404, 404);
    //         // }

    //         // Password / permission check (prefer token-based permission)
    //         $user = User::find($data->user_id);
    //         if (!$user || !Hash::check($data->password, $user->password)) {
    //             DB::rollBack();
    //             return errorResponse(null, 'Invalid password.', 401, 401);
    //         }

    //         $returnDestination = strtolower($data->return_destination ?? 'godown');

    //         $salesmanId = $data->salesman_id ?? null;
    //         if ($returnDestination === 'vehicle' && !$salesmanId) {
    //             DB::rollBack();
    //             return errorResponse(null, 'Salesman is required when return destination is vehicle.', 400, 400);
    //         }

    //         // If vehicle: find vehicle load stock row (lock for update)
    //         $vehicleLoadStock = null;
    //         if ($returnDestination === 'vehicle') {
    //             $vehicleLoadStock = VehicleLoadStock::where('user_id', $salesmanId)
    //                 ->where('load_status', 'approved')
    //                 ->where('day_status', 'started')
    //                 ->orderBy('created_at', 'desc')
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (!$vehicleLoadStock) {
    //                 DB::rollBack();
    //                 return errorResponse(null, 'No active vehicle load stock found for the salesman.', 404, 404);
    //             }
    //         }

    //         // Approve return
    //         $salesReturn->update([
    //             'return_status' => 'approved',
    //             'approved_by' => $data->user_id,
    //             'approved_date' => Carbon::now(),
    //             'return_destination' => $returnDestination,
    //             'salesman_id' => $salesmanId,
    //             'return_no' => $salesReturn->return_no ?? $salesReturn->order->order_no,
    //         ]);

    //         $returnCustomer = Customer::find($salesReturn->customer_id);
    //         $returnCustomerName = $returnCustomer ? $returnCustomer->customer_name : 'Customer';
    //         $journalEntry = $this->journalEntryService->createSalesReturnCreditEntry([
    //             'date' => Carbon::now()->toDateString(),
    //             'reference_type' => 'App\\Models\\SalesReturn',
    //             'reference_id' => $salesReturn->id,
    //             'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $returnCustomerName,
    //             'amount' => $salesReturn->total_amount,
    //             'user_id' => $data->user_id,

    //             'customer_id' => $returnCustomer->id,
    //             'customer_name' => $returnCustomerName,

    //             'customer_type' => 'customer',
    //         ]);



    //         if (!$journalEntry) {
    //             return errorResponse(null, 'Failed to create journal entry', 500);
    //         }
    //         Log::channel('sales_return')->info('Journal entry created for sales return', [
    //             'sales_return_id' => $salesReturn->id,

    //         ]);

    //         // 1) Restore stock & create stock movements
    //         foreach ($salesReturn->returnItems as $returnItem) {
    //             // lock product or vehicle stock rows if needed
    //             if ($returnDestination === 'vehicle') {
    //                 // update or create VehicleLoadStockItem
    //                 $vItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicleLoadStock->id)
    //                     ->where('product_id', $returnItem->product_id)
    //                     ->lockForUpdate()
    //                     ->first();

    //                 $totalReturnQty = $returnItem->return_quantity + $returnItem->free_quantity;
    //                 if ($vItem) {
    //                     $vItem->total_qty += $totalReturnQty;
    //                     $vItem->available_qty += $totalReturnQty;
    //                     $vItem->save();
    //                 } else {
    //                     VehicleLoadStockItem::create([
    //                         'vehicle_load_stock_id' => $vehicleLoadStock->id,
    //                         'product_id' => $returnItem->product_id,
    //                         'available_qty' => $totalReturnQty,
    //                         'total_qty' => $totalReturnQty,
    //                     ]);
    //                 }


    //                 // create load detail (audit)
    //                 VehicleLoadItemDetail::create([
    //                     'vehicle_load_id' => $vehicleLoadStock->id,
    //                     'product_id' => $returnItem->product_id,
    //                     'requested_qty' => $totalReturnQty,
    //                     'approved_qty' => $totalReturnQty,
    //                     'requested_date' => Carbon::now(),
    //                     'status' => 'approved',
    //                     'description' => 'Return from Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id),
    //                 ]);
    //             } else {
    //                 // add to central product stock (godown)
    //                 // Products::where('id', $returnItem->product_id)->lockForUpdate()->increment('total_stock', $returnItem->return_quantity);
    //                 $totalReturnQty = $returnItem->return_quantity + $returnItem->free_quantity;
    //                 Products::where('id', $returnItem->product_id)->increment('total_stock', $totalReturnQty);

    //                 StockMovement::create([
    //                     'product_id' => $returnItem->product_id,
    //                     'movement_type' => 'in',
    //                     'quantity' => $totalReturnQty,

    //                     'reason' => 'sales_return',
    //                     'ref_id' => $salesReturn->id,
    //                     'date' => Carbon::now(),
    //                     'created_by' => $data->user_id,
    //                     'created_at' => Carbon::now(),
    //                 ]);
    //             }

    //             // $total_returned_quantity = $returnItem->return_quantity + $returnItem->free_quantity;
    //             // $cost_price = $returnItem->total / $total_returned_quantity;

    //             $soldCostPrice = StockOriginal::where('ref_id', $salesReturn->order_id)
    //                 ->where('product_id', $returnItem->product_id)->first();
    //             $cost_price = $soldCostPrice ? $soldCostPrice->cost_price : ($returnItem->price ?? 0);
    //             $originalStock = StockOriginal::create([
    //                 'ref_id'       => $salesReturn->id,
    //                 'ref_no'       =>  $salesReturn->return_no ?? $salesReturn->order->order_no,
    //                 'product_id'   => $returnItem->product_id,
    //                 'quantity'     => $totalReturnQty,
    //                 'cost_price' => $cost_price,
    //                 'movement_type' => 'in',
    //                 'reason'       => 'Sales Return',
    //                 'date' => $salesReturn->return_date,
    //                 'created_by'   => $data->user_id,
    //             ]);
    //             $orderItem = OrderItem::find($returnItem->order_item_id);
    //             if ($orderItem) {
    //                 $orderItem->increment('returned_quantity', $returnItem->return_quantity);
    //                 $orderItem->increment('returned_free_quantity', $returnItem->free_quantity);
    //             }
    //         }

    //         // 2) Process refunds (finance decides refundRows)
    //         $refundRows = json_decode($data->refund_rows, true) ?? [];
    //         $totalRefund = 0;
    //         $totalRefundFromPaid = 0; // Track refunds that reduce paid_amount
    //         $totalRefundFromBalance = 0; // Track refunds that reduce balance_amount

    //         // Get order with current balance to check for credit
    //         $order = Order::lockForUpdate()->find($salesReturn->order_id);
    //         $originalOrderBalance = $order ? $order->balance_amount : 0; // Original credit balance on the order
    //         $orderCreditBalance = $originalOrderBalance; // Credit balance on the order (customer owes this)

    //         // Get customer for balance updates
    //         $customer = null;
    //         if ($salesReturn->customer_id) {
    //             $customer = Customer::lockForUpdate()->find($salesReturn->customer_id);
    //         }



    //         foreach ($refundRows as $row) {
    //             $refundAmount = (float)$row['refund_amount'];
    //             if ($refundAmount <= 0) {
    //                 continue;
    //             }
    //             $refundMode = strtolower($row['refund_mode'] ?? 'cash'); // e.g., cash, credit, bank_transfer, order_balance
    //             $totalRefund += $refundAmount;

    //             // Handle customer balance and paid amount based on order credit balance
    //             // If order has credit balance, refunds should first reduce customer_balance_amount
    //             $amountFromCredit = 0;
    //             $amountFromPaid = 0;
    //             $actualRefundMode = $refundMode; // Store the actual refund mode

    //             if ($orderCreditBalance > 0 && $customer) {
    //                 // Order has credit balance - refund should first reduce customer_balance_amount
    //                 if ($refundAmount <= $orderCreditBalance) {
    //                     // Case 1: Refund amount <= credit balance
    //                     // Example: Order credit 250, refund 100 -> customer_balance -= 100
    //                     $amountFromCredit = $refundAmount;
    //                     $customer->balance_amount -= $amountFromCredit;
    //                     $customer->save();
    //                     $orderCreditBalance -= $amountFromCredit; // Update for next iteration
    //                     $totalRefundFromBalance += $amountFromCredit; // Track total refund from balance
    //                     // If refund is fully from balance, set mode to order_balance
    //                     $actualRefundMode = 'order_balance';
    //                 } else {
    //                     // Case 2: Refund amount > credit balance
    //                     // Example: Order credit 250, refund 300 -> customer_balance -= 250, paid_amount -= 50
    //                     $amountFromCredit = $orderCreditBalance;
    //                     $amountFromPaid = $refundAmount - $orderCreditBalance;
    //                     $customer->balance_amount -= $amountFromCredit;
    //                     $customer->save();
    //                     $orderCreditBalance = 0; // All credit consumed
    //                     $totalRefundFromBalance += $amountFromCredit; // Track total refund from balance
    //                     // If refund_mode is order_balance, keep it, otherwise use original mode
    //                     // Since part comes from balance and part from paid, we keep the original mode
    //                     // But if explicitly set to order_balance, keep it
    //                     if ($refundMode !== 'order_balance') {
    //                         $actualRefundMode = $refundMode; // Use original mode (cash, credit, etc.)
    //                     }
    //                 }
    //             } else {
    //                 // No credit balance on order - refund reduces paid amount only
    //                 $amountFromPaid = $refundAmount;
    //                 // Keep original refund mode (not order_balance since no balance exists)
    //                 if ($refundMode === 'order_balance') {
    //                     $actualRefundMode = 'cash'; // Convert to cash if no balance exists
    //                 }
    //             }

    //             // Record sales return payment log with actual refund mode
    //             SalesReturnPayment::create([
    //                 'sales_return_id' => $salesReturn->id,
    //                 'order_id' => $salesReturn->order_id,
    //                 'customer_id' => $salesReturn->customer_id,
    //                 'user_id' => $salesReturn->returned_by,
    //                 'refund_amount' => $refundAmount,
    //                 'refund_mode' => $actualRefundMode, // Use actual refund mode (order_balance if fully from balance)
    //                 'refund_date' => Carbon::now(),
    //                 'description' => $row['description'] ?? null,
    //             ]);

    //             // Track total refund from paid for order recalculation
    //             $totalRefundFromPaid += $amountFromPaid;
    //             $rDate = Carbon::now();
    //             $financialYear = FinancialYear::whereDate('start_date', '<=', $rDate)
    //                 ->whereDate('end_date', '>=', $rDate)
    //                 ->first();

    //             if (!$financialYear) {
    //                 // throw new Exception('No financial year found for the selected date.');
    //                 DB::rollBack();
    //                 return errorResponse(null, 'No financial year found for the selected date.', 500);
    //             }
    //             if ($financialYear->is_closed == 1) {
    //                 DB::rollBack();
    //                 return errorResponse(null, 'Financial year closed for the selected date.', 500);
    //             }
    //             // Create OrderPayment entry for refund
    //             // Only create negative entry for the portion that reduces paid_amount
    //             // The credit portion is handled via customer balance update above
    //             if ($amountFromPaid > 0) {
    //                 OrderPayment::create([
    //                     'order_id' => $salesReturn->order_id,
    //                     'customer_id' => $salesReturn->customer_id,
    //                     'user_id' => $salesReturn->returned_by,
    //                     'total_amount' => $amountFromPaid,
    //                     'paid_amount' => -$amountFromPaid, // Negative to represent refund from paid
    //                     'balance_amount' => 0,
    //                     'payment_mode' => $refundMode . '_refund',
    //                     'type' => 'refund',
    //                     'payment_date' => Carbon::now(),
    //                     'description' => 'Refund for SR: ' . ($salesReturn->return_no ?? $salesReturn->id) .
    //                         ($amountFromCredit > 0 ? ' (From Credit: ' . $amountFromCredit . ', From Paid: ' . $amountFromPaid . ')' : ''),
    //                     'created_at' => Carbon::now(),
    //                     'financial_year_id'  => $financialYear->id,
    //                 ]);
    //             }
    //         }

    //         // 3) Create Journal Entry for Sales Return
    //         // This records the accounting impact of the return
    //         if ($totalRefund > 0) {



    //             $refund_mode = json_decode($data->refund_rows, true);

    //             // Determine the predominant refund mode for the journal entry description
    //             $predominantMode = 'order_balance';
    //             if ($totalRefundFromPaid > $totalRefundFromBalance) {

    //                 $predominantMode = $refund_mode[0]['refund_mode'];
    //             }

    //             // if ($predominantMode != 'order_balance') {
    //             //     $journalEntry = $this->journalEntryService->createSalesReturnEntry([
    //             //         'date' => Carbon::now()->toDateString(),
    //             //         'reference_type' => 'App\\Models\\SalesReturn',
    //             //         'reference_id' => $salesReturn->id,
    //             //         'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $returnCustomerName,
    //             //         'amount' => $totalRefund,
    //             //         'user_id' => $data->user_id,
    //             //         'return_no' => $salesReturn->return_no ?? $salesReturn->id,
    //             //         'customer_id' => $returnCustomer->id,
    //             //         'customer_name' => $returnCustomerName,
    //             //         'payment_mode' => $predominantMode,
    //             //         'customer_type' => 'customer',
    //             //     ]);
    //             //     if (!$journalEntry) {
    //             //         return errorResponse(null, 'Failed to create journal entry', 500);
    //             //     }
    //             //     Log::channel('sales_return')->info('Journal entry created for sales return', [
    //             //         'sales_return_id' => $salesReturn->id,
    //             //         'refund_amount' => $totalRefund,
    //             //         'refund_mode' => $predominantMode,
    //             //     ]);
    //             // }
    //         }

    //         // 4) Reverse commissions (do not delete original; create reversal entries)
    //         $this->reverseCommissionsForReturn($salesReturn);

    //         // 5) Recalculate order-level summaries
    //         // Get the order again to ensure we have latest data
    //         $order = Order::lockForUpdate()->find($salesReturn->order_id);


    //         if ($order) {
    //             // Calculate total refunded (from both credit and paid)
    //             // $order->total_refunded_amount = $totalRefund;

    //             // Recalculate paid_amount: original paid - refunds from paid portion only
    //             $originalPaidAmount = $order->paid_amount;
    //             $newPaidAmount = max(0, $originalPaidAmount - $totalRefundFromPaid);
    //             // $order->paid_amount = $newPaidAmount;

    //             // Recalculate balance_amount: original balance - refunds from balance
    //             // Balance reduces when refund comes from balance, stays same when refund comes from paid
    //             $newBalanceAmount = max(0, $originalOrderBalance - $totalRefundFromBalance);


    //             // $order->total_refunded_amount += $totalRefundFromPaid;
    //             $order->total_refund_from_order_balance += $totalRefundFromBalance;
    //             $order->balance_amount = $newBalanceAmount;

    //             // Update payment status based on new balance
    //             if ($order->balance_amount <= 0) {
    //                 $order->payment_status = 'Paid';
    //             } else {
    //                 $order->payment_status = 'Partial';
    //             }


    //             $order->is_returned = '1';
    //             $order->save();
    //         }


    //         if ($totalRefundFromPaid > 0) {

    //             $ret = $this->stockService->payCreditBalanceMultiplePayment($refundRows, $financialYear, Carbon::now(), $salesReturn->customer_id, 'customer');
    //         }



    //         Log::channel('sales_return')->info('Sales return approved successfully', ['sales_return_id' => $salesReturn->id]);

    //         DB::commit();
    //         return successResponse($salesReturn->fresh(['returnItems', 'order', 'customer', 'returnPayments']), 'Sales return approved successfully.', 0, 200);
    //     } catch (Exception $e) {
    //         Log::channel('sales_return')->error('Error approving sales return', ['error' => $e->getMessage()]);
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    public function approveSalesReturn($data)
    {


        try {
            DB::beginTransaction();

            Log::channel('sales_return')->info('---------------- approveSalesReturn --------------');
            Log::channel('sales_return')->info('approving sales return', [
                'return_id' => $data->return_id,
                'user_id' => $data->user_id,
                'return_destination' => $data->return_destination,
                'salesman_id' => $data->salesman_id,
                'returnAmount' => $data->returnAmount
            ]);



            $salesReturn = SalesReturn::with('returnItems', 'order')->lockForUpdate()->find($data->return_id);
            if (!$salesReturn) {
                DB::rollBack();
                Log::channel('sales_return')->info('Sales return not found', ['return_id' => $data->return_id]);
                return errorResponse(null, 'Sales return not found.', 404, 404);
            }

            if ($salesReturn->return_status === 'approved') {
                DB::rollBack();
                return errorResponse(null, 'Return already approved.', 400, 400);
            }
            $financialYear = FinancialYear::whereDate('start_date', '<=', $salesReturn->return_date)
                ->whereDate('end_date', '>=', $salesReturn->return_date)
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
            // check the  order alreadey retunred once then block the apporval
            $order = Order::with('orderItems')->lockForUpdate()->find($salesReturn->order_id);


            // Password / permission check (prefer token-based permission)
            $user = User::find($data->user_id);
            if (!$user || !Hash::check($data->password, $user->password)) {
                DB::rollBack();
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            $returnDestination = strtolower($data->return_destination ?? 'godown');

            $salesmanId = $data->salesman_id ?? null;
            if ($returnDestination === 'vehicle' && !$salesmanId) {
                DB::rollBack();
                return errorResponse(null, 'Salesman is required when return destination is vehicle.', 400, 400);
            }

            // If vehicle: find vehicle load stock row (lock for update)
            $vehicleLoadStock = null;
            if ($returnDestination === 'vehicle') {
                $vehicleLoadStock = VehicleLoadStock::where('user_id', $salesmanId)
                    ->where('load_status', 'approved')
                    ->where('day_status', 'started')
                    ->orderBy('created_at', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (!$vehicleLoadStock) {
                    DB::rollBack();
                    return errorResponse(null, 'No active vehicle load stock found for the salesman.', 404, 404);
                }
            }

            // Approve return
            $salesReturn->update([
                'return_status' => 'approved',
                'approved_by' => $data->user_id,
                'approved_date' => Carbon::now(),
                'return_destination' => $returnDestination,
                'salesman_id' => $salesmanId,
                'return_no' => $salesReturn->return_no ?? $salesReturn->order->order_no,
            ]);
            $order->is_returned = 1;
            $order->save();
            $returnCustomer = Customer::find($salesReturn->customer_id);
            $returnCustomerName = $returnCustomer ? $returnCustomer->customer_name : 'Customer';
            $journalEntry = $this->journalEntryService->createSalesReturnCreditEntry([
                'date' => Carbon::now()->toDateString(),
                'reference_type' => 'App\\Models\\SalesReturn',
                'reference_id' => $salesReturn->id,
                'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $returnCustomerName,
                'amount' => $salesReturn->total_amount,
                'user_id' => $data->user_id,

                'customer_id' => $returnCustomer->id,
                'customer_name' => $returnCustomerName,

                'customer_type' => 'customer',
            ]);



            if (!$journalEntry) {
                return errorResponse(null, 'Failed to create journal entry', 500);
            }
            Log::channel('sales_return')->info('Journal entry created for sales return', [
                'sales_return_id' => $salesReturn->id,

            ]);

            // 1) Restore stock & create stock movements
            foreach ($salesReturn->returnItems as $returnItem) {
                // lock product or vehicle stock rows if needed
                if ($returnDestination === 'vehicle') {
                    // update or create VehicleLoadStockItem
                    $vItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicleLoadStock->id)
                        ->where('product_id', $returnItem->product_id)
                        ->lockForUpdate()
                        ->first();

                    $totalReturnQty = $returnItem->return_quantity + $returnItem->free_quantity;
                    if ($vItem) {
                        $vItem->total_qty += $totalReturnQty;
                        $vItem->available_qty += $totalReturnQty;
                        $vItem->save();
                    } else {
                        VehicleLoadStockItem::create([
                            'vehicle_load_stock_id' => $vehicleLoadStock->id,
                            'product_id' => $returnItem->product_id,
                            'available_qty' => $totalReturnQty,
                            'total_qty' => $totalReturnQty,
                        ]);
                    }


                    // create load detail (audit)
                    VehicleLoadItemDetail::create([
                        'vehicle_load_id' => $vehicleLoadStock->id,
                        'product_id' => $returnItem->product_id,
                        'requested_qty' => $totalReturnQty,
                        'approved_qty' => $totalReturnQty,
                        'requested_date' => Carbon::now(),
                        'status' => 'approved',
                        'description' => 'Return from Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id),
                    ]);
                } else {
                    // add to central product stock (godown)
                    // Products::where('id', $returnItem->product_id)->lockForUpdate()->increment('total_stock', $returnItem->return_quantity);
                    $totalReturnQty = $returnItem->return_quantity + $returnItem->free_quantity;
                    Products::where('id', $returnItem->product_id)->increment('total_stock', $totalReturnQty);

                    StockMovement::create([
                        'product_id' => $returnItem->product_id,
                        'movement_type' => 'in',
                        'quantity' => $totalReturnQty,

                        'reason' => 'sales_return',
                        'ref_id' => $salesReturn->id,
                        'date' => Carbon::now(),
                        'created_by' => $data->user_id,
                        'created_at' => Carbon::now(),
                    ]);
                }

                // $total_returned_quantity = $returnItem->return_quantity + $returnItem->free_quantity;
                // $cost_price = $returnItem->total / $total_returned_quantity;

                $soldCostPrice = StockOriginal::where('ref_id', $salesReturn->order_id)
                    ->where('product_id', $returnItem->product_id)->first();
                $cost_price = $soldCostPrice ? $soldCostPrice->cost_price : ($returnItem->price ?? 0);
                $originalStock = StockOriginal::create([
                    'ref_id'       => $salesReturn->id,
                    'ref_no'       =>  $salesReturn->return_no ?? $salesReturn->order->order_no,
                    'product_id'   => $returnItem->product_id,
                    'quantity'     => $totalReturnQty,
                    'cost_price' => $cost_price,
                    'movement_type' => 'in',
                    'reason'       => 'Sales Return',
                    'date' => $salesReturn->return_date,
                    'created_by'   => $data->user_id,
                ]);
                $orderItem = OrderItem::find($returnItem->order_item_id);
                if ($orderItem) {
                    $orderItem->increment('returned_quantity', $returnItem->return_quantity);
                    $orderItem->increment('returned_free_quantity', $returnItem->free_quantity);
                }
            }



            $returnAmount = $data->returnAmount;
            $customer = Customer::find($salesReturn->customer_id);

            $payToPending = 0;


            if ((float)$order->balance_amount > 0) {


                $balanceAfterRefund = (float)$order->balance_amount - (float)$returnAmount;

                if ($balanceAfterRefund >= 0) {


                    SalesReturnPayment::create([
                        'sales_return_id' => $salesReturn->id,
                        'order_id' => $salesReturn->order_id,
                        'customer_id' => $salesReturn->customer_id,
                        'user_id' => $salesReturn->returned_by,
                        'refund_amount' => (float)$returnAmount,
                        'refund_mode' => 'order_balance', // Use actual refund mode (order_balance if fully from balance)
                        'refund_date' => $salesReturn->return_date,
                        'description' => 'salesman salesreturn',
                    ]);
                    generateAutoCode(
                        $salesReturn,
                        'return_no',
                        'AI_SALES_RETURN_CODE'
                    );
                    $order->balance_amount -= (float)$returnAmount;

                    $customer->balance_amount -= (float)$returnAmount;
                    $customer->save();

                    if ($balanceAfterRefund == 0) {
                        $order->payment_status = 'Paid';
                    }
                    $order->total_refund_from_order_balance = (float)$returnAmount;

                    $order->save();
                    // DB::commit();
                } else {

                    SalesReturnPayment::create([
                        'sales_return_id' => $salesReturn->id,
                        'order_id' => $salesReturn->order_id,
                        'customer_id' => $salesReturn->customer_id,
                        'user_id' => $salesReturn->returned_by,
                        'refund_amount' => (float)$order->balance_amount,
                        'refund_mode' => 'order_balance', // Use actual refund mode (order_balance if fully from balance)
                        'refund_date' => $salesReturn->return_date,
                        'description' => 'salesman salesreturn',
                    ]);
                    // $customer->balance_amount -= (float)$order->balance_amount;
                    // $customer->save();
                    $order->total_refund_from_order_balance += (float)$order->balance_amount;
                    $payToPending += (float)$returnAmount - (float)$order->balance_amount;
                    $order->balance_amount = 0;

                    $order->payment_status = 'Paid';
                    $order->save();
                    $customer->balance_amount -= (float)$returnAmount;
                    $customer->save();

                    // DB::commit();
                }
            } else {


                $customer->balance_amount -= (float)$returnAmount;
                $customer->save();
                // DB::commit();
                $payToPending += (float)$returnAmount;
            }

            if ($payToPending > 0) {

                $amountAfterPendingPayment = $this->payToPendingFun($payToPending, $customer, $salesReturn);
            }
            // 4) Reverse commissions (do not delete original; create reversal entries)
            $this->reverseCommissionsForReturn($salesReturn);


            $order = Order::lockForUpdate()->find($salesReturn->order_id);


            // if ($totalRefundFromPaid > 0) {

            //     $ret = $this->stockService->payCreditBalanceMultiplePayment($refundRows, $financialYear, Carbon::now(), $salesReturn->customer_id, 'customer');
            // }



            Log::channel('sales_return')->info('Sales return approved successfully', ['sales_return_id' => $salesReturn->id]);

            DB::commit();
            return successResponse($salesReturn->fresh(['returnItems', 'order', 'customer', 'returnPayments']), 'Sales return approved successfully.', 0, 200);
        } catch (Exception $e) {
            Log::channel('sales_return')->error('Error approving sales return', ['error' => $e->getMessage()]);
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function payToPendingFun($amountfrom, $user, $salesReturn)
    {

        DB::beginTransaction();
        $amount = $amountfrom;
        $previousOrders = Order::with('orderItems')
            ->where('customer_id', $user->id)
            ->whereRaw('LOWER(payment_status) != ?', ['paid'])
            // ->where('balance_amount','>',0)
            ->where('order_status', 'delivered')
            ->where('order_type', '!=', 'Outside Sale')

            ->orderBy('created_at', 'asc')
            ->get();


        foreach ($previousOrders as $prevOrder) {
            if ($amount <= 0)  break;

            if ((float)$prevOrder->balance_amount < $amount) {
                (float)$prevOrder->total_refund_from_order_balance += (float)$prevOrder->balance_amount;
                $prevOrder->payment_status = 'Paid';
                (float)$amount -= (float)$prevOrder->balance_amount;
                $prevOrder->balance_amount = 0;

                $prevOrder->save();
                DB::commit();

                // $employee->balance_amount -= $prevOrder->balance_amount;
            } else {
                $prevOrder->total_refund_from_order_balance += (float)$amount;
                $prevOrder->balance_amount -= (float)$amount;
                (float)$amount -= (float)$amount;

                $prevOrder->save();
                DB::commit();
            }
            SalesReturnPayment::create([
                'sales_return_id' => $salesReturn->id,
                'order_id' => $salesReturn->order_id,
                'customer_id' => $salesReturn->customer_id,
                'user_id' => $salesReturn->returned_by,
                'refund_amount' => (float)$amount,
                'refund_mode' => 'order_balance', // Use actual refund mode (order_balance if fully from balance)
                'refund_date' => $salesReturn->return_date,
                'description' => 'salesman salesreturn' . $salesReturn->order_id,
            ]);
        }
        DB::commit();
        return $amount;
    }
    public function approveSalesReturn123($data)
    {
        try {
            DB::beginTransaction();

            $salesReturn = SalesReturn::with('returnItems', 'order')->lockForUpdate()->find($data->return_id);
            if (!$salesReturn) {
                DB::rollBack();
                return errorResponse(null, 'Sales return not found.', 404, 404);
            }

            if ($salesReturn->return_status === 'approved') {
                DB::rollBack();
                return errorResponse(null, 'Return already approved.', 400, 400);
            }

            // check the  order alreadey retunred once then block the apporval
            $order = Order::with('orderItems')->lockForUpdate()->find($salesReturn->order_id);
            if ($order->is_returned == 1) {
                DB::rollBack();
                return errorResponse(null, 'Sales already returned.', 404, 404);
            }

            // Password / permission check (prefer token-based permission)
            $user = User::find($data->user_id);
            if (!$user || !Hash::check($data->password, $user->password)) {
                DB::rollBack();
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            $returnDestination = strtolower($data->return_destination ?? 'godown');
            $salesmanId = $data->salesman_id ?? null;

            if ($returnDestination === 'vehicle' && !$salesmanId) {
                DB::rollBack();
                return errorResponse(null, 'Salesman is required when return destination is vehicle.', 400, 400);
            }

            // If vehicle: find vehicle load stock row (lock for update)
            $vehicleLoadStock = null;
            if ($returnDestination === 'vehicle') {
                $vehicleLoadStock = VehicleLoadStock::where('user_id', $salesmanId)
                    ->where('load_status', 'approved')
                    ->where('day_status', 'started')
                    ->orderBy('created_at', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (!$vehicleLoadStock) {
                    DB::rollBack();
                    return errorResponse(null, 'No active vehicle load stock found for the salesman. Please start the vehicle first.', 404, 404);
                }
            }

            // Approve return
            $salesReturn->update([
                'return_status' => 'approved',
                'approved_by' => $data->user_id,
                'approved_date' => Carbon::now(),
                'return_destination' => $returnDestination,
                'salesman_id' => $salesmanId,
                'return_no' => $salesReturn->return_no ?? $salesReturn->order->order_no,
            ]);

            // 1) Restore stock & create stock movements
            foreach ($salesReturn->returnItems as $returnItem) {
                // lock product or vehicle stock rows if needed
                if ($returnDestination === 'vehicle') {
                    // update or create VehicleLoadStockItem
                    $vItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicleLoadStock->id)
                        ->where('product_id', $returnItem->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($vItem) {
                        $vItem->total_qty += $returnItem->return_quantity;
                        $vItem->available_qty += $returnItem->return_quantity;
                        $vItem->save();
                    } else {
                        VehicleLoadStockItem::create([
                            'vehicle_load_stock_id' => $vehicleLoadStock->id,
                            'product_id' => $returnItem->product_id,
                            'available_qty' => $returnItem->return_quantity,
                            'total_qty' => $returnItem->return_quantity,
                        ]);
                    }

                    // create load detail (audit)
                    VehicleLoadItemDetail::create([
                        'vehicle_load_id' => $vehicleLoadStock->id,
                        'product_id' => $returnItem->product_id,
                        'requested_qty' => $returnItem->return_quantity,
                        'approved_qty' => $returnItem->return_quantity,
                        'requested_date' => Carbon::now(),
                        'status' => 'approved',
                        'description' => 'Return from Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id),
                    ]);
                } else {
                    // add to central product stock (godown)
                    // Products::where('id', $returnItem->product_id)->lockForUpdate()->increment('total_stock', $returnItem->return_quantity);

                    Products::where('id', $returnItem->product_id)->increment('total_stock', $returnItem->return_quantity);

                    StockMovement::create([
                        'product_id' => $returnItem->product_id,
                        'movement_type' => 'in',
                        'quantity' => $returnItem->return_quantity,
                        'reason' => 'sales_return',
                        'ref_id' => $salesReturn->id,
                        'date' => Carbon::now(),
                        'created_by' => $data->user_id,
                        'created_at' => Carbon::now(),
                    ]);
                }


                $orderItem = OrderItem::find($returnItem->order_item_id);
                if ($orderItem) {
                    $orderItem->increment('returned_quantity', $returnItem->return_quantity);
                }
            }

            // 2) Process refunds (finance decides refundRows)
            $refundRows = json_decode($data->refund_rows, true) ?? [];
            $totalRefund = 0;
            $totalRefundFromPaid = 0; // Track refunds that reduce paid_amount
            $totalRefundFromBalance = 0; // Track refunds that reduce balance_amount

            // Get order with current balance to check for credit
            $order = Order::lockForUpdate()->find($salesReturn->order_id);
            $originalOrderBalance = $order ? $order->balance_amount : 0; // Original credit balance on the order
            $orderCreditBalance = $originalOrderBalance; // Credit balance on the order (customer owes this)

            // Get customer for balance updates
            $customer = null;
            if ($salesReturn->customer_id) {
                $customer = Customer::lockForUpdate()->find($salesReturn->customer_id);
            }

            foreach ($refundRows as $row) {
                $refundAmount = (float)$row['refund_amount'];
                if ($refundAmount <= 0) {
                    continue;
                }
                $refundMode = strtolower($row['refund_mode'] ?? 'cash'); // e.g., cash, credit, bank_transfer, order_balance
                $totalRefund += $refundAmount;

                // Handle customer balance and paid amount based on order credit balance
                // If order has credit balance, refunds should first reduce customer_balance_amount
                $amountFromCredit = 0;
                $amountFromPaid = 0;
                $actualRefundMode = $refundMode; // Store the actual refund mode

                if ($orderCreditBalance > 0 && $customer) {
                    // Order has credit balance - refund should first reduce customer_balance_amount
                    if ($refundAmount <= $orderCreditBalance) {
                        // Case 1: Refund amount <= credit balance
                        // Example: Order credit 250, refund 100 -> customer_balance -= 100
                        $amountFromCredit = $refundAmount;
                        $customer->balance_amount -= $amountFromCredit;
                        $customer->save();
                        $orderCreditBalance -= $amountFromCredit; // Update for next iteration
                        $totalRefundFromBalance += $amountFromCredit; // Track total refund from balance
                        // If refund is fully from balance, set mode to order_balance
                        $actualRefundMode = 'order_balance';
                    } else {
                        // Case 2: Refund amount > credit balance
                        // Example: Order credit 250, refund 300 -> customer_balance -= 250, paid_amount -= 50
                        $amountFromCredit = $orderCreditBalance;
                        $amountFromPaid = $refundAmount - $orderCreditBalance;
                        $customer->balance_amount -= $amountFromCredit;
                        $customer->save();
                        $orderCreditBalance = 0; // All credit consumed
                        $totalRefundFromBalance += $amountFromCredit; // Track total refund from balance
                        // If refund_mode is order_balance, keep it, otherwise use original mode
                        // Since part comes from balance and part from paid, we keep the original mode
                        // But if explicitly set to order_balance, keep it
                        if ($refundMode !== 'order_balance') {
                            $actualRefundMode = $refundMode; // Use original mode (cash, credit, etc.)
                        }
                    }
                } else {
                    // No credit balance on order - refund reduces paid amount only
                    $amountFromPaid = $refundAmount;
                    // Keep original refund mode (not order_balance since no balance exists)
                    if ($refundMode === 'order_balance') {
                        $actualRefundMode = 'cash'; // Convert to cash if no balance exists
                    }
                }

                // Record sales return payment log with actual refund mode
                SalesReturnPayment::create([
                    'sales_return_id' => $salesReturn->id,
                    'order_id' => $salesReturn->order_id,
                    'customer_id' => $salesReturn->customer_id,
                    'user_id' => $data->user_id,
                    'refund_amount' => $refundAmount,
                    'refund_mode' => $actualRefundMode, // Use actual refund mode (order_balance if fully from balance)
                    'refund_date' => Carbon::now(),
                    'description' => $row['description'] ?? null,
                ]);

                // Track total refund from paid for order recalculation
                $totalRefundFromPaid += $amountFromPaid;

                $rDate = Carbon::now();
                $financialYear = FinancialYear::whereDate('start_date', '<=', $rDate)
                    ->whereDate('end_date', '>=', $rDate)
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
                // Create OrderPayment entry for refund
                // Only create negative entry for the portion that reduces paid_amount
                // The credit portion is handled via customer balance update above
                if ($amountFromPaid > 0) {
                    OrderPayment::create([
                        'order_id' => $salesReturn->order_id,
                        'customer_id' => $salesReturn->customer_id,
                        'user_id' => $data->user_id,
                        'total_amount' => $amountFromPaid,
                        'paid_amount' => -$amountFromPaid, // Negative to represent refund from paid
                        'balance_amount' => 0,
                        'payment_mode' => $refundMode . '_refund',
                        'type' => 'refund',
                        'payment_date' => Carbon::now(),
                        'description' => 'Refund for SR: ' . ($salesReturn->return_no ?? $salesReturn->id) .
                            ($amountFromCredit > 0 ? ' (From Credit: ' . $amountFromCredit . ', From Paid: ' . $amountFromPaid . ')' : ''),
                        'created_at' => Carbon::now(),
                        'financial_year_id'  => $financialYear->id,
                    ]);
                }

                // Also create a record for credit portion refund (for audit trail, but doesn't affect paid_amount)
                // if ($amountFromCredit > 0) {
                //     OrderPayment::create([
                //         'order_id' => $salesReturn->order_id,
                //         'customer_id' => $salesReturn->customer_id,
                //         'user_id' => $data->user_id,
                //         'total_amount' => $amountFromCredit,
                //         'paid_amount' => 0, // Zero because it doesn't reduce paid_amount, only customer balance
                //         'balance_amount' => 0,
                //         'payment_mode' => $refundMode . '_refund',
                //         'type' => 'refund_credit',
                //         'payment_date' => Carbon::now(),
                //         'description' => 'Credit Refund for SR: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' (Amount: ' . $amountFromCredit . ')',
                //         'created_at' => Carbon::now(),
                //     ]);
                // }
            }

            // 3) Reverse commissions (do not delete original; create reversal entries)
            $this->reverseCommissionsForReturn($salesReturn);

            // 4) Recalculate order-level summaries
            // Get the order again to ensure we have latest data
            $order = Order::lockForUpdate()->find($salesReturn->order_id);
            if ($order) {
                // Reduce order total_amount by return amount
                // $order->total_amount -= $salesReturn->total_amount;
                // $order->total_quantity -= $salesReturn->total_quantity;

                // Calculate total refunded (from both credit and paid)
                $order->total_refunded_amount = $totalRefund;

                // Recalculate paid_amount: original paid - refunds from paid portion only
                $originalPaidAmount = $order->paid_amount;
                $newPaidAmount = max(0, $originalPaidAmount - $totalRefundFromPaid);
                $order->paid_amount = $newPaidAmount;

                // Recalculate balance_amount: original balance - refunds from balance
                // Balance reduces when refund comes from balance, stays same when refund comes from paid
                $newBalanceAmount = max(0, $originalOrderBalance - $totalRefundFromBalance);
                $order->balance_amount = $newBalanceAmount;

                // Update payment status based on new balance
                if ($order->balance_amount <= 0) {
                    $order->payment_status = 'Paid';
                } else {
                    $order->payment_status = 'Partial';
                }


                $order->is_returned = '1';


                // compute return_status for UI convenience
                // $totalOrderedQty = $order->orderItems()->sum('quantity');
                // $totalReturnedQty = $order->orderItems()->sum('returned_quantity');
                // if ($totalReturnedQty <= 0) {
                //     $order->return_status = 'none';
                // } elseif ($totalReturnedQty >= $totalOrderedQty) {
                //     $order->return_status = 'full_return';
                // } else {
                //     $order->return_status = 'partial_return';
                // }
                $order->save();
            }

            DB::commit();
            return successResponse($salesReturn->fresh(['returnItems', 'order', 'customer', 'returnPayments']), 'Sales return approved successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Reject sales return
     */
    public function rejectSalesReturn($data)
    {
        try {
            DB::beginTransaction();

            $salesReturn = SalesReturn::find($data->return_id);
            if (!$salesReturn) {
                return errorResponse(null, 'Sales return not found.', 404, 404);
            }

            if ($salesReturn->return_status === 'approved') {
                return errorResponse(null, 'Cannot reject an already approved return.', 400, 400);
            }

            // Verify password
            $user = User::find($data->user_id);
            if (!$user || !Hash::check($data->password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            // $salesReturn->update([
            //     'return_status' => 'rejected',
            //     'approved_by' => $data->user_id,
            //     'approved_date' => Carbon::now(),
            // ]);

            $salesReturn->update([
                'return_status' => 'rejected',
                'rejected_by'   => $data->user_id,
                'rejected_date' => Carbon::now(),
                'rejection_reason' => $data->rejection_reason ?? null,
            ]);


            DB::commit();
            return successResponse($salesReturn->fresh(), 'Sales return rejected successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Reverse commissions for returned items
     * Handles different commission types:
     * - Salesman/Sales Executive: Based on quantity AND payment ratio
     * - Delivery Vehicle: Based on quantity only (full commission on delivery)
     */
    protected function reverseCommissionsForReturn($salesReturn)
    {
        try {

            Log::channel('commission_history')->info('Reversing commissions for sales return ID: ' . $salesReturn->id);

            $order = $salesReturn->order;
            if (!$order) {
                return;
            }

            // Load order with items
            $order->load('orderItems.product');

            // Get commission histories for the original order - only positive (original) commissions
            $commissionHistories = CommissionHistory::where('order_id', $order->id)
                ->where('commission_amount', '>', 0) // Only get original positive commissions, not reversals
                ->get();

            Log::channel('commission_history')->info('----Before sales return, the Order Commission Histories----', ['histories' => $commissionHistories->toArray()]);

            if ($commissionHistories->isEmpty()) {
                return; // No commissions to reverse
            }

            // Determine order type to identify commission types
            $isSalesManOrder = ($order->order_by === $order->delivered_by);


            // Get all previous approved sales returns for this order to calculate already returned quantities
            $previousReturns = SalesReturn::where('order_id', $order->id)
                ->where('return_status', 'approved')
                ->where('id', '!=', $salesReturn->id) // Exclude current return
                ->with('returnItems')
                ->get();

            // Process each returned item to calculate commission reversal
            foreach ($salesReturn->returnItems as $returnItem) {
                $orderItem = OrderItem::with('product')->find($returnItem->order_item_id);
                if (!$orderItem || !$orderItem->product) {
                    continue;
                }

                // Calculate total already returned quantity for this product from previous approved returns
                $alreadyReturnedQuantity = 0;
                foreach ($previousReturns as $prevReturn) {
                    foreach ($prevReturn->returnItems as $prevReturnItem) {
                        if ($prevReturnItem->product_id == $returnItem->product_id) {
                            $alreadyReturnedQuantity += $prevReturnItem->return_quantity;
                        }
                    }
                }

                // Get commission histories for this specific product and user
                $productHistories = $commissionHistories->where('product_id', $returnItem->product_id);

                foreach ($productHistories as $history) {
                    $user = User::find($history->user_id);
                    if (!$user) {
                        continue;
                    }

                    // Identify commission type based on user and order
                    $commissionType = $this->identifyCommissionType($history, $order, $isSalesManOrder);

                    // Get original commission rate for this product
                    $commissionRate = $this->getCommissionRate($order, $orderItem, $commissionType);

                    if ($commissionRate <= 0) {
                        continue;
                    }

                    // Calculate commission to reverse based on commission type
                    $returnCommission = 0;

                    if ($commissionType === 'delivery_vehicle') {
                        // Delivery Vehicle: Simple reversal based on quantity only
                        // Full commission per unit, no payment ratio
                        $returnCommission = $returnItem->return_quantity * $commissionRate;
                    } else {
                        // Salesman/Sales Executive: Reversal based on quantity and payment ratio
                        // The stored commission already includes payment ratio, so we need to reverse proportionally
                        $originalItemCommission = $history->commission_amount;
                        $originalItemQuantity = $history->product_quantity;

                        if ($originalItemQuantity <= 0 || $originalItemCommission <= 0) {
                            continue;
                        }

                        // Calculate remaining quantity after previous returns
                        $remainingQuantity = $originalItemQuantity - $alreadyReturnedQuantity;

                        if ($remainingQuantity <= 0) {
                            // All quantity already returned, skip
                            continue;
                        }

                        // Calculate remaining commission based on remaining quantity
                        // Commission is proportional to quantity
                        $remainingCommission = ($originalItemCommission / $originalItemQuantity) * $remainingQuantity;

                        // Calculate commission per unit from remaining commission and remaining quantity
                        $commissionPerUnit = $remainingCommission / $remainingQuantity;

                        // Commission to reverse = returned quantity * commission per unit
                        $returnCommission = $returnItem->return_quantity * $commissionPerUnit;
                    }

                    if ($returnCommission <= 0) {
                        continue;
                    }

                    // Reverse CommissionMaster
                    $commissionMaster = CommissionMaster::find($history->commision_master_id);
                    if ($commissionMaster) {
                        $newAmount = max(0, $commissionMaster->commission_amount - $returnCommission);
                        $commissionMaster->update(['commission_amount' => $newAmount]);
                    }

                    // Reverse Incentive
                    $incentive = Incentive::where('user_id', $history->user_id)->first();
                    if ($incentive) {
                        $newTotal = max(0, $incentive->total_commission - $returnCommission);
                        $incentive->update(['total_commission' => $newTotal]);
                    }


                    $returnCommission = round($returnCommission, 2);


                    $commission = CommissionHistory::create([
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'product_id' => $returnItem->product_id,
                        'commision_master_id' => $history->commision_master_id,
                        'product_quantity' => $returnItem->return_quantity,
                        'commission_amount' => -$returnCommission, // negative to indicate reversal
                        'date' => $salesReturn->return_date,
                    ]);

                    $employee = Employee::where('user_id', $user->id)->lockForUpdate()->first();

                    $employee->decrement('commission', $returnCommission);
                    $employee->save();

                    // $journalEntry = $this->journalEntryService->createCommissionEntry([
                    //     'date' => $salesReturn->return_date,
                    //     'reference_type' => 'App\\Models\\ReturnCommission',
                    //     'reference_id' => $commission->id,
                    //     'description' => 'Commission payable: ' . $commission->id ,
                    //     'amount' => -$returnCommission,
                    //     'user_id' => Auth::id(),
                    //     'payroll_description' =>'Commission Return',
                    //     'employee'=>$employee->id,

                    // ]);
                }


                Log::channel('commission_history')->info('----After sales return, the Order Commission Histories----', ['histories' => CommissionHistory::where('order_id', $order->id)->get()->toArray()]);
            }
        } catch (Exception $e) {
            Log::channel('commission_history')->error('Error reversing commissions for sales return ID: ' . $salesReturn->id, ['error' => $e->getMessage()]);
            Log::error('Error reversing commissions for return: ' . $e->getMessage());
        }
    }

    /**
     * Identify commission type based on user and order
     *
     */
    protected function identifyCommissionType($history, $order, $isSalesManOrder)
    {
        $userId = $history->user_id;

        if ($isSalesManOrder) {
            // If order_by === delivered_by, it's a salesman order
            // The commission is for the salesman
            return 'salesman';
        } else {
            // If order_by !== delivered_by
            if ($userId == $order->order_by) {
                // Commission for order_by user = Sales Executive
                return 'executive';
            } elseif ($userId == $order->delivered_by) {
                // Commission for delivered_by user when order is delivered = Delivery Vehicle
                return 'delivery_vehicle';
            }
        }

        // Default fallback
        return 'executive';
    }


    /**
     * Get all sales returns
     */
    public function getAllSalesReturns($data, $platform = 'web')
    {
        try {
            $query = SalesReturn::with('customer', 'users', 'order', 'returnedBy', 'approvedBy', 'returnItems.product');

            if (isset($data->search) && $data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('return_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($cat) use ($searchTerm) {
                            $cat->where('customer_name', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('order', function ($cat) use ($searchTerm) {
                            $cat->where('order_no', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if (isset($data->return_status) && $data->return_status) {
                $query->where('return_status', $data->return_status);
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'sales_returns' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Sales returns retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Sales returns retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Get sales return details
     */
    public function getSalesReturnDetails($returnId)
    {
        try {
            $salesReturn = SalesReturn::with([
                'customer',
                'order.orderItems.product',
                'returnItems.product',
                'returnPayments.user',
                'returnedBy',
                'approvedBy'
            ])->find($returnId);

            if (!$salesReturn) {
                return errorResponse(null, 'Sales return not found.', 404, 404);
            }

            return successResponse($salesReturn, 'Sales return details retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Get orders available for return
     */
    public function getOrdersForReturn($data, $platform = 'web')
    {
        try {
            $query = Order::with('customer', 'orderItems.product')
                ->where('order_status', 'delivered')
                ->where('order_type', '!=', 'Outside Sale')
                ->where('is_outside', 0);
            // ->where('is_returned', '0')
            if (isset($data->search) && $data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('id', $searchTerm)
                        ->orWhereHas('customer', function ($cat) use ($searchTerm) {
                            $cat->where('customer_name', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            // Use pagination for both web and api platforms
            $perPage = isset($data->per_page) ? (int)$data->per_page : 50;
            $page = isset($data->page) ? (int)$data->page : 1;

            $result = $query->orderBy('created_at', 'DESC')->paginate($perPage, ['*'], 'page', $page);

            if ($platform == 'web') {
                $response = [
                    'orders' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Orders retrieved successfully.', 0, 200);
            } else {
                // For API/Select2, return paginated results with pagination metadata
                // Select2 expects 'more' boolean in pagination to enable infinite scroll
                $response = [
                    'orders' => $result->items(),
                    'pagination' => [
                        'more' => $result->hasMorePages()
                    ]
                ];
                return successResponse($response, 'Orders retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function customerCreditBalance($request)
    {
        $customer = Customer::find($request->customer_id);
        return successResponse($customer->balance_amount, 'Customer Balance Retrieved Successfully.', 0, 200);
    }
}
