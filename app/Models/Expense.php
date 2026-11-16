<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense_no',
        'expense_type',
        'expense_category_id',
        'account_id',
        'cr_account_id',
        'expense_date',
        'amount',
        'payment_mode',
        'reference_no',
        'description',
        'receipt_path',
        'created_by',
        'approved_by',
        'status',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            // 'expense_date' => 'date',
            'expense_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the expense category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Get the account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function cr_account(): BelongsTo
    {
        return $this->belongsTo(Account::class,'cr_account_id');
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
     * Scope to get approved expenses
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

   
}




