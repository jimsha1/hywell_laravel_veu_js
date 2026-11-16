<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $guarded = [];
    protected $appends = ['display_customer'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function returnItems()
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id');
    }

    public function returnPayments()
    {
        return $this->hasMany(SalesReturnPayment::class, 'sales_return_id');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users()
    {
        return $this->belongsTo(
            User::class,
            'customer_id',
            'id'
        );
    }
    public function getDisplayCustomerAttribute()
    {
        // if ((int) $this->is_outside_salesman === 1) {
        //     return $this->users;
        // }

        // return $this->customer;

        if ($this->is_outside_salesman === 1) {

            return [
                'id'   => optional($this->users)->id,
                'name' => optional($this->users)->name,
                'code' => 'UID-' . optional($this->users)->id,
                'type' => 'employee',
            ];
        }

        return [
            'id'   => optional($this->customer)->id,
            'name' => optional($this->customer)->customer_name,
            'code' => optional($this->customer)->customer_code,
            'type' => 'customer',
        ];
    }
}
