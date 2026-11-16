<?php

namespace App\Services;

use App\Models\SpecialPrice;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpecialPriceService
{
    public function specialPriceStore($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $exists = SpecialPrice::where('product_id', $data->product_id)
                ->where('price', $data->price)
                ->exists();

            if ($exists) {
                return errorResponse(null, 'A special price for this product with the same price already exists.', 100, 422);
            }

            $tax = SpecialPrice::Create([
                'product_id' => $data->product_id,
                'price' => $data->price,
                'salesman_commission' => $data->salesman_commission ?? 0,
                'executive_commission' => $data->executive_commission ?? 0,
                'status' => $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);
            DB::commit();

            return successResponse($tax, 'Special Price created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function specialPriceUpdate($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $tax = SpecialPrice::find($data->id);
            if (!$tax) {
                return errorResponse(null, 'Special Price not found', 404, 404);
            }

            $exists = SpecialPrice::where('product_id', $data->product_id)
                ->where('price', $data->price)
                ->where('id', '!=', $data->id)
                ->exists();

            if ($exists) {
                return errorResponse(null, 'A special price for this product with the same price already exists.', 100, 422);
            }

            $tax->update([
                'product_id'      => $data->product_id,
                'price'   => $data->price,
                'salesman_commission' => $data->salesman_commission ?? 0,
                'executive_commission' => $data->executive_commission ?? 0,
                'status'            => $data->status,
                'updated_by' => $data->user_id,
            ]);

            DB::commit();
            return successResponse(null, 'Special Price updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function specialPriceList($data, $platform = 'web')
    {

        try {
            $query = SpecialPrice::with('product');

            if (!empty($data->search)) {
                $query->whereHas('product', function ($q) use ($data) {
                    $q->where('product_name', 'LIKE', "%{$data->search}%");
                });
            }

            if (!empty($data->product_id)) {
                $query->where('product_id', $data->product_id);
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'specialPrice'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Special Price retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Special Price retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function specialPriceDelete($data)
    {
        try {
            DB::beginTransaction();
            $tax = SpecialPrice::findOrFail($data->id);
            if ($tax) {
                $tax->deleted_by = $data->user_id;
                $tax->save();
                $tax->delete();
                DB::commit();
                return successResponse(null, 'Special Price deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Special Price not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
