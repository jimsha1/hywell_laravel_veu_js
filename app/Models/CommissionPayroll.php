<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPayroll extends Model
{
    use SoftDeletes;

    protected $table = 'commission_payroll';
    protected $fillable = [
        
        'commission_no',
        'employee_id',
      
        'month',
        'year',
        'commission',
      
        'payment_date',
        'payment_mode',
        'reference_no',
        'status',
        'created_by',
        'approved_by',
        'journal_entry_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    /**
     * Get the employee (legacy)
     */
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

    /**
     * Get the journal entry
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

   

    /**
     * Get month name
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by month/year
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}

