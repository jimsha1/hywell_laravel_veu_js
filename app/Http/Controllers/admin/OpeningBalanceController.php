<?php

namespace App\Http\Controllers\admin;


use App\Http\Controllers\Controller;

use App\Services\OpeningBalanceService;
use Symfony\Component\HttpFoundation\Request;

class OpeningBalanceController extends Controller
{
    private OpeningBalanceService $openingBalanceService;

    public function __construct(OpeningBalanceService $openingBalanceService)
    {
        $this->openingBalanceService = $openingBalanceService;
    }
    public function index()
    {
        return view('admin.pages.opening_balance.index');
    }
    public function openingBalanceList(Request $request)
    {
        return $this->openingBalanceService->openingBalanceList($request);
    }
    public function openingBalanceCustomerList(Request $request)
    {
        return $this->openingBalanceService->openingBalanceCustomerList($request);
    }
    public function openingBalanceSupplierList(Request $request)
    {
        return $this->openingBalanceService->openingBalanceSupplierList($request);
    }
       
    public function openingBalanceStore(Request $request)
    {
        return $this->openingBalanceService->openingBalanceStore($request);
    }
    public function openingBalanceUpdate(Request $request)
    {
        return $this->openingBalanceService->openingBalanceUpdate($request);
    }
}
