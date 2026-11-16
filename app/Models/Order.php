<?php

namespace App\Models;

use App\Traits\OrderHelpers;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use OrderHelpers;

    protected $guarded = [];


    protected $appends = ['display_customer'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    public function orderBy()
    {
        return $this->hasOne(User::class, 'id', 'order_by');
    }
    public function deliveryBy()
    {
        return $this->hasOne(User::class, 'id', 'delivered_by');
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }
    public function users()
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }
    public function getCustomerDetailsAttribute()
    {
        return $this->order_type === 'Outside Sale'
            ? $this->user
            : $this->customer;
    }
    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class, 'order_id');
    }

    public function getDisplayCustomerAttribute()
    {
        if ($this->order_type == 'Outside Sale') {

            return [
                'id'   => optional($this->orderBy)->id,
                'name' => optional($this->orderBy)->name,
                'code'=>'UID-'.optional($this->orderBy)->id,
                'type' => 'employee',
            ];
        }

        return [
            'id'   => optional($this->customer)->id,
            'name' => optional($this->customer)->customer_name,
            'code'=>optional($this->customer)->customer_code,
            'type' => 'customer',
        ];
    }
}
