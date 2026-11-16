<?php

namespace App\Services;

use App\Models\GlobalTax;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GlobalTaxService
{
    public function tax_store($data = null, $platform = 'web')
    {
       
        try {
            DB::beginTransaction();

            $tax = GlobalTax::Create([
                'name' => $data->name,
                'tax' => $data->tax,
                'status' => $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);
            DB::commit();

            return successResponse($tax, 'Tax created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function tax_update($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $tax = GlobalTax::find($data->id);
            if (!$tax) {
                return errorResponse(null, 'Tax not found', 404, 404);
            }

            $tax->update([
                'name'      => $data->name,
                'tax'   => $data->tax,
                'status'            => $data->status,
                'updated_by' => $data->user_id,
            ]);

            DB::commit();
            return successResponse(null, 'Tax updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getAllTax($data, $platform = 'web')
    {
        try {
            $query = GlobalTax::query();

            if ($data->search) {
                $query->where('name', 'LIKE', "%{$data->search}%");
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'tax'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Tax retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Tax retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function taxDelete($data)
    {
        try {
            DB::beginTransaction();
            $tax = GlobalTax::findOrFail($data->id);
            if ($tax) {
                $tax->deleted_by = $data->user_id;
                $tax->save();
                $tax->delete();
                DB::commit();
                return successResponse(null, 'Tax deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Tax not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
