<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionHistory extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
    public function orderBy()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
}
// public function orderBy()
// {
//     return $this->belongsTo(User::class, 'order_by'); // assuming 'order_by' is the foreign key
// }
