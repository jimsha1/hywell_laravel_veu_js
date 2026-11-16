<?php

namespace App\Http\Controllers\salesMan;

use App\Http\Controllers\Controller;
use App\Models\AssignedSpecialPrice;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\salesMan\CommissionTypeService;
use App\Services\salesMan\OrderService;
use App\Services\salesMan\ProfileService;
use App\Services\salesMan\VehicleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesManAppController extends Controller
{
    private ProfileService $profileService;
    private VehicleService $vehicleService;
    private OrderService $orderService;
    private CommissionTypeService $commissionTypeService;

    public function __construct(ProfileService $profileService, VehicleService $vehicleService, OrderService $orderService, CommissionTypeService $commissionTypeService)
    {
        $this->profileService = $profileService;
        $this->vehicleService = $vehicleService;
        $this->orderService = $orderService;
        $this->commissionTypeService = $commissionTypeService;
    }

    public function getSalesManProfile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required'
            ]);
            return $this->profileService->getSalesManProfile($validatedData);
        } catch (Exception $e) {
            return apiResponse(100, $e->getMessage(), null);
        }
    }

    public function getVehicleList()
    {
        try {
            return $this->vehicleService->getVehicleList();
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function getProductList(Request $request)
    {
        try {
            if ($request->customer_id) {
                $category_product_list = ProductCategory::with(['products' => function ($query) {
                    $query->where('status', 1)
                        ->orderBy('created_at', 'asc');
                }])
                    ->where('status', 1)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // override selling price after fetching
                $category_product_list->each(function ($category) use ($request) {
                    $category->products->transform(function ($product) use ($request) {
                        $assigned = AssignedSpecialPrice::where('customer_id', $request->customer_id)
                            ->where('product_id', $product->id)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($assigned) {
                            $product->selling_price = $assigned->special_price;
                        }

                        return $product;
                    });
                });
            } else {
                $category_product_list = ProductCategory::with('products')->where('status', 1)->orderBy('created_at', 'asc')->get();
            }

            $user = User::find($request->user_id);
            if ($user->user_type == 'outside_salesman') {
                $category_product_list = $category_product_list->where('id', '!=', 2);
            }
            if (!$category_product_list) {
                return errorResponse(null, "products not available", 100, 200);
            }
            return successResponse($category_product_list, 'Products retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function openVehicleLoadRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'vehicle_id' => 'required',
                'sales_man_user_id' => 'required',
                'products' => 'required'
            ]);
            return $this->vehicleService->openVehicleLoadRequest($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function addExtraLoadToVehicleRequest(Request $request)
    {

        try {
            $request->validate([
                // 'vehicle_id' => 'required',
                'sales_man_user_id' => 'required',
                'products' => 'required'
            ]);
            return $this->vehicleService->addExtraLoadToVehicleRequest($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function closeVehicleLoadDetails(Request $request)
    {
        try {
            $validated = $request->validate([
                'sales_man_user_id' => 'required',
            ]);
            return $this->vehicleService->closeVehicleLoadDetails($validated);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function closeVehicleLoadStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'sales_man_user_id' => 'required',
            ]);


            $requested_date = isset($request['date'])
                ? \Carbon\Carbon::parse($request['date'])->format('Y-m-d H:i:s')
                : now();

            return $this->vehicleService->closeVehicleLoadStock($validated, $requested_date);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function getOrdersRequestList(Request $request)
    {
        try {
            $request->validate([
                'sales_man_user_id' => 'required',
            ]);
            return $this->orderService->getOrdersRequestList($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function getOrderRequestDetails(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required',
            ]);
            return $this->orderService->getOrderRequestDetails($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function deliveryOrder(Request $request)
    {
        try {
            $request->validate([
                'sales_man_user_id' => 'required',
                // 'customer_id' => 'required',
                'order_id' => 'required',
            ]);
            return $this->orderService->deliveryOrder($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function salesManDeliverWithInvoice(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required',
                'customer_id' => 'required',
                'sales_man_user_id' => 'required',
                'payment_modes' => 'required',
                // 'products' => 'required',
            ]);
            return $this->orderService->salesManDeliverWithInvoice($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function payPendingOrderAmount(Request $request)
    {
        try {
            $request->validate([
                'sales_man_user_id' => 'required',
                'order_id' => 'required',
                // 'payment_amount' => 'required',
                // 'payment_mode' => 'required'
            ]);
            return $this->orderService->payPendingOrderAmount($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function getCommissionType(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required',
            ]);
            return $this->commissionTypeService->getAllCommissionTypes($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function returnVehicleStock(Request $request)
    {
        try {
            $request->validate([
                'sales_man_user_id' => 'required|integer',
                'return_items' => 'required|array|min:1',
                'return_items.*.product_id' => 'required|integer',
                'return_items.*.return_qty' => 'required|integer|min:1',
            ]);

            return $this->vehicleService->returnVehicleStock($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function requestReturnVehicleStock(Request $request)
    {
        try {
            $request->validate([
                'sales_man_user_id' => 'required|integer',
                'return_items' => 'required|array|min:1',
                'return_items.*.product_id' => 'required|integer',
                'return_items.*.return_qty' => 'required|integer|min:1',
            ]);

            return $this->vehicleService->requestReturnVehicleStock($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function rejectOrder(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required',
            ]);

            return $this->orderService->rejectOrder($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
}
