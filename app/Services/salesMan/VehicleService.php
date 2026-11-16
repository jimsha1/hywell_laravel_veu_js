<?php

namespace App\Services\salesMan;

use App\Models\Employee;
use App\Models\Order;
use App\Models\FinancialYear;
use App\Models\Products;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLoadItemDetail;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    public function getVehicleList()
    {
        try {
            $vehicle = Vehicle::where('status', 1)->where('load_status', 1)
                ->orderBy('created_at', 'desc')
                ->select('id', 'vehicle_code', 'vehicle_name', 'vehicle_type', 'vehicle_plate_no')
                ->get();

            if (!$vehicle) {
                return errorResponse(null, "vehicles not available", 100, 200);
            }
            return successResponse($vehicle, 'Vehicle retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function openVehicleLoadRequest($request)
    {

        try {

            // 1. Request Date
            $requestedDate = isset($request['date'])
                ? Carbon::parse($request['date'])
                : now();

            // 2. Financial Year Validation
            $financialYear = FinancialYear::whereDate('start_date', '<=', $requestedDate->toDateString())
                ->whereDate('end_date', '>=', $requestedDate->toDateString())
                ->first();

            if (!$financialYear) {
                return errorResponse(null, 'No financial year found for the selected date.', 100, 200);
            }

            if ($financialYear->is_closed == 1) {
                return errorResponse(null, 'Financial year is closed. Vehicle load request cannot be created.', 100, 200);
            }

            $requested_date = $requestedDate->format('Y-m-d H:i:s');

            // 3. Check already requested vehicle
            $checkVehicleLoad = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])
                ->whereIn('day_status', ['started', 'closing_requested'])
                ->whereIn('load_status', ['requested', 'approved'])
                ->first();

            if ($checkVehicleLoad && $checkVehicleLoad->day_status == 'started') {
                return errorResponse(null, "You are already requested a vehicle", 200);
            }
            if ($checkVehicleLoad && $checkVehicleLoad->day_status == 'closing_requested') {
                return errorResponse(null, "Pending Vehicle Closure", 200);
            }


            $vehicleLoad = VehicleLoadStock::create([
                'vehicle_id'        => $request['vehicle_id'],
                'day_status'       => "started",
                'load_status'      => "requested",
                'user_id' => $request['sales_man_user_id'],
                'created_at' => $request['date'] ? $request['date'] : now(),
            ]);

            // $requested_date = isset($request['date'])
            //     ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
            //     : now();


            generateAutoCode($vehicleLoad, 'v_stock_code', 'AI_V_STOCK_CODE');

            $requestedSalesmanType = Employee::where('user_id', $request['sales_man_user_id'])->first();


            foreach ($request['products'] as $product) {

                // skip this iteration  if qty is 0,
                if ($product['qty'] == 0) {
                    continue;
                }
                if ($requestedSalesmanType->designation == 'outside_salesman') {
                    $description = "Vehicle Load Requested by Outsider";
                } else {
                    $description = "Vehicle Load Requested";
                }
                VehicleLoadStockItem::create([
                    'vehicle_load_stock_id' => $vehicleLoad->id,
                    'product_id'         => $product['product_id'],
                    'available_qty'     => $product['qty'],
                    'total_qty'        => $product['qty'],
                    'created_at' => $request['date'] ? $request['date'] : now(),
                ]);


                VehicleLoadItemDetail::create([
                    'vehicle_load_id' => $vehicleLoad->id,
                    'product_id'         => $product['product_id'],
                    'requested_qty'     => $product['qty'],
                    'requested_date' => $requested_date,
                    'status'        => "request",
                    'description' => $description,
                ]);
            }

            $vehicle =  Vehicle::find($request['vehicle_id']);

            if ($vehicle && $vehicle->load_status == 1) {
                $vehicle->load_status = 0;
                $vehicle->save();
            }

            return successResponse($vehicleLoad, 'Vehicle Load requested successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function addExtraLoadToVehicleRequest($request)
    {

        try {
            $vehicleLoad = VehicleLoadStock::where('user_id', $request['sales_man_user_id'])
                ->where('day_status', 'started')
                // ->where('load_status', 'requested')
                ->whereIn('load_status', ['requested', 'approved'])
                ->first();

            if (!$vehicleLoad) {
                return errorResponse(null, "You are not Loaded a vehicle", 200);
            }


            // $requested_date = isset($request['date'])
            //     ? \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', $request['date'] . ' ' . now()->format('H:i:s'))->format('Y-m-d H:i:s')
            //     : now();
            $requestedDate = isset($request['date'])
                ? Carbon::parse($request['date'])
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requestedDate->toDateString())
                ->whereDate('end_date', '>=', $requestedDate->toDateString())
                ->first();

            if (!$financialYear) {
                return errorResponse(null, 'No financial year found for the selected date.', 100, 200);
            }

            if ($financialYear->is_closed == 1) {
                return errorResponse(null, 'Financial year is closed. Vehicle load request cannot be created.', 100, 200);
            }

            $requested_date = $requestedDate->format('Y-m-d H:i:s');

            $requestedSalesmanType = Employee::where('user_id', $request['sales_man_user_id'])->first();
            if ($requestedSalesmanType->designation == 'outside_salesman') {
                $description = "Vehicle Load Re Requested by Outsider";
            } else {
                $description = "Vehicle Load Re Requested";
            }

            foreach ($request['products'] as $product) {

                // skip this iteration  if qty is 0,
                if ($product['qty'] == 0) {
                    continue;
                }


                VehicleLoadItemDetail::create([
                    'vehicle_load_id' => $vehicleLoad->id,
                    'product_id'         => $product['product_id'],
                    'requested_qty'     => $product['qty'],
                    'requested_date'     => $requested_date,
                    'status'        => "re_requested",
                    'description' => $description,
                ]);
            }
            // $vehicleLoad->load_status = 'requested';
            // $vehicleLoad->save();

            return successResponse(null, 'Vehicle Load requested successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function closeVehicleLoadDetails($request)
    {
        try {
            // $vehicleLoad = VehicleLoadStock::with([
            //     'vehicleLoadStockItems.product.productCategory',
            //     'vehicle'
            // ])
            //     ->where("user_id", $request['sales_man_user_id'])
            //     ->where("load_status", "approved")
            //     ->where("day_status", "started")
            //     ->orderBy('created_at', 'desc')
            //     ->first();



            $vehicleLoad = VehicleLoadStock::with([
                'vehicleLoadStockDetails' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('product');
                },

                'vehicleLoadStockItems.product'
            ])

                ->where("user_id", $request['sales_man_user_id'])
                ->where('day_status', 'started')
                ->where('load_status', 'approved')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($vehicleLoad) {

                $approvedProductIds = $vehicleLoad->vehicleLoadStockDetails
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                $filteredItems = $vehicleLoad->vehicleLoadStockItems
                    ->filter(function ($item) use ($approvedProductIds) {

                        return $approvedProductIds->contains(
                            $item->product_id
                        );
                    })
                    ->values();
                foreach ($filteredItems as $product) {

                    $product->delivered_qty =
                        $product->total_qty -
                        $product->available_qty;


                    if ($product->total_qty > 0) {

                        $product->delivered_percentage = round(
                            (
                                $product->delivered_qty /
                                $product->total_qty
                            ) * 100,
                            0
                        );
                    } else {

                        $product->delivered_percentage = 0;
                    }
                }
                $vehicleLoad->setRelation(
                    'vehicleLoadStockItems',
                    $filteredItems
                );

                foreach ($vehicleLoad->vehicleLoadStockItems as $product) {


                    $product->delivered_qty = $product->total_qty - $product->available_qty;
                    // $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);

                    if ($product->total_qty > 0) {
                        $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);
                    } else {
                        $product->delivered_percentage = 0; // avoid divide by zero
                    }
                }
            } else {
                return errorResponse(null, 'No vehicle load found', 100, 200);
            }

            // if (!$vehicleLoad) {
            //     return errorResponse(null, 'No vehicle load found', 100, 200);
            // }

            // Group products by category
            $grouped = [];
            foreach ($vehicleLoad->vehicleLoadStockItems as $item) {
                $categoryName = $item->product->productCategory->category_name ?? 'category';

                $grouped[$categoryName][] = [
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->product_name,
                    'qty'          => $item->total_qty,
                    'balance_qty'   => $item->available_qty,
                    'delivered_qty'   => $item->total_qty - $item->available_qty,
                ];
            }

            // Convert to array format
            $categories = [];
            foreach ($grouped as $categoryName => $products) {
                $categories[] = [
                    'category_name' => $categoryName,
                    'products'      => $products,
                ];
            }

            // $vehicleLoad->load_status = 'requested';
            // $vehicleLoad->save();
            $response =
                [
                    'vehicle' => $vehicleLoad->vehicle->vehicle_name . ' (' . $vehicleLoad->vehicle->vehicle_plate_no . ')',
                    'vehicle_load' => $vehicleLoad->id,
                    'categories'   => $categories
                ];


            return successResponse($response, 'Vehicle Load retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    // public function closeVehicleLoadStock($request)
    // {
    //     try {
    //         $vehicleLoad = VehicleLoadStock::with([
    //             'vehicleLoadStockItems'
    //         ])
    //             ->where("user_id", $request['sales_man_user_id'])
    //             ->where("load_status", "approved")
    //             ->where("day_status", "started")
    //             ->orderBy('created_at', 'desc')
    //             ->first();

    //         if (!$vehicleLoad) {
    //             return errorResponse(null, 'No vehicle load found', 100, 200);
    //         }

    //         if ($vehicleLoad->day_status === 'closed') {
    //             return errorResponse(null, 'This vehicle day stock is already closed.', 100, 200);
    //         }

    //         $vehicleLoad->update([
    //             'day_status' => 'closed',
    //             'closing_date' => now(),
    //         ]);


    //         foreach ($vehicleLoad->vehicleLoadStockItems as $stock_item) {

    //             $product = Products::where('id', $stock_item->product_id)->first();

    //             if (!$product) {
    //                 return errorResponse(null, "Not available the product", 100, 200);
    //             }

    //             $balance_qty = $product->total_stock + $stock_item->available_qty;

    //             $product->update([
    //                 'total_stock'      => $balance_qty,
    //             ]);

    //             StockMovement::create([
    //                 'product_id' => $product->id,
    //                 'quantity'      => $stock_item->available_qty,
    //                 'movement_type' => "in",
    //                 'reason'        => "closing stock",
    //                 'created_by'        => $request['sales_man_user_id'],
    //             ]);
    //         }


    //         $vehicle =  Vehicle::find($vehicleLoad->vehicle_id);

    //         if ($vehicle) {
    //             $vehicle->load_status = 1;
    //             $vehicle->save();
    //         }


    //         return successResponse($vehicleLoad, 'Vehicle Load retrieved successfully.', 0, 200);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 200);
    //     }
    // }

    public function closeVehicleLoadStock($request, $requested_date = null)
    {
        try {

            $vehicleLoad = VehicleLoadStock::with([
                'vehicleLoadStockDetails' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('product');
                },

                'vehicleLoadStockItems.product'
            ])

                ->where("user_id", $request['sales_man_user_id'])
                ->where('day_status', 'started')
                ->where('load_status', 'approved')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($vehicleLoad) {

                $approvedProductIds = $vehicleLoad->vehicleLoadStockDetails
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                $filteredItems = $vehicleLoad->vehicleLoadStockItems
                    ->filter(function ($item) use ($approvedProductIds) {

                        return $approvedProductIds->contains(
                            $item->product_id
                        );
                    })
                    ->values();
                foreach ($filteredItems as $product) {

                    $product->delivered_qty =
                        $product->total_qty -
                        $product->available_qty;


                    if ($product->total_qty > 0) {

                        $product->delivered_percentage = round(
                            (
                                $product->delivered_qty /
                                $product->total_qty
                            ) * 100,
                            0
                        );
                    } else {

                        $product->delivered_percentage = 0;
                    }
                }
                $vehicleLoad->setRelation(
                    'vehicleLoadStockItems',
                    $filteredItems
                );

                foreach ($vehicleLoad->vehicleLoadStockItems as $product) {


                    $product->delivered_qty = $product->total_qty - $product->available_qty;
                    // $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);

                    if ($product->total_qty > 0) {
                        $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);
                    } else {
                        $product->delivered_percentage = 0; // avoid divide by zero
                    }
                }
            } else {
                return errorResponse(null, 'No vehicle load found', 100, 200);
            }




            // $vehicleLoad = VehicleLoadStock::with([
            //     'vehicleLoadStockItems'
            // ])
            //     ->where("user_id", $request['sales_man_user_id'])
            //     ->where("load_status", "approved")
            //     ->where("day_status", "started")
            //     ->orderBy('created_at', 'desc')
            //     ->first();

            // if (!$vehicleLoad) {
            //     return errorResponse(null, 'No vehicle load found', 100, 200);
            // }

            if ($vehicleLoad->day_status === 'closed') {
                return errorResponse(null, 'This vehicle day stock is already closed.', 100, 200);
            }


            $vehicleLoad->update([
                'day_status' => 'closing_requested',
                'closing_req_date' => $requested_date,
            ]);
            $vehicle = Vehicle::find($vehicleLoad->vehicle_id);

            if ($vehicle) {
                $vehicle->load_status = 0;
                $vehicle->save();
            }

            return successResponse($vehicleLoad, 'Vehicle Load closing requested successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }



    public function returnVehicleStock($request)
    {
        DB::beginTransaction();
        try {
            // $vehicleLoadStockId = $request->vehicle_load_stock_id;
            $returnItems = $request->return_items ?? [];
            $userId = $request->user_id;

            if (empty($returnItems)) {
                DB::rollBack();
                return errorResponse(null, 'No items to return', 100, 200);
            }

            // Get vehicle load stock with items
            // $vehicleLoadStock = VehicleLoadStock::with('vehicleLoadStockItems.product')
            //     ->where('id', $vehicleLoadStockId)
            //     ->where('day_status', 'started')
            //     ->where('load_status', 'approved')
            //     ->first();
            $vehicleLoadStock = VehicleLoadStock::with('vehicleLoadStockItems.product')
                ->where('user_id', $request['sales_man_user_id'])
                ->where('day_status', 'started')
                // ->where('load_status', 'requested')
                ->whereIn('load_status', ['requested', 'approved'])
                ->first();





            if (!$vehicleLoadStock) {
                DB::rollBack();
                return errorResponse(null, 'Vehicle load stock not found or not available for return', 100, 200);
            }

            $vehicleLoadStockId = $vehicleLoadStock->id;

            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            // Process each return item
            foreach ($returnItems as $returnItem) {

                $productId = $returnItem['product_id'] ?? null;
                $returnQty = isset($returnItem['return_qty']) ? (int)$returnItem['return_qty'] : 0;

                if ($returnQty <= 0 || !$productId) {
                    continue;
                }

                // Find the vehicle load stock item
                $stockItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicleLoadStockId)
                    ->where('product_id', $productId)
                    ->first();

                if (!$stockItem) {
                    DB::rollBack();
                    return errorResponse(null, 'Vehicle load stock item not found for product ID: ' . $productId, 100, 200); // Replace with appropriate error message.', 100, 200);
                }

                // Validate return quantity doesn't exceed available quantity
                if ($returnQty > $stockItem->available_qty) {
                    DB::rollBack();
                    return errorResponse(null, 'Return quantity cannot exceed available quantity for product ID: ' . $productId, 100, 200);
                }

                // Get product
                $product = Products::find($productId);
                if (!$product) {
                    DB::rollBack();
                    return errorResponse(null, 'Product not found (ID: ' . $productId . ')', 100, 200);
                }

                // Reduce available quantity from vehicle stock
                $stockItem->available_qty -= $returnQty;
                $stockItem->total_qty -= $returnQty;
                $stockItem->save();

                // Add quantity back to central stock
                $product->total_stock += $returnQty;
                $product->save();


                // VehicleLoadItemDetail::where('vehicle_load_id', $vehicleLoadStockId)
                //     ->where('product_id', $productId)
                //     ->update([
                //         'return_qty' => $returnQty,
                //     ]);

                VehicleLoadItemDetail::create([
                    'vehicle_load_id' => $vehicleLoadStockId,
                    'product_id' => $productId,
                    'requested_qty' => $returnQty,
                    'approved_qty' => $returnQty,
                    'requested_date' => $requested_date,
                    'status' => 'return_load',
                    'description' => 'Returned from vehicle',
                ]);

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $productId,
                    'quantity' => $returnQty,
                    'movement_type' => 'in',
                    'reason' => 'return_vehicle_stock',
                    'created_by' => $userId,
                    'date' => $requested_date
                ]);
            }

            DB::commit();
            return successResponse(null, 'Stock returned successfully from vehicle.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    // Request return vehicle stock (creates request, needs admin approval)
    public function requestReturnVehicleStock($request)
    {
        try {
            $returnItems = $request->return_items ?? [];

            if (empty($returnItems)) {
                return errorResponse(null, 'No items to return', 100, 200);
            }

            // Get vehicle load stock with items
            $vehicleLoadStock = VehicleLoadStock::with('vehicleLoadStockItems.product')
                ->where('user_id', $request['sales_man_user_id'])
                ->where('day_status', 'started')
                ->whereIn('load_status', ['requested', 'approved'])
                ->first();

            if (!$vehicleLoadStock) {
                return errorResponse(null, 'Vehicle load stock not found or not available for return', 100, 200);
            }

            $vehicleLoadStockId = $vehicleLoadStock->id;
            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            // Process each return item - create request
            foreach ($returnItems as $returnItem) {
                $productId = $returnItem['product_id'] ?? null;
                $returnQty = isset($returnItem['return_qty']) ? (int)$returnItem['return_qty'] : 0;

                if ($returnQty <= 0 || !$productId) {
                    continue;
                }

                // Find the vehicle load stock item
                $stockItem = VehicleLoadStockItem::where('vehicle_load_stock_id', $vehicleLoadStockId)
                    ->where('product_id', $productId)
                    ->first();

                if (!$stockItem) {
                    return errorResponse(null, 'Vehicle load stock item not found for product ID: ' . $productId, 100, 200);
                }

                // Validate return quantity doesn't exceed available quantity
                if ($returnQty > $stockItem->available_qty) {
                    return errorResponse(null, 'Return quantity cannot exceed available quantity for product ID: ' . $productId, 100, 200);
                }

                // Create return request (similar to addExtraLoadToVehicleRequest)
                VehicleLoadItemDetail::create([
                    'vehicle_load_id' => $vehicleLoadStockId,
                    'product_id' => $productId,
                    'requested_qty' => $returnQty,
                    'requested_date' => $requested_date,
                    'status' => 'return_requested',
                    'description' => 'Return Stock Requested',
                ]);
            }

            return successResponse(null, 'Return stock requested successfully. Waiting for admin approval.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
}
