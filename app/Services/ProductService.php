<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\Products;
use App\Models\AssignedSpecialPrice;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function product_store($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $supplier = Products::Create([
                'product_code' => $this->genarateProCode(),
                'category_id' => $data->category_id,
                'product_name' => $data->product_name,
                'ar_product_name' => $data->ar_product_name,
                'product_uom' => $data->product_uom,
                'product_type' => $data->product_type,
                'unit_price' => $data->unit_price,
                'selling_price' => $data->selling_price ?? 0,
                'outside_selling_price'=>$data->outside_selling_price??0,
                'tax_id' => $data->tax_id ?? 0,
                'remarks' => $data->remarks,
                'status' => $data->status ?? 1,
                'created_by' => $data->user_id,
                'salesman_commission' => $data->salesman_commission,
                'executive_commission' => $data->executive_commission,
                'delivery_vehicle_commission' => $data->delivery_vehicle_commission,
            ]);
            DB::commit();

            return successResponse($supplier, 'Product created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function genarateProCode()
    {
        $auto_pro_code = AutoIncrementMaster::where('auto_code', 'AI_PRODUCT_CODE')->first();
        $currentValue = $auto_pro_code->auto_increment_general_value;
        $auto_pro_code->auto_increment_general_value = $currentValue + 1;
        $auto_pro_code->save();

        return $auto_pro_code->auto_prefix.$currentValue;
    }

    public function product_update($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $product = Products::find($data->id);
            if (! $product) {
                return errorResponse(null, 'Product not found', 404, 404);
            }

            $product->update([
                'category_id' => $data->category_id,
                'product_name' => $data->product_name,
                'ar_product_name' => $data->ar_product_name,
                'product_uom' => $data->product_uom,
                'product_type' => $data->product_type,
                'unit_price' => $data->unit_price,
                'selling_price' => $data->selling_price,
                'outside_selling_price'=>$data->outside_selling_price,
                'tax_id' => $data->tax_id ?? 0,
                'remarks' => $data->remarks,
                'status' => $data->status,
                'updated_by' => $data->user_id,
                'salesman_commission' => $data->salesman_commission,
                'executive_commission' => $data->executive_commission,
                'delivery_vehicle_commission' => $data->delivery_vehicle_commission,
            ]);

            DB::commit();

            return successResponse(null, 'Product updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    // public function getAllProduct($data, $platform = 'web')
    // {

    //     try {
    //         $query = Products::with('category', 'tax');

    //         if ($data->search) {
    //             $searchTerm = $data->search;
    //             $query->where(function ($q) use ($searchTerm) {
    //                 $q->where('product_name', 'LIKE', "%{$searchTerm}%")
    //                     ->orWhere('product_code', 'LIKE', "%{$searchTerm}%")
    //                     ->orWhereHas('category', function ($cat) use ($searchTerm) {
    //                         $cat->where('category_name', 'LIKE', "%{$searchTerm}%")
    //                             ->orWhere('category_code', 'LIKE', "%{$searchTerm}%");
    //                     });
    //             });
    //         }

    //         if (!empty($data->category_id)) {
    //             $query->where('category_id', $data->category_id);
    //         }

    //         if (!empty($data->product_type)) {
    //             $query->where('product_type', $data->product_type);
    //         }

    //         if ($platform == 'web') {
    //             $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
    //             $response = [
    //                 'products' => $result->items(),
    //                 'pagination' => generatePaginationData($result),
    //             ];
    //             return successResponse($response, 'Products retrieved successfully.', 0, 200);
    //         } else {
    //             $result = $query->where('status', 1)->orderBy('created_at', 'ASC')->get();

    //             $response = [
    //                 "products" => $result,
    //             ];
    //             return successResponse($response, 'Products retrieved successfully.', 0, 200);
    //         }
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    public function getAllProduct($data, $platform = 'web')
    {
        try {
            $query = Products::with('category', 'tax');

            if ($data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('product_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('product_code', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('category', function ($cat) use ($searchTerm) {
                            $cat->where('category_name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('category_code', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if (! empty($data->category_id)) {
                $query->where('category_id', $data->category_id);
            }

            if (! empty($data->product_type)) {
                $query->where('product_type', $data->product_type);
            }

            // ✅ GET PRODUCTS
            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')
                    ->paginate(config('settings.pagination.perpage.admin'));

                $products = collect($result->items());

            } else {
                $products = $query->where('status', 1)
                    ->orderBy('created_at', 'ASC')
                    ->get();
            }

            // ✅ APPLY SPECIAL PRICE (OPTIMIZED)
            if (! empty($data->customer_id)) {

                $specialPrices = AssignedSpecialPrice::where('customer_id', $data->customer_id)
                    ->pluck('special_price', 'product_id');

                $products->transform(function ($product) use ($specialPrices) {
                    if (isset($specialPrices[$product->id])) {
                        $product->selling_price = $specialPrices[$product->id];
                    }

                    return $product;
                });
            }

            // ✅ RESPONSE
            if ($platform == 'web') {
                $response = [
                    'products' => $products,
                    'pagination' => generatePaginationData($result),
                ];
            } else {
                $response = [
                    'products' => $products,
                ];
            }

            return successResponse($response, 'Products retrieved successfully.', 0, 200);

        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function productDelete($data)
    {
        try {
            DB::beginTransaction();
            $product = Products::findOrFail($data->id);
            if ($product) {
                $product->deleted_by = $data->user_id;
                $product->save();
                $product->delete();
                DB::commit();

                return successResponse(null, 'Product deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Product not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
