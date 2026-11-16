<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\AdminOrderService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    private AdminOrderService $adminOrderService;

    public function __construct(AdminOrderService $AdminOrderService)
    {
        $this->adminOrderService = $AdminOrderService;
    }
    public function index()
    {
        return view('admin.pages.admin_order.index');
    }
    public function orderList()
    {
        return view('admin.pages.admin_order.order_list');
    }
    public function adminOrderStore(Request $request)
    {
        if (empty($request->id)) {
            $this->validate($request, []);
            return $this->adminOrderService->admin_order_store($request);
        }
    }
    public function adminrOrderList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->adminOrderService->getAllAdminOrder($request, $platform);
    }

    public function editOrderPayment()
    {
        return view('admin.pages.admin_order.edit_order_payment');
    }

    public function getOrderDetailsWithPayments(Request $request)
    {
        return $this->adminOrderService->getOrderDetailsWithPayments($request->order_id);
    }

    public function verifyPasswordAndGetOrder(Request $request)
    {
        return $this->adminOrderService->verifyPasswordAndGetOrder(
            $request->order_id,
            $request->password,
            $request->user_id
        );
    }



    public function getOrdersForPaymentEdit(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->adminOrderService->getAllOrdersForPaymentEdit($request, $platform);
    }

    public function updateAllOrderPayments(Request $request)
    {
        return $this->adminOrderService->updateAllOrderPayments($request);
    }

}
