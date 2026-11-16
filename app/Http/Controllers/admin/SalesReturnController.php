<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\SalesReturnService;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    private SalesReturnService $salesReturnService;

    public function __construct(SalesReturnService $salesReturnService)
    {
        $this->salesReturnService = $salesReturnService;
    }

    public function index()
    {
        return view('admin.pages.sales_return.index');
    }

    public function createSalesReturn(Request $request)
    {
        return $this->salesReturnService->createSalesReturn($request);
    }

    public function approveSalesReturn(Request $request)
    {
        return $this->salesReturnService->approveSalesReturn($request);
    }

    public function rejectSalesReturn(Request $request)
    {
        return $this->salesReturnService->rejectSalesReturn($request);
    }

    public function getAllSalesReturns(Request $request)
    {
        $platform = $request->platform == "api" ? 'api' : 'web';
        return $this->salesReturnService->getAllSalesReturns($request, $platform);
    }

    public function getSalesReturnDetails(Request $request)
    {
        return $this->salesReturnService->getSalesReturnDetails($request->return_id);
    }

    public function getOrdersForReturn(Request $request)
    {
        $platform = $request->platform == "api" ? 'api' : 'web';
        return $this->salesReturnService->getOrdersForReturn($request, $platform);
    }
    public function getCustomerCreditBalance(Request $request){
        return $this->salesReturnService->customerCreditBalance($request);
        
    }
}




