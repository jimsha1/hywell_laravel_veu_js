<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];


    public function products()
    {
        return $this->hasMany(Products::class, 'category_id', 'id')
            ->where('status', 1)
            ->orderBy('created_at', 'asc');
    }
}
