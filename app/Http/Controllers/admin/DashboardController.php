<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
// use App\Models\WalletHistory;
use App\Models\Order;
use App\Models\User;
use App\Models\VehicleLoadStock;
use App\Services\DashboardService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index()
    {
        return view('admin.pages.dashboard');
    }
    public function purchaseIndex()
    {
        return view('admin.pages.purchase');
    }
    public function sales_head_index()
    {
        return view('admin.pages.sales-head-dashboard');
    }
    public function accountant_index()
    {
        return view('admin.pages.accountant-dashboard');
    }

    public function getDashboardData(Request $request)
    {
        try {
            return $this->dashboardService->getDashboardData($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function getSalesManList(Request $request)
    {
        try {
            return $this->dashboardService->getSalesManList($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
    public function getGodownStockList(Request $request)
    {
        try {
            return $this->dashboardService->getGodownStockList($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
    public function getVehicleStockList(Request $request)
    {
        try {
            return $this->dashboardService->getVehicleStockList($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
    public function employeeCommissionList(Request $request)
    {
        try {
            return $this->dashboardService->getEmployeeCommission($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }


    public function getHomePageData(Request $request)
    {
        try {
            return $this->dashboardService->getHomePageData($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function getPendingExpenses(Request $request)
    {
        try {
            return $this->dashboardService->getPendingExpenses($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
}
