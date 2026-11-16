<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLoadStock extends Model
{
    protected $guarded = [];

    public function vehicleLoadStockItems()
    {
        return $this->hasMany(VehicleLoadStockItem::class, 'vehicle_load_stock_id')->orderBy("product_id", "asc");
    }
    public function vehicleLoadStockDetails()
    {
        return $this->hasMany(VehicleLoadItemDetail::class, 'vehicle_load_id')->orderBy("product_id", "asc");
    }

    public function reRequestedStockItems()
    {
        return $this->hasMany(VehicleLoadItemDetail::class, 'vehicle_load_id')->where('status', 're_requested')->orderBy("created_at", "DESC");
    }

    public function returnRequestedStockItems()
    {
        return $this->hasMany(VehicleLoadItemDetail::class, 'vehicle_load_id')->where('status', 'return_requested')->orderBy("created_at", "DESC");
    }



    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'id', 'vehicle_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
