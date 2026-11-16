<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\CommisionTypeMaster;
use App\Models\CommissionTypeItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionTypeService
{
    // public function commission_type_store($data = null, $platform = 'web')
    // {
    //     try {
    //         DB::beginTransaction();

    //         $commissionType = CommisionTypeMaster::create([
    //             'name' => $data->name,
    //             'salesman_commission' => $data->salesman_commission,
    //             'salesexecutive_commission' => $data->salesexecutive_commission,
    //             'delivery_vehicle_commission' => $data->delivery_vehicle_commission,
    //             'status' => (int) $data->status ?? 1,
    //             'created_by' => $data->user_id,
    //         ]);
    //         DB::commit();

    //         return successResponse($commissionType, 'Commission Type created successfully.', 0, 200);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }


    public function commission_type_store($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $commissionType = CommisionTypeMaster::create([
                'name' => $data->name,
                'salesman_commission' => $data->salesman_commission,
                'salesexecutive_commission' => $data->salesexecutive_commission,
                'delivery_vehicle_commission' => $data->delivery_vehicle_commission,
                'status' => (int) $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);
            DB::commit();



            CommissionTypeItem::create([
                'product_type' => 'cartoon',
                'commission_type_master_id' => $commissionType->id,
                'salesman_commission' => $data->cartoon_salesman_commission,
                'executive_commission' => $data->cartoon_salesexecutive_commission,
                'delivery_vehicle_commission' => $data->cartoon_delivery_vehicle_commission,
            ]);



            CommissionTypeItem::create([
                'product_type' => 'pack',
                'commission_type_master_id' => $commissionType->id,
                'salesman_commission' => $data->pack_salesman_commission,
                'executive_commission' => $data->pack_salesexecutive_commission,
                'delivery_vehicle_commission' => $data->pack_delivery_vehicle_commission,
            ]);


            return successResponse($commissionType, 'Commission Type created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    // public function commission_type_update($data = null, $platform = 'web')
    // {
    //     try {
    //         DB::beginTransaction();

    //         $commissionType = CommisionTypeMaster::find($data->id);
    //         if (!$commissionType) {
    //             return errorResponse(null, 'Commission Type not found', 404, 404);
    //         }

    //         $commissionType->update([
    //             'id' => $data->id,
    //             'name' => $data->name,
    //             'salesman_commission' => $data->salesman_commission,
    //             'salesexecutive_commission' => $data->salesexecutive_commission,
    //             'delivery_vehicle_commission' => $data->delivery_vehicle_commission,
    //             'status' => $data->status ?? 1,
    //             'updated_by' => $data->user_id,
    //         ]);

    //         DB::commit();
    //         return successResponse(null, 'Commission Type updated successfully.', 0, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }



    public function commission_type_update($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $commissionType = CommisionTypeMaster::find($data->id);
            if (!$commissionType) {
                return errorResponse(null, 'Commission Type not found', 404, 404);
            }
            $commissionType->update([
                'name'       => $data->name,
                'status'     => $data->status,
                'updated_by' => $data->user_id,
            ]);

            $commission_item_cartoon = CommissionTypeItem::where('commission_type_master_id', $data->id)->where('product_type', 'cartoon')->first();

            if (!$commission_item_cartoon) {
                CommissionTypeItem::create([
                    'product_type' => 'cartoon',
                    'commission_type_master_id' => $data->id,
                    'salesman_commission' => $data->cartoon_salesman_commission,
                    'executive_commission' => $data->cartoon_salesexecutive_commission,
                    'delivery_vehicle_commission' => $data->cartoon_delivery_vehicle_commission,
                ]);
            } else {
                $commission_item_cartoon->update([
                    'salesman_commission' => $data->cartoon_salesman_commission,
                    'executive_commission' => $data->cartoon_salesexecutive_commission,
                    'delivery_vehicle_commission' => $data->cartoon_delivery_vehicle_commission,
                ]);
            }

            $commission_item_pack = CommissionTypeItem::where('commission_type_master_id', $data->id)->where('product_type', 'pack')->first();

            if (!$commission_item_pack) {
                CommissionTypeItem::create([
                    'product_type' => 'pack',
                    'commission_type_master_id' => $data->id,
                    'salesman_commission' => $data->pack_salesman_commission,
                    'executive_commission' => $data->pack_salesexecutive_commission,
                    'delivery_vehicle_commission' => $data->pack_delivery_vehicle_commission,
                ]);
            } else {
                $commission_item_pack->update([
                    'salesman_commission' => $data->pack_salesman_commission,
                    'executive_commission' => $data->pack_salesexecutive_commission,
                    'delivery_vehicle_commission' => $data->pack_delivery_vehicle_commission,
                ]);
            }



            DB::commit();
            return successResponse(null, 'Commission Type updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }



    public function getAllCommissionType($data, $platform = 'web')
    {
        try {
            // $query = CommisionTypeMaster::query();
            $query = CommisionTypeMaster::with('commissionTypeItems');

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('name', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'commi_type'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Commission Type retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Commission Type retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
