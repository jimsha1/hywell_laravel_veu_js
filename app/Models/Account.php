<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_type_id',
        'parent_id',
        'code',
        'name',
        'description',
        'opening_balance',
        'opening_balance_date',
        'is_active',
        'is_system',
        'sort_order',
        'reference_id',
        'reference_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_date' => 'date',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the account type
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * Get the parent account
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get child accounts
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get journal entry lines
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get account balances
     */
    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class);
    }

    /**
     * Get current balance for this account
     */
    public function getCurrentBalance(?string $date = null, ?int $financialYearId = null): float
    {
        $date = $date ?? now()->toDateString();

        $balance = $this->balances()

            ->where('balance_date', '<=', $date)
            ->when($financialYearId, function ($query) use ($financialYearId) {
                $query->where('financial_year_id', $financialYearId);
            })
            ->orderByDesc('balance_date')
            ->first();

        if ($balance) {

            if ($this->opening_balance_date && $this->opening_balance_date <= $date) {
                $balance->net_balance += (float) $this->opening_balance;
            }
            return (float) $balance->net_balance;
        }


        // Calculate from journal entries if no balance record exists
        return $this->calculateBalanceFromEntries(
            $date,
            $financialYearId
        );
    }




    /**
     * Calculate balance from journal entries
     */
    public function calculateBalanceFromEntries(?string $date = null, ?int $financialYearId = null): float
    {
        $date = $date ?? now()->toDateString();
        $debitQuery = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($date, $financialYearId) {

                $query->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $date);

                if ($financialYearId) {
                    $query->where('financial_year_id', $financialYearId);
                }
            });

        $creditQuery = clone $debitQuery;


        $debitTotal = $debitQuery
            ->where('type', 'debit')
            ->sum('amount');

        $creditTotal = $creditQuery
            ->where('type', 'credit')
            ->sum('amount');

        $netBalance = (float) $debitTotal - (float) $creditTotal;


        // Adjust based on account type normal balance
        if ($this->accountType && $this->accountType->isCreditNormal()) {
            $netBalance = (float) $creditTotal - (float) $debitTotal;
        }

        // Add opening balance
        if ($this->opening_balance_date && $this->opening_balance_date <= $date) {
            $netBalance += (float) $this->opening_balance;
        }



        return $netBalance;
    }

    public function calculateBalanceFromEntries1(?string $date = null): float
    {
        $date = $date ?? now()->toDateString();

        $debitTotal = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $date);
            })
            ->where('type', 'debit')
            ->sum('amount');

        $creditTotal = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($date) {
                $query->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $date);
            })
            ->where('type', 'credit')
            ->sum('amount');

        $netBalance = $debitTotal - $creditTotal;

        // 🔹 Adjust for account type
        if ($this->accountType && $this->accountType->isCreditNormal()) {
            $netBalance = $creditTotal - $debitTotal;
        }

        // 🔹 Add opening balance
        if ($this->opening_balance_date && $this->opening_balance_date <= $date) {
            $netBalance += $this->opening_balance;
        }

        return (float) $netBalance;
    }

    public function calculateBalanceForDateRange(
        string $startDate,
        string $endDate
    ): float {

        $debitTotal = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {

                $query->where('status', 'posted')
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
            })
            ->where('type', 'debit')
            ->sum('amount');


        $creditTotal = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {

                $query->where('status', 'posted')
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
            })
            ->where('type', 'credit')
            ->sum('amount');


        $netBalance =
            (float) $debitTotal -
            (float) $creditTotal;


        if (
            $this->accountType &&
            $this->accountType->isCreditNormal()
        ) {

            $netBalance =
                (float) $creditTotal -
                (float) $debitTotal;
        }


        return (float) $netBalance;
    }
    /**
     * Update account balance for a specific date
     */


    // public function updateBalance(
    //     ?string $date = null,
    //     ?int $financialYearId = null
    // ): void {

    //     $debitTotal = $this->journalEntryLines()
    //         ->whereHas('journalEntry', function ($query) use ($date, $financialYearId) {
    //             $query->where('status', 'posted')
    //                 ->where('entry_date', '<=', $date);
    //             if ($financialYearId) {
    //                 $query->where('financial_year_id', $financialYearId);
    //             }
    //         })

    //         ->where('type', 'debit')
    //         ->sum('amount');

    //     $creditTotal = $this->journalEntryLines()
    //         ->whereHas('journalEntry', function ($query) use ($date, $financialYearId) {
    //             $query->where('status', 'posted')
    //                 ->where('entry_date', '<=', $date);
    //             if ($financialYearId) {
    //                 $query->where('financial_year_id', $financialYearId);
    //             }
    //         })
    //         ->where('type', 'credit')
    //         ->sum('amount');

    //     $netBalance = (float) $debitTotal - (float) $creditTotal;

    //     // Adjust based on account type normal balance
    //     if ($this->accountType && $this->accountType->isCreditNormal()) {

    //         $netBalance = (float) $creditTotal - (float) $debitTotal;
    //     }

    //     // Add opening balance
    //     // if ($this->opening_balance_date && $this->opening_balance_date <= $date) {

    //     //     $netBalance += (float) $this->opening_balance;
    //     // }


    //     // Update or create balance record
    //     $this->balances()->updateOrCreate(
    //         [
    //             'balance_date' => $date,
    //             'financial_year_id' => $financialYearId,
    //         ],
    //         [
    //             'debit_balance' => (float) $debitTotal,
    //             'credit_balance' => (float) $creditTotal,
    //             'net_balance' => $netBalance,
    //         ]
    //     );
    // }

    public function updateBalance(
        ?string $date = null,
        ?int $financialYearId = null
    ): void {

        if (!$date) {
            $date = now()->toDateString();
        }

        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($date, $financialYearId) {

                $query->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $date);

                if ($financialYearId) {
                    $query->where('financial_year_id', $financialYearId);
                }
            });

        $totals = $query
            ->selectRaw("
            COALESCE(
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END),
                0
            ) AS debit_total
        ")
            ->selectRaw("
            COALESCE(
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END),
                0
            ) AS credit_total
        ")
            ->first();

        $debitTotal = (float) $totals->debit_total;
        $creditTotal = (float) $totals->credit_total;

        if ($this->accountType && $this->accountType->isCreditNormal()) {
            $netBalance = $creditTotal - $debitTotal;
        } else {
            $netBalance = $debitTotal - $creditTotal;
        }

        $this->balances()->updateOrCreate(
            [
                'balance_date' => $date,
                'financial_year_id' => $financialYearId,
            ],
            [
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'net_balance' => $netBalance,
            ]
        );
    }

    public function recalculateBalancesFromDate(
        string $fromDate,
        ?int $financialYearId = null
    ): void {

        $fromDate = Carbon::parse($fromDate)->toDateString();

        $dates = $this->balances()
            ->where('balance_date', '>=', $fromDate)
            ->when($financialYearId, function ($query) use ($financialYearId) {
                $query->where('financial_year_id', $financialYearId);
            })
            ->pluck('balance_date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        /*
     * Also make sure the new journal entry date itself
     * is included.
     */
        $dates->push($fromDate);

        $dates = $dates
            ->unique()
            ->sort()
            ->values();

        foreach ($dates as $date) {

            $this->updateBalance(
                $date,
                $financialYearId
            );
        }
    }
    /**
     * Scope to get active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get accounts by type
     */
    public function scopeOfType($query, $accountTypeCode)
    {
        return $query->whereHas('accountType', function ($q) use ($accountTypeCode) {
            $q->where('code', $accountTypeCode);
        });
    }

    /**
     * Get full account path (for hierarchical display)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->code];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->code);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    public function getReferenceNameAttribute()
    {
        if ($this->reference_type === 'employee' || $this->reference_type === 'commission' || $this->reference_type === 'advance') {
            return \App\Models\Employee::find($this->reference_id);
        }

        if ($this->reference_type === 'customer') {
            return \App\Models\Customer::find($this->reference_id);
        }

        if ($this->reference_type === 'supplier') {
            return \App\Models\Supplier::find($this->reference_id);
        }

        return null;
    }

    public function voucher()
    {

        return $this->hasMany(VoucherEntry::class, 'account_id', 'id');
    }
}
