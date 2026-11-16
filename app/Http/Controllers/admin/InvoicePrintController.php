<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoicePrintController extends Controller
{
    public function print_invoice($order_code)
    {
        $orders = Order::with('orderItems.product','customer')->where('order_no', $order_code)->firstOrFail();
// dd($orders);
        return view('admin.pages.invoice_print.index', compact('orders'));
    }
}
