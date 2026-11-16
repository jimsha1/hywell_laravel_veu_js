<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Employee extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roles()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
    /**
     * Get the linked user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outsideSaleOrders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'user_id')
            ->where('order_type', 'Outside Sale');
    }
    /**
     * Get all payroll records for this employee
     */
}
