<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\OpeningBalance;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

class FinancialYearService
{
    protected JournalEntryService $journalEntryService;
    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }
    /**
     * Get the financial year for a given date
     *
     * @param string|null $date
     * @return FinancialYear|null
     */
    public function getFinancialYearForDate(?string $date = null): ?FinancialYear
    {
        if (!config('accounting.enable_financial_year')) {
            return null;
        }

        $date = $date ?? now()->toDateString();
        return FinancialYear::forDate($date);
    }

    /**
     * Get the active financial year
     *
     * @return FinancialYear|null
     */
    public function getActiveFinancialYear(): ?FinancialYear
    {
        if (!config('accounting.enable_financial_year')) {
            return null;
        }

        return FinancialYear::active();
    }

    /**
     * Get current financial year (active or for today's date)
     *
     * @return FinancialYear|null
     */
    public function getCurrentFinancialYear(): ?FinancialYear
    {
        if (!config('accounting.enable_financial_year')) {
            return null;
        }

        $active = $this->getActiveFinancialYear();
        if ($active) {
            return $active;
        }

        return $this->getFinancialYearForDate(now()->toDateString());
    }

    /**
     * Create a new financial year
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $name
     * @param string|null $code
     * @param bool $activate
     * @return FinancialYear
     */
    public function createFinancialYear(
        string $startDate,
        string $endDate,
        ?string $name = null,
        ?string $code = null,
        bool $activate = false
    ): FinancialYear {

        DB::beginTransaction();
        $prevFins = FinancialYear::get();

        // Generate name if not provided
        if (!$name) {
            $startYear = Carbon::parse($startDate)->format('Y');
            $endYear = Carbon::parse($endDate)->format('Y');
            $name = "FY {$startYear}-{$endYear}";
        }

        // Generate code if not provided
        if (!$code) {
            $startYear = Carbon::parse($startDate)->format('Y');
            $endYear = Carbon::parse($endDate)->format('y');
            $code = "FY{$startYear}-{$endYear}";
        }

        $financialYear = FinancialYear::create([
            'name' => $name,
            'code' => $code,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $activate,
            'is_closed' => 0,
        ]);
       
        $closingStock = self::getStockValue('<', $financialYear->start_date);


        if (FinancialYear::count() == 1) {

            $account = Account::where('code', 'A1001')->first();

            $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                'date' => $financialYear->start_date,
                // 'date' => $requested_date,
                'amount' => $closingStock,
                'reference_type' => 'App\\Models\\OpeningStock',
                'reference_id' => 0,
                'description' => 'opening stock entry',
                'account' => $account,
                'user_id' => 1,

            ]);

            Log::info('====================OPENING STOCK JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            $suppliersOpeningBalances = SupplierOpeningBalance::get();

            foreach ($suppliersOpeningBalances as $supplierBalance) {

                $supplier = Supplier::find($supplierBalance->supplier_id);
                $account = Account::find($supplier->account_id);

                // $account->opening_balance = $supplierBalance->amount;
                // $account->opening_balance_date = $financialYear->start_date;
                // $account->save();

                // $accountBalace = AccountBalance::create([
                //     'account_id' => $account->id,
                //     'financial_year_id' => 1,
                //     'balance_date' => "2025-08-31",
                //     'debit_balance' => 0,
                //     'credit_balance' => $supplierBalance->amount,
                //     'net_balance' => $supplierBalance->amount,
                // ]);

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => $financialYear->start_date,
                    // 'date' => Carbon::now(),
                    'amount' => $supplierBalance->amount,
                    'reference_type' => 'App\\Models\\SupplierOpeningBalance',
                    'reference_id' => $supplierBalance->id,
                    'description' => 'Supplier opening balance entry',
                    'account' => $account,
                    'user_id' => 1,

                ]);
            }

            $customersOpeningBalances = OpeningBalance::get();

            foreach ($customersOpeningBalances as $customerBalance) {
                $customer = Customer::find($customerBalance->customer_id);

                $account = Account::find($customer->account_id);

                // $account->opening_balance = $customerBalance->amount;
                // $account->opening_balance_date = $financialYear->start_date;
                // $account->save();

                // $accountBalace = AccountBalance::create([
                //     'account_id' => $account->id,
                //     'financial_year_id' => 1,
                //     'balance_date' => "2025-08-31",
                //     'debit_balance' => $customerBalance->amount,
                //     'credit_balance' => 0,
                //     'net_balance' => $customerBalance->amount,
                // ]);

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => $financialYear->start_date,
                    // 'date' => $requested_date,
                    'amount' => $customerBalance->amount,
                    'reference_type' => 'App\\Models\\CustomerOpeningBalance',
                    'reference_id' => $customerBalance->id,
                    'description' => 'Customer opening balance entry',
                    'account' => $account,
                    'user_id' => 1,
                ]);
            }
        }


        if ($activate) {
            $financialYear->activate();
        }

        DB::commit();
        return $financialYear;
    }
    public function financialYearUpdate(Request $request)
    {

        $financialYear = FinancialYear::where('id', $request['id'])->first();

        if (!$financialYear) {
            throw new \Exception('Financial year not found for the given start date.');
        }

        // Update fields
        $financialYear->name = $request['name'] ?? $financialYear->name;
        $financialYear->code = $request['code'] ?? $financialYear->code;
        $financialYear->end_date = $request['end_date'];
        $financialYear->is_active = $request['status'] ?? $financialYear->is_active;

        $financialYear->save();

        // if ($activate) {
        //     $financialYear->activate();
        // }

        return $financialYear;
    }



    public function financialYearClose($data)
    {
        try {

            DB::beginTransaction();

            $financialYear = FinancialYear::findOrFail($data->id);

            if ($financialYear->is_closed) {
                return errorResponse(
                    null,
                    'Financial year already closed.',
                    0,
                    200
                );
            }

            $closingDate = $financialYear->end_date->toDateString();

            //Calculate current year profit/loss

            $incomeStatement = \App\Helpers\ReportHelper::getIncomeStatement(
                $financialYear->start_date->toDateString(),
                $closingDate,
                $financialYear->id
            );

            $netIncome = $incomeStatement['net_income'];



            // Change this code to your Capital account code
            $capitalAccount = Account::where('code', '5100')->first();

            $openingStockAccount = Account::where('code', 'A1001')->first();
            if (!$capitalAccount) {
                throw new \Exception('Capital account not found.');
            }
            if (!$openingStockAccount) {
                throw new \Exception('Stock account not found.');
            }

            $capitalBalance = $capitalAccount->getCurrentBalance(
                $closingDate,
                $financialYear->id
            );

            $stockBalance = self::getStockValue('<=', $closingDate);
            //Transfer Profit/Loss to Capital


            $capitalAccount->update([
                'opening_balance' => $capitalBalance + $netIncome,
                'opening_balance_date' => Carbon::parse($closingDate)
                    ->addDay()
                    ->toDateString(),
            ]);
            $openingStockAccount->update([
                'opening_balance' => $stockBalance,
                'opening_balance_date' => Carbon::parse($closingDate)
                    ->addDay()
                    ->toDateString(),
            ]);

            //Carry forward balances

            $accounts = Account::with('accountType')->get();


            foreach ($accounts as $account) {

                // Skip Capital (already updated)
                if ($account->id == $capitalAccount->id) {
                    continue;
                }
                if (FinancialYear::count() == 2 && $account->code == 'A1001') {
                    Log::info('====================OPENING STOCK resetting ====================');
                    continue;
                }

                // Income & Expense accounts start from zero
                if (
                    $account->accountType->code == 'REVENUE' ||
                    $account->accountType->code == 'EXPENSE'
                ) {

                    $account->update([
                        'opening_balance' => 0,
                        'opening_balance_date' => Carbon::parse($closingDate)
                            ->addDay()
                            ->toDateString(),
                    ]);

                    continue;
                }

                // Carry forward Asset/Liability/Equity
                $closingBalance = $account->getCurrentBalance(
                    $closingDate,
                    $financialYear->id
                );

                $account->update([
                    'opening_balance' => $closingBalance,
                    'opening_balance_date' => Carbon::parse($closingDate)
                        ->addDay()
                        ->toDateString(),
                ]);
            }

            //Close Financial Year
            $closingStock = self::getStockValue('<=', $financialYear->end_date);
            $financialYear->update([
                'is_closed' => true,
                'is_active' => false,
                'closed_at' => now(),
                'closing_stock_value' => $closingStock
            ]);

            // Activate Next Financial Year


            $nextFinancialYear = FinancialYear::where(
                'start_date',
                Carbon::parse($closingDate)->addDay()->toDateString()
            )->first();


            if ($nextFinancialYear) {

                FinancialYear::where('is_active', true)->update(['is_active' => false]);

                $nextFinancialYear->update([
                    'is_active' => true
                ]);
            } else {
                return errorResponse(
                    null,
                    'Please create the next financial year before closing the current financial year.',
                    100,
                    200
                );
            }


            DB::commit();

            return successResponse(
                null,
                'Financial year closed successfully.',
                0,
                200
            );
        } catch (\Exception $e) {

            DB::rollBack();

            return errorResponse(
                null,
                $e->getMessage(),
                100,
                200
            );
        }
    }
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
    private static function getStockValuePrev($firstFinancialYear)
    {

        return DB::table('stock_originals')
            ->selectRaw("
           SUM(CASE WHEN movement_type='in' THEN quantity*cost_price ELSE 0 END)-
            SUM(CASE WHEN movement_type='out' THEN quantity*cost_price ELSE 0 END)
            as stock
        ")
            ->whereDate('date', '>=', $firstFinancialYear->start_date)
            ->whereDate('date', '<=', $firstFinancialYear->end_date)
            ->whereNull('deleted_at')

            ->value('stock') ?? 0;
    }
    /**
     * Create a financial year based on calendar year
     *
     * @param int $year
     * @param bool $activate
     * @return FinancialYear
     */
    public function createCalendarYearFinancialYear(int $year, bool $activate = false): FinancialYear
    {
        $startDate = Carbon::create($year, 1, 1)->toDateString();
        $endDate = Carbon::create($year, 12, 31)->toDateString();

        return $this->createFinancialYear($startDate, $endDate, null, null, $activate);
    }

    /**
     * Create a financial year with custom start month (e.g., April to March)
     *
     * @param int $startYear Year when the financial year starts
     * @param int $startMonth Month when the financial year starts (1-12)
     * @param bool $activate
     * @return FinancialYear
     */
    public function createCustomFinancialYear(int $startYear, int $startMonth = 4, bool $activate = false): FinancialYear
    {
        $startDate = Carbon::create($startYear, $startMonth, 1)->toDateString();
        $endDate = Carbon::create($startYear, $startMonth, 1)
            ->addYear()
            ->subDay()
            ->toDateString();

        return $this->createFinancialYear($startDate, $endDate, null, null, $activate);
    }

    /**
     * Get financial year dates for a given date
     *
     * @param string|null $date
     * @return array|null Returns ['start_date' => ..., 'end_date' => ...] or null
     */
    public function getFinancialYearDates(?string $date = null): ?array
    {
        $financialYear = $this->getFinancialYearForDate($date);

        if (!$financialYear) {
            return null;
        }

        return [
            'start_date' => $financialYear->start_date->toDateString(),
            'end_date' => $financialYear->end_date->toDateString(),
            'financial_year' => $financialYear,
        ];
    }

    /**
     * Check if a date is within the active financial year
     *
     * @param string|null $date
     * @return bool
     */
    public function isDateInActiveFinancialYear(?string $date = null): bool
    {
        $date = $date ?? now()->toDateString();
        $activeYear = $this->getActiveFinancialYear();

        if (!$activeYear) {
            return false;
        }

        return $activeYear->containsDate($date);
    }

    public function getAllFinancialYears(Request $request, $platform)
    {
        if ($platform == 'api') {
            $response = FinancialYear::all()->map(function ($year) {
                return [
                    'id' => $year->id,
                    'name' => $year->name,
                    'code' => $year->code,
                    'start_date' => $year->start_date->format('Y-m-d'),
                    'end_date' => $year->end_date->format('Y-m-d'),
                ];
            });
        } else {
            $financialYear = FinancialYear::orderBy('start_date', 'desc')->paginate(config('settings.pagination.perpage.admin'));

            $response = [
                'financial_years' => $financialYear->items(),
                'pagination' => generatePaginationData($financialYear),
            ];
        }
        return successResponse($response, 'Financial years retrieved successfully.', 0, 200);
    }
}
