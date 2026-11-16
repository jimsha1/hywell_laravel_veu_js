<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyOpeningBalance extends Model
{
    protected $guarded = [];
    protected $table = "company_opening_balance";

    protected function casts(): array
    {
        return [
            'opening_date' => 'date',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        
        return $query->whereBetween('opening_date', [$startDate, $endDate]);
    }
}
