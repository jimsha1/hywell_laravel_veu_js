<?php

namespace App\Http\Controllers\admin;

use App\Models\Vehicle;
use App\Http\Controllers\Controller;
use App\Services\VehicleService;

use Illuminate\Http\Request;

class VehicleController extends Controller
{

    private VehicleService $vehicleService;

    public function __construct(VehicleService $VehicleService)
    {
        $this->vehicleService = $VehicleService;
    }
    public function index()
    {
        return view('admin.pages.vehicle.index');
    }
    public function vehicleStore(Request $request)
    {

        if (empty($request->id)) {
            $this->validate($request, [
                'vehicle_plate_no' => 'required',
                'vehicle_name' => 'required',
                'vehicle_type' => 'required',
                'manufacture_date' => 'nullable|date|before_or_equal:today',
            ]);
            return $this->vehicleService->vehicle_store($request);
        } else {
            $this->validate($request, [

                'vehicle_plate_no' => 'required',
                'vehicle_name' => 'required',
                'vehicle_type' => 'required',
            ]);
            return $this->vehicleService->vehicle_update($request);
        }
    }
    public function vehicleList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->vehicleService->getAllVehicle($request, $platform);
    }
    public function vehicleDelete(Request $request)
    {
        return $this->vehicleService->vehicleDelete($request->id);
    }
}
