<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];


    public function customer_product()
    {
        return $this->hasOne(AssignedSpecialPrice::class, 'product_id', 'id');
    }
    public function category()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'category_id');
    }
    public function tax()
    {
        return $this->hasOne(GlobalTax::class, 'id', 'tax_id');
    }
    public function vehicleLoadStockItems()
    {
        return $this->hasMany(VehicleLoadStockItem::class, 'product_id');
    }
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }
}
