<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AutoIncrementMaster;
use App\Models\CompanyOpeningBalance;
use App\Models\User;

use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use App\Models\SupplierOpeningBalance;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OpeningBalanceService
{

    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }
    public function openingBalanceList($request, $platform = 'web')
    {

        try {
            $query = CompanyOpeningBalance::with('account');

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('payment_mode', 'LIKE', "%{$request->search}%");
                    // ->orWhere('phone', 'LIKE', "%{$request->search}%")
                    // ->orWhere('email', 'LIKE', "%{$request->search}%")
                    // ->orWhere('employee_code', 'LIKE', "%{$request->search}%");
                });
            }
            if ($request->account_id) {
                $query->where('account_id', $request->account_id);
            }
            if ($request->startDate && $request->endDate) {

                $query->dateRange($request->startDate, $request->endDate);
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'opening_balances' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->where('status', 1)->get();

                $response = [
                    'opening_balances' => $result,
                ];

                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function openingBalanceCustomerList($request, $platform = 'web')
    {

        try {
            $query = OpeningBalance::with('customer')->whereHas('customer', function ($q) {
                $q->where('is_outside', 0);
            });

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->WhereHas('customer', function ($customerQuery) use ($request) {
                        $customerQuery->where(
                            'customer_name',
                            'LIKE',
                            "%{$request->search}%"
                        );
                    });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'opening_balances' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->where('status', 1)->get();

                $response = [
                    'opening_balances' => $result,
                ];

                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function openingBalanceSupplierList($request, $platform = 'web')
    {

        try {
            $query = SupplierOpeningBalance::with('supplier');

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->WhereHas('supplier', function ($supplierQuery) use ($request) {
                        $supplierQuery->where(
                            'supplier_name',
                            'LIKE',
                            "%{$request->search}%"
                        );
                    });
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'opening_balances' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->where('status', 1)->get();

                $response = [
                    'opening_balances' => $result,
                ];

                return successResponse($response, 'Opening balances retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function openingBalanceStore($data, $platform = 'web')
    {

        try {

            $financialYear = FinancialYear::where('start_date', '<=', $data->start_date)
                ->where('end_date', '>=', $data->start_date)
                ->first();

            $alreadyExists = CompanyOpeningBalance::where('account_id', $data['account_id'])
                ->whereBetween('opening_date', [$financialYear->start_date, $financialYear->end_date])
                ->first();

            if ($alreadyExists) {
                return errorResponse(null, 'Opening balance for this account is already entered', 100, 200);
            }
            $account = Account::where('id', $data['account_id'])->first();



            DB::beginTransaction();


            $openingBalance = CompanyOpeningBalance::create([
                'account_id' => $data['account_id'],
                'opening_balance' => $data['amount'],
                'opening_date' => $data['start_date'],
                'payment_mode' => $account->name,
            ]);


            $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                'date' => $data['start_date'],
                'amount' => $data['amount'],
                'reference_type' => 'App\\Models\\OpeningBalance',
                'reference_id' => $openingBalance->id,
                'description' => 'Opening balance entry',
                'account' => $account,
                'user_id' => $data['logged_user_id'],

            ]);
            if ($journalEntry) {
                // $currentBalance = $account->opening_balance;
                // $account->opening_balance = $currentBalance + $data['amount'];
                // $account->save();
                DB::commit();

                return successResponse($openingBalance, 'Opening balance created successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Failed to create journal entry', 500);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function openingBalanceUpdate($data, $platform = 'web')
    {

        try {

            DB::beginTransaction();
            $openingBalance = CompanyOpeningBalance::findOrFail($data->id);

            $account = Account::findOrFail($openingBalance->account_id);

            $journalEntry = JournalEntry::where('reference_type', 'App\\Models\\OpeningBalance')
                ->where('reference_id', $openingBalance->id)
                ->first();

            if (!$journalEntry) {
                throw new Exception("Journal entry not found");
            }

            $oldAmount = $openingBalance->opening_balance;

            $openingBalance->update([
                'opening_balance' => $data['amount'],
                'opening_date' => $data['start_date'],
            ]);


            $journalEntry->update([
                'entry_date' => $data['start_date'],
                'description' => 'Updated opening balance entry',
            ]);


            $lines = JournalEntryLine::where('journal_entry_id', $journalEntry->id)->get();

            foreach ($lines as $line) {
                $line->update([
                    'amount' => $data['amount'],
                ]);
            }


            $account->opening_balance = ($account->opening_balance - $oldAmount) + $data['amount'];
            $account->save();

            $this->journalEntryService->updateOpeningBalanceEntry($journalEntry);
            $accountBalances = AccountBalance::where('account_id', $account->id)->get();
            foreach ($accountBalances as $balance) {
                $oldDebitAmount = $balance->debit_balance;
                $oldNetAmount = $balance->net_balance;
                $balance->debit_balance = ($balance->debit_balance - $oldDebitAmount) + $data['amount'];
                $balance->net_balance = ($balance->net_balance - $oldNetAmount) + $data['amount'] - $balance->credit_balance;
                $balance->save();
            }



            DB::commit();

            return successResponse(null, 'Opening balance updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function genarateEmpCode()
    {
        $auto_emp_code = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_CODE')->first();
        $currentValue = $auto_emp_code->auto_increment_general_value;
        $auto_emp_code->auto_increment_general_value = $currentValue + 1;
        $auto_emp_code->save();

        return $auto_emp_code->auto_prefix . $currentValue;
    }
}
