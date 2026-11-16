<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLoadStockItem extends Model
{
    protected $guarded = [];


    public function stock()
    {
        return $this->belongsTo(VehicleLoadStock::class, 'vehicle_load_stock_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    
}
