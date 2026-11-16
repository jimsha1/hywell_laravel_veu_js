<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLoadItemDetail extends Model
{
    protected $guarded = [];


    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
