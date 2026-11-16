<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Http\Controllers\VehicleController;
use App\Models\Vehicle;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    public function vehicle_store($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $vehicle = Vehicle::create([

                'vehicle_code' => $this->genarateVehCode(),
                'vehicle_plate_no' => $data->vehicle_plate_no,
                'vehicle_type' => $data->vehicle_type,
                'vehicle_name' => $data->vehicle_name,
                'fuel_type' => $data->fuel_type,
                'manufacture_date' => $data->manufacture_date,
                'status' => (int) $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);
            DB::commit();

            return successResponse($vehicle, 'Vehicle created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function genarateVehCode()
    {
        $auto_veh_code = AutoIncrementMaster::where('auto_code', 'AI_VEHICLE_CODE')->first();
        $currentValue = $auto_veh_code->auto_increment_general_value;
        $auto_veh_code->auto_increment_general_value = $currentValue + 1;
        $auto_veh_code->save();

        return $auto_veh_code->auto_prefix . $currentValue;
    }

    public function vehicle_update($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $vehicle = vehicle::find($data->id);
            if (!$vehicle) {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }

            $vehicle->update([
                'id' => $data->id,
                'vehicle_type' => $data->vehicle_type,
                'vehicle_name' => $data->vehicle_name,
                'vehicle_plate_no' => $data->vehicle_plate_no,
                'fuel_type' => $data->fuel_type,
                'manufacture_date' => ($data->manufacture_date == 'null' || empty($data->manufacture_date)) ? null : $data->manufacture_date,
                'status' => $data->status ?? 1,
                'updated_by' => $data->user_id,
            ]);

            DB::commit();
            return successResponse(null, 'Vehicle updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getAllVehicle($data, $platform = 'web')
    {
        try {
            $query = Vehicle::query();

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('vehicle_code', 'LIKE', "%{$data->search}%")
                        ->orWhere('vehicle_name', 'LIKE', "%{$data->search}%")
                        ->orWhere('vehicle_plate_no', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'vehicles'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Vehicles retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Vehicles retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function vehicleDelete($id)
    {
        try {
            DB::beginTransaction();
            $vehicle = Vehicle::findOrFail($id);
            if ($vehicle) {
                $vehicle->delete();
                DB::commit();
                return successResponse(null, 'Vehicle deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Vehicle not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}