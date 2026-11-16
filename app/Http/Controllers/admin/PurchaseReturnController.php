<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    private PurchaseReturnService $purchaseReturnService;

    public function __construct(PurchaseReturnService $purchaseReturnService)
    {
        $this->purchaseReturnService = $purchaseReturnService;
    }

    public function index()
    {
        return view('admin.pages.purchase_return.index');
    }

    public function createPurchaseReturn(Request $request)
    {
        return $this->purchaseReturnService->createPurchaseReturn($request);
    }

    public function approvePurchaseReturn(Request $request)
    {
        return $this->purchaseReturnService->approvePurchaseReturn($request);
    }

    public function rejectPurchaseReturn(Request $request)
    {
        return $this->purchaseReturnService->rejectPurchaseReturn($request);
    }

    public function getAllPurchaseReturns(Request $request)
    {
        $platform = $request->platform == "api" ? 'api' : 'web';
        return $this->purchaseReturnService->getAllPurchaseReturns($request, $platform);
    }

    public function getPurchaseReturnDetails(Request $request)
    {
        return $this->purchaseReturnService->getPurchaseReturnDetails($request->return_id);
    }

    public function getPurchasesForReturn(Request $request)
    {
        $platform = $request->platform == "api" ? 'api' : 'web';
        return $this->purchaseReturnService->getPurchasesForReturn($request, $platform);
    }
}
