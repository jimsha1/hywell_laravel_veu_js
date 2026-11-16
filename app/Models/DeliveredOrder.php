<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DeliveredOrder extends Model
{
    protected $guarded = [];



    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
}
