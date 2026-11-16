<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\ProductCategory;
use App\Models\VehicleLoadStockItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function category_store($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $category = ProductCategory::Create([
                'category_code' => $this->genarateCategoryCode(),
                'category_name' => $data->category_name,
                'ar_category_name' => $data->ar_category_name,
                'status' => $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);
            DB::commit();

            return successResponse($category, 'Category created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function genarateCategoryCode()
    {
        $auto_category_code = AutoIncrementMaster::where('auto_code', 'AI_CATEGORY_CODE')->first();
        $currentValue = $auto_category_code->auto_increment_general_value;
        $auto_category_code->auto_increment_general_value = $currentValue + 1;
        $auto_category_code->save();

        return $auto_category_code->auto_prefix . $currentValue;
    }
    public function category_update($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $category = ProductCategory::find($data->id);
            if (!$category) {
                return errorResponse(null, 'Category not found', 404, 404);
            }

            $category->update([
                'category_name'      => $data->category_name,
                'ar_category_name'   => $data->ar_category_name,
                'status'            => $data->status,
                'updated_by' => $data->user_id,
            ]);

            DB::commit();
            return successResponse(null, 'Category updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getAllCategory($data, $platform = 'web')
    {
        try {
            $query = ProductCategory::query();

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('category_name', 'LIKE', "%{$data->search}%")
                        ->orWhere('category_code', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'category'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Category retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status',1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Category retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function categoryDelete($data)
{
    try {
        DB::beginTransaction();

        $category = ProductCategory::findOrFail($data->id);

        // Check whether any product in this category has available stock
        $stockExists = VehicleLoadStockItem::whereHas('product', function ($query) use ($category) {
                $query->where('category_id', $category->id);
            })
            ->where('available_qty', '>', 0)
            ->exists();

        if ($stockExists) {
            DB::rollBack();

            return errorResponse(
                null,
                'Cannot delete this category because products under this category have available stock.',
                100,
                400
            );
        }

        $category->deleted_by = $data->user_id;
        $category->save();
        $category->delete();

        DB::commit();

        return successResponse(null, 'Category deleted successfully.', 0, 200);

    } catch (Exception $e) {
        DB::rollBack();
        return errorResponse(null, $e->getMessage(), 100, 500);
    }
}
}
