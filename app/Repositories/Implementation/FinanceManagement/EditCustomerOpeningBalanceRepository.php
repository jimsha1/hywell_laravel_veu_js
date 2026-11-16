<?php

namespace App\Repositories\Implementation\FinanceManagement;

use App\Models\OrderPayment;
use App\Repositories\interfaces\FinanceManagement\EditCustomerOpeningBalanceRepositoryInterface;

class EditCustomerOpeningBalanceRepository implements EditCustomerOpeningBalanceRepositoryInterface
{

    protected $order_payment;

    public function __construct(OrderPayment $order_payment){
        $this->order_payment = $order_payment;
    }

    public function get_all($request){
        //code here
        $query = $this->order_payment::select([
                'id',
                'customer_id',
                'user_id',
                'paid_amount',
                'payment_mode',
                'payment_date',
                'created_at'
            ])
            ->with([
                'customer:id,customer_name,customer_code',
                'user:id,name'
            ])
            ->where('type', 'opening_balance')
            ->whereNull('order_id');


        if ($request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('customer', function ($cat) use ($searchTerm) {
                    $cat->where('customer_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('customer_code', 'LIKE', "%{$searchTerm}%");
                })
                    ->orWhereHas('user', function ($cat) use ($searchTerm) {
                        $cat->where('name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }


        return $query;  
    }

    public function get_payment_by_id($request){
        $data = $this->order_payment::where('id', $request->payment_id)->first();
        return $data;
    }
}
