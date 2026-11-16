<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    private StockService $stockService;

    public function __construct(StockService $StockService)
    {
        $this->stockService = $StockService;
    }
    public function index()
    {
        return view('admin.pages.approve_load.index');
    }
    public function vehicleLoadList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->stockService->getAllVehicleLoad($request, $platform);
    }
    public function vehicleLoadApproved(Request $request)
    {
        return $this->stockService->vehicleLoadApproved($request->id, $request->approved_items, $request);
    }

    public function approveExtraLoadToVehicle(Request $request)
    {
        return $this->stockService->approveExtraLoadToVehicle($request->id, $request->approved_items, $request);
    }
    public function rejectExtraLoadToVehicle(Request $request)
    {
        return $this->stockService->rejectExtraLoadToVehicle($request);
    }
    public function vehicleLoadReject(Request $request)
    {
        return $this->stockService->vehicleLoadReject($request->id);
    }
    public function returnLoad()
    {
        return view('admin.pages.return_load.index');
    }
    public function vehicleLoadReturnList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->stockService->getAllVehicleLoadReturn($request, $platform);
    }

    public function vehicleLoadReturn(Request $request)
    {

        return $this->stockService->vehicleLoadReturn($request);
    }

    public function returnStockRequestList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->stockService->getAllReturnStockRequests($request, $platform);
    }

    public function approveReturnStock(Request $request)
    {
        return $this->stockService->approveReturnVehicleStock($request->id, $request->approved_items);
    }
    public function rejectReturnStock(Request $request)
    {
        return $this->stockService->rejectReturnVehicleStock($request->id);
    }
    public function rejectClosingRequest(Request $request)
    {
        return $this->stockService->rejectClosingRequest($request);
    }

    public function getPendingAmount(Request $request)
    {
        return $this->stockService->getPendingAmount($request);
    }
    public function getCustomerPendingAmount(Request $request)
    {
        return $this->stockService->getCustomerPendingAmount($request);
    }
}
