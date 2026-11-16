<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee(){
        return $this->belongsTo(Employee::class,'employee_id')->select('id','name','employee_code','is_active','profile_image','designation');
    }
}
