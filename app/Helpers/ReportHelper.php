<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountType;
use App\Models\CompanyOpeningBalance;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\VehicleLoadStock;
use App\Models\VehicleLoadStockItem;
use App\Models\VoucherEntry;
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
    // public static function getTrialBalance(
    //     ?string $startDate = null,
    //     ?string $endDate = null,
    //     ?int $financialYearId = null
    // ): array {

    //     if ($financialYearId) {
    //         $financialYear = FinancialYear::findOrFail($financialYearId);
    //     } else {
    //         $financialYear = FinancialYear::where('is_active', true)
    //             ->where('is_closed', false)
    //             ->first();
    //     }

    //     if (!$financialYear) {
    //         return [
    //             'message' => 'Financial year not found.',
    //         ];
    //     }

    //     $financialYearId = $financialYear->id;
    //     // Use financial year dates if dates are not supplied
    //     $startDate = $startDate ?: $financialYear->start_date->toDateString();
    //     $endDate   = $endDate ?: $financialYear->end_date->toDateString();

    //     // Validate date range
    //     if (
    //         $startDate < $financialYear->start_date->toDateString() ||
    //         $endDate > $financialYear->end_date->toDateString()
    //     ) {
    //         return [
    //             'message' => 'Selected dates are outside the selected financial year.',
    //         ];
    //     }

    //     $accounts = Account::with('accountType')
    //         // ->where('code', '!=', 'A1001')
    //         ->active()
    //         ->orderBy('code');


    //     if (FinancialYear::count() > 2) {
    //         $accounts = $accounts->where('code', '!=', 'A1001');
    //     }
    //     $accounts = $accounts->get();
    //     $trialBalance = [];
    //     $totalDebits = 0;
    //     $totalCredits = 0;

    //     foreach ($accounts as $account) {

    //         $balance = $account->getCurrentBalance(
    //             $endDate,
    //             $financialYearId
    //         );


    //         $isDebitNormal = $account->accountType->isDebitNormal();

    //         $debitAmount = 0;
    //         $creditAmount = 0;

    //         if ($balance >= 0) {

    //             if ($isDebitNormal) {
    //                 $debitAmount = abs($balance);
    //             } else {
    //                 $creditAmount = abs($balance);
    //             }
    //         } else {

    //             if ($isDebitNormal) {
    //                 $creditAmount = abs($balance);
    //             } else {
    //                 $debitAmount = abs($balance);
    //             }
    //         }

    //         if ($debitAmount != 0 || $creditAmount != 0) {

    //             $trialBalance[] = [
    //                 'account_id'   => $account->id,
    //                 'account_code' => $account->code,
    //                 'account_name' => $account->name,
    //                 'account_type' => $account->accountType->name,
    //                 'debit'        => $debitAmount,
    //                 'credit'       => $creditAmount,
    //             ];

    //             $totalDebits += $debitAmount;
    //             $totalCredits += $creditAmount;
    //         }
    //     }


    //     return [
    //         'financial_year_id' => $financialYearId,
    //         'start_date'        => $startDate,
    //         'end_date'          => $endDate,
    //         'accounts'          => $trialBalance,
    //         'total_debits'      => $totalDebits,
    //         'total_credits'     => $totalCredits,
    //         'is_balanced'       => round($totalDebits, 2) == round($totalCredits, 2),
    //     ];
    // }


    public static function getTrialBalance(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $financialYearId = null
    ): array {

        /*
    |--------------------------------------------------------------------------
    | Determine Date Range / Financial Year
    |--------------------------------------------------------------------------
    */

        $financialYear = null;

        if ($financialYearId) {

            // Financial year was explicitly selected
            $financialYear = FinancialYear::find($financialYearId);

            if (!$financialYear) {
                return [
                    'message' => 'Financial year not found.',
                ];
            }

            $financialYearId = $financialYear->id;

            // If dates are not supplied, use financial year dates
            $startDate = $startDate ?: $financialYear->start_date->toDateString();
            $endDate   = $endDate ?: $financialYear->end_date->toDateString();

            // Validate dates against selected financial year
            if (
                $startDate < $financialYear->start_date->toDateString() ||
                $endDate > $financialYear->end_date->toDateString()
            ) {
                return [
                    'message' => 'Selected dates are outside the selected financial year.',
                ];
            }
        } else {

            /*
        |--------------------------------------------------------------------------
        | No Financial Year ID
        |--------------------------------------------------------------------------
        |
        | In this case, use the supplied dates directly.
        |
        */

            if (!$startDate || !$endDate) {
                return [
                    'message' => 'Start date and end date are required when financial year is not specified.',
                ];
            }

            // Normalize dates
            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate   = Carbon::parse($endDate)->toDateString();
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Date Range
    |--------------------------------------------------------------------------
    */

        if ($startDate > $endDate) {
            return [
                'message' => 'Start date cannot be greater than end date.',
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | Get Accounts
    |--------------------------------------------------------------------------
    */

        $accounts = Account::with('accountType')
            ->active()
            ->orderBy('code');

        /*
    |--------------------------------------------------------------------------
    | Opening Stock Account
    |--------------------------------------------------------------------------
    |
    | After multiple financial years you were excluding A1001.
    |
    */

        if (FinancialYear::count() > 2) {
            $accounts->where('code', '!=', 'A1001');
        }

        $accounts = $accounts->get();


        /*
    |--------------------------------------------------------------------------
    | Trial Balance
    |--------------------------------------------------------------------------
    */

        $trialBalance = [];

        $totalDebits = 0;
        $totalCredits = 0;


        foreach ($accounts as $account) {

            /*
        |--------------------------------------------------------------------------
        | Get Account Balance
        |--------------------------------------------------------------------------
        */

            if ($financialYearId) {



                $balance = $account->getCurrentBalance(
                    $endDate,
                    $financialYearId
                );
            } else {



                $balance = $account->calculateBalanceForDateRange(
                    $startDate,
                    $endDate
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Convert Balance to Debit / Credit
        |--------------------------------------------------------------------------
        */

            $isDebitNormal = $account->accountType->isDebitNormal();

            $debitAmount = 0;
            $creditAmount = 0;

            if ($balance >= 0) {

                if ($isDebitNormal) {

                    $debitAmount = abs($balance);
                } else {

                    $creditAmount = abs($balance);
                }
            } else {

                if ($isDebitNormal) {

                    $creditAmount = abs($balance);
                } else {

                    $debitAmount = abs($balance);
                }
            }


            /*
        |--------------------------------------------------------------------------
        | Add Account to Trial Balance
        |--------------------------------------------------------------------------
        */

            if ($debitAmount != 0 || $creditAmount != 0) {

                $trialBalance[] = [
                    'account_id'   => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->accountType->name,
                    'debit'        => round($debitAmount, 2),
                    'credit'       => round($creditAmount, 2),
                ];

                $totalDebits += $debitAmount;
                $totalCredits += $creditAmount;
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Return Result
    |--------------------------------------------------------------------------
    */

        return [
            'financial_year_id' => $financialYearId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,

            'accounts' => $trialBalance,

            'total_debits' => round($totalDebits, 2),

            'total_credits' => round($totalCredits, 2),

            'difference' => round(
                $totalDebits - $totalCredits,
                2
            ),

            'is_balanced' =>
            round($totalDebits, 2) ==
                round($totalCredits, 2),
        ];
    }
    /**
     * Get Balance Sheet
     *
     * @param string|null $date
     * @return array
     */
    // public static function getBalanceSheet(?string $endDate = null, ?string $startDate = null, ?int $financialYearId = null): array
    // {
    //     if ($financialYearId) {
    //         $financialYear = FinancialYear::findOrFail($financialYearId);
    //     } else {
    //         $financialYear = FinancialYear::where('is_active', true)
    //             ->where('is_closed', false)
    //             ->first();
    //     }

    //     if (!$financialYear) {
    //         return [
    //             'message' => 'Financial year not found.',
    //         ];
    //     }

    //     $financialYearId = $financialYear->id;

    //     $startDate = $startDate ?: $financialYear->start_date->toDateString();
    //     $endDate   = $endDate ?: $financialYear->end_date->toDateString();



    //     // ACCOUNTS
    //     $assets = self::getAccountsByType('ASSET', $endDate, $startDate, $financialYearId);
    //     $liabilities = self::getAccountsByType('LIABILITY', $endDate, $startDate, $financialYearId);
    //     $equity = self::getAccountsByType('EQUITY', $endDate, $startDate, $financialYearId);

    //     $totalAssets = array_sum(array_column($assets, 'balance'));
    //     $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
    //     $totalEquity = array_sum(array_column($equity, 'balance'));
    //     //  STOCK 

    //     // $previousFinancialYear = FinancialYear::where('end_date', '<', $financialYear->start_date)
    //     //     ->latest('end_date')
    //     //     ->first();

    //     // $closingStock = $previousFinancialYear?->closing_stock_value ?? 0;


    //     // $closingStock = self::getStockValue('<', Carbon::parse($endDate)->addDay());
    //     $closingStock = self::getStockValue('<=', $endDate);

    //     // $closingStock += $openingStockLedgerBalance;
    //     //  Opening stock belongs to CAPITAL


    //     // $totalEquity += $openingStock;


    //     // ✅ PROFIT (ONLY ONCE)
    //     $netIncome = self::getIncomeStatement($startDate, $endDate, $financialYearId)['net_income'];



    //     // 👉 Add profit to equity (ONLY once)
    //     // $totalEquity -= $stockDiffInPrev;
    //     // FINAL TOTALS
    //     $totalAssetsWithStock = $totalAssets + $closingStock;
    //     // $totalAssetsWithStock = $totalAssets;
    //     // $totalLiabilitiesEquity = $totalLiabilities + $totalEquity + $netIncome;

    //     if ($financialYear->is_closed) {

    //         $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;
    //     } else {

    //         $totalLiabilitiesEquity = $totalLiabilities + $totalEquity + $netIncome;
    //     }
    //     return [
    //         'startDate' => $startDate,
    //         'date' => $endDate,

    //         'equity' => [
    //             'accounts' => $equity,
    //             'profit' => $netIncome,
    //             'total' => $totalEquity,
    //         ],
    //         'assets' => [
    //             'accounts' => $assets,
    //             'total' => $totalAssets,
    //         ],

    //         'closingStock' => $closingStock,
    //         // 'openingStock' => $openingStock,


    //         'liabilities' => [
    //             'accounts' => $liabilities,
    //             'total' => $totalLiabilities,
    //         ],



    //         'total_assets_and_stock' => $totalAssetsWithStock,
    //         'total_liabilities_and_equity' => $totalLiabilitiesEquity,

    //         'is_balanced' => abs($totalAssetsWithStock - $totalLiabilitiesEquity) < 0.01,
    //     ];
    // }


    // public static function getBalanceSheet(
    //     ?string $endDate = null,
    //     ?string $startDate = null,
    //     ?int $financialYearId = null
    // ): array {

    //     /*
    // |--------------------------------------------------------------------------
    // | Determine Financial Year / Date Range
    // |--------------------------------------------------------------------------
    // */

    //     $financialYear = null;

    //     if ($financialYearId) {

    //         // Financial year explicitly selected
    //         $financialYear = FinancialYear::find($financialYearId);

    //         if (!$financialYear) {
    //             return [
    //                 'message' => 'Financial year not found.',
    //             ];
    //         }

    //         $financialYearId = $financialYear->id;

    //         // Use financial year dates if dates are not supplied
    //         $startDate = $startDate
    //             ?: $financialYear->start_date->toDateString();

    //         $endDate = $endDate
    //             ?: $financialYear->end_date->toDateString();

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Validate dates against selected financial year
    //     |--------------------------------------------------------------------------
    //     */

    //         if (
    //             $startDate < $financialYear->start_date->toDateString() ||
    //             $endDate > $financialYear->end_date->toDateString()
    //         ) {
    //             return [
    //                 'message' => 'Selected dates are outside the selected financial year.',
    //             ];
    //         }
    //     } else {

    //         /*
    //     |--------------------------------------------------------------------------
    //     | No Financial Year ID
    //     |--------------------------------------------------------------------------
    //     |
    //     | Use the supplied dates directly.
    //     |
    //     */

    //         if (!$startDate || !$endDate) {

    //             return [
    //                 'message' => 'Start date and end date are required when financial year is not specified.',
    //             ];
    //         }

    //         $startDate = Carbon::parse($startDate)->toDateString();
    //         $endDate   = Carbon::parse($endDate)->toDateString();

    //         // Do NOT use active financial year
    //         $financialYear = null;
    //     }


    //     /*
    // |--------------------------------------------------------------------------
    // | Validate Date Range
    // |--------------------------------------------------------------------------
    // */

    //     if ($startDate > $endDate) {

    //         return [
    //             'message' => 'Start date cannot be greater than end date.',
    //         ];
    //     }


    //     /*
    // |--------------------------------------------------------------------------
    // | ACCOUNTS
    // |--------------------------------------------------------------------------
    // */

    //     $assets = self::getAccountsByType(
    //         'ASSET',
    //         $endDate,
    //         $startDate,
    //         $financialYearId
    //     );

    //     $liabilities = self::getAccountsByType(
    //         'LIABILITY',
    //         $endDate,
    //         $startDate,
    //         $financialYearId
    //     );

    //     $equity = self::getAccountsByType(
    //         'EQUITY',
    //         $endDate,
    //         $startDate,
    //         $financialYearId
    //     );


    //     /*
    // |--------------------------------------------------------------------------
    // | Account Totals
    // |--------------------------------------------------------------------------
    // */

    //     $totalAssets = array_sum(
    //         array_column($assets, 'balance')
    //     );

    //     $totalLiabilities = array_sum(
    //         array_column($liabilities, 'balance')
    //     );

    //     $totalEquity = array_sum(
    //         array_column($equity, 'balance')
    //     );


    //     /*
    // |--------------------------------------------------------------------------
    // | Closing Stock
    // |--------------------------------------------------------------------------
    // */

    //     $closingStock = self::getStockValue(
    //         '<=',
    //         $endDate
    //     );


    //     /*
    // |--------------------------------------------------------------------------
    // | Profit / Loss
    // |--------------------------------------------------------------------------
    // */

    //     if ($financialYearId) {

    //         $incomeStatement = self::getIncomeStatement(
    //             $startDate,
    //             $endDate,
    //             $financialYearId
    //         );
    //     } else {

    //         /*
    //     |--------------------------------------------------------------------------
    //     | No Financial Year ID
    //     |--------------------------------------------------------------------------
    //     |
    //     | Income statement must also work only with the
    //     | supplied date range.
    //     |
    //     */

    //         $incomeStatement = self::getIncomeStatement(
    //             $startDate,
    //             $endDate,
    //             null
    //         );
    //     }

    //     $netIncome = $incomeStatement['net_income'] ?? 0;


    //     /*
    // |--------------------------------------------------------------------------
    // | Final Totals
    // |--------------------------------------------------------------------------
    // */

    //     $totalAssetsWithStock =
    //         $totalAssets + $closingStock;


    //     /*
    // |--------------------------------------------------------------------------
    // | Profit Handling
    // |--------------------------------------------------------------------------
    // |
    // | If a financial year is selected and it is closed,
    // | profit should already have been transferred/carried
    // | forward according to your closing process.
    // |
    // */

    //     if ($financialYear && $financialYear->is_closed) {

    //         $totalLiabilitiesEquity =
    //             $totalLiabilities + $totalEquity;
    //     } else {

    //         $totalLiabilitiesEquity =
    //             $totalLiabilities +
    //             $totalEquity +
    //             $netIncome;
    //     }


    //     /*
    // |--------------------------------------------------------------------------
    // | Return
    // |--------------------------------------------------------------------------
    // */

    //     return [

    //         'financial_year_id' => $financialYearId,

    //         'startDate' => $startDate,

    //         'date' => $endDate,

    //         'equity' => [
    //             'accounts' => $equity,
    //             'profit' => $netIncome,
    //             'total' => $totalEquity,
    //         ],

    //         'assets' => [
    //             'accounts' => $assets,
    //             'total' => $totalAssets,
    //         ],

    //         'closingStock' => $closingStock,

    //         'liabilities' => [
    //             'accounts' => $liabilities,
    //             'total' => $totalLiabilities,
    //         ],

    //         'total_assets_and_stock' =>
    //         $totalAssetsWithStock,

    //         'total_liabilities_and_equity' =>
    //         $totalLiabilitiesEquity,

    //         'difference' => round(
    //             $totalAssetsWithStock -
    //                 $totalLiabilitiesEquity,
    //             2
    //         ),

    //         'is_balanced' =>
    //         abs(
    //             $totalAssetsWithStock -
    //                 $totalLiabilitiesEquity
    //         ) < 0.01,
    //     ];
    // }

    public static function getBalanceSheet(
        ?string $endDate = null,
        ?string $startDate = null,
        ?int $financialYearId = null
    ): array {

        /*
    |--------------------------------------------------------------------------
    | Determine Financial Year / Date Range
    |--------------------------------------------------------------------------
    */

        $financialYear = null;

        if ($financialYearId) {

            $financialYear = FinancialYear::find($financialYearId);

            if (!$financialYear) {
                return [
                    'message' => 'Financial year not found.',
                ];
            }

            $financialYearId = $financialYear->id;

            $startDate = $startDate
                ?: $financialYear->start_date->toDateString();

            $endDate = $endDate
                ?: $financialYear->end_date->toDateString();

            if (
                $startDate < $financialYear->start_date->toDateString() ||
                $endDate > $financialYear->end_date->toDateString()
            ) {
                return [
                    'message' =>
                    'Selected dates are outside the selected financial year.',
                ];
            }
        } else {

            if (!$startDate || !$endDate) {
                return [
                    'message' =>
                    'Start date and end date are required when financial year is not specified.',
                ];
            }

            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate = Carbon::parse($endDate)->toDateString();
        }

        /*
    |--------------------------------------------------------------------------
    | Validate Date
    |--------------------------------------------------------------------------
    */

        if ($startDate > $endDate) {
            return [
                'message' =>
                'Start date cannot be greater than end date.',
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Accounts
    |--------------------------------------------------------------------------
    */

        $assets = self::getAccountsByType(
            'ASSET',
            $endDate,
            $startDate,
            $financialYearId
        );

        $liabilities = self::getAccountsByType(
            'LIABILITY',
            $endDate,
            $startDate,
            $financialYearId
        );

        $equity = self::getAccountsByType(
            'EQUITY',
            $endDate,
            $startDate,
            $financialYearId
        );

        /*
    |--------------------------------------------------------------------------
    | Account Totals
    |--------------------------------------------------------------------------
    */

        $totalAssets = 0;
        foreach ($assets as $account) {
            $totalAssets += (float) $account['balance'];
        }

        $totalLiabilities = 0;
        foreach ($liabilities as $account) {
            $totalLiabilities += (float) $account['balance'];
        }

        $totalEquity = 0;
        foreach ($equity as $account) {
            $totalEquity += (float) $account['balance'];
        }

        /*
    |--------------------------------------------------------------------------
    | Closing Stock
    |--------------------------------------------------------------------------
    */

        $closingStock = (float) self::getStockValue(
            '<=',
            $endDate
        );

        /*
    |--------------------------------------------------------------------------
    | Income Statement
    |--------------------------------------------------------------------------
    */

        $currentFinYear = FinancialYear::where('is_active', true)->first();
        $incomeStatement = self::getIncomeStatement(
            $currentFinYear->start_date->toDateString(),
            $endDate,
            $financialYearId
        );

        $netIncome = (float) (
            $incomeStatement['net_income'] ?? 0
        );

        /*
    |--------------------------------------------------------------------------
    | Final Totals
    |--------------------------------------------------------------------------
    */

        $totalAssetsWithStock =
            $totalAssets + $closingStock;

        if (
            $financialYear &&
            $financialYear->is_closed
        ) {

            $totalLiabilitiesEquity =
                $totalLiabilities +
                $totalEquity;
        } else {

            $totalLiabilitiesEquity =
                $totalLiabilities +
                $totalEquity +
                $netIncome;
        }

        $difference = round(
            $totalAssetsWithStock -
                $totalLiabilitiesEquity,
            2
        );

        /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

        return [

            'financial_year_id' => $financialYearId,

            'startDate' => $startDate,

            'date' => $endDate,

            'equity' => [
                'accounts' => $equity,
                'profit' => round($netIncome, 2),
                'total' => round($totalEquity, 2),
            ],

            'assets' => [
                'accounts' => $assets,
                'total' => round($totalAssets, 2),
            ],

            'closingStock' => round($closingStock, 2),

            'liabilities' => [
                'accounts' => $liabilities,
                'total' => round($totalLiabilities, 2),
            ],

            'total_assets_and_stock' =>
            round($totalAssetsWithStock, 2),

            'total_liabilities_and_equity' =>
            round($totalLiabilitiesEquity, 2),

            'difference' => $difference,

            'is_balanced' =>
            abs($difference) < 0.01,
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


    public static function getIncomeStatement(?string $startDate = null, ?string $endDate = null, ?int $financialYearId = null): array
    {
        if ($financialYearId) {
            $financialYear = FinancialYear::findOrFail($financialYearId);
        } else {
            $financialYear = FinancialYear::where('is_active', true)
                ->where('is_closed', false)
                ->first();
        }

        if (!$financialYear) {
            return [
                'message' => 'Financial year not found.',
            ];
        }

        $financialYearId = $financialYear->id;

        $closingStock = self::getStockValue('<=', $endDate);

        $previousFinancialYear = FinancialYear::where('end_date', '<', $financialYear->start_date)

            ->count();




        $openingStockLedger = Account::where('code', 'A1001')->first();

        // $openingStockLedgerBalance = $openingStockLedger->getCurrentBalance($endDate, $financialYearId);

        if ($previousFinancialYear > 2) {

            $openingStock =
                $previousFinancialYear
                ->closing_stock_value;
        } else {

            $openingStockLedgerBalance = self::getStockValue('<', $startDate);
            // $openingStockLedgerBalance =
            //     $openingStockLedger
            //     ->getCurrentBalance(
            //         $endDate,
            //         $financialYearId
            //     );

            $openingStock =
                $openingStockLedgerBalance;
        }


        // ACCOUNTS
        $purchases = self::getAccountsByGroup('10000', $endDate, $startDate, $financialYearId);
        $purchaseAccount = Account::where('code', '10000')->first();

        $purchaseJournals = JournalEntryLine::with('journalEntry')->where('account_id', $purchaseAccount->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->get();

        $purchaseVouchers = VoucherEntry::with('voucher')->where('account_id', $purchaseAccount->id)
            ->whereHas('voucher', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('voucher_date', [$startDate, $endDate])
                    ->where('voucher_type', 'purchase');
            })
            ->get();
        $purchaseReturn = self::getAccountsByGroup('11000', $endDate, $startDate, $financialYearId);
        $purchaseReturnAccount = Account::where('code', '11000')->first();
        $purchaseReturnJournals = JournalEntryLine::with('journalEntry')->where('account_id', $purchaseReturnAccount->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->get();
        $purchaseReturnVouchers = VoucherEntry::with('voucher')->where('account_id', $purchaseReturnAccount->id)
            ->whereHas('voucher', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('voucher_date', [$startDate, $endDate])
                    ->where('voucher_type', 'purchase_return');
            })
            ->get();
        $directExpenses = self::getAccountsByGroup('8000', $endDate, $startDate, $financialYearId);
        $sales = self::getAccountsByGroup('6100', $endDate, $startDate, $financialYearId);
        $salesAccount = Account::where('code', '6100')->first();
        $salesJournals = JournalEntryLine::with('journalEntry')->where('account_id', $salesAccount->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->get();
        $salesVouchers = VoucherEntry::with('voucher')->where('account_id', $salesAccount->id)
            ->whereHas('voucher', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('voucher_date', [$startDate, $endDate])
                    ->where('voucher_type', 'sales');
            })->get();
        $salesReturn = self::getAccountsByGroup('6300', $endDate, $startDate, $financialYearId);
        $salesReturnAccount = Account::where('code', '6300')->first();
        $salesReturnJournals = JournalEntryLine::with('journalEntry')->where('account_id', $salesReturnAccount->id)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('status', 'posted');
            })
            ->get();
        $salesReturnVouchers = VoucherEntry::with('voucher')->where('account_id', $salesReturnAccount->id)
            ->whereHas('voucher', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('voucher_date', [$startDate, $endDate])
                    ->where('voucher_type', 'sales_return');
            })->get();
        $indirectIncome = self::getAccountsByGroup('7000', $endDate, $startDate, $financialYearId);
        $indirectExpenses = self::getAccountsByGroup('9000', $endDate, $startDate, $financialYearId);

        $totalPurchase = array_sum(array_column($purchases, 'balance'));
        $totalPurchaseReturn = array_sum(array_column($purchaseReturn, 'balance'));
        $totalDirectExpense = array_sum(array_column($directExpenses, 'balance'));
        $totalSales = array_sum(array_column($sales, 'balance'));
        $totalSalesReturn = array_sum(array_column($salesReturn, 'balance'));
        $totalIndirectIncome = array_sum(array_column($indirectIncome, 'balance'));
        $totalIndirectExpense = array_sum(array_column($indirectExpenses, 'balance'));


        // ✅ COGS
        $cogs = $openingStock + $totalPurchase + $totalPurchaseReturn + $totalDirectExpense - $closingStock;

        // ✅ PROFITS
        $grossProfit = ($totalSales + $totalSalesReturn) - $cogs;
        $netIncome = $grossProfit + $totalIndirectIncome - $totalIndirectExpense;

        $trading_total = $openingStock + $totalPurchase + $totalPurchaseReturn + $totalDirectExpense + $grossProfit;
        $trading_total_right = ($totalSales + $totalSalesReturn) + $closingStock;

        $totalSalesReturn = -$totalSalesReturn;
        $totalPurchaseReturn = -$totalPurchaseReturn;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,

            'openingStock' => $openingStock,
            'closingStock' => $closingStock,

            'sales' => ['accounts' => $sales, 'total' => $totalSales, 'salesJournals' => $salesJournals, 'salesVouchers' => $salesVouchers],
            'salesReturn' => ['accounts' => $salesReturn, 'total' => $totalSalesReturn, 'salesReturnJournals' => $salesReturnJournals, 'salesReturnVouchers' => $salesReturnVouchers],
            'purchases' => ['accounts' => $purchases, 'total' => $totalPurchase, 'purchaseJournals' => $purchaseJournals, 'purchaseVouchers' => $purchaseVouchers],
            'purchaseReturn' => ['accounts' => $purchaseReturn, 'total' => $totalPurchaseReturn, 'purchaseReturnJournals' => $purchaseReturnJournals, 'purchaseReturnVouchers' => $purchaseReturnVouchers],
            'directExpenses' => ['accounts' => $directExpenses, 'total' => $totalDirectExpense],
            'indirectIncome' => ['accounts' => $indirectIncome, 'total' => $totalIndirectIncome],
            'indirectExpenses' => ['accounts' => $indirectExpenses, 'total' => $totalIndirectExpense],

            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'trading_total' => $trading_total,
            'trading_total_right' => $trading_total_right,
            'net_income' => $netIncome,
        ];
    }

    // public static function getGeneralLedger(int $account, ?string $startDate = null, ?string $endDate = null, ?int $financialYearId = null): array
    // {
    //     if (is_int($account)) {
    //         $account = Account::findOrFail($account);
    //     }

    //     if ($financialYearId) {
    //         $financialYear = FinancialYear::findOrFail($financialYearId);
    //     } else {
    //         $financialYear = FinancialYear::where('is_active', true)
    //             ->where('is_closed', false)
    //             ->first();
    //     }

    //     if (!$financialYear) {
    //         return [
    //             'message' => 'Financial year not found.',
    //         ];
    //     }

    //     $financialYearId = $financialYear->id;
    //     // Calculate opening balance up to the day before start date
    //     $openingBalanceDate = \Carbon\Carbon::parse($startDate)->subDay()->toDateString();


    //     $openingBalance = $account->calculateBalanceFromEntries($openingBalanceDate, null);

    //     $lines = JournalEntryLine::where('account_id', $account->id)
    //         ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
    //             $query->where('status', 'posted')
    //                 ->whereBetween('entry_date', [$startDate, $endDate]);
    //         })
    //         ->with(['journalEntry' => function ($query) {
    //             $query->select('id', 'entry_number', 'entry_date', 'description');
    //         }])
    //         ->orderBy('journal_entry_id')
    //         ->orderBy('id')
    //         ->get();

    //     $runningBalance = $openingBalance;
    //     $transactions = [];

    //     foreach ($lines as $line) {
    //         if ($line->isDebit()) {
    //             $runningBalance += $line->amount;
    //         } else {
    //             $runningBalance -= $line->amount;
    //         }

    //         $transactions[] = [
    //             'date' => $line->journalEntry->entry_date,
    //             'entry_number' => $line->journalEntry->entry_number,
    //             'description' => $line->description ?? $line->journalEntry->description,
    //             'debit' => $line->isDebit() ? $line->amount : 0,
    //             'credit' => $line->isCredit() ? $line->amount : 0,
    //             'balance' => $runningBalance,
    //         ];
    //     }

    //     return [
    //         'account' => [
    //             'id' => $account->id,
    //             'code' => $account->code,
    //             'name' => $account->name,
    //         ],
    //         'start_date' => $startDate,
    //         'end_date' => $endDate,
    //         'opening_balance' => $openingBalance,
    //         'closing_balance' => $runningBalance,
    //         'transactions' => $transactions,
    //     ];
    // }


    public static function getGeneralLedger(
        int $account,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $financialYearId = null
    ): array {

        if (is_int($account)) {
            $account = Account::findOrFail($account);
        }

        /*
    |--------------------------------------------------------------------------
    | Financial Year
    |--------------------------------------------------------------------------
    */

        if ($financialYearId) {

            $financialYear = FinancialYear::findOrFail($financialYearId);
        } else {

            $financialYear = FinancialYear::where('is_active', true)
                ->where('is_closed', false)
                ->first();
        }

        if (!$financialYear) {
            return [
                'message' => 'Financial year not found.',
            ];
        }

        $financialYearId = $financialYear->id;

        /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    */

        $startDate = $startDate
            ? Carbon::parse($startDate)->toDateString()
            : $financialYear->start_date->toDateString();

        $endDate = $endDate
            ? Carbon::parse($endDate)->toDateString()
            : $financialYear->end_date->toDateString();

        /*
    |--------------------------------------------------------------------------
    | Determine Account Normal Balance
    |--------------------------------------------------------------------------
    */

        $isCreditNormal =
            $account->accountType &&
            $account->accountType->isCreditNormal();

        /*
    |--------------------------------------------------------------------------
    | Opening Balance
    |--------------------------------------------------------------------------
    */

        // $openingBalanceDate = Carbon::parse($startDate)
        //     ->subDay()
        //     ->toDateString();

        // $openingBalance = $account->calculateBalanceFromEntries(
        //     $openingBalanceDate,
        //     $financialYearId
        // );


        /*
|--------------------------------------------------------------------------
| Opening Balance
|--------------------------------------------------------------------------
*/

        // Get opening balance record for this account and financial year
        /*
|--------------------------------------------------------------------------
| Opening Balance
|--------------------------------------------------------------------------
*/

        $openingRecord = AccountBalance::where('account_id', $account->id)
            ->where('financial_year_id', $financialYearId)
            ->whereDate('balance_date', '<', $financialYear->start_date)
            ->first();

        if ($openingRecord) {
            $openingBalance = $isCreditNormal
                ? (float) $openingRecord->credit_balance - (float) $openingRecord->debit_balance
                : (float) $openingRecord->debit_balance - (float) $openingRecord->credit_balance;
        } else {
            $openingBalance = (float) ($account->opening_balance ?? 0);
        }

        /*
|--------------------------------------------------------------------------
| Transactions
|--------------------------------------------------------------------------
*/

        $lines = JournalEntryLine::where(
            'journal_entry_lines.account_id',
            $account->id
        )
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_entry_lines.journal_entry_id'
            )
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $startDate)
            ->whereDate('journal_entries.entry_date', '<=', $endDate)
            ->where('journal_entries.financial_year_id', $financialYearId)
            ->with('journalEntry')
            ->select('journal_entry_lines.*')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->get();
        /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */

        // $lines = JournalEntryLine::where(
        //     'account_id',
        //     $account->id
        // )
        //     ->whereHas('journalEntry', function ($query) use (
        //         $startDate,
        //         $endDate,
        //         $financialYearId
        //     ) {

        //         $query->where('status', 'posted')
        //             ->whereBetween(
        //                 'entry_date',
        //                 [$startDate, $endDate]
        //             )
        //             ->where(
        //                 'financial_year_id',
        //                 $financialYearId
        //             );
        //     })
        //     ->with([
        //         'journalEntry' => function ($query) {
        //             $query->select(
        //                 'id',
        //                 'entry_number',
        //                 'entry_date',
        //                 'description'
        //             );
        //         }
        //     ])
        //     ->orderBy('journal_entry_id')
        //     ->orderBy('id')
        //     ->get();

        // $lines = JournalEntryLine::where(
        //     'journal_entry_lines.account_id',
        //     $account->id
        // )
        //     ->join(
        //         'journal_entries',
        //         'journal_entries.id',
        //         '=',
        //         'journal_entry_lines.journal_entry_id'
        //     )
        //     ->where('journal_entries.status', 'posted')
        //     ->whereBetween(
        //         'journal_entries.entry_date',
        //         [$startDate, $endDate]
        //     )
        //     ->where(
        //         'journal_entries.financial_year_id',
        //         $financialYearId
        //     )
        //     ->with([
        //         'journalEntry' => function ($query) {
        //             $query->select(
        //                 'id',
        //                 'entry_number',
        //                 'entry_date',
        //                 'description'
        //             );
        //         }
        //     ])
        //     ->select('journal_entry_lines.*')
        //     ->orderBy('journal_entries.entry_date', 'asc')
        //     ->orderBy('journal_entries.id', 'asc')
        //     ->orderBy('journal_entry_lines.id', 'asc')
        //     ->get();

        /*
    |--------------------------------------------------------------------------
    | Running Balance
    |--------------------------------------------------------------------------
    */

        $runningBalance = (float) $openingBalance;

        $transactions = [];

        foreach ($lines as $line) {

            $amount = (float) $line->amount;

            $debit = $line->isDebit()
                ? $amount
                : 0;

            $credit = $line->isCredit()
                ? $amount
                : 0;

            /*
        |--------------------------------------------------------------------------
        | Debit Normal Account
        |--------------------------------------------------------------------------
        |
        | Debit  -> Increase
        | Credit -> Decrease
        |
        */

            if (!$isCreditNormal) {

                $runningBalance += $debit;
                $runningBalance -= $credit;
            }

            /*
        |--------------------------------------------------------------------------
        | Credit Normal Account
        |--------------------------------------------------------------------------
        |
        | Credit -> Increase
        | Debit  -> Decrease
        |
        */ else {

                $runningBalance += $credit;
                $runningBalance -= $debit;
            }

            $transactions[] = [
                'date' => $line->journalEntry->entry_date,

                'entry_number' =>
                $line->journalEntry->entry_number,

                'description' =>
                $line->description
                    ?? $line->journalEntry->description,

                'debit' => $debit,

                'credit' => $credit,

                'balance' => round($runningBalance, 2),
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

        return [

            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ],

            'start_date' => $startDate,

            'end_date' => $endDate,

            'opening_balance' =>
            round($openingBalance, 2),

            'closing_balance' =>
            round($runningBalance, 2),

            'transactions' => $transactions,
        ];
    }
    /**
     * Resolve financial year from ID, instance, or null
     *
     * @param int|FinancialYear|null $financialYear
     * @return FinancialYear|null
     */
    private static function getStockValue($operator, $date)
    {
        return DB::table('stock_originals')
            ->selectRaw("
            SUM(CASE WHEN movement_type='in' THEN quantity*cost_price ELSE 0 END) -
            SUM(CASE WHEN movement_type='out' THEN quantity*cost_price ELSE 0 END)
            as stock
        ")
            ->whereDate('date', $operator, $date)
            ->whereNull('deleted_at')

            ->value('stock') ?? 0;
    }
    private static function getStockValuePrev($previousYear)
    {

        return DB::table('stock_originals')
            ->selectRaw("
           SUM(CASE WHEN movement_type='in' THEN quantity*cost_price ELSE 0 END)-
            SUM(CASE WHEN movement_type='out' THEN quantity*cost_price ELSE 0 END)
            as stock
        ")
            ->whereDate('date', '>=', $previousYear->start_date)
            ->whereDate('date', '<=', $previousYear->end_date)
            ->whereNull('deleted_at')

            ->value('stock') ?? 0;
    }
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

    // protected static function getAccountsByType(string $typeCode, ?string $date = null, ?string $startDate = null, ?int $financialYearId = null): array
    // {
    //     $date = $date ?? now()->toDateString();
    //     $accountType = AccountType::where('code', $typeCode)->first();

    //     if (!$accountType) {
    //         return [];
    //     }

    //     $accounts = Account::where('account_type_id', $accountType->id)
    //         ->where('code', '!=', 'A1001')   // Exclude Opening Stock account
    //         ->active()
    //         // ->where('name','!=','Opening Stock')
    //         ->with([
    //             'journalEntryLines' => function ($query) use ($financialYearId) {
    //                 $query->when($financialYearId, function ($q) use ($financialYearId) {
    //                     $q->whereHas('journalEntry', function ($je) use ($financialYearId) {
    //                         $je->where('financial_year_id', $financialYearId);
    //                     });
    //                 });
    //             },
    //             'journalEntryLines.journalEntry',
    //             'journalEntryLines.journalEntry.vouchers'
    //         ])
    //         ->orderBy('code')
    //         ->get();


    //     $result = [];

    //     foreach ($accounts as $account) {


    //         $balance = $account->getCurrentBalance($date, $financialYearId);


    //         if ($balance != 0 || $account->opening_balance != 0) {
    //             $result[] = [
    //                 'id' => $account->id,
    //                 'code' => $account->code,
    //                 'name' => $account->name,
    //                 'balance' => $balance,
    //                 'parent_id' => $account->parent_id,
    //                 'journalEntryLines' => $account->journalEntryLines

    //             ];
    //         }
    //     }

    //     return $result;
    // }

    protected static function getAccountsByType(
        string $typeCode,
        ?string $date = null,
        ?string $startDate = null,
        ?int $financialYearId = null
    ): array {
        $date = $date ?? now()->toDateString();

        /*
    |--------------------------------------------------------------------------
    | Get Account Type
    |--------------------------------------------------------------------------
    */

        $accountType = AccountType::where('code', $typeCode)->first();

        if (!$accountType) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Get Accounts
    |--------------------------------------------------------------------------
    */

        $accounts = Account::query()
            ->select([
                'id',
                'code',
                'name',
                'parent_id',
                'account_type_id',
                'opening_balance',
                'opening_balance_date',
            ])
            ->where('account_type_id', $accountType->id)
            ->where('code', '!=', 'A1001')
            ->active()
            ->orderBy('code')
            ->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Account IDs
    |--------------------------------------------------------------------------
    */

        $accountIds = $accounts->pluck('id')->toArray();

        /*
    |--------------------------------------------------------------------------
    | Get Debit / Credit Totals in ONE query
    |--------------------------------------------------------------------------
    */

        $journalTotals = DB::table('journal_entry_lines as jel')
            ->join(
                'journal_entries as je',
                'je.id',
                '=',
                'jel.journal_entry_id'
            )
            ->whereIn('jel.account_id', $accountIds)
            ->where('je.status', 'posted')
            ->whereDate('je.entry_date', '<=', $date)

            // ->when($startDate, function ($query) use ($startDate) {
            //     $query->whereDate('je.entry_date', '>=', $startDate);
            // })
            // ->whereDate('je.entry_date', '<=', $date)
            ->when($financialYearId, function ($query) use ($financialYearId) {
                $query->where(
                    'je.financial_year_id',
                    $financialYearId
                );
            })

            ->select(
                'jel.account_id',

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'debit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS debit_total
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'credit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS credit_total
            ")
            )

            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        /*
    |--------------------------------------------------------------------------
    | Build Result
    |--------------------------------------------------------------------------
    */

        $result = [];

        foreach ($accounts as $account) {

            $totals = $journalTotals->get($account->id);

            $debitTotal = $totals
                ? (float) $totals->debit_total
                : 0;

            $creditTotal = $totals
                ? (float) $totals->credit_total
                : 0;

            /*
        |--------------------------------------------------------------------------
        | Calculate Balance
        |--------------------------------------------------------------------------
        */

            $balance = $debitTotal - $creditTotal;

            /*
        |--------------------------------------------------------------------------
        | Credit Normal Account
        |--------------------------------------------------------------------------
        */

            if ($accountType->isCreditNormal()) {
                $balance = $creditTotal - $debitTotal;
            }

            /*
        |--------------------------------------------------------------------------
        | Opening Balance
        |--------------------------------------------------------------------------
        */

            if (
                $account->opening_balance_date &&
                $account->opening_balance_date <= $date
            ) {
                $balance += (float) $account->opening_balance;
            }

            /*
        |--------------------------------------------------------------------------
        | Only return accounts having balance
        |--------------------------------------------------------------------------
        */

            if (
                abs($balance) > 0.0001 ||
                (float) $account->opening_balance != 0
            ) {

                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => round($balance, 2),
                    'parent_id' => $account->parent_id,
                ];
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Free memory
    |--------------------------------------------------------------------------
    */

        unset($accounts);
        unset($accountIds);
        unset($journalTotals);

        return $result;
    }

    protected static function getAccountsByGroup(
        string $typeCode,
        ?string $date = null,
        ?string $startDate = null,
        ?int $financialYearId = null
    ): array {

        $date = $date ?? now()->toDateString();

        /*
    |--------------------------------------------------------------------------
    | Get Account Group
    |--------------------------------------------------------------------------
    */

        $accountGroup = Account::where('code', $typeCode)->first();

        if (!$accountGroup) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Get Accounts
    |--------------------------------------------------------------------------
    */

        $accounts = Account::query()
            ->select([
                'id',
                'code',
                'name',
                'parent_id',
                'account_type_id',
                'opening_balance',
                'opening_balance_date',
            ])
            ->where(function ($query) use ($accountGroup, $typeCode) {

                $query->where('parent_id', $accountGroup->id)
                    ->orWhere('code', $typeCode);
            })
            ->active()
            ->orderBy('code')
            ->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Account IDs
    |--------------------------------------------------------------------------
    */

        $accountIds = $accounts->pluck('id')->toArray();

        /*
    |--------------------------------------------------------------------------
    | Get Period Debit / Credit Totals
    |--------------------------------------------------------------------------
    |
    | Instead of loading journalEntryLines + journalEntry + vouchers,
    | calculate everything in ONE database query.
    |
    */

        $journalTotals = DB::table('journal_entry_lines as jel')
            ->join(
                'journal_entries as je',
                'je.id',
                '=',
                'jel.journal_entry_id'
            )
            ->whereIn('jel.account_id', $accountIds)
            ->where('je.status', 'posted')
            ->whereDate('je.entry_date', '>=', $startDate)
            ->whereDate('je.entry_date', '<=', $date)

            ->when($financialYearId, function ($query) use ($financialYearId) {

                $query->where(
                    'je.financial_year_id',
                    $financialYearId
                );
            })

            ->select(
                'jel.account_id',

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'debit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS debit_total
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN jel.type = 'credit'
                        THEN jel.amount
                        ELSE 0
                    END
                ) AS credit_total
            ")
            )

            ->groupBy('jel.account_id')
            ->get()
            ->keyBy('account_id');

        /*
    |--------------------------------------------------------------------------
    | Account Type
    |--------------------------------------------------------------------------
    */

        $accountType = AccountType::find(
            $accounts->first()->account_type_id
        );

        /*
    |--------------------------------------------------------------------------
    | Build Result
    |--------------------------------------------------------------------------
    */

        $result = [];

        foreach ($accounts as $account) {

            $totals = $journalTotals->get($account->id);

            $debitTotal = $totals
                ? (float) $totals->debit_total
                : 0;

            $creditTotal = $totals
                ? (float) $totals->credit_total
                : 0;

            /*
        |--------------------------------------------------------------------------
        | Calculate Balance
        |--------------------------------------------------------------------------
        */

            $balance = $debitTotal - $creditTotal;

            /*
        |--------------------------------------------------------------------------
        | Credit Normal Account
        |--------------------------------------------------------------------------
        */

            if (
                $accountType &&
                $accountType->isCreditNormal()
            ) {
                $balance = $creditTotal - $debitTotal;
            }

            /*
        |--------------------------------------------------------------------------
        | Only accounts having activity/balance
        |--------------------------------------------------------------------------
        */

            if (
                abs($balance) > 0.0001 ||
                (float) $account->opening_balance != 0
            ) {

                $result[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => round($balance, 2),
                    'parent_id' => $account->parent_id,
                ];
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Free memory
    |--------------------------------------------------------------------------
    */

        unset($accounts);
        unset($accountIds);
        unset($journalTotals);

        return $result;
    }
    // protected static function getAccountsByGroup(string $typeCode, ?string $date = null, ?string $startDate = null, ?int $financialYearId = null): array
    // {
    //     $date = $date ?? now()->toDateString();
    //     $accountGroup = Account::where('code', $typeCode)->first();

    //     if (!$accountGroup) {
    //         return [];
    //     }


    //     // $accounts = Account::where('parent_id', $accountGroup->id)->orWhere('code', $typeCode)
    //     //     ->active()
    //     //     ->with('journalEntryLines', 'journalEntryLines.journalEntry', 'journalEntryLines.journalEntry.vouchers')
    //     //     ->orderBy('code')
    //     //     ->get();

    //     // $accounts = Account::where(function ($query) use ($accountGroup, $typeCode) {
    //     //     $query->where('parent_id', $accountGroup->id)
    //     //         ->orWhere('code', $typeCode);
    //     // })
    //     //     ->active()
    //     //     ->with([
    //     //         'journalEntryLines' => function ($query) use ($startDate, $date) {
    //     //             $query->whereHas('journalEntry', function ($q) use ($startDate, $date) {
    //     //                 $q->whereBetween('entry_date', [$startDate, $date]);
    //     //             });
    //     //         },
    //     //         'journalEntryLines.journalEntry',
    //     //         'journalEntryLines.journalEntry.vouchers',
    //     //     ])
    //     //     ->whereHas('journalEntryLines.journalEntry', function ($query) use ($startDate, $date) {
    //     //         $query->whereBetween('entry_date', [$startDate, $date])
    //     //         ->where('status','posted');
    //     //     })
    //     //     ->orderBy('code')
    //     //     ->get();

    //     $accounts = Account::where(function ($query) use ($accountGroup, $typeCode) {

    //         $query->where('parent_id', $accountGroup->id)
    //             ->orWhere('code', $typeCode);
    //     })
    //         ->active()
    //         ->with([
    //             'journalEntryLines' => function ($query) use ($startDate, $date) {

    //                 $query->whereHas('journalEntry', function ($q) use ($startDate, $date) {

    //                     $q->whereBetween('entry_date', [$startDate, $date])
    //                         ->where('status', 'posted');
    //                 });
    //             },

    //             'journalEntryLines.journalEntry',
    //             'journalEntryLines.journalEntry.vouchers',

    //         ])
    //         ->whereHas('journalEntryLines.journalEntry', function ($query) use ($startDate, $date) {

    //             $query->whereBetween('entry_date', [$startDate, $date])
    //                 ->where('status', 'posted');
    //         })
    //         ->orderBy('code')
    //         ->get();

    //     $result = [];

    //     foreach ($accounts as $account) {


    //         if ($startDate) {
    //             // For period-based (like income statement), calculate balance for the period
    //             $balance = self::getAccountBalanceForPeriod($account, $startDate, $date, $financialYearId);
    //         } else {
    //             // For point-in-time (like balance sheet), get current balance

    //             $balance = $account->getCurrentBalance($date, $financialYearId);
    //         }


    //         if ($balance != 0 || $account->opening_balance != 0) {
    //             $result[] = [
    //                 'id' => $account->id,
    //                 'code' => $account->code,
    //                 'name' => $account->name,
    //                 'balance' => $balance,
    //                 'journalEntryLines' => $account->journalEntryLines

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
    protected static function getAccountBalanceForPeriod(Account $account, string $startDate, string $endDate, int $financialYearId): float
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
    protected static function calculateRetainedEarnings(?string $date = null, ?int $financialYearId = null): float
    {
        $date = $date ?? now()->toDateString();
        $startOfYear = Carbon::parse($date)->startOfYear()->toDateString();

        // Get net income for the period
        $incomeStatement = self::getIncomeStatement($startOfYear, $date, $financialYearId);

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
     * @param string|int|Account|null $cashAccount Cash account code, ID, or Account instance (default: '1100100')
     * @return array
     */


    public static function getCashBook(
        ?string $startDate = null,
        ?string $endDate = null,
        $cashAccount = null
    ): array {

        $startDate = $startDate ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        // 🔹 Find cash account
        if ($cashAccount === null) {
            $cashAccount = Account::where('code', '1100100')->first();
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
    // public static function getDayBook(?string $startDate = null, ?string $endDate = null, ?int $financialYearId = null): array
    // {
    //     $startDate = $startDate ?? now()->toDateString();
    //     $endDate = $endDate ?? now()->toDateString();

    //     // Get all posted journal entries for the date
    //     $entries = JournalEntry::with(['lines.account', 'creator', 'poster'])
    //         ->where('status', 'posted')
    //         ->whereBetween('entry_date', [$startDate, $endDate])
    //         ->orderBy('entry_number')
    //         ->get();

    //     $transactions = [];
    //     $totalDebits = 0;
    //     $totalCredits = 0;

    //     foreach ($entries as $entry) {
    //         $entryDebits = 0;
    //         $entryCredits = 0;
    //         $lines = [];

    //         foreach ($entry->lines as $line) {
    //             if ($line->isDebit()) {
    //                 $entryDebits += $line->amount;
    //                 $totalDebits += $line->amount;
    //             } else {
    //                 $entryCredits += $line->amount;
    //                 $totalCredits += $line->amount;
    //             }

    //             $lines[] = [
    //                 'account_code' => $line->account->code,
    //                 'account_name' => $line->account->name,
    //                 'type' => $line->type,
    //                 'amount' => $line->amount,
    //                 'description' => $line->description,
    //             ];
    //         }

    //         $transactions[] = [
    //             'entry_number' => $entry->entry_number,
    //             'date' => $entry->entry_date,
    //             'description' => $entry->description,
    //             'notes' => $entry->notes,
    //             'reference_type' => $entry->reference_type,
    //             'reference_id' => $entry->reference_id,
    //             'created_by' => $entry->creator ? $entry->creator->name : null,
    //             'posted_by' => $entry->poster ? $entry->poster->name : null,
    //             'posted_at' => $entry->posted_at,
    //             'lines' => $lines,
    //             'total_debits' => $entryDebits,
    //             'total_credits' => $entryCredits,
    //         ];
    //     }

    //     return [
    //         'startDate' => $startDate,
    //         'endDate' => $endDate,
    //         'transactions' => $transactions,
    //         'total_debits' => $totalDebits,
    //         'total_credits' => $totalCredits,
    //         'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
    //         'total_entries' => count($transactions),
    //     ];
    // }

    public static function getDayBook(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $financialYearId = null
    ): array {

        /*
    |--------------------------------------------------------------------------
    | Determine Date Range
    |--------------------------------------------------------------------------
    */
        if ($financialYearId) {

            // Financial year selected
            $financialYear = FinancialYear::find($financialYearId);

            if (!$financialYear) {
                return [
                    'message' => 'Financial year not found.',
                ];
            }

            // When FY is selected, always use FY dates
            $startDate = Carbon::parse(
                $financialYear->start_date
            )->toDateString();

            $endDate = Carbon::parse(
                $financialYear->end_date
            )->toDateString();
        } else {

            // No FY selected - use passed dates
            $startDate = $startDate
                ? Carbon::parse($startDate)->toDateString()
                : now()->toDateString();

            $endDate = $endDate
                ? Carbon::parse($endDate)->toDateString()
                : now()->toDateString();
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Date Range
    |--------------------------------------------------------------------------
    */

        if ($startDate > $endDate) {
            return [
                'message' => 'Start date cannot be greater than end date.',
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | Get Journal Entries
    |--------------------------------------------------------------------------
    */


        $entries = JournalEntry::with(['lines.account', 'creator', 'poster'])
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_number')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Prepare Result
    |--------------------------------------------------------------------------
    */

        $transactions = [];

        $totalDebits = 0;
        $totalCredits = 0;


        foreach ($entries as $entry) {

            $entryDebits = 0;
            $entryCredits = 0;

            $lines = [];


            foreach ($entry->lines as $line) {

                $amount = (float) $line->amount;

                if ($line->isDebit()) {

                    $entryDebits += $amount;
                    $totalDebits += $amount;
                } else {

                    $entryCredits += $amount;
                    $totalCredits += $amount;
                }


                $lines[] = [

                    'account_code' => $line->account
                        ? $line->account->code
                        : null,

                    'account_name' => $line->account
                        ? $line->account->name
                        : null,

                    'type' => $line->type,

                    'amount' => $amount,

                    'description' => $line->description,
                ];
            }


            $transactions[] = [

                'entry_number' =>
                $entry->entry_number,

                'date' =>
                $entry->entry_date,

                'description' =>
                $entry->description,

                'notes' =>
                $entry->notes,

                'reference_type' =>
                $entry->reference_type,

                'reference_id' =>
                $entry->reference_id,

                'created_by' =>
                $entry->creator
                    ? $entry->creator->name
                    : null,

                'posted_by' =>
                $entry->poster
                    ? $entry->poster->name
                    : null,

                'posted_at' =>
                $entry->posted_at,

                'lines' =>
                $lines,

                'total_debits' =>
                $entryDebits,

                'total_credits' =>
                $entryCredits,
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

        return [

            'financial_year_id' =>
            $financialYearId,

            'startDate' =>
            $startDate,

            'endDate' =>
            $endDate,

            'transactions' =>
            $transactions,

            'total_debits' =>
            round($totalDebits, 2),

            'total_credits' =>
            round($totalCredits, 2),

            'is_balanced' =>
            abs(
                $totalDebits - $totalCredits
            ) < 0.01,

            'total_entries' =>
            count($transactions),
        ];
    }

    /**
     * Get Trial Balance for a specific financial year
     *
     * @param int|FinancialYear|null $financialYear
     * @param string|null $date Optional date within the financial year
     * @return array
     */


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
            $fy->id
        );
    }
}
