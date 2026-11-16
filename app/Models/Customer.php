<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    // public function customerOrders()
    // {
    //     return $this->hasMany(Order::class, 'customer_id', 'id')->where('order_status', '!=', 'draft')->orderBy('created_at', 'desc');
    // }
    public function customerOrders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id')->where('order_status', 'delivered')->orderBy('created_at', 'desc');
    }
    public function assignedSpecialPrice()
    {
        return $this->hasMany(AssignedSpecialPrice::class, 'customer_id');
    }
    public function open_balance()
    {
        return $this->hasOne(OpeningBalance::class, 'customer_id', 'id');
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class, 'customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
