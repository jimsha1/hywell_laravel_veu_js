<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommisionTypeMaster extends Model
{
    protected $guarded = [];


    public function commissionTypeItems()
    {
        return $this->hasMany(CommissionTypeItem::class, 'commission_type_master_id', 'id');
    }
}
