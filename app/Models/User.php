<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vehicleLoadStocks()
    {
        return $this->hasMany(VehicleLoadStock::class, 'user_id', 'id');
    }
    public function todayOpenedVehicleLoadStocks()
    {
        return $this->hasOne(VehicleLoadStock::class, 'user_id', 'id')
            ->where('day_status', 'started')
            ->where('load_status', 'approved')
            ->orderBy("created_at", "DESC");
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'sales_man_user_id', 'id');
    }

    
    public function customer()
    {
        return $this->hasMany(Customer::class, 'created_by', 'id');
    }
    public function commissionHistories()
    {
        return $this->hasMany(CommissionHistory::class, 'user_id');
    }

    public function paid_commissions()
    {
        return $this->hasMany(PaidCommission::class, 'user_id');
    }
    public function todaySales()
    {
        return $this->hasMany(Order::class, 'sales_man_user_id')
            ->where('order_status', '!=', 'draft')
            ->whereDate('created_at', Carbon::today()->toDateString());
    }

    public function latestSalesExecutiveOrders()
    {
        return $this->hasMany(Order::class, 'order_by', 'id')
            ->where('order_status', '!=', 'draft')   // <-- missing quote fixed
            ->orderBy('created_at', 'desc')
            ->take(10);
    }

    public function latestSalesManOrders()
    {
        return $this->hasMany(Order::class, 'delivered_by', 'id')->where('order_status', 'delivered')->orderBy('created_at', 'desc')->take(10);
    }

    public function todaySalesManSales()
    {
        return $this->hasMany(Order::class, 'delivered_by', 'id')
            ->where('order_status', 'delivered')
            ->whereDate('created_at', Carbon::today()->toDateString());
    }
}
