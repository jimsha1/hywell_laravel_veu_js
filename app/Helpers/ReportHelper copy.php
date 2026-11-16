<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\CompanyOpeningBalance;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportHelper
{
    /**
     * Get Trial Balance Report
     *
     * @param string|null $date
     * @return array
     */
    public static function getTrialBalance(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $accounts = Account::with('accountType')->active()->get();

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $account->getCurrentBalance($date);

            // Determine debit/credit based on account type
            $isDebitNormal = $account->accountType->isDebitNormal();
            $debitAmount = $isDebitNormal && $balance >= 0 ? abs($balance) : 0;
            $creditAmount = !$isDebitNormal && $balance >= 0 ? abs($balance) : 0;

            // Handle negative balances
            if ($balance < 0) {
                if ($isDebitNormal) {
                    $creditAmount = abs($balance);
                } else {
                    $debitAmount = abs($balance);
                }
            }

            if ($debitAmount > 0 || $creditAmount > 0) {
                $trialBalance[] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->accountType->name,
                    'debit' => $debitAmount,
                    'credit' => $creditAmount,
                ];

                $totalDebits += $debitAmount;
                $totalCredits += $creditAmount;
            }
        }

        return [
            'date' => $date,
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
        ];
    }

    /**
     * Get Balance Sheet
     *
     * @param string|null $date
     * @return array
     */
    // public static function getBalanceSheet(?string $date = null): array
    // {
    //     $date = $date ?? now()->toDateString();

    //     $assets = self::getAccountsByType('ASSET', $date);
    //     $liabilities = self::getAccountsByType('LIABILITY', $date);

    //     $equity = self::getAccountsByType('EQUITY', $date);

    //     $totalAssets = array_sum(array_column($assets, 'balance'));
    //     $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
    //     $totalEquity = array_sum(array_column($equity, 'balance'));

    //     // Calculate retained earnings (if not explicitly tracked)
    //     $retainedEarnings = self::calculateRetainedEarnings($date);
    //     $totalEquity += $retainedEarnings;

    //     return [
    //         'date' => $date,
    //         'assets' => [
    //             'accounts' => $assets,
    //             'total' => $totalAssets,
    //         ],
    //         'liabilities' => [
    //             'accounts' => $liabilities,
    //             'total' => $totalLiabilities,
    //         ],
    //         'equity' => [
    //             'accounts' => $equity,
    //             'retained_earnings' => $retainedEarnings,
    //             'total' => $totalEquity,
    //         ],
    //         'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
    //         'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
    //     ];
    // }

    // public static function getBalanceSheet(?string $date = null): array
    // {
    //     $date = $date ?? now()->toDateString();

    //     $assets = self::getAccountsByType('ASSET', $date);
    //     $liabilities = self::getAccountsByType('LIABILITY', $date);
    //     $equity = self::getAccountsByType('EQUITY', $date);

    //     $totalAssets = array_sum(array_column($assets, 'balance'));
    //     $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
    //     $totalEquity = array_sum(array_column($equity, 'balance'));


    //     $incomeStatement = self::getIncomeStatement(null, $date);


    //     $retainedEarnings = $incomeStatement['net_income'];


    //     $totalEquity += $retainedEarnings;

    //     return [
    //         'date' => $date,

    //         'assets' => [
    //             'accounts' => $assets,
    //             'total' => $totalAssets,
    //         ],

    //         'liabilities' => [
    //             'accounts' => $liabilities,
    //             'total' => $totalLiabilities,
    //         ],

    //         'equity' => [
    //             'accounts' => $equity,
    //             'retained_earnings' => $retainedEarnings,
    //             'total' => $totalEquity,
    //         ],

    //         'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,

    //         'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
    //     ];
    // }


    public static function getBalanceSheet(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $assets = self::getAccountsByType('ASSET', $date);
        $liabilities = self::getAccountsByType('LIABILITY', $date);
        $equity = self::getAccountsByType('EQUITY', $date);

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));

        // 🔥 ADD PROFIT TO EQUITY
        $incomeStatement = self::getIncomeStatement(null, $date);
        $retainedEarnings = $incomeStatement['net_income'];

        $totalEquity += $retainedEarnings;

        return [
            'date' => $date,

            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],

            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],

            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],

            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Get Income Statement (Profit & Loss)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|FinancialYear|null $financialYear Financial year ID, FinancialYear instance, or null to auto-detect
     * @return array
     */
    // public static function getIncomeStatement(?string $startDate = null, ?string $endDate = null, $financialYear = null): array
    // {

    //     // If financial year is provided, use its dates
    //     if ($financialYear && config('accounting.enable_financial_year')) {
    //         $fy = self::resolveFinancialYear($financialYear);

    //         if ($fy) {
    //             $startDate = $startDate ?? $fy->start_date->toDateString();
    //             $endDate = $endDate ?? $fy->end_date->toDateString();
    //         }
    //     } elseif (config('accounting.enable_financial_year')) {

    //         // Try to get financial year for current date
    //         $financialYearService = app(\App\Services\FinancialYearService::class);

    //         $currentFY = $financialYearService->getCurrentFinancialYear();

    //         if ($currentFY && !$startDate && !$endDate) {
    //             $startDate = $currentFY->start_date->toDateString();
    //             $endDate = $currentFY->end_date->toDateString();
    //         } else {
    //             $startDate = $startDate ?? Carbon::now()->startOfYear()->toDateString();
    //             $endDate = $endDate ?? now()->toDateString();
    //         }
    //     } else {
    //         // Financial year disabled, use default date logic
    //         $startDate = $startDate ?? Carbon::now()->startOfYear()->toDateString();
    //         $endDate = $endDate ?? now()->toDateString();
    //     }



    //     $totalInwardQuery = StockMovement::query();
    //     $totalOutwardQuery = StockMovement::query();

    //     if ($endDate != null && $startDate != null) {
    //         $totalInwardQuery->where(function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
    //                 ->orWhere(function ($q) use ($startDate, $endDate) {
    //                     $q->whereNull('date')
    //                         ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //                 });
    //         });
    //         $totalOutwardQuery->where(function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
    //                 ->orWhere(function ($q) use ($startDate, $endDate) {
    //                     $q->whereNull('date')
    //                         ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //                 });
    //         });
    //     }

    //     $totalInward = (int) $totalInwardQuery->where('movement_type', 'in')->sum('amount');
    //     $totalOutward = (int) $totalOutwardQuery->where('movement_type', 'out')->sum('amount');
    //     $closingStock = $totalInward - $totalOutward;


    //     $openingStock = Account::where('name', 'Opening Stock')
    //         ->first()?->getCurrentBalance($startDate) ?? 0;
    //     // $openingStock=self::getAccountsByGroup(1700,$endDate,$startDate);
    //     // $openingStock=array_sum(array_column($openingStock, 'balance'));


    //     // $sales = self::getAccountsByGroup('REVENUE', $endDate, $startDate);
    //     // $totalSales = array_sum(array_column($sales, 'balance'));


    //     // $otherIncomeAccounts = self::getAccountsBy('REVENUE', $endDate, $startDate);
    //     // $totalOtherIncome = array_sum(array_column($otherIncomeAccounts, 'balance'));

    //     $revenues = self::getAccountsByType('REVENUE', $endDate, $startDate);
    //     $totalRevenue = array_sum(array_column($revenues, 'balance'));

    //     $expenses = self::getAccountsByType('EXPENSE', $endDate, $startDate);
    //     $totalExpenses = array_sum(array_column($expenses, 'balance'));




    //     $netIncome = $totalRevenue - $totalExpenses;

    //     $fy = null;
    //     if (config('accounting.enable_financial_year')) {
    //         $fy = self::resolveFinancialYear($financialYear) ??
    //             app(\App\Services\FinancialYearService::class)->getFinancialYearForDate($startDate);
    //     }

    //     return [
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         'financial_year' => $fy ? [
    //             'id' => $fy->id,
    //             'name' => $fy->name,
    //             'code' => $fy->code,
    //         ] : null,
    //         // 'sales' => [
    //         //     'accounts' => $sales,
    //         //     'total' => $totalSales,
    //         // ],
    //         'revenues' => [
    //             'accounts' => $revenues,
    //             'total' => $totalRevenue,
    //         ],


    //         'expenses' => [
    //             'accounts' => $expenses,
    //             'total' => $totalExpenses,
    //         ],
    //         'openingStock' => $openingStock,
    //         'closingStock' => $closingStock,
    //         'net_income' => $netIncome,


    //     ];
    // }

    // public static function getIncomeStatement(?string $startDate = null, ?string $endDate = null): array
    // {
    //     $startDate = $startDate ?? now()->startOfYear()->toDateString();
    //     $endDate   = $endDate ?? now()->toDateString();

    //     // 🔹 STOCK MOVEMENT (Closing Stock)
    //     $totalInward = StockMovement::where('movement_type', 'in')
    //         ->whereBetween('date', [$startDate, $endDate])
    //         ->sum('amount');

    //     $totalOutward = StockMovement::where('movement_type', 'out')
    //         ->whereBetween('date', [$startDate, $endDate])
    //         ->sum('amount');

    //     $closingStock = $totalInward - $totalOutward;

    //     // 🔹 OPENING STOCK
    //     $openingStock = Account::where('name', 'Opening Stock')
    //         ->first()?->getCurrentBalance($startDate) ?? 0;

    //     // 🔹 SALES
    //     $salesAccounts = self::getAccountsByType('INCOME', $endDate, $startDate);
    //     $totalSales = array_sum(array_column($salesAccounts, 'balance'));

    //     // 🔹 PURCHASE / COGS
    //     $purchaseAccounts = self::getAccountsByType('COGS', $endDate, $startDate);
    //     $totalPurchase = array_sum(array_column($purchaseAccounts, 'balance'));

    //     // 🔹 COST OF GOODS SOLD
    //     $COGS = $openingStock + $totalPurchase - $closingStock;

    //     // 🔹 GROSS PROFIT
    //     $grossProfit = $totalSales - $COGS;

    //     // 🔹 OTHER INCOME
    //     $otherIncomeAccounts = self::getAccountsByType('REVENUE', $endDate, $startDate);
    //     $totalOtherIncome = array_sum(array_column($otherIncomeAccounts, 'balance'));

    //     // 🔹 EXPENSES
    //     $expenseAccounts = self::getAccountsByType('EXPENSE', $endDate, $startDate);
    //     $totalExpenses = array_sum(array_column($expenseAccounts, 'balance'));

    //     // 🔹 NET PROFIT
    //     $netProfit = $grossProfit + $totalOtherIncome - $totalExpenses;

    //     return [
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         // 'financial_year' => $fy ? [
    //         //     'id' => $fy->id,
    //         //     'name' => $fy->name,
    //         //     'code' => $fy->code,
    //         // ] : null,
    //         'sales' => [
    //             'accounts' => $salesAccounts,
    //             'total' => $totalSales,
    //         ],
    //         'revenues' => [
    //             'accounts' => $otherIncomeAccounts,
    //             'total' => $totalOtherIncome,
    //         ],


    //         'expenses' => [
    //             'accounts' => $expenseAccounts,
    //             'total' => $totalExpenses,
    //         ],
    //         'purchases' => [
    //             'accounts' => $purchaseAccounts,
    //             'total' => $totalPurchase
    //         ],
    //         'closingStock' => $closingStock,
    //         'net_income' => $netProfit,
    //     ];
    //     return [
    //         'start_date' => $startDate,
    //         'end_date'   => $endDate,

    //         // 🔵 TRADING ACCOUNT
    //         'trading' => [
    //             'opening_stock' => $openingStock,
    //             'purchases' => $totalPurchase,
    //             'closing_stock' => $closingStock,
    //             'sales' => $totalSales,
    //             'cogs' => $COGS,
    //             'gross_profit' => $grossProfit,
    //         ],

    //         // 🟢 PROFIT & LOSS
    //         'profit_and_loss' => [
    //             'other_income' => $totalOtherIncome,
    //             'expenses' => $totalExpenses,
    //             'net_profit' => $netProfit,
    //         ],

    //         // 🔍 DETAILS (for UI)
    //         'sales_accounts' => $salesAccounts,
    //         'purchase_accounts' => $purchaseAccounts,
    //         'expense_accounts' => $expenseAccounts,
    //         'other_income_accounts' => $otherIncomeAccounts,
    //     ];
    // }

    public static function getIncomeStatement(?string $startDate = null, ?string $endDate = null, $financialYear = null): array
    {
        // ---------------- DATE LOGIC (keep same) ----------------
        if ($financialYear && config('accounting.enable_financial_year')) {
            $fy = self::resolveFinancialYear($financialYear);
            if ($fy) {
                $startDate = $startDate ?? $fy->start_date->toDateString();
                $endDate = $endDate ?? $fy->end_date->toDateString();
            }
        } else {
            $startDate = $startDate ?? Carbon::now()->startOfYear()->toDateString();
            $endDate = $endDate ?? now()->toDateString();
        }

        // ---------------- STOCK ----------------
        $totalInward = StockMovement::where('movement_type', 'in')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalOutward = StockMovement::where('movement_type', 'out')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // $closingStock = $totalInward - $totalOutward;
        $openingStock = StockMovement::where('movement_type', 'in')
            ->where('reason', 'opening_stock')
            ->where('date', '=', $startDate)
            ->sum('amount');


        // $openingStock = Account::where('name', 'Opening Stock')
        //     ->first()?->getCurrentBalance($startDate) ?? 0;


        // ---------------- PURCHASE ----------------
        $purchaseAccounts = self::getAccountsByGroup('10000', $endDate, $startDate);
        $totalPurchase = array_sum(array_column($purchaseAccounts, 'balance'));
        
        $closingStock = $totalInward - $totalOutward + $totalPurchase;
        // ---------------- SALES ----------------
        $salesAccounts = self::getAccountsByGroup('6000', $endDate, $startDate);

        $totalSales = array_sum(array_column($salesAccounts, 'balance'));

        // ---------------- COGS ----------------
        $cogs = $openingStock + $totalPurchase - $closingStock;

        // ---------------- GROSS PROFIT ----------------
        $grossProfit = $totalSales - $cogs;

        // ---------------- EXPENSES ----------------
        $expenses = self::getAccountsByType('EXPENSE', $endDate, $startDate);
        $directExpense = self::getAccountsByGroup('8000', $endDate, $startDate);
        $totalDirectExpenses = array_sum(array_column($directExpense, 'balance'));
        $indirectExpense = self::getAccountsByGroup('9000', $endDate, $startDate);
        $totalIndirectExpense = array_sum(array_column($indirectExpense, 'balance'));
        // $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $totalExpenses = $totalDirectExpenses + $totalIndirectExpense;
        // ---------------- NET PROFIT ----------------
        $netIncome = $grossProfit - $totalDirectExpenses - $totalIndirectExpense;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,

            // TRADING
            'trading' => [
                'opening_stock' => $openingStock,
                'purchase' => $totalPurchase,
                'closing_stock' => $closingStock,
                'sales' => $totalSales,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
            ],

            // P&L
            'direct_expenses' => [
                'accounts' => $directExpense,
                'total' => $totalExpenses,
            ],
            'indirect_expenses' => [
                'accounts' => $indirectExpense,
                'total' => $totalExpenses,
            ],

            'net_income' => $netIncome,
        ];
    }

    // public static function getIncomeStatement(?string $startDate = null, ?string $endDate = null): array
    // {
    //     $startDate = $startDate ?? now()->startOfYear()->toDateString();
    //     $endDate   = $endDate ?? now()->toDateString();

    //     // ---------------- OPENING STOCK (BEFORE PERIOD) ----------------
    //     $openingStock = StockMovement::where('movement_type', 'in')
    //         ->where('reason', 'opening_stock')
    //         ->where('date', '<', $startDate)
    //         ->sum('amount');

    //     // ---------------- STOCK MOVEMENT DURING PERIOD ----------------
    //     $totalInward = StockMovement::where('movement_type', 'in')
    //         ->whereBetween('date', [$startDate, $endDate])
    //         ->where('reason', '!=', 'opening_stock')
    //         ->sum('amount');

    //     $totalOutward = StockMovement::where('movement_type', 'out')
    //         ->whereBetween('date', [$startDate, $endDate])
    //         ->sum('amount');

    //     // ---------------- CLOSING STOCK ----------------
    //     $closingStock = $openingStock + $totalInward - $totalOutward;

    //     // ---------------- PURCHASE ----------------
    //     $purchaseAccounts = self::getAccountsByGroup('8100', $endDate, $startDate);
    //     $totalPurchase = array_sum(array_column($purchaseAccounts, 'balance'));

    //     // ---------------- SALES ----------------
    //     $salesAccounts = self::getAccountsByGroup('6000', $endDate, $startDate);
    //     $totalSales = array_sum(array_column($salesAccounts, 'balance'));

    //     // ---------------- COGS ----------------
    //     $cogs = $openingStock + $totalPurchase - $closingStock;

    //     // ---------------- GROSS PROFIT ----------------
    //     $grossProfit = $totalSales - $cogs;

    //     // ---------------- EXPENSES ----------------
    //     $expenses = self::getAccountsByType('EXPENSE', $endDate, $startDate);
    //     $totalExpenses = array_sum(array_column($expenses, 'balance'));

    //     // ---------------- NET PROFIT ----------------
    //     $netIncome = $grossProfit - $totalExpenses;

    //     return [
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,

    //         'trading' => [
    //             'opening_stock' => $openingStock,
    //             'purchase' => $totalPurchase,
    //             'closing_stock' => $closingStock,
    //             'sales' => $totalSales,
    //             'cogs' => $cogs,
    //             'gross_profit' => $grossProfit,
    //         ],

    //         'expenses' => [
    //             'accounts' => $expenses,
    //             'total' => $totalExpenses,
    //         ],

    //         'net_income' => $netIncome,
    //     ];
    // }
    /**
     * Resolve financial year from ID, instance, or null
     *
     * @param int|FinancialYear|null $financialYear
     * @return FinancialYear|null
     */
    protected static function resolveFinancialYear($financialYear): ?FinancialYear
    {
        if ($financialYear instanceof FinancialYear) {
            return $financialYear;
        }

        if (is_int($financialYear)) {
            return FinancialYear::find($financialYear);
        }

        return null;
    }

    /**
     * Get accounts by type with balances
     *
     * @param string $typeCode
     * @param string|null $date
     * @param string|null $startDate For period-based calculations
     * @return array
     */
    protected static function getAccountsByType(string $typeCode, ?string $date = null, ?string $startDate = null): array
    {
        $date = $date ?? now()->toDateString();
        $accountType = AccountType::where('code', $typeCode)->first();

        if (!$accountType) {
            return [];
        }

        $accounts = Account::where('account_type_id', $accountType->id)
            ->active()
            ->with('journalEntryLines', 'journalEntryLines.journalEntry')
            ->orderBy('code')
            ->get();


        $result = [];

        foreach ($accounts as $account) {


            if ($startDate) {
                // For period-based (like income statement), calculate balance for the period
                $balance = self::getAccountBalanceForPeriod($account, $startDate, $date);
            } else {
                // For point-in-time (like balance sheet), get current balance

                $balance = $account->getCurrentBalance($date);
            }


            if ($balance != 0 || $account->opening_balance != 0) {
                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                    'journalEntryLines' => $account->journalEntryLines

                ];
            }
        }

        return $result;
    }

    protected static function getAccountsByGroup(string $typeCode, ?string $date = null, ?string $startDate = null): array
    {
        $date = $date ?? now()->toDateString();
        $accountGroup = Account::where('code', $typeCode)->first();

        if (!$accountGroup) {
            return [];
        }

        $accounts = Account::where('parent_id', $accountGroup->id)
            ->active()
            ->with('journalEntryLines', 'journalEntryLines.journalEntry')
            ->orderBy('code')
            ->get();


        $result = [];

        foreach ($accounts as $account) {


            if ($startDate) {
                // For period-based (like income statement), calculate balance for the period
                $balance = self::getAccountBalanceForPeriod($account, $startDate, $date);
            } else {
                // For point-in-time (like balance sheet), get current balance

                $balance = $account->getCurrentBalance($date);
            }


            if ($balance != 0 || $account->opening_balance != 0) {
                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => $balance,
                    'journalEntryLines' => $account->journalEntryLines

                ];
            }
        }

        return $result;
    }

    // protected static function getAllChildAccounts($parentId)
    // {
    //     $children = Account::where('parent_id', $parentId)->active()->get();

    //     $all = collect($children);

    //     foreach ($children as $child) {
    //         $all = $all->merge(self::getAllChildAccounts($child->id));
    //     }

    //     return $all;
    // }
    // protected static function getAccountsByType(string $typeCode, ?string $date = null, ?string $startDate = null): array
    // {
    //     $date = $date ?? now()->toDateString();

    //     $accountType = AccountType::where('code', $typeCode)->first();

    //     if (!$accountType) {
    //         return [];
    //     }

    //     $parentAccounts = Account::where('account_type_id', $accountType->id)
    //         ->whereNull('parent_id')
    //         ->active()
    //         ->orderBy('code')
    //         ->get();
        
    //     $accounts = collect();

    //     foreach ($parentAccounts as $parent) {
    //         $accounts->push($parent);
            
    //         $accounts = $accounts->merge(self::getAllChildAccounts($parent->id));
    //         dd($accounts);
    //     }

    //     $result = [];

    //     foreach ($accounts as $account) {

    //         if ($startDate) {
    //             $balance = self::getAccountBalanceForPeriod($account, $startDate, $date);
    //         } else {
    //             $balance = $account->getCurrentBalance($date);
    //         }

    //         if ($balance != 0 || $account->opening_balance != 0) {
    //             $result[] = [
    //                 'code' => $account->code,
    //                 'name' => $account->name,
    //                 'balance' => abs($balance),
    //             ];
    //         }
    //     }

    //     return $result;
    // }
    /**
     * Get account balance for a period
     *
     * @param Account $account
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    protected static function getAccountBalanceForPeriod(Account $account, string $startDate, string $endDate): float
    {
        $debitTotal = $account->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->where('type', 'debit')
            ->sum('amount');

        $creditTotal = $account->journalEntryLines()
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->where('type', 'credit')
            ->sum('amount');

        $netBalance = (float) $debitTotal - (float) $creditTotal;

        // Adjust based on account type normal balance
        if ($account->accountType && $account->accountType->isCreditNormal()) {
            $netBalance = (float) $creditTotal - (float) $debitTotal;
        }

        return $netBalance;
    }

    /**
     * Calculate retained earnings
     *
     * @param string|null $date
     * @return float
     */
    protected static function calculateRetainedEarnings(?string $date = null): float
    {
        $date = $date ?? now()->toDateString();
        $startOfYear = Carbon::parse($date)->startOfYear()->toDateString();

        // Get net income for the period
        $incomeStatement = self::getIncomeStatement($startOfYear, $date);

        return $incomeStatement['net_income'];
    }

    /**
     * Get General Ledger for an account
     *
     * @param int|Account $account
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public static function getGeneralLedger($account, ?string $startDate = null, ?string $endDate = null): array
    {
        if (is_int($account)) {
            $account = Account::findOrFail($account);
        }

        $startDate = $startDate ?? Carbon::now()->startOfYear()->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        // Calculate opening balance up to the day before start date
        $openingBalanceDate = \Carbon\Carbon::parse($startDate)->subDay()->toDateString();

        $openingBalance = $account->calculateBalanceFromEntries($openingBalanceDate);

        $lines = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->with(['journalEntry' => function ($query) {
                $query->select('id', 'entry_number', 'entry_date', 'description');
            }])
            ->orderBy('journal_entry_id')
            ->orderBy('id')
            ->get();

        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($lines as $line) {
            if ($line->isDebit()) {
                $runningBalance += $line->amount;
            } else {
                $runningBalance -= $line->amount;
            }

            $transactions[] = [
                'date' => $line->journalEntry->entry_date,
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => $line->isDebit() ? $line->amount : 0,
                'credit' => $line->isCredit() ? $line->amount : 0,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get Chart of Accounts
     *
     * @param bool $includeInactive
     * @return array
     */
    public static function getChartOfAccounts(bool $includeInactive = false): array
    {
        $query = Account::with(['accountType', 'parent'])
            ->orderBy('account_type_id')
            ->orderBy('code');

        if (!$includeInactive) {
            $query->active();
        }

        $accounts = $query->get();

        $result = [];
        foreach ($accounts as $account) {
            $result[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->accountType->name,
                'parent_code' => $account->parent ? $account->parent->code : null,
                'is_active' => $account->is_active,
                'current_balance' => $account->getCurrentBalance(),
            ];
        }

        return $result;
    }

    /**
     * Get Journal Entries Report
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $status
     * @return array
     */
    public static function getJournalEntriesReport(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $status = 'posted'
    ): array {
        $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $query = JournalEntry::with(['lines.account', 'creator', 'poster'])
            ->dateRange($startDate, $endDate);

        if ($status) {
            $query->where('status', $status);
        }

        $entries = $query->orderBy('entry_date')
            ->orderBy('entry_number')
            ->get();

        $result = [];
        foreach ($entries as $entry) {
            $result[] = [
                'entry_number' => $entry->entry_number,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'status' => $entry->status,
                'total_debits' => $entry->total_debits,
                'total_credits' => $entry->total_credits,
                'lines' => $entry->lines->map(function ($line) {
                    return [
                        'account_code' => $line->account->code,
                        'account_name' => $line->account->name,
                        'type' => $line->type,
                        'amount' => $line->amount,
                        'description' => $line->description,
                    ];
                })->toArray(),
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'entries' => $result,
        ];
    }

    /**
     * Get Cash Book Report
     * Shows all cash receipts and payments for a date range
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|int|Account|null $cashAccount Cash account code, ID, or Account instance (default: '1100')
     * @return array
     */
    // public static function getCashBook(
    //     ?string $startDate = null,
    //     ?string $endDate = null,
    //     $cashAccount = null
    // ): array {


    //     $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
    //     $endDate = $endDate ?? now()->toDateString();

    //     // Find cash account
    //     if ($cashAccount === null) {
    //         $cashAccount = Account::where('code', '1100')->first();
    //     } elseif (is_string($cashAccount)) {
    //         $cashAccount = Account::where('code', $cashAccount)->first();
    //     } elseif (is_int($cashAccount)) {
    //         $cashAccount = Account::find($cashAccount);
    //     }

    //     if (!$cashAccount instanceof Account) {
    //         throw new \Exception('Cash account not found. Please provide a valid cash account.');
    //     }

    //     // Calculate opening balance up to the day before start date
    //     $openingBalanceDate = \Carbon\Carbon::parse($startDate)->toDateString();

    //     $openingBalance = $cashAccount->calculateBalanceFromEntries($openingBalanceDate);

    //     // Get all journal entry lines for cash account in the date range
    //     $lines = JournalEntryLine::where('account_id', $cashAccount->id)

    //         ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
    //             $query->where('status', 'posted')
    //                 ->whereBetween('entry_date', [$startDate, $endDate]);
    //         })
    //         ->with(['journalEntry' => function ($query) {
    //             $query->select('id', 'entry_number', 'entry_date', 'description', 'reference_type', 'reference_id');
    //         }])
    //         ->with(['account' => function ($query) {
    //             $query->select('id', 'code', 'name');
    //         }])
    //         ->orderBy('journal_entry_id')
    //         ->orderBy('id')
    //         ->get();

    //     $receipts = [];
    //     $payments = [];
    //     $runningBalance = $openingBalance;
    //     $totalReceipts = 0;
    //     $totalPayments = 0;

    //     foreach ($lines as $line) {
    //         $entry = $line->journalEntry;


    //         // Get the contra account (the other side of the transaction)
    //         $contraAccount = JournalEntryLine::where('journal_entry_id', $entry->id)
    //             ->where('account_id', '!=', $cashAccount->id)
    //             ->with('account')
    //             ->first();

    //         $transaction = [
    //             'date' => $entry->entry_date,
    //             'entry_number' => $entry->entry_number,
    //             'description' => $line->description ?? $entry->description,
    //             'contra_account_code' => $contraAccount ? $contraAccount->account->code : null,
    //             'contra_account_name' => $contraAccount ? $contraAccount->account->name : null,
    //             'reference_type' => $entry->reference_type,
    //             'reference_id' => $entry->reference_id,
    //         ];

    //         if ($line->isDebit()) {

    //             // Cash receipt (debit to cash)
    //             $transaction['amount'] = $line->amount;
    //             if ($entry->reference_type != 'App\\Models\\OpeningBalance') {
    //                 $runningBalance += $line->amount;
    //                 $totalReceipts += $line->amount;



    //                 $receipts[] = $transaction;
    //             }
    //         } else {
    //             // Cash payment (credit to cash)
    //             $transaction['amount'] = $line->amount;
    //             $runningBalance -= $line->amount;
    //             $totalPayments += $line->amount;
    //             $payments[] = $transaction;
    //         }
    //     }

    //     return [
    //         'cash_account' => [
    //             'id' => $cashAccount->id,
    //             'code' => $cashAccount->code,
    //             'name' => $cashAccount->name,
    //         ],
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         'opening_balance' => $openingBalance,
    //         'receipts' => $receipts,
    //         'payments' => $payments,
    //         'total_receipts' => $totalReceipts,
    //         'total_payments' => $totalPayments,
    //         'closing_balance' => $runningBalance,
    //     ];
    // }

    public static function getCashBook(
        ?string $startDate = null,
        ?string $endDate = null,
        $cashAccount = null
    ): array {

        $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        // 🔹 Find cash account
        if ($cashAccount === null) {
            $cashAccount = Account::where('code', '1100')->first();
        } elseif (is_string($cashAccount)) {
            $cashAccount = Account::where('code', $cashAccount)->first();
        } elseif (is_int($cashAccount)) {
            $cashAccount = Account::find($cashAccount);
        }

        if (!$cashAccount instanceof Account) {
            throw new \Exception('Cash account not found.');
        }

        // ✅ FIX: Opening balance till PREVIOUS DAY
        $openingBalanceDate = Carbon::parse($startDate)->subDay()->toDateString();

        $openingBalance = $cashAccount->calculateBalanceFromEntries1($openingBalanceDate);

        // 🔹 Get transactions
        $lines = JournalEntryLine::where('account_id', $cashAccount->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
            })
            ->with(['journalEntry' => function ($query) {
                $query->select('id', 'entry_number', 'entry_date', 'description', 'reference_type', 'reference_id');
            }])
            ->with(['account' => function ($query) {
                $query->select('id', 'code', 'name');
            }])
            ->orderBy('journal_entry_id')
            ->orderBy('id')
            ->get();

        $receipts = [];
        $payments = [];
        $runningBalance = $openingBalance;
        $totalReceipts = 0;
        $totalPayments = 0;

        foreach ($lines as $line) {

            $entry = $line->journalEntry;

            // 🔹 Get contra account
            $contraAccount = JournalEntryLine::where('journal_entry_id', $entry->id)
                ->where('account_id', '!=', $cashAccount->id)
                ->with('account')
                ->first();

            $transaction = [
                'date' => $entry->entry_date,
                'entry_number' => $entry->entry_number,
                'description' => $line->description ?? $entry->description,
                'contra_account_code' => $contraAccount?->account?->code,
                'contra_account_name' => $contraAccount?->account?->name,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'amount' => $line->amount,
            ];

            if ($line->isDebit()) {

                // ✅ Receipt
                $runningBalance += $line->amount;
                $totalReceipts += $line->amount;

                $transaction['balance'] = $runningBalance;
                $receipts[] = $transaction;
            } else {

                // ✅ Payment
                $runningBalance -= $line->amount;
                $totalPayments += $line->amount;

                $transaction['balance'] = $runningBalance;
                $payments[] = $transaction;
            }
        }

        return [
            'cash_account' => [
                'id' => $cashAccount->id,
                'code' => $cashAccount->code,
                'name' => $cashAccount->name,
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'receipts' => $receipts,
            'payments' => $payments,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'closing_balance' => $runningBalance,
        ];
    }
    /**
     * Get Day Book Report
     * Shows all transactions for a specific day
     *
     * @param string|null $date
     * @return array
     */
    public static function getDayBook(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        // Get all posted journal entries for the date
        $entries = JournalEntry::with(['lines.account', 'creator', 'poster'])
            ->where('status', 'posted')
            ->where('entry_date', $date)
            ->orderBy('entry_number')
            ->get();

        $transactions = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($entries as $entry) {
            $entryDebits = 0;
            $entryCredits = 0;
            $lines = [];

            foreach ($entry->lines as $line) {
                if ($line->isDebit()) {
                    $entryDebits += $line->amount;
                    $totalDebits += $line->amount;
                } else {
                    $entryCredits += $line->amount;
                    $totalCredits += $line->amount;
                }

                $lines[] = [
                    'account_code' => $line->account->code,
                    'account_name' => $line->account->name,
                    'type' => $line->type,
                    'amount' => $line->amount,
                    'description' => $line->description,
                ];
            }

            $transactions[] = [
                'entry_number' => $entry->entry_number,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'notes' => $entry->notes,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'created_by' => $entry->creator ? $entry->creator->name : null,
                'posted_by' => $entry->poster ? $entry->poster->name : null,
                'posted_at' => $entry->posted_at,
                'lines' => $lines,
                'total_debits' => $entryDebits,
                'total_credits' => $entryCredits,
            ];
        }

        return [
            'date' => $date,
            'transactions' => $transactions,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            'total_entries' => count($transactions),
        ];
    }

    /**
     * Get Trial Balance for a specific financial year
     *
     * @param int|FinancialYear|null $financialYear
     * @param string|null $date Optional date within the financial year
     * @return array
     */
    public static function getTrialBalanceForFinancialYear($financialYear, ?string $date = null): array
    {
        if (!config('accounting.enable_financial_year')) {
            throw new \Exception('Financial year management is disabled. Enable it in config/accounting.php');
        }

        $fy = self::resolveFinancialYear($financialYear);

        if (!$fy) {
            throw new \Exception('Financial year not found.');
        }

        $date = $date ?? $fy->end_date->toDateString();

        // Ensure date is within financial year
        if (!$fy->containsDate($date)) {
            $date = $fy->end_date->toDateString();
        }

        return self::getTrialBalance($date);
    }

    /**
     * Get Balance Sheet for a specific financial year
     *
     * @param int|FinancialYear|null $financialYear
     * @param string|null $date Optional date within the financial year
     * @return array
     */
    public static function getBalanceSheetForFinancialYear($financialYear, ?string $date = null): array
    {
        if (!config('accounting.enable_financial_year')) {
            throw new \Exception('Financial year management is disabled. Enable it in config/accounting.php');
        }

        $fy = self::resolveFinancialYear($financialYear);

        if (!$fy) {
            throw new \Exception('Financial year not found.');
        }

        $date = $date ?? $fy->end_date->toDateString();

        // Ensure date is within financial year
        if (!$fy->containsDate($date)) {
            $date = $fy->end_date->toDateString();
        }

        return self::getBalanceSheet($date);
    }

    /**
     * Get Income Statement for a specific financial year
     *
     * @param int|FinancialYear|null $financialYear
     * @return array
     */
    public static function getIncomeStatementForFinancialYear($financialYear): array
    {
        if (!config('accounting.enable_financial_year')) {
            throw new \Exception('Financial year management is disabled. Enable it in config/accounting.php');
        }

        $fy = self::resolveFinancialYear($financialYear);

        if (!$fy) {
            throw new \Exception('Financial year not found.');
        }

        return self::getIncomeStatement(
            $fy->start_date->toDateString(),
            $fy->end_date->toDateString(),
            $fy
        );
    }
}
