<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    private PurchaseService $purchaseService;

    public function __construct(PurchaseService $PurchaseService)
    {
        $this->purchaseService = $PurchaseService;
    }
    public function index()
    {
        return view('admin.pages.purchase.index');
    }
    public function purchaseStore(Request $request)
    {
        // dd($request);
        if (empty($request->id)) {
            $this->validate($request, [
                'supplier_id' => 'required|integer',
                'purchase_date' => 'required',
                'purchase_no' => 'required',
            ]);
            return $this->purchaseService->purchase_store($request);
        } else {
            $this->validate($request, [
                'supplier_id' => 'required|integer',
                'purchase_date' => 'required',
                'purchase_no' => 'required',
            ]);
            return $this->purchaseService->purchase_update($request);
        }
    }
    public function purchaseList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->purchaseService->getAllPurchase($request, $platform);
    }
    public function supplierDelete(Request $request)
    {
        return $this->purchaseService->purchaseDelete($request->id);
    }

    public function generateNextPurchaseRefCode()
    {
        return $this->purchaseService->generateNextPurchaseRefCode();
    }
    public function purchaseCreditPayment(Request $request)
    {
        try {
            return $this->purchaseService->purchaseCreditPayment($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
}
