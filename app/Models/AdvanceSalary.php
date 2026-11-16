<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceSalary extends Model

{
    use SoftDeletes;

    protected $fillable = [

        'employee_id',
        'user_id', // For salesman/salesexecutive users

        'amount',
        'date',
        'is_adjusted',
        'remarks',
        'payment_mode',
    ];


    protected function casts(): array
    {
        return [
            'advance_salary' => 'decimal:2',
            'advance_date' => 'date',
          


        ];
    }
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user (salesman/salesexecutive)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff member (returns user if user_id exists, otherwise employee's user)
     */
    public function getStaffAttribute()
    {
        if ($this->user_id) {
            return $this->user;
        }
        return $this->employee?->user;
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    
}
