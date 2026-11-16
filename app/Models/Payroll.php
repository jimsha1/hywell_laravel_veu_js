<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_no',
        'employee_id',
        'user_id', // For salesman/salesexecutive users
        'month',
        'year',
        'basic_salary',
        'allowances',
        'overtime',
        'bonus',
        'gross_salary',
        'deductions',
        'net_salary',
        'working_days',
        'present_days',
        'leave_days',
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
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime' => 'decimal:2',
            'bonus' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
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
     * Calculate gross salary
     */
    public function calculateGrossSalary(): float
    {
        return (float)$this->basic_salary + (float)$this->allowances + (float)$this->overtime + (float)$this->bonus;
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary(): float
    {
        return $this->calculateGrossSalary() - (float)$this->deductions;
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

