<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\OpeningBalance;
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
use App\Models\Vehicle;
use App\Models\VehicleLoadItemDetail;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }
    // public function getAllVehicleLoad($data, $platform = 'web')
    // {
    //     try {
    //         $query = VehicleLoadStock::with(
    //             'user',
    //             'vehicleLoadStockItems.product',
    //             'vehicle',
    //             'reRequestedStockItems.product',
    //             'returnRequestedStockItems.product',
    //         )
    //             ->withCount([
    //                 'reRequestedStockItems as re_requested_count',
    //                 'returnRequestedStockItems as return_requested_count',
    //             ]);

    //         if ($data->search) {
    //             $query->where(function ($q) use ($data) {
    //                 $q->where('v_stock_code', 'LIKE', "%{$data->search}%")
    //                     ->orWhereHas('vehicle', function ($v) use ($data) {
    //                         $v->where('vehicle_name', 'LIKE', "%{$data->search}%")
    //                             ->orWhere('vehicle_plate_no', 'LIKE', "%{$data->search}%");
    //                     })
    //                     ->orWhereHas('user', function ($u) use ($data) {
    //                         $u->where('name', 'LIKE', "%{$data->search}%");
    //                     });
    //             });
    //         }

    //         if ($platform == 'web') {
    //             $result = $query->orderBy('id', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
    //             $items = $result->items();

    //             foreach ($items as $load) {
    //                 $load->is_re_requested = ($load->re_requested_count ?? 0) > 0;
    //                 $load->is_return_requested = ($load->return_requested_count ?? 0) > 0;
    //             }

    //             $response = [
    //                 'vehicleLoad' => $items,
    //                 'pagination' => generatePaginationData($result),
    //             ];

    //             return successResponse($response, 'Vehicle load retrieved successfully.', 0, 200);
    //         } else {
    //             $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
    //             foreach ($result as $load) {
    //                 $load->is_re_requested = ($load->re_requested_count ?? 0) > 0;
    //                 $load->is_return_requested = ($load->return_requested_count ?? 0) > 0;
    //             }

    //             return successResponse($result, 'Vehicle load retrieved successfully.', 0, 200);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();

    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }


    public function getAllVehicleLoad($data, $platform = 'web')
    {
        try {

            $query = VehicleLoadStock::with([
                'user',
                'vehicleLoadStockItems.product',
                'vehicle',

                'vehicleLoadStockDetails' => function ($query) {
                    $query->where('status', '!=', 'rejected');
                },

                'reRequestedStockItems.product',
                'returnRequestedStockItems.product',
            ])->withCount([
                'reRequestedStockItems as re_requested_count',
                'returnRequestedStockItems as return_requested_count',
            ]);


            if (!empty($data->search)) {

                $query->where(function ($q) use ($data) {

                    $q->where(
                        'v_stock_code',
                        'LIKE',
                        "%{$data->search}%"
                    )

                        ->orWhereHas('vehicle', function ($v) use ($data) {

                            $v->where(
                                'vehicle_name',
                                'LIKE',
                                "%{$data->search}%"
                            )
                                ->orWhere(
                                    'vehicle_plate_no',
                                    'LIKE',
                                    "%{$data->search}%"
                                );
                        })

                        ->orWhereHas('user', function ($u) use ($data) {

                            $u->where(
                                'name',
                                'LIKE',
                                "%{$data->search}%"
                            );
                        });
                });
            }




            if ($platform == 'web') {

                $result = $query
                    ->orderBy('id', 'DESC')
                    ->paginate(
                        config('settings.pagination.perpage.admin')
                    );

                $items = $result->items();


                foreach ($items as $load) {


                    $approvedProductIds =
                        $load->vehicleLoadStockDetails
                        ->pluck('product_id')
                        ->unique()
                        ->values();



                    $filteredItems =
                        $load->vehicleLoadStockItems
                        ->filter(function ($item) use ($approvedProductIds) {

                            return $approvedProductIds->contains(
                                $item->product_id
                            );
                        })
                        ->values();



                    $load->setRelation(
                        'vehicleLoadStockItems',
                        $filteredItems
                    );




                    $load->is_re_requested =
                        ($load->re_requested_count ?? 0) > 0;

                    $load->is_return_requested =
                        ($load->return_requested_count ?? 0) > 0;
                }


                $response = [
                    'vehicleLoad' => $items,

                    'pagination' =>
                    generatePaginationData($result),
                ];


                return successResponse(
                    $response,
                    'Vehicle load retrieved successfully.',
                    0,
                    200
                );
            }




            $result = $query
                ->where('status', 1)
                ->orderBy('created_at', 'DESC')
                ->get();


            foreach ($result as $load) {



                $approvedProductIds =
                    $load->vehicleLoadStockDetails
                    ->pluck('product_id')
                    ->unique()
                    ->values();


                $filteredItems =
                    $load->vehicleLoadStockItems
                    ->filter(function ($item) use ($approvedProductIds) {

                        return $approvedProductIds->contains(
                            $item->product_id
                        );
                    })
                    ->values();



                $load->setRelation(
                    'vehicleLoadStockItems',
                    $filteredItems
                );



                $load->is_re_requested =
                    ($load->re_requested_count ?? 0) > 0;

                $load->is_return_requested =
                    ($load->return_requested_count ?? 0) > 0;
            }


            return successResponse(
                $result,
                'Vehicle load retrieved successfully.',
                0,
                200
            );
        } catch (Exception $e) {

            DB::rollBack();

            return errorResponse(
                null,
                $e->getMessage(),
                100,
                500
            );
        }
    }

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
    public function vehicleLoadApproved($id, $approvedItems = [], $data = null)
    {


        try {
            DB::beginTransaction();

            $orderDate = $data->order_date;
            $financialYear = FinancialYear::whereDate('start_date', '<=', $orderDate)
                ->whereDate('end_date', '>=', $orderDate)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.',  500);
            }

            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            $vehicleLoad = VehicleLoadStock::findOrFail($id);

            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            if (empty($approvedItems) || ! is_array($approvedItems)) {
                DB::rollBack();

                return errorResponse(null, 'No approved items provided.', 200, 200);
            }

            $hasValidProduct = false;

            foreach ($approvedItems as $item) {
                if (
                    isset($item['product_id'], $item['id']) &&
                    isset($item['approved_qty']) &&
                    (int) $item['approved_qty'] > 0
                ) {
                    $hasValidProduct = true;
                    break;
                }
            }

            if (! $hasValidProduct) {
                DB::rollBack();

                return errorResponse(null, 'Please add at least one product before approval.', 200, 200);
            }
            $vehicleLoad_user_type = Employee::where('user_id', $vehicleLoad->user_id)->first();

            // Normalize payment modes
            $paymentModes = [];
            if (isset($data['payment_modes']) && is_array($data['payment_modes']) && count($data['payment_modes']) > 0) {
                foreach ($data['payment_modes'] as $pm) {
                    if (!empty($pm['payment_mode'])) {
                        $paymentModes[] = [
                            'payment_mode' => $pm['payment_mode'],
                            'amount' => (float)($pm['amount'] ?? 0)
                        ];
                    }
                }
            }
            if (empty($paymentModes)) {
                $paymentModes[] = [
                    'payment_mode' => $data['payment_mode'] ?? 'NA',
                    'amount' => (float)($data['payment_amount'] ?? 0)
                ];
            }

            $totalPaidAmount = 0;
            $hasNonCredit = false;
            foreach ($paymentModes as $pm) {
                if (strtolower($pm['payment_mode']) !== 'credit') {
                    $totalPaidAmount += (float)$pm['amount'];
                    $hasNonCredit = true;
                }
            }


            $paymentAmount = $totalPaidAmount;

            if ($vehicleLoad_user_type->designation == 'outside_salesman') {

                $paymentStatus = 'Paid';
                $totalQty = 0;

                $totalAmount = 0;
                $totalCreditAmount = 0;

                $employee = Employee::where('user_id', $data->created_user)->first();



                foreach ($approvedItems as $row) {

                    $product = Products::where('id', $row['product_id'])->first();

                    $qty = (float)$row['approved_qty'];
                    $price = (float)$product->outside_selling_price;
                    $totalQty += $qty;
                    $totalAmount += $qty * $price;


                    $closingStock = self::getStockValue('<=', $orderDate, $row['product_id']);
                    if ($closingStock < $qty) {
                        DB::rollBack();
                        return errorResponse(
                            null,
                            $product->product_name .
                                ' available stock is only ' .
                                $closingStock .
                                '. Requested qty is ' .
                                $qty,
                            200,
                            200
                        );
                    }
                }

                $balanceAmount = 0;


                $totalRefundedAmount = 0;
                if ($data['credit'] > 0) {

                    if ($totalAmount > $data['credit']) {
                        $totalRefundedAmount = $data['credit'];
                        $balanceAmount = $totalAmount - $paymentAmount - $data['credit'];

                        $employee->balance_amount += $totalAmount - $paymentAmount;

                        $employee->save();
                    } else {
                        $totalRefundedAmount = $totalAmount;
                        $employee->balance_amount += $totalAmount;

                        $employee->save();
                    }
                } else {

                    $balanceAmount = $totalAmount - $paymentAmount;
                    $employee->balance_amount += $balanceAmount;
                    $employee->save();
                }
                if ($balanceAmount > 0) {
                    if ($balanceAmount > $vehicleLoad_user_type->credit_limit && $vehicleLoad_user_type->credit_limit != 0) {
                        DB::rollBack();

                        return errorResponse(null, 'Credit Limit Reached.', 200, 200);
                    }
                    $paymentStatus = 'Partial';
                }


                $orderNo = $this->generateOrderCode();


                $orderDate = $data->order_date;


                $orderStatus = Order::ORDER_STATUS_DELIVERED;


                $order = Order::create([
                    'customer_id'        => $data->created_user,
                    'order_no'           => $orderNo,
                    'total_quantity'     => $totalQty,
                    'total_amount'       => $totalAmount,
                    'paid_amount'        => $paymentAmount,
                    'balance_amount'     => $balanceAmount,
                    'total_refunded_amount' => $totalRefundedAmount,
                    'order_status'       => $orderStatus,
                    'payment_status'     => $paymentStatus,
                    'order_type'         => 'Outside Sale',
                    'invoice_type'       => 'web',
                    // 'commission_type_id' => $data->commission_type,
                    'order_by'           => $data->created_user,
                    'delivered_by'       => $data->user_id,
                    'order_date'         => $orderDate,
                    'financial_year_id'  => $financialYear->id,
                    'delivered_date'      => $orderDate,
                ]);
            }
            foreach ($approvedItems as $approvedItem) {


                $itemId = $approvedItem['id'] ?? null;
                $productId = $approvedItem['product_id'] ?? null;
                $product1 = Products::where('id', $approvedItem['product_id'])->first();
                $approvedQty = isset($approvedItem['approved_qty']) ? (int) $approvedItem['approved_qty'] : 0;
                $requestedQty = isset($approvedItem['requested_qty']) ? (int) $approvedItem['requested_qty'] : 0;

                $closingStock = self::getStockValue('<=', $orderDate, $productId);
                if ($closingStock < $approvedQty) {
                    DB::rollBack();
                    return errorResponse(
                        null,
                        $product1->product_name .
                            ' available stock is only ' .
                            $closingStock .
                            '. Requested qty is ' .
                            $approvedQty,
                        200,
                        200
                    );
                }


                $vehicleLaodDetails = VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)
                    ->where('product_id', $productId)
                    ->where('status', 'request')
                    ->first();

                if (Carbon::parse($orderDate)->toDateString() < Carbon::parse($vehicleLaodDetails->requested_date)->toDateString()) {
                    DB::rollBack();
                    return errorResponse(
                        null,
                        'approval date cannot be before requested date',
                        200,
                        200
                    );
                }
                if ($approvedQty < 0 || !$productId || !$itemId) {

                    // $item->available_qty = 0;
                    // $item->total_qty = 0;
                    // $item->save();
                    continue;
                }

                // Verify the item exists
                $item = VehicleLoadStockItem::where('id', $itemId)
                    ->where('vehicle_load_stock_id', $id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$item) {
                    DB::rollBack();

                    return errorResponse(null, 'Invalid vehicle load item (ID: ' . $itemId . ')', 200, 200);
                }

                // Validate approved quantity doesn't exceed requested
                if ($approvedQty > $item->total_qty) {
                    DB::rollBack();

                    return errorResponse(null, 'Approved quantity cannot exceed requested quantity for product ID: ' . $productId, 200, 200);
                }

                $product = Products::find($productId);
                if (! $product) {
                    DB::rollBack();

                    return errorResponse(null, 'Product not found (ID: ' . $productId . ')', 200, 200);
                }

                if ($product->total_stock < $approvedQty) {
                    DB::rollBack();

                    return errorResponse(null, 'Insufficient stock for product ' . $product->product_name, 200, 200);
                }

                // Deduct central stock
                $product->total_stock -= $approvedQty;
                $product->save();
                if ($approvedQty == 0) {
                    $item->delete();
                } else {

                    // Update item quantities
                    $item->available_qty = $approvedQty;
                    $item->total_qty = $approvedQty;
                    $item->save();
                }
                if ($vehicleLoad_user_type->designation == 'outside_salesman') {
                    $reason = 'vehicle opening stock for outsider';
                    $costOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $productId)->sum('cost_price');
                    $countOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $productId)->count();
                    if ($countOfPrevPurchases <= 0) {
                        $cost_price = Products::where('id', $productId)->first()->unit_price;
                    } else {
                        $cost_price = $costOfPrevPurchases / $countOfPrevPurchases;
                    }
                    StockOriginal::create([
                        'ref_id'       => $vehicleLoad->id,
                        'ref_no'       => $vehicleLoad->v_stock_code,
                        'product_id'   => $productId,
                        'quantity'     => $approvedQty,
                        'cost_price' => $cost_price,
                        'movement_type' => 'out',
                        'reason'       => 'Outsider Sale',
                        'date' =>   $data->order_date,
                        'created_by'   =>  $vehicleLoad->user_id,
                    ]);


                    $product = Products::where('id', $approvedItem['product_id'])->first();

                    $qty = (float)$approvedItem['approved_qty'];
                    $price = (float)$product->outside_selling_price;

                    $total = $qty * $price;

                    OrderItem::create([
                        'order_id'      => $order->id,
                        'product_id'    => $approvedItem['product_id'],
                        'quantity'      => $qty,
                        'free_quantity' => 0,
                        'price'         => $price,
                        'tax_percentage' => 0,
                        'tax_amount'    => 0,
                        'total'         => $total,
                    ]);
                } else {
                    $reason = 'vehicle opening stock';
                }
                // Stock movement
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $approvedQty,
                    'movement_type' => 'out',
                    'reason' => $reason,
                    'created_by' => $vehicleLoad->user_id,
                    // 'created_at' => $vehicleLoad->created_at ?? now(),
                    'date' =>   $data->order_date,
                ]);


                $vehicleLoadItemDetail = VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)->where('product_id', $productId)->first();

                if ($vehicleLoadItemDetail) {
                    $vehicleLoadItemDetail->status = 'approved';
                    $vehicleLoadItemDetail->description = 'Vehicle Load Approved';
                    $vehicleLoadItemDetail->approved_qty = $approvedQty;
                    $vehicleLoadItemDetail->save();
                }
            }


            if ($vehicleLoad_user_type->designation == 'outside_salesman') {

                $salesman = Employee::where('user_id', $data->created_user)->first();

                $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                    'date' => $orderDate ?? Carbon::now(),
                    'amount' => $order->total_amount,
                    'reference_type' => 'App\\Models\\Order',
                    'reference_id' => $order->id,
                    'description' => 'Outside Order ' . $order->order_no,
                    'customer_id' => $salesman->user_id,
                    'customer_type' => 'outside',
                    'user_id' => $data->created_user,

                ]);
                if (!$journalEntry) {
                    return errorResponse(null, 'Failed to create journal entry12', 500);
                }
                if ($paymentAmount == 0 || !$hasNonCredit) {
                    $creditPayment = CreditPayment::create([
                        'order_id'    => $order->id,
                        'customer_id' => $data->created_user,
                        'amount'      => $order->total_amount,
                        'date' => $orderDate ?? Carbon::now(),
                        'customer_type' => 'outside',
                    ]);
                } else {
                    $employee = Employee::where('user_id', $data->created_user)->first();


                    foreach ($paymentModes as $pm) {
                        $pMode = $pm['payment_mode'];
                        $pAmount = (float)$pm['amount'];
                        if (strtolower($pMode) === 'credit' || $pAmount <= 0) {
                            continue;
                        }

                        $orderPayment = OrderPayment::create([
                            'order_id'       => $order->id,
                            'customer_id'    => $data->created_user,
                            'user_id'        => $data->user_id,
                            'total_amount'   => $order->total_amount,
                            'paid_amount'    => $pAmount,
                            'balance_amount' => $order->balance_amount,
                            'payment_mode'   => $pMode,
                            'payment_date'   => $orderDate,
                            'financial_year_id'  => $financialYear->id,
                        ]);



                        $journalEntry1 = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $orderDate ?? Carbon::now(),
                            'amount' => $pAmount,
                            'reference_type' => 'App\\Models\\OrderPayment',
                            'reference_id' => $orderPayment->id,
                            'description' => 'Order Payment received - Order ' . $order->order_no,
                            'customer_id' => $data->created_user,
                            'customer_type' => 'outside',
                            'user_id' => $data->created_user,
                            'payment_mode' => strtolower($pMode),
                        ]);
                        if (!$journalEntry1) {
                            return errorResponse(null, 'Failed to create journal entry3', 500);
                        }
                    }
                }
                // $vehicleLoad->approved_date =  $orderDate;
                // $vehicleLoad->save();
            }

            $vehicleLoad->load_status = 'approved';
            $vehicleLoad->approved_date =  $orderDate;
            $vehicleLoad->save();

            DB::commit();

            return successResponse(null, 'Vehicle Load Approved successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function approveExtraLoadToVehicle($id, $approvedItems = [], $data = null)
    {


        try {
            DB::beginTransaction();
            $orderDate = $data->order_date ?? Carbon::now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $orderDate)
                ->whereDate('end_date', '>=', $orderDate)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.',  500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            $vehicleLoad = VehicleLoadStock::findOrFail($id);

            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            $hasValidProduct = false;

            foreach ($approvedItems as $item) {
                if (
                    isset($item['product_id'], $item['id']) &&
                    isset($item['approved_qty']) &&
                    (int) $item['approved_qty'] > 0
                ) {
                    $hasValidProduct = true;
                    break;
                }
            }


            if (! $hasValidProduct) {
                DB::rollBack();

                return errorResponse(null, 'Please add at least one product before approval.', 200, 200);
            }
            if (empty($approvedItems) || ! is_array($approvedItems)) {

                DB::rollBack();
                return errorResponse(null, 'No approved items provided.', 200, 200);
            }

            $vehicleLoad_user_type = Employee::where('user_id', $vehicleLoad->user_id)->first();

            // Normalize payment modes
            $paymentModes = [];
            if (isset($data['payment_modes']) && is_array($data['payment_modes']) && count($data['payment_modes']) > 0) {
                foreach ($data['payment_modes'] as $pm) {
                    if (!empty($pm['payment_mode'])) {
                        $paymentModes[] = [
                            'payment_mode' => $pm['payment_mode'],
                            'amount' => (float)($pm['amount'] ?? 0)
                        ];
                    }
                }
            }
            if (empty($paymentModes)) {
                $paymentModes[] = [
                    'payment_mode' => $data['payment_mode'] ?? 'NA',
                    'amount' => (float)($data['payment_amount'] ?? 0)
                ];
            }

            $totalPaidAmount = 0;
            $hasNonCredit = false;
            foreach ($paymentModes as $pm) {
                if (strtolower($pm['payment_mode']) !== 'credit') {
                    $totalPaidAmount += (float)$pm['amount'];
                    $hasNonCredit = true;
                }
            }
            $paymentAmount = $totalPaidAmount;

            if ($vehicleLoad_user_type->designation == 'outside_salesman') {

                $paymentStatus = 'Paid';
                $totalQty = 0;

                $totalAmount = 0;
                $totalCreditAmount = 0;
                foreach ($approvedItems as $row) {

                    $product = Products::where('id', $row['product_id'])->first();

                    $qty = (float)$row['approved_qty'];
                    $price = (float)$product->outside_selling_price;
                    $totalQty += $qty;
                    $totalAmount += $qty * $price;

                    $closingStock = self::getStockValue('<=', $orderDate, $product->id);
                    if ($closingStock < $qty) {
                        DB::rollBack();
                        return errorResponse(
                            null,
                            $product->product_name .
                                ' available stock is only ' .
                                $closingStock .
                                '. Requested qty is ' .
                                $qty,
                            200,
                            200
                        );
                    }
                }

                // if ($data['credit'] <= $totalAmount) {

                //     $totalRefundedAmount = $data['credit'];
                //     if ($data['credit'] > 0) {

                //         $data['credit'] -= $totalAmount;
                //     }


                //     $balanceAmount = $totalAmount - $paymentAmount - $data['credit'];
                // } else {

                //     $balanceAmount = 0;
                //     $totalRefundedAmount = $totalAmount;
                // }

                // if ($balanceAmount < 0) {
                //     $balanceAmount = 0;
                // }

                $totalRefundedAmount = 0;
                $employee = Employee::where('user_id', $data->created_user)->first();
                $balanceAmount = 0;

                if ($data['credit'] > 0) {

                    if ($totalAmount > $data['credit']) {
                        $totalRefundedAmount = $data['credit'];
                        $balanceAmount = $totalAmount - $paymentAmount - $data['credit'];

                        $employee->balance_amount += $totalAmount - $paymentAmount;

                        $employee->save();
                    } else {
                        $totalRefundedAmount = $totalAmount;
                        $employee->balance_amount += $totalAmount;

                        $employee->save();
                    }
                } else {

                    $balanceAmount = $totalAmount - $paymentAmount;

                    $employee->balance_amount += $balanceAmount;
                    $employee->save();
                }
                if ($balanceAmount > 0) {
                    if ($balanceAmount > $vehicleLoad_user_type->credit_limit && $vehicleLoad_user_type->credit_limit != 0) {
                        DB::rollBack();

                        return errorResponse(null, 'Credit Limit Reached.', 200, 200);
                    }
                    $paymentStatus = 'Partial';
                }


                $orderNo = $this->generateOrderCode();


                $orderDate = $data->order_date;


                $orderStatus = Order::ORDER_STATUS_DELIVERED;


                if ($balanceAmount > 0) {
                    $paymentStatus = 'Partial';
                }
                $prevOrdersBalanceAmount = Order::where('customer_id', $data['created_user'])->sum('balance_amount');

                $totalCreditAmount += $prevOrdersBalanceAmount;
                $totalCreditAmount += $balanceAmount;
                // if ($totalCreditAmount > $vehicleLoad_user_type->credit_limit) {
                if ($employee->balance_amount > $vehicleLoad_user_type->credit_limit && $vehicleLoad_user_type->credit_limit != 0) {
                    DB::rollBack();

                    return errorResponse(null, 'Credit Limit Reached.', 200, 200);
                }
                $orderNo = $this->generateOrderCode();


                // $orderDate =  $vehicleLoad->created_at;
                $deliveredBy = $data->created_user;
                $orderStatus = Order::ORDER_STATUS_DELIVERED;

                $order = Order::create([
                    'customer_id'        => $data->created_user,
                    'order_no'           => $orderNo,
                    'total_quantity'     => $totalQty,
                    'total_amount'       => $totalAmount,
                    'paid_amount'        => $paymentAmount,
                    'balance_amount'     => $balanceAmount,
                    'total_refunded_amount' => $totalRefundedAmount,
                    'order_status'       => $orderStatus,
                    'payment_status'     => $paymentStatus,
                    'order_type'         => 'Outside Sale',
                    'invoice_type'       => 'web',
                    // 'commission_type_id' => $data->commission_type,
                    'order_by'           => $data->created_user,
                    'delivered_by'       => $data->user_id,
                    'order_date'         => $orderDate,
                    'financial_year_id'  => $financialYear->id,
                    'delivered_date'      => $orderDate,
                ]);

                // $employee->balance_amount += $balanceAmount + $data['credit'];
                // $employee->save();
                // if ($data['credit'] < $totalAmount) {
                //     $employee->balance_amount += $balanceAmount + $data['credit'];;
                //     $employee->save();
                // } else {
                //     $employee->balance_amount += $balanceAmount + $totalAmount;
                //     $employee->save();
                // }
            }

            foreach ($approvedItems as $approvedItem) {
                $detailId = $approvedItem['id'] ?? null;
                $productId = $approvedItem['product_id'] ?? null;
                $approvedQty = isset($approvedItem['approved_qty']) ? (int) $approvedItem['approved_qty'] : 0;
                $requestedQty = isset($approvedItem['requested_qty']) ? (int) $approvedItem['requested_qty'] : 0;

                // if ($approvedQty <= 0 || !$productId || !$detailId) {
                //     continue;
                // }

                if ($approvedQty < 0 || ! $productId || ! $detailId) {
                    continue;
                }

                // Verify the detail exists and is in re_requested status
                $detail = VehicleLoadItemDetail::where('id', $detailId)
                    ->where('vehicle_load_id', $id)
                    ->where('status', 're_requested')
                    ->where('product_id', $productId)
                    ->first();

                if (!$detail) {
                    DB::rollBack();

                    return errorResponse(null, 'Invalid re-request item (ID: ' . $detailId . ')', 200, 200);
                }

                // Get the original requested quantity from detail (database field is 'quantity')
                $originalRequestedQty = $detail->requested_qty ?? $requestedQty;

                // Validate approved quantity doesn't exceed requested
                if ($approvedQty > $originalRequestedQty) {
                    DB::rollBack();

                    return errorResponse(null, 'Approved quantity cannot exceed requested quantity for product ID: ' . $productId, 200, 200);
                }

                $vehicleLaodDetails = VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)
                    ->where('product_id', $productId)
                    ->where('status', 're_requested')
                    ->first();


                if (Carbon::parse($orderDate)->toDateString() < Carbon::parse($vehicleLaodDetails->requested_date)->toDateString()) {
                    DB::rollBack();
                    return errorResponse(
                        null,
                        'approval date cannot be before requested date',
                        200,
                        200
                    );
                }
                $product = Products::find($productId);
                if (!$product) {
                    DB::rollBack();

                    return errorResponse(null, 'Product not found (ID: ' . $productId . ')', 200, 200);
                }

                if ($product->total_stock < $approvedQty) {
                    DB::rollBack();

                    return errorResponse(null, 'Insufficient stock for product ' . $product->product_name, 200, 200);
                }

                // Deduct central stock
                $product->total_stock -= $approvedQty;
                $product->save();

                // Update or create vehicle load stock item
                $stockItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $id)
                    ->where('product_id', $productId)
                    ->first();

                if ($stockItem) {

                    $stockItem->total_qty += $approvedQty;
                    $stockItem->available_qty += $approvedQty;
                    $stockItem->save();
                } else {
                    VehicleLoadStockItem::create([
                        'vehicle_load_stock_id' => $id,
                        'product_id' => $productId,
                        'available_qty' => $approvedQty,
                        'total_qty' => $approvedQty,
                        'created_at' => now(),
                    ]);
                }

                if ($vehicleLoad_user_type->designation == 'outside_salesman') {
                    $reason = 'vehicle opening re-stock for outsider';
                    $costOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $productId)->sum('cost_price');
                    $countOfPrevPurchases = StockOriginal::where('movement_type', 'in')
                        ->whereIn('reason', ['Purchase', 'opening_stock'])

                        ->where('product_id', $productId)->count();
                    if ($countOfPrevPurchases <= 0) {
                        $cost_price = Products::where('id', $productId)->first()->unit_price;
                    } else {
                        $cost_price = $costOfPrevPurchases / $countOfPrevPurchases;
                    }
                    StockOriginal::create([
                        'ref_id'       => $vehicleLoad->id,
                        'ref_no'       => $vehicleLoad->v_stock_code,
                        'product_id'   => $productId,
                        'quantity'     => $approvedQty,
                        'cost_price' => $cost_price,
                        'movement_type' => 'out',
                        'reason'       => 'Outsider Sale',
                        'date' => $vehicleLoad->created_at ?? now(),
                        'created_by'   =>  $vehicleLoad->user_id,
                    ]);


                    $product = Products::where('id', $approvedItem['product_id'])->first();

                    $qty = (float)$approvedItem['approved_qty'];
                    $price = (float)$product->outside_selling_price;

                    $total = $qty * $price;

                    OrderItem::create([
                        'order_id'      => $order->id,
                        'product_id'    => $approvedItem['product_id'],
                        'quantity'      => $qty,
                        'free_quantity' => 0,
                        'price'         => $price,
                        'tax_percentage' => 0,
                        'tax_amount'    => 0,
                        'total'         => $total,
                    ]);
                } else {
                    $reason = 'vehicle re-request approved';
                }
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $approvedQty,
                    'movement_type' => 'out',
                    'reason' => $reason,
                    'created_by' => $vehicleLoad->user_id,
                    'date' => $detail->requested_date ? $detail->requested_date : now(),
                ]);


                if ($approvedQty > 0) {
                    VehicleLoadItemDetail::where('id', $detailId)
                        ->where('vehicle_load_id', $id)
                        ->where('status', 're_requested')
                        ->where('product_id', $productId)
                        ->update([
                            'status' => 'approved',
                            'approved_qty' => $approvedQty,

                        ]);
                } else {
                    VehicleLoadItemDetail::where('id', $detailId)
                        ->where('vehicle_load_id', $id)
                        ->where('status', 're_requested')
                        ->where('product_id', $productId)
                        ->update([
                            'status' => 'rejected',
                            'approved_qty' => $approvedQty,

                        ]);
                }
            }
            if ($vehicleLoad_user_type->designation == 'outside_salesman') {

                $salesman = Employee::where('user_id', $data->created_user)->first();

                $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                    'date' => $orderDate ?? Carbon::now(),
                    'amount' => $order->total_amount,
                    'reference_type' => 'App\\Models\\Order',
                    'reference_id' => $order->id,
                    'description' => 'Outside Order ' . $order->order_no,
                    'customer_id' => $salesman->user_id,
                    'customer_type' => 'outside',
                    'user_id' => $data->created_user,

                ]);
                if (!$journalEntry) {
                    return errorResponse(null, 'Failed to create journal entry12', 500);
                }

                if ($paymentAmount == 0 || !$hasNonCredit) {
                    $creditPayment = CreditPayment::create([
                        'order_id'    => $order->id,
                        'customer_id' => $data->created_user,
                        'amount'      => $order->total_amount,
                        'date' => $orderDate ?? Carbon::now(),
                        'customer_type' => 'outside',
                    ]);
                } else {
                    foreach ($paymentModes as $pm) {
                        $pMode = $pm['payment_mode'];
                        $pAmount = (float)$pm['amount'];
                        if (strtolower($pMode) === 'credit' || $pAmount <= 0) {
                            continue;
                        }

                        $orderPayment = OrderPayment::create([
                            'order_id'       => $order->id,
                            'customer_id'    => $data->created_user,
                            'user_id'        => $data->user_id,
                            'total_amount'   => $order->total_amount,
                            'paid_amount'    => $pAmount,
                            'balance_amount' => $order->balance_amount,
                            'payment_mode'   => $pMode,
                            'payment_date'   => $orderDate,
                            'financial_year_id'  => $financialYear->id,
                        ]);

                        $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
                            'date' => $orderDate ?? Carbon::now(),
                            'amount' => $pAmount,
                            'reference_type' => 'App\\Models\\OrderPayment',
                            'reference_id' => $orderPayment->id,
                            'description' => 'Order Payment received - Order ' . $order->order_no,
                            'customer_id' => $data->created_user,
                            'customer_type' => 'outside',
                            'user_id' => $data->created_user,
                            'payment_mode' => strtolower($pMode),
                        ]);
                        if (!$journalEntry) {
                            return errorResponse(null, 'Failed to create journal entry3', 500);
                        }
                    }

                    if ($order->balance_amount > 0) {
                        $creditPayment = CreditPayment::create([
                            'order_id'    => $order->id,
                            'customer_id' => $data->created_user,
                            'amount'      => $order->balance_amount,
                            'date' => $orderDate ?? Carbon::now(),
                            'customer_type' => 'outside',
                        ]);
                    }
                }
            }

            DB::commit();

            return successResponse(null, 'Re-request items approved and added to vehicle load.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function rejectExtraLoadToVehicle($request)
    {


        try {
            DB::beginTransaction();
            // $vehicleLoad = VehicleLoadStock::findOrFail($id);
            $vehicleLoad = VehicleLoadStock::where('id', $request->id)->withCount([
                'reRequestedStockItems as re_requested_count',
                'returnRequestedStockItems as return_requested_count',
            ])->first();


            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }
            $approvedItems = $request->approved_items;

            $hasValidProduct = false;

            foreach ($approvedItems as $item) {
                if (
                    isset($item['product_id'], $item['id']) &&
                    isset($item['approved_qty']) &&
                    (int) $item['approved_qty'] > 0
                ) {
                    $hasValidProduct = true;
                    break;
                }
            }


            if (! $hasValidProduct) {
                DB::rollBack();

                return errorResponse(null, 'Please add at least one product before approval.', 200, 200);
            }
            if (empty($approvedItems) || ! is_array($approvedItems)) {

                DB::rollBack();
                return errorResponse(null, 'No approved items provided.', 200, 200);
            }

            foreach ($approvedItems as $approvedItem) {

                $productId = $approvedItem['product_id'] ?? null;
                $approvedQty = isset($approvedItem['approved_qty']) ? (int) $approvedItem['approved_qty'] : 0;
                $requestedQty = isset($approvedItem['requested_qty']) ? (int) $approvedItem['requested_qty'] : 0;

                $stockItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $request->id)
                    ->where('product_id', $productId)
                    ->first();

                if (!$stockItem) {

                    VehicleLoadStockItem::create([
                        'vehicle_load_stock_id' => $request->id,
                        'product_id' => $productId,
                        'available_qty' => 0,
                        'total_qty' => 0,
                        'created_at' => now(),
                    ]);
                }
            }
            // when vehicle load reject then change status to 1 for list available vehicles
            $vehicle = Vehicle::findOrFail($vehicleLoad->vehicle_id);
            $vehicle->load_status = 1;
            $vehicle->save();


            $productIds = VehicleLoadStockItem::where(
                'vehicle_load_stock_id',
                $request->id
            )->pluck('product_id')->toArray();
            // foreach ($items as $item) {
            // $product = Products::find($item->product_id);

            // if ($product) {

            // Update vehicle load item details
            $vehicleLoadItemDetails = VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)
                ->whereIn('product_id', $productIds)
                ->where('status', 're_requested')
                ->update([
                    'status' => 'rejected',
                    'description' => 'Vehicle Load Rejected',
                ]);
            // }
            // }

            DB::commit();

            return successResponse(null, 'Vehicle Load Rejected successfully.', 0, 200);
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
    public function vehicleLoadReject($id)
    {
        try {
            DB::beginTransaction();
            // $vehicleLoad = VehicleLoadStock::findOrFail($id);
            $vehicleLoad = VehicleLoadStock::where('id', $id)->withCount([
                'reRequestedStockItems as re_requested_count',
                'returnRequestedStockItems as return_requested_count',
            ])->first();
            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            // when vehicle load reject then change status to 1 for list available vehicles
            $vehicle = Vehicle::findOrFail($vehicleLoad->vehicle_id);
            $vehicle->load_status = 1;
            $vehicle->save();

            $items = VehicleLoadStockItem::where('vehicle_load_stock_id', $id)->get();

            foreach ($items as $item) {
                $product = Products::find($item->product_id);

                if ($product) {

                    // Update vehicle load item details
                    VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)
                        ->where('product_id', $product->id)
                        ->update([
                            'status' => 'rejected',
                            'description' => 'Vehicle Load Rejected',
                        ]);
                }
            }

            if ($vehicleLoad->re_requested_count == 0 && $vehicleLoad->return_requested_count == 0) {
                $vehicleLoad->load_status = 'rejected';
                $vehicleLoad->save();
            }

            DB::commit();

            return successResponse(null, 'Vehicle Load Rejected successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // Approve return vehicle stock (admin approval with editable qty)
    public function approveReturnVehicleStock($id, $approvedItems = [], $date = null)
    {

        try {
            DB::beginTransaction();
            $vehicleLoad = VehicleLoadStock::findOrFail($id);

            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            if (empty($approvedItems) || ! is_array($approvedItems)) {
                DB::rollBack();

                return errorResponse(null, 'No approved items provided.', 200, 200);
            }

            foreach ($approvedItems as $approvedItem) {
                $detailId = $approvedItem['id'] ?? null;
                $productId = $approvedItem['product_id'] ?? null;
                $approvedQty = isset($approvedItem['approved_qty']) ? (int) $approvedItem['approved_qty'] : 0;
                $requestedQty = isset($approvedItem['requested_qty']) ? (int) $approvedItem['requested_qty'] : 0;

                // if ($approvedQty <= 0 || !$productId || !$detailId) {
                //     continue;
                // }

                if ($approvedQty < 0 || ! $productId || ! $detailId) {
                    continue;
                }

                // Verify the detail exists and is in return_requested status
                $detail = VehicleLoadItemDetail::where('id', $detailId)
                    ->where('vehicle_load_id', $id)
                    ->where('status', 'return_requested')
                    ->where('product_id', $productId)
                    ->first();

                if (! $detail) {
                    DB::rollBack();

                    return errorResponse(null, 'Invalid return request item (ID: ' . $detailId . ')', 200, 200);
                }

                // Get the original requested quantity from detail
                $originalRequestedQty = $detail->requested_qty ?? $requestedQty;

                // Validate approved quantity doesn't exceed requested
                if ($approvedQty > $originalRequestedQty) {
                    DB::rollBack();

                    return errorResponse(null, 'Approved quantity cannot exceed requested quantity for product ID: ' . $productId, 200, 200);
                }

                // Find the vehicle load stock item
                $stockItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $id)
                    ->where('product_id', $productId)
                    ->first();

                if (! $stockItem) {
                    DB::rollBack();

                    return errorResponse(null, 'Vehicle load stock item not found for product ID: ' . $productId, 200, 200);
                }

                // Validate approved quantity doesn't exceed available quantity
                if ($approvedQty > $stockItem->available_qty) {
                    DB::rollBack();

                    return errorResponse(null, 'Approved quantity cannot exceed available quantity for product ID: ' . $productId, 200, 200);
                }

                // Get product
                $product = Products::find($productId);
                if (! $product) {
                    DB::rollBack();

                    return errorResponse(null, 'Product not found (ID: ' . $productId . ')', 200, 200);
                }

                // Reduce available quantity from vehicle stock
                $stockItem->available_qty -= $approvedQty;
                $stockItem->total_qty -= $approvedQty;
                $stockItem->save();

                // Add quantity back to central stock
                $product->total_stock += $approvedQty;
                $product->save();

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $approvedQty,
                    'movement_type' => 'in',
                    'reason' => 'return_vehicle_stock_approved',
                    'created_by' => $vehicleLoad->user_id,
                    'date' => $detail->requested_date ?? now(),
                ]);

                // Update detail status to approved
                VehicleLoadItemDetail::where('id', $detailId)
                    ->where('vehicle_load_id', $id)
                    ->where('status', 'return_requested')
                    ->where('product_id', $productId)
                    ->update([
                        'status' => 'return_approved',
                        'approved_qty' => $approvedQty,
                        'description' => 'Return Stock Approved',
                    ]);
            }

            DB::commit();

            return successResponse(null, 'Return stock approved successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // Approve return vehicle stock (admin approval with editable qty)
    public function rejectReturnVehicleStock($id)
    {

        try {
            DB::beginTransaction();
            // $vehicleLoad = VehicleLoadStock::findOrFail($id);
            $vehicleLoad = VehicleLoadStock::where('id', $id)->withCount([
                'reRequestedStockItems as re_requested_count',
                'returnRequestedStockItems as return_requested_count',
            ])->first();
            if (! $vehicleLoad) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            // when vehicle load reject then change status to 1 for list available vehicles
            $vehicle = Vehicle::findOrFail($vehicleLoad->vehicle_id);
            $vehicle->load_status = 1;
            $vehicle->save();

            $items = VehicleLoadStockItem::where('vehicle_load_stock_id', $id)->get();

            foreach ($items as $item) {
                // $item->total_qty = 0;
                // $item->save();
                $product = Products::find($item->product_id);

                if ($product) {

                    // Update vehicle load item details
                    VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoad->id)
                        ->where('product_id', $product->id)
                        ->where('status', 'return_requested')
                        ->update([
                            'status' => 'rejected',
                            'description' => 'Vehicle Load Rejected',
                        ]);
                }
            }

            DB::commit();

            return successResponse(null, 'Vehicle Load Rejected successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function rejectClosingRequest($request)
    {
        try {
            $vehicleLoad = VehicleLoadStock::with([
                'vehicleLoadStockItems',
            ])
                ->where('id', $request->id)
                ->where('day_status', 'closing_requested')->first();

            if (! $vehicleLoad) {
                return errorResponse(null, 'No vehicle load found', 100, 200);
            }

            if ($vehicleLoad->day_status === 'closed') {
                return errorResponse(null, 'This vehicle day stock is already closed.', 100, 200);
            }

            $vehicleLoad->update([
                'day_status' => 'started',

            ]);

            return successResponse($vehicleLoad, 'Vehicle Load closing request rejected.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    // Get list of return stock requests for admin
    public function getAllReturnStockRequests($data, $platform = 'web')
    {
        try {
            $query = VehicleLoadStock::with('user', 'vehicleLoadStockItems.product', 'vehicle')
                ->whereHas('returnRequestedStockItems')
                ->with(['returnRequestedStockItems' => function ($q) {
                    $q->with('product');
                }]);

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('v_stock_code', 'LIKE', "%{$data->search}%")
                        ->orWhereHas('vehicle', function ($v) use ($data) {
                            $v->where('vehicle_name', 'LIKE', "%{$data->search}%")
                                ->orWhere('vehicle_plate_no', 'LIKE', "%{$data->search}%");
                        })
                        ->orWhereHas('user', function ($u) use ($data) {
                            $u->where('name', 'LIKE', "%{$data->search}%");
                        });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'vehicleLoad' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];

                return successResponse($response, 'Return stock requests retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();

                return successResponse($result, 'Return stock requests retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getAllVehicleLoadReturn($data, $platform = 'web')
    {

        try {
            $query = VehicleLoadStock::with('user', 'vehicleLoadStockItems.product', 'vehicle')
                ->where('day_status', 'closing_requested');

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('v_stock_code', 'LIKE', "%{$data->search}%")
                        ->orWhereHas('vehicle', function ($v) use ($data) {
                            $v->where('vehicle_name', 'LIKE', "%{$data->search}%")
                                ->orWhere('vehicle_plate_no', 'LIKE', "%{$data->search}%");
                        })
                        ->orWhereHas('user', function ($u) use ($data) {
                            $u->where('name', 'LIKE', "%{$data->search}%");
                        });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'vehicleLoad' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];

                return successResponse($response, 'Vehicle load retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();

                return successResponse($result, 'Vehicle load retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function vehicleLoadReturn($request)
    {

        try {
            DB::beginTransaction();
            $vehicleLoad = VehicleLoadStock::with([
                'vehicleLoadStockItems',
            ])
                ->where('id', $request->id)
                ->where('day_status', 'closing_requested')->first();

            if (! $vehicleLoad) {
                return errorResponse(null, 'No vehicle load found', 100, 200);
            }

            if ($vehicleLoad->day_status === 'closed') {
                return errorResponse(null, 'This vehicle day stock is already closed.', 100, 200);
            }
            $requested_date = $vehicleLoad->closing_req_date ?? now();
            $user = User::find($vehicleLoad->user_id);

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
            $payToPending = 0;
            $originalIds = [];
            if ($user->user_type == 'salesman') {

                $vehicleLoad->update([
                    'day_status' => 'closed',
                    'closing_date' => $requested_date,
                ]);
            } else {

                $groupedReturnedOrders = $request['completeData']['orders'];

                if ($groupedReturnedOrders) {
                    $orderIds = [];
                    $extraAmountAfter = $request['completeData']['orderRefundAmount'];

                    $customer_id = $groupedReturnedOrders[0]['customer_id'];



                    foreach ($groupedReturnedOrders as $orderId => $items) {


                        $orderIds[] = $items['order_id'];
                        $originalIds[] = $items['order_id'];
                        $originalOrder = Order::where('id', $items['order_id'])->with('orderItems')->first();

                        if (!$originalOrder) {
                            Log::channel('sales_return')->info('Order not found', ['order_id' => $items['order_id']]);
                            DB::rollBack();
                            return errorResponse(null, 'Order not found.', 404, 404);
                        }

                        if ($originalOrder->order_status !== 'delivered') {
                            DB::rollBack();
                            Log::channel('sales_return')->info('Only delivered orders can be returned', ['order_id' => $items['order_id']]);
                            return errorResponse(null, 'Only delivered orders can be returned.', 400, 400);
                        }

                        if (SalesReturn::where('order_id', $originalOrder->id)->whereNotIn('return_status', ['approved', 'rejected'])->exists()) {
                            DB::rollBack();
                            Log::channel('sales_return')->info('Please Accept or Reject the Existing Sales return of this order', ['order_id' => $items['order_id']]);
                            return errorResponse(null, 'Please Accept or Reject the Existing Sales return of this order.', 400, 400);
                        }


                        $totalQty = 0;
                        $totalAmount = 0;

                        foreach ($items['items'] as $item) {

                            $orderItem = OrderItem::where('order_id', $item['order_id'])
                                ->where('product_id', $item['product_id'])
                                ->first();

                            if (!$orderItem) {
                                throw new Exception(
                                    "Order item not found for order {$items['order_id']} and product {$item['product_id']}"
                                );
                            }

                            // Ensure product matches
                            if (isset($item['product_id']) && $item['product_id'] != $orderItem->product_id) {
                                DB::rollBack();
                                return errorResponse(null, 'Product mismatch for order item ID ' . $orderItem->id, 400, 400);
                            }

                            $returnQty = $item['return_qty'];


                            // Already returned (approved) qty
                            $alreadyReturned = (int)$orderItem->returned_quantity; // we track returned_quantity on order_items

                            $maxReturnable = $orderItem->quantity - $alreadyReturned;

                            if ($returnQty > $maxReturnable) {
                                DB::rollBack();
                                $productName = $orderItem->product ? $orderItem->product->product_name : 'Product ID ' . $orderItem->product_id;
                                return errorResponse(null, "Return quantity ({$returnQty}) cannot exceed ordered quantity ({$orderItem->quantity}) for {$productName}. Maximum returnable: {$maxReturnable}.", 400, 400);
                            }

                            $price = (float)$item['item_price'];

                            $lineTotal = $returnQty * $price;

                            $totalQty += $returnQty;

                            $totalAmount += $lineTotal;
                        }

                        $orderTotalQty = $originalOrder->orderItems->sum('quantity');
                        $returnType = ($totalQty >= $orderTotalQty) ? 'full' : 'partial';

                        $salesReturn = SalesReturn::create([
                            'order_id' => $originalOrder->id,
                            'customer_id' => $originalOrder->customer_id,
                            'return_no' => null,

                            'total_quantity' => $totalQty,

                            'total_amount' => $totalAmount,

                            'return_status' => 'approved',

                            'return_type' => $returnType,
                            'financial_year_id' => $financialYear->id,

                            'return_reason' => 'Outside Salesman Closing Vechicle',

                            'returned_by' => $vehicleLoad->user_id ?? null,

                            'return_date' => $request->return_date,

                            'is_outside_salesman' => 1,
                        ]);

                        generateAutoCode(
                            $salesReturn,
                            'return_no',
                            'AI_SALES_RETURN_CODE'
                        );
                        $originalOrder->is_returned = 1;
                        $originalOrder->save();
                        $returnCustomer = User::find($salesReturn->customer_id);
                        $returnCustomerName = $returnCustomer->name;
                        $journalEntry = $this->journalEntryService->createSalesReturnCreditEntry([
                            'date' => $request->return_date,
                            'reference_type' => 'App\\Models\\SalesReturn',
                            'reference_id' => $salesReturn->id,
                            'description' => 'Sales Return: ' . ($salesReturn->return_no ?? $salesReturn->id) . ' - ' . $returnCustomerName,
                            'amount' => $salesReturn->total_amount,
                            'user_id' => 1,

                            'customer_id' => $returnCustomer->id,
                            'customer_name' => $returnCustomerName,

                            'customer_type' => 'outside',
                        ]);



                        if (!$journalEntry) {
                            return errorResponse(null, 'Failed to create journal entry', 500);
                        }
                        Log::channel('sales_return')->info('Journal entry created for sales return', [
                            'sales_return_id' => $salesReturn->id,

                        ]);

                        // $refundMode = strtolower($request->refund_mode ?? 'order_balance'); // e.g., cash, credit, bank_transfer, order_balance
                        $employee = Employee::where('user_id', $salesReturn->customer_id)->first();


                        $balanceAfterRefund = 0;



                        if ((float)$items['order_balance_amount'] > 0) {
                            $balanceAfterRefund = (float)$items['order_balance_amount'] - (float)$items['totalPrice'];

                            if ($balanceAfterRefund >= 0) {

                                SalesReturnPayment::create([
                                    'sales_return_id' => $salesReturn->id,
                                    'order_id' => $salesReturn->order_id,
                                    'customer_id' => $salesReturn->customer_id,
                                    'user_id' => $salesReturn->returned_by,
                                    'refund_amount' => $items['totalPrice'],
                                    'refund_mode' => 'order_balance', // Use actual refund mode (order_balance if fully from balance)
                                    'refund_date' => $salesReturn->return_date,
                                    'description' => 'outside salesman salesreturn',
                                ]);
                                generateAutoCode(
                                    $salesReturn,
                                    'return_no',
                                    'AI_SALES_RETURN_CODE'
                                );
                                $originalOrder->balance_amount -= $items['totalPrice'];
                                $employee->balance_amount -= $items['totalPrice'];
                                $employee->save();

                                if ($balanceAfterRefund == 0) {
                                    $originalOrder->payment_status = 'Paid';
                                }
                                $originalOrder->total_refund_from_order_balance = $items['totalPrice'];
                                $originalOrder->save();
                            } else {

                                SalesReturnPayment::create([
                                    'sales_return_id' => $salesReturn->id,
                                    'order_id' => $salesReturn->order_id,
                                    'customer_id' => $salesReturn->customer_id,
                                    'user_id' => $salesReturn->returned_by,
                                    'refund_amount' => (float)$items['order_balance_amount'],
                                    'refund_mode' => 'order_balance', // Use actual refund mode (order_balance if fully from balance)
                                    'refund_date' => $salesReturn->return_date,
                                    'description' => 'outside salesman salesreturn',
                                ]);
                                $employee->balance_amount -= (float)$items['order_balance_amount'];
                                $employee->save();
                                $originalOrder->balance_amount = 0;

                                $originalOrder->payment_status = 'Paid';

                                $originalOrder->total_refund_from_order_balance = (float)$items['order_balance_amount'];
                                $originalOrder->save();

                                $refundPaymentModes = $items['refund_payment_modes'] ?? [];
                                $originalOrder->save();


                                $employee->balance_amount -= (float)$items['orderRefundAmount'];
                                $employee->save();
                                $payToPending += (float)$items['orderRefundAmount'];
                            }
                        } else {


                            $payToPending = (float)$items['totalPrice'];
                            $employee->balance_amount -= (float)$items['orderRefundAmount'];
                            $employee->save();
                        }



                        foreach ($items['items'] as $item) {

                            $orderItem = OrderItem::where('order_id', $items['order_id'])
                                ->where('product_id', $item['product_id'])
                                ->first();

                            if (!$orderItem) {
                                throw new Exception(
                                    "Order item not found for order {$items['order_id']} and product {$item['product_id']}"
                                );
                            }

                            $returnQty = $item['return_qty'];



                            SalesReturnItem::create([
                                'sales_return_id' => $salesReturn->id,

                                'order_item_id' => $orderItem->id,

                                'product_id' => $orderItem->product_id,

                                'return_quantity' => $returnQty,

                                'price' => $orderItem->price,

                                'total' => $orderItem->price * $returnQty,
                            ]);



                            $orderItem->returned_quantity =
                                ($orderItem->returned_quantity ?? 0)
                                + $returnQty;

                            $orderItem->save();


                            $soldCostPrice = StockOriginal::where(
                                'ref_id',
                                $orderItem->order_id
                            )
                                ->where(
                                    'product_id',
                                    $orderItem->product_id
                                )
                                ->first();

                            $cost_price = $soldCostPrice
                                ? $soldCostPrice->cost_price
                                : ($orderItem->price ?? 0);

                            StockOriginal::create([
                                'ref_id' => $salesReturn->id,

                                'ref_no' =>
                                $salesReturn->return_no
                                    ?? $originalOrder->order_no,

                                'product_id' =>
                                $orderItem->product_id,

                                'quantity' =>
                                $returnQty,

                                'cost_price' =>
                                $cost_price,

                                'movement_type' => 'in',

                                'reason' => 'Sales Return',

                                'date' =>
                                $salesReturn->return_date,

                                'created_by' =>
                                $vehicleLoad->user_id,
                            ]);
                        }
                    }

                    // if ($extraAmountAfter > 0) {
                    //     $employee->balance_amount -= $extraAmountAfter;
                    //     $employee->save();
                    // }
                } else {
                    $vehicle = Vehicle::find($vehicleLoad->vehicle_id);

                    if ($vehicle) {
                        $vehicle->load_status = 1;
                        $vehicle->save();
                    }

                    $vehicleLoad->day_status = 'closed';
                    $vehicleLoad->save();

                    DB::commit();
                    return successResponse($vehicleLoad, 'Vehicle Load retrieved successfully.', 0, 200);
                }
            }



            foreach ($vehicleLoad->vehicleLoadStockItems as $stock_item) {

                $product = Products::where('id', $stock_item->product_id)->first();

                if (! $product) {
                    return errorResponse(null, 'Not available the product', 100, 200);
                }

                $balance_qty = $product->total_stock + $stock_item->available_qty;

                $product->update([
                    'total_stock' => $balance_qty,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $stock_item->available_qty,
                    'movement_type' => 'in',
                    'reason' => 'closing stock',
                    'created_by' => $request['user_id'],
                    'date' => $requested_date,
                ]);
            }

            $vehicle = Vehicle::find($vehicleLoad->vehicle_id);

            if ($vehicle) {
                $vehicle->load_status = 1;
                $vehicle->save();
            }

            $vehicleLoad->day_status = 'closed';
            $vehicleLoad->save();

            DB::commit();




            if ($user->user_type == 'outside_salesman') {
                $amountAfterPendingPayment = 0;
                if ($payToPending > 0) {

                    $amountAfterPendingPayment = $this->payToPendingFun($payToPending, $user, $salesReturn);
                }
                if ($employee->balance_amount < 0) {

                    $ret = $this->payCreditBalanceMultiplePayment($request['refund_payment_modes'], $financialYear, $request->return_date, $customer_id, 'employee', $salesReturn);
                }
            }


            return successResponse($vehicleLoad, 'Vehicle Load retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function payToPendingFun($amountfrom, $user, $salesReturn)
    {

        $employee = Employee::where('user_id', $user->id)->first();

        $amount = $amountfrom;
        $previousOrders = Order::with('orderItems')
            ->where('customer_id', $user->id)
            ->whereRaw('LOWER(payment_status) != ?', ['paid'])
            ->where('order_status', 'delivered')
            ->where('order_type', 'Outside Sale')

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

                // $employee->balance_amount -= $prevOrder->balance_amount;
            } else {
                $prevOrder->total_refund_from_order_balance += (float)$amount;
                $prevOrder->balance_amount -= (float)$amount;
                (float)$amount -= (float)$amount;
                $prevOrder->save();
            }
            SalesReturnPayment::create([
                'sales_return_id' => $salesReturn->id,
                'order_id' => $salesReturn->order_id,
                'customer_id' => $salesReturn->customer_id,
                'user_id' => $salesReturn->returned_by,
                'refund_amount' => (float)$amount,
                'refund_mode' => 'refund_from_prev', // Use actual refund mode (order_balance if fully from balance)
                'refund_date' => $salesReturn->return_date,
                'description' => 'outside salesman salesreturn',
            ]);
        }

        return $amount;
    }
    public function payCreditBalanceMultiplePayment($refundPaymentModes, $financialYear, $returnDate, $customer_id, $userType, $salesReturn)
    {
        try {

            $customer = User::find($customer_id);

            // Get previous unpaid orders
            $previousOrders = Order::with('orderItems')
                ->where('customer_id', $customer->id)
                ->whereRaw('LOWER(payment_status) != ?', ['paid'])
                ->where('order_status', 'delivered')
                ->where('order_type', 'Outside Sale')

                ->orderBy('created_at', 'asc')
                ->get();

            Log::info('PREVIOUS ORDERS COUNT', [
                'count' => $previousOrders->count()
            ]);




            DB::beginTransaction();
            foreach ($previousOrders as $prevOrder) {


                $remaining = $prevOrder->balance_amount;

                if ($remaining <= 0) continue;



                $applied = min($customer->balance_amount, $remaining);

                $prevOrder->update([

                    'balance_amount' => $remaining - $applied,
                    'total_refunded_amount' => $prevOrder->total_refunded_amount + $applied,
                    'payment_status' => ($remaining - $applied) <= 0 ? "Paid" : "Partial",
                ]);

                SalesReturnPayment::create([
                    'sales_return_id' => $salesReturn->id,
                    'order_id' => $salesReturn->order_id,
                    'customer_id' => $salesReturn->customer_id,
                    'user_id' => $salesReturn->returned_by,
                    'refund_amount' => (float)$applied,
                    'refund_mode' => 'refund_from_prev', // Use actual refund mode (order_balance if fully from balance)
                    'refund_date' => $salesReturn->return_date,
                    'description' => 'outside salesman salesreturn',
                ]);
                // Reduce from this payment mode

                $customer->balance_amount -= $applied;
                $customer->save();

                $remaining -= $applied;

                if ($remaining <= 0) break;
            }


            Log::info('========== CREDIT PAYMENT END ==========');
            DB::commit();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function opening_stock_store($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $product = Products::findOrFail($data->product_id);

            $product->total_stock += $data->qty;
            $product->updated_by = $data->user_id;
            $product->save();

            $lastOpening = StockMovement::where('reason', 'opening_stock')
                ->orderBy('id', 'desc')
                ->first();

            $nextSeq = 101;
            if ($lastOpening) {
                preg_match('/OPEN-(\d+)/', $lastOpening->ref_no, $matches);
                if (isset($matches[1])) {
                    $nextSeq = (int) $matches[1] + 1;
                }
            }

            $refNo = 'OPEN-' . $nextSeq;

            $requested_date = isset($data->date) && ! empty($data->date)
                ? \Carbon\Carbon::parse($data->date)->format('Y-m-d H:i:s')
                : now();

            StockMovement::create([
                'ref_id' => null,
                'ref_no' => $refNo,
                'product_id' => $product->id,
                'quantity' => $data->qty,
                'amount' => $data->amount,
                'movement_type' => 'in',
                'reason' => 'opening_stock',
                'created_by' => $data->user_id,
                'date' => $requested_date,
            ]);

            $cost_price = $data->amount / $data->qty;
            StockOriginal::create([
                'ref_id'       => null,
                'ref_no'       =>  $refNo,
                'product_id'   => $product->id,
                'quantity'     => $data->qty,
                'cost_price' => $cost_price,
                'movement_type' => 'in',
                'reason'       => 'Opening stock',
                'date'   => $requested_date,
            ]);
            // $this->journalEntryService->createOpeningStockEntry([
            //     'date' => $requested_date,
            //     'amount' => $data->amount,
            //     'reference_type' => 'App\\Models\\OpeningStock',
            //     'reference_id' => $openingStock->id,
            //     'description' => 'Opening stock entry',

            //     'user_id' => $data->user_id,

            // ]);

            DB::commit();

            return successResponse(null, 'Opening Stock created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getAllOpeningStock($data, $platform = 'web')
    {
        try {
            $query = StockMovement::with('product', 'stockOriginal')->where('reason', 'opening_stock');

            if (!empty($data->search)) {
                $search = trim($data->search);

                $query->where(function ($q) use ($search) {
                    $q->where('ref_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('product', function ($p) use ($search) {
                            $p->where('product_name', 'LIKE', "%{$search}%");
                        });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'open_balance' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];

                return successResponse($response, 'Opening Stock retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();

                return successResponse($result, 'Opening Stock retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getPendingAmount($request)
    {
        $vehicleLoad = VehicleLoadStock::with([
            'vehicleLoadStockItems',
        ])
            ->where('id', $request->id)
            ->where('day_status', 'closing_requested')->first();

        $allOrdersOfOutsideSalesman = Order::where('customer_id', $vehicleLoad->user_id)
            ->where('order_type', 'Outside Sale')
            ->pluck('id');
        $returnedOrders = [];


        foreach ($request['return_items'] as $item) {

            $item = json_decode($item, true);
            $returnQty = $item['return_qty'];

            $orderItems = OrderItem::whereIn('order_id', $allOrdersOfOutsideSalesman)
                ->where('product_id', $item['product_id'])
                ->orderByDesc('created_at')
                ->with('product', 'order')
                ->get();

            foreach ($orderItems as $orderItem) {

                if ($returnQty <= 0) {
                    break;
                }


                if ($orderItem->quantity < $returnQty) {

                    $returnedOrders[] = [
                        'order_id'    => $orderItem->order_id,
                        'product_id'  => $item['product_id'],
                        'product_name' => $orderItem->product->product_name,
                        'return_qty'  => $orderItem->quantity,
                        'ordered_qty' => $orderItem->quantity,
                        'total_price' => $orderItem->price * $orderItem->quantity,
                        'item_price' => (float)$orderItem->price,
                        'return_type' => 'full',
                    ];

                    $returnQty -= $orderItem->quantity;
                } else {

                    $returnedOrders[] = [
                        'order_id'    => $orderItem->order_id,
                        'product_id'  => $item['product_id'],
                        'product_name' => $orderItem->product->product_name,
                        'return_qty'  => $returnQty,
                        'ordered_qty' => $orderItem->quantity,
                        'total_price' => $orderItem->price * $returnQty,
                        'item_price' => (float)$orderItem->price,
                        'return_type' => 'partial',
                    ];

                    $returnQty = 0;

                    break;
                }
            }

            // if ($returnQty > 0) {
            //     throw new Exception(
            //         "Return quantity exceeds sold quantity for product {$item['product_id']}"
            //     );
            // }
        }

        $groupedReturnedOrders = collect($returnedOrders)
            ->groupBy('order_id')
            ->map(function ($items, $orderId) {

                $order = Order::find($orderId);
                $totalPrice = $items->sum(function ($item) {

                    return (float) ($item['total_price'] ?? 0);
                });


                $orderRefundAmount = 0;
                $orderRefundAmountOg = 0;
                if ($order?->balance_amount > 0) {
                    $orderRefundAmount = $order?->balance_amount - $totalPrice;
                    // if ($orderRefundAmount < 0) {
                    $orderRefundAmountOg = $orderRefundAmount * -1;
                    // }

                    $orderMessage = 'Returned amount ' . $totalPrice . ' will be deducted from the order balance amount ' . $order?->balance_amount;
                } else {

                    $orderRefundAmount = $totalPrice;
                    $orderMessage = 'Returned amount ' . $totalPrice . ' will be refunded later or deducted from credit balance';
                    $orderRefundAmountOg = $orderRefundAmount;
                }
                return [
                    'order_id' => $orderId,
                    'order_no' => $order?->order_no,
                    'order_total_amount' => (float)$order?->total_amount,
                    'order_paid_amount' => (float)$order?->paid_amount,
                    'order_balance_amount' => (float)$order?->balance_amount,

                    'customer_id' => $order?->customer_id,
                    'return_type' => $items->contains(
                        fn($item) => ($item['return_type'] ?? '') === 'full'
                    ) ? 'full' : 'partial',

                    'totalPrice' => $totalPrice,
                    'orderRefundAmount' => $orderRefundAmountOg,

                    'orderMessage' => $orderMessage,
                    'items' => $items->values()->toArray(),

                ];
            })
            ->values();

        $orderPaidAmount = 0;
        $ordersTotalAmount = 0;
        $ordersBalanceAmount = 0;

        $orderRefundAmount = 0;
        $orderIds = [];
        foreach ($groupedReturnedOrders as $orderId => $items) {

            $orderIds[] = $items['order_id'];
            $originalOrder = Order::where('id', $items['order_id'])->with('orderItems')->first();
            $orderPaidAmount += $originalOrder->paid_amount;
            $ordersTotalAmount += $items['totalPrice'];
            $ordersBalanceAmount += $originalOrder->balance_amount;
            if ($items['orderRefundAmount'] > 0)
                $orderRefundAmount += $items['orderRefundAmount'];
            if ($orderRefundAmount < 0) {
                $orderRefundAmount = 0;
            }
        }
        // $orderRefundAmount = $ordersTotalAmount - $ordersBalanceAmount;
        $user = User::where('id', $vehicleLoad->user_id)->first();
        $employee = Employee::where('user_id', $user->id)->first();
        $customerCreditBalance = (float)$employee->balance_amount - $ordersBalanceAmount;
        // $customerCreditBalance = (float)$employee->balance_amount;



        $response = [
            'ordersTotalAmount' => $ordersTotalAmount,
            'orderPaidAmount' => $orderPaidAmount,
            'ordersBalanceAmount' => $ordersBalanceAmount,
            'orderRefundAmount' => $orderRefundAmount,

            'customerCreditBalance' => $customerCreditBalance,
            'orders' => $groupedReturnedOrders
        ];
        return successResponse($response, 'Vehicle Load retrieved successfully.', 0, 200);
    }

    public function getCustomerPendingAmount($request)
    {
        $user = User::find($request->selectedSalesmanId);
        if ($user->user_type == 'salesman') {
            $response = null;
        } else {
            $employee = Employee::where('user_id', $user->id)->first();

            if ($employee->balance_amount < 0) {
                $response = (float)$employee->balance_amount;
            } else $response = 0;
        }

        return successResponse($response, 'Balance amount retrieved successfully.', 0, 200);
    }
}
