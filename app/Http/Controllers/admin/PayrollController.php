<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdvanceSalary;
use App\Models\CommissionPayroll;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeesAccountTable;
use App\Models\FinancialYear;
use App\Models\PaidCommission;
use App\Models\Payroll;
use App\Models\Supplier;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    protected JournalEntryService $journalEntryService;

    protected EmployeeService $employeeService;

    public function __construct(JournalEntryService $journalEntryService, EmployeeService $employeeService)
    {
        $this->journalEntryService = $journalEntryService;
        $this->employeeService = $employeeService;
    }

    /**
     * Display the payroll management page
     */
    public function index()
    {
        return view('admin.pages.payroll.index');
    }

    public function commission_index()
    {
        return view('admin.pages.commission.index');
    }

    public function advance_index()
    {
        return view('admin.pages.payroll.advance');
    }
    /**
     * Display the employee management page
     */
    // public function employeesPage()
    // {
    //     return view('admin.pages.payroll.employees');
    // }

    // ==================== EMPLOYEE MANAGEMENT (Users with salesman/salesexecutive roles) ====================

    /**
     * Get list of employees (Users with salesman/salesexecutive roles)
     */
    public function getEmployees(Request $request)
    {

        try {
            // $query = User::role(['salesman', 'salesexecutive', 'Salesman', 'SalesExecutive', 'sales_executive', 'sales_man'])
            //     ->with('roles');

            $query = Employee::whereIn('designation', ['salesman', 'salesexecutive', 'Salesman', 'SalesExecutive', 'sales_executive', 'sales_man'])->pluck('user_id');

            $employees = User::whereIn('id', $query)->get();

            // if ($request->search) {
            //     $search = $request->search;
            //     $query->where(function ($q) use ($search) {
            //         $q->where('name', 'like', "%{$search}%")
            //             ->orWhere('email', 'like', "%{$search}%")
            //             ->orWhere('phone', 'like', "%{$search}%");
            //     });
            // }

            // if ($request->has('status')) {
            //     $query->where('status', $request->status);
            // }

            // $employees = $query->orderBy('name')
            //     ->paginate($request->per_page ?? 15);

            // Add role name to each user
            // $items = collect($employees->items())->map(function ($user) {
            //     $user->role_name = $user->roles->pluck('name')->implode(', ');
            //     return $user;
            // });

            return successResponse([
                'employees' => $employees,

            ], 'Employees retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function getReferenceData($type)
    {
        switch ($type) {

            case 'customer':
                $data = Customer::select('id', 'customer_name as name')->get();
                break;

            case 'employee':
                $data = Employee::select('id', 'name')->get();
                break;

            case 'supplier':
                $data = Supplier::select('id', 'supplier_name as name')->get();
                break;

            case 'commission':
                $data = Employee::select('id', 'name')->get();
                break;
            case 'advance':
                $data = Employee::select('id', 'name')->get();
                break;

            default:
                $data = [];
        }

        return response()->json([
            'status' => 'Success',
            'data' => $data,
        ]);
    }

    /**
     * Update employee salary details
     */
    public function updateEmployee(Request $request, $id)
    {

        $request->validate([
            'basic_salary' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $employee = Employee::findOrFail($id);

            $employee->update($request->only([
                'basic_salary',
                'allowance',
                'bank_name',
                'bank_account_no',
                'bank_ifsc',
            ]));

            DB::commit();

            return successResponse($employee, 'Employee salary details updated successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get all users who can receive salary (for dropdown)
     */
    public function getSalaryEligibleUsers()
    {
        try {
            $users = User::role(['salesman', 'salesexecutive', 'Salesman', 'SalesExecutive', 'sales_executive', 'sales_man'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'basic_salary']);

            return successResponse($users, 'Salary eligible users retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    // ==================== PAYROLL MANAGEMENT ====================

    /**
     * Get list of payrolls
     */
    public function getPayrolls(Request $request)
    {
        try {
            $query = Payroll::with(['user', 'employee', 'creator']);

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('payroll_no', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($usr) use ($search) {
                            $usr->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->month && $request->year) {
                $query->forPeriod($request->month, $request->year);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->status) {
                $query->status($request->status);
            }

            $payrolls = $query->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Add staff name to each payroll
            $items = collect($payrolls->items())->map(function ($payroll) {
                $payroll->staff_name = $payroll->user ? $payroll->user->name : ($payroll->employee ? $payroll->employee->name : 'N/A');

                return $payroll;
            });

            return successResponse([
                'payrolls' => $items,
                'pagination' => generatePaginationData($payrolls),
            ], 'Payrolls retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Create a new payroll record
     */
    public function storePayroll(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {

            $employee = Employee::findOrFail($request->user_id);

            $currentMonth = now()->month;
            $currentYear = now()->year;

            if (
                (int)$request->month < $currentMonth ||
                (int)$request->year < $currentYear
            ) {
                return errorResponse(
                    null,
                    'Previous month Payroll can not be created',
                    400
                );
            }

            // Check if payroll already exists for this month/year
            $exists = Payroll::where('user_id', $employee->user_id)
                ->forPeriod($request->month, $request->year)
                ->exists();

            if ($exists) {
                return errorResponse(null, 'Payroll already exists for this user for this month', 400);
            }

            $user = User::findOrFail($employee->user_id);

            // Generate payroll number
            $lastPayroll = Payroll::withTrashed()->orderBy('id', 'desc')->first();

            $nextId = $lastPayroll ? $lastPayroll->id + 1 : 1;
            $payrollNo = 'PAY-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // Calculate salaries
            $basicSalary = $request->basic_salary;
            $allowances = $request->allowance;
            $overtime = $request->overtime ?? 0;
            $bonus = $request->bonus ?? 0;
            $grossSalary = $basicSalary + $allowances + $overtime + $bonus;

            $deductions = $request->deductions ?? 0;
            $netSalary = $grossSalary - $deductions;

            $payroll = Payroll::create([
                'payroll_no' => $payrollNo,
                'user_id' => $employee->user_id,
                'employee_id' => $employee->id,
                'month' => $request->month,
                'year' => $request->year,
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'overtime' => $overtime,
                'bonus' => $bonus,
                'gross_salary' => $grossSalary,
                'deductions' => $deductions,
                'net_salary' => $netSalary,
                'working_days' => $request->working_days ?? 30,
                'present_days' => $request->present_days ?? 30,
                'leave_days' => $request->leave_days ?? 0,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            $lastDay = Carbon::create($request->year, $request->month, 1)->endOfMonth()->toDateString();
            $account = Account::where('code', '9100')->first();


            $employeePayableAccount = EmployeesAccountTable::where('account_type', 'salary')->where('employee_id', $employee->id)->first();

            // $employeeAdvanceAccountTable = EmployeesAccountTable::where('account_type', 'advance')->where('employee_id', $employee->id)->first();
            // $employeeAdvanceAccount = Account::find($employeeAdvanceAccountTable->account_id);
            // // Get total advance
            // $advanceAmount = AdvanceSalary::where('employee_id', $employee->id)
            //     ->where('is_adjusted', false)
            //     ->sum('amount');
            // $advanceAccount = AdvanceSalary::where('employee_id', $employee->id)
            //     ->where('is_adjusted', false)->first();
            // $cashAccount = Account::find($advanceAccount->payment_mode)->payment_mode;
            $journalEntry = $this->journalEntryService->createPayrollEntry([
                'date' => $lastDay,
                'reference_type' => 'App\\Models\\Payroll',
                'reference_id' => $payroll->id,
                'description' => 'Payroll payable: ' . $payroll->payroll_no . ' - ' . $payroll->id,
                'amount' => $payroll->net_salary,
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'cr_account_id' => $employeePayableAccount->account_id,
                'payroll_no' =>  $payroll->payroll_no,
                'payroll_description' => $payroll->notes ?? $payroll->payroll_no,
                // 'cashAccount' => $cashAccount,
                // 'payment_mode' => $expense->payment_mode,
                // 'advanceAmount' => $advanceAmount,
                // 'employeeAdvanceAccount' => $employeeAdvanceAccount,
                'employee_id' => $employee->id,
            ]);

            //     // Adjust Advance
            // if ($advanceAmount > 0) {

            //     AdvanceSalary::where('employee_id', $employee->id)
            //         ->update(['is_adjusted' => true]);
            // }
            if ($journalEntry) {
                DB::commit();

                return successResponse($payroll->load('user'), 'Payroll created successfully', 0, 200);
            } else {
                DB::rollBack();
                return errorResponse(null, 'Failed to create journal entry for the Payroll', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get a single payroll
     */
    public function showPayroll($id)
    {
        try {
            $payroll = Payroll::with(['user', 'employee', 'creator', 'approver', 'journalEntry.lines'])
                ->findOrFail($id);
            $payroll->staff_name = $payroll->user ? $payroll->user->name : ($payroll->employee ? $payroll->employee->name : 'N/A');

            return successResponse($payroll, 'Payroll retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Approve and Pay payroll - Creates journal entry
     */
    public function payPayroll(Request $request, $id)
    {
        $request->validate([
            'payment_mode' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $payroll = Payroll::with(['user', 'employee'])->findOrFail($id);

            if ($payroll->status === 'paid') {
                return errorResponse(null, 'Payroll already paid', 400);
            }

            if ($payroll->status === 'cancelled') {
                return errorResponse(null, 'Cannot pay a cancelled payroll', 400);
            }
            $financialYear = FinancialYear::whereDate('start_date', '<=', $request->payment_date)
                ->whereDate('end_date', '>=', $request->payment_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            // Get staff name
            $staffName = $payroll->user ? $payroll->user->name : ($payroll->employee ? $payroll->employee->name : 'Unknown');

            // Create Journal Entry for salary payment
            $monthName = date('F', mktime(0, 0, 0, $payroll->month, 1));
            $period = $monthName . ' ' . $payroll->year;
            $employeePayableAccount = Account::where('reference_type', 'employee')->where('reference_id', $payroll->employee_id)->first();
            $employeeAdvanceAccount = Account::where('reference_type', 'advance')->where('reference_id', $payroll->employee_id)->first();

            $adjustjournalEntry = $this->journalEntryService->createPayrollAdjustmentEntry([
                'date' => $request->payment_date,
                'reference_type' => 'App\\Models\\PayrollPayment',
                'reference_id' => $payroll->id,
                'description' => 'Salary Payment: ' . $staffName . ' - ' . $period,
                'amount' => $employeePayableAccount->getCurrentBalance(),
                'advance_amount' => $employeeAdvanceAccount->getCurrentBalance(),
                'total_salary' => $payroll->net_salary,
                'user_id' => Auth::id(),
                'account_id' => $employeePayableAccount->id,
                'advance_account_id' => $employeeAdvanceAccount->id,
                'employee_id' => $request->employee_id,
                'employee_name' => $staffName,
                'period' => $period,
                'payment_mode' => $request->payment_mode,
                'payroll_no' =>  $payroll->payroll_no,
                'payroll_description' => $payroll->notes ?? $payroll->payroll_no,
                // 'payment_mode' => $expense->payment_mode,
            ]);

            $journalEntry = $this->journalEntryService->createPayrollPaymentEntry([
                'date' => $request->payment_date,
                'reference_type' => 'App\\Models\\PayrollPayment',
                'reference_id' => $payroll->id,
                'description' => 'Salary Payment: ' . $staffName . ' - ' . $period,
                'amount' => $employeePayableAccount->getCurrentBalance(),
                'advance_amount' => $employeeAdvanceAccount->getCurrentBalance(),
                'total_salary' => $payroll->net_salary,
                'user_id' => Auth::id(),
                'account_id' => $employeePayableAccount->id,
                'advance_account_id' => $employeeAdvanceAccount->id,
                'employee_id' => $request->employee_id,
                'employee_name' => $staffName,
                'period' => $period,
                'payment_mode' => $request->payment_mode,
                'payroll_no' =>  $payroll->payroll_no,
                'payroll_description' => $payroll->notes ?? $payroll->payroll_no,
                // 'payment_mode' => $expense->payment_mode,
            ]);

            if ($journalEntry && $adjustjournalEntry) {
                // Update payroll status
                $payroll->update([
                    'status' => 'paid',
                    'payment_date' => $request->payment_date,
                    'payment_mode' => $request->payment_mode,
                    'reference_no' => $request->reference_no,
                    'approved_by' => Auth::id(),
                    'journal_entry_id' => $journalEntry ? $journalEntry->id : null,
                ]);



                DB::commit();

                return successResponse($payroll->fresh(['user', 'employee', 'journalEntry']), 'Payroll paid successfully with journal entry', 0, 200);
            } else {
                DB::rollBack();

                return errorResponse(null, 'Failed to create journal entry, Check your account balances', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a payroll
     */
    public function cancelPayroll($id)
    {
        DB::beginTransaction();
        try {
            $payroll = Payroll::findOrFail($id);

            if ($payroll->status === 'paid') {
                // Reverse the journal entry if exists
                if ($payroll->journal_entry_id && $payroll->journalEntry) {
                    $payroll->journalEntry->reverse('Payroll cancelled: ' . $payroll->payroll_no);
                }
            }

            $payroll->update(['status' => 'cancelled']);

            DB::commit();

            return successResponse(null, 'Payroll cancelled successfully', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Generate payroll for all salesman/salesexecutive users for a month
     */
    public function generateBulkPayroll(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        DB::beginTransaction();
        try {
            // Get all users with salesman/salesexecutive roles
            // $users = User::role(['salesman', 'salesexecutive', 'Salesman', 'SalesExecutive', 'sales_executive', 'sales_man'])
            //     ->where('status', 'active')
            //     ->get();

            $employees = Employee::where('is_active', 1)->get();
            $created = 0;
            $skipped = 0;

            foreach ($employees as $employee) {
                // Check if already exists
                $exists = Payroll::where('employee_id', $employee->id)
                    ->forPeriod($request->month, $request->year)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                // Generate payroll number
                $lastPayroll = Payroll::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastPayroll ? $lastPayroll->id + 1 : 1;
                $payrollNo = 'PAY-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

                $basicSalary = $employee->basic_salary ?? 0;
                $allowances = $employee->allowances ?? 0;

                $user_id = $employee->user_id;

                Payroll::create([
                    'payroll_no' => $payrollNo,
                    'employee_id' => $employee->id,
                    'user_id' => $user_id,
                    'month' => $request->month,
                    'year' => $request->year,
                    'basic_salary' => $basicSalary,
                    'allowances' => $allowances,
                    'overtime' => 0,
                    'bonus' => 0,
                    'gross_salary' => $basicSalary,
                    'deductions' => 0,
                    'net_salary' => $basicSalary,
                    'working_days' => $request->working_days ?? 30,
                    'present_days' => $request->working_days ?? 30,
                    'leave_days' => 0,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ]);

                $created++;
            }

            DB::commit();

            return successResponse([
                'created' => $created,
                'skipped' => $skipped,
            ], "Payroll generated: {$created} created, {$skipped} skipped", 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get payroll summary
     */
    public function getSummary(Request $request)
    {
        try {
            $month = $request->month ?? now()->month;
            $year = $request->year ?? now()->year;

            $summary = Payroll::forPeriod($month, $year)
                ->select(
                    DB::raw('COUNT(*) as total_payrolls'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count'),
                    DB::raw('SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN net_salary ELSE 0 END) as total_paid'),
                    DB::raw('SUM(CASE WHEN status = "draft" THEN net_salary ELSE 0 END) as total_pending'),
                    DB::raw('SUM(gross_salary) as total_gross'),
                    DB::raw('SUM(deductions) as total_deductions')
                )
                ->first();

            return successResponse([
                'month' => $month,
                'year' => $year,
                'summary' => $summary,
            ], 'Payroll summary retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function getCommissionList(Request $request)
    {
        try {
            $query = CommissionPayroll::with(['user', 'employee', 'creator']);

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('commission_no', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($usr) use ($search) {
                            $usr->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->month && $request->year) {
                $query->forPeriod($request->month, $request->year);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->status) {
                $query->status($request->status);
            }

            $payrolls = $query->orderBy('year', 'desc')
                ->orderBy('id', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Add staff name to each payroll
            $items = collect($payrolls->items())->map(function ($payroll) {
                $payroll->staff_name = $payroll->user ? $payroll->user->name : ($payroll->employee ? $payroll->employee->name : 'N/A');

                return $payroll;
            });

            return successResponse([
                'payrolls' => $items,
                'pagination' => generatePaginationData($payrolls),
            ], 'Payrolls retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function cancelCommission($id)
    {
        DB::beginTransaction();

        try {

            $commission = CommissionPayroll::findOrFail($id);

            if ($commission->status == 'paid') {
                return errorResponse(null, 'Paid commission cannot be cancelled', 400);
            }

            $commission->update([
                'status' => 'cancelled'
            ]);

            DB::commit();

            return successResponse(null, 'Commission cancelled successfully', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function storeCommission(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'total_commission' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {

            $employee = Employee::findOrFail($request->user_id);

            // Check if payroll already exists for this month/year
            $exists = CommissionPayroll::where('employee_id', $employee->id)
                ->forPeriod($request->month, $request->year)
                ->exists();

            if ($exists) {
                return errorResponse(null, 'Commission Payroll already exists for this user for this month', 400);
            }



            // Generate payroll number
            $lastPayroll = CommissionPayroll::withTrashed()->orderBy('id', 'desc')->first();

            $nextId = $lastPayroll ? $lastPayroll->id + 1 : 1;
            $payrollNo = 'COMMISSION-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // Calculate salaries
            $total_commission = $request->total_commission;

            $payroll = CommissionPayroll::create([

                'employee_id' => $employee->id,
                'commission_no' => $payrollNo,
                'month' => $request->month,
                'year' => $request->year,
                'commission' => $total_commission,
                'status' => 'draft',

                'created_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            $lastDay = Carbon::create($request->year, $request->month, 1)->endOfMonth()->toDateString();
            $account = Account::where('code', '9500')->first();
            $employeePayableAccount = Account::where('reference_type', 'commission')
                ->where('reference_id', $employee->id)
                ->where('is_active', 1)
                ->first();



            $journalEntry = $this->journalEntryService->createCommissionEntry([
                'date' => $lastDay,
                'reference_type' => 'App\\Models\\Commission',
                'reference_id' => $payroll->id,
                'description' => 'Commission payable: ' . $payroll->commission_no . ' - ' . $payroll->id,
                'amount' => $payroll->commission,
                'user_id' => Auth::id(),
                'account_id' => $account->id,
                'cr_account_id' => $employeePayableAccount->id,
                'commission_no' =>  $payroll->commission_no,
                'payroll_description' => $payroll->notes ?? $payroll->payroll_no,

            ]);

            if ($journalEntry) {
                DB::commit();

                return successResponse($payroll->load('user'), 'Payroll created successfully', 0, 200);
            } else {
                return errorResponse(null, 'Failed to create journal entry', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    public function payCommission(Request $request, $id)
    {


        $request->validate([

            'employee_id' => 'required',
            'commission' => 'required',
            'payment_mode' => 'required|string',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {

            $financialYear = FinancialYear::whereDate('start_date', '<=', $request->payment_date)
                ->whereDate('end_date', '>=', $request->payment_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            $employee = Employee::where('id', $request->employee_id)->lockForUpdate()->first();
            $payroll = CommissionPayroll::with(['user', 'employee'])->findOrFail($id);

            $staffName = $payroll->user ? $payroll->user->name : ($payroll->employee ? $payroll->employee->name : 'Unknown');

            // Create Journal Entry for salary payment
            $monthName = date('F', mktime(0, 0, 0, $payroll->month, 1));
            $period = $monthName . ' ' . $payroll->year;
            $employeePayableAccountTable = EmployeesAccountTable::where('account_type', 'commission')->where('employee_id', $payroll->employee_id)->first();
            $employeePayableAccount = Account::find($employeePayableAccountTable->account_id);

            $journalEntry = $this->journalEntryService->createCommissionPaymentEntry([
                
                'date' => $request->payment_date,
                'reference_type' => 'App\\Models\\CommissionPayment',
                'reference_id' => $payroll->id,
                'description' => 'Commission Payment: ' . $staffName . ' - ' . $period,
                'amount' => $payroll->commission,
                'user_id' => Auth::id(),
                'account_id' => $employeePayableAccount->id,
                'employee_id' => $request->employee_id,
                'employee_name' => $staffName,
                'period' => $period,
                'payment_mode' => $request->payment_mode,
                'payroll_no' =>  $payroll->payroll_no,
                'payroll_description' => $payroll->notes ?? $payroll->payroll_no,
            ]);


            // if ($employee->commission < $request->commission) {
            //     throw new \Exception('Insufficient commission');
            // }


            // $employee->decrement('commission', $request->commission);
            if ($journalEntry) {
                $payroll->update([
                    'status' => 'paid',
                    'payment_date' => $request->payment_date,
                    'payment_mode' => $request->payment_mode,
                    'approved_by' => Auth::id(),
                    'journal_entry_id' => $journalEntry->id,
                ]);
                DB::commit();

                return successResponse(null, 'Commission paid successfully with journal entry', 0, 200);
            } else {
                return errorResponse(null, 'Failed to create journal entry', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }



    public function storeAdvance(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:employees,id',
            'advance_salary' => 'required|numeric|min:0',
            'advance_date' => 'required|date',
            'notes' => 'nullable|string',
            'payment_mode' => 'required|exists:accounts,id',
        ]);

        DB::beginTransaction();
        try {

            $financialYear = FinancialYear::whereDate('start_date', '<=', $request->advance_date)
                ->whereDate('end_date', '>=', $request->advance_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }
            $employee = Employee::findOrFail($request->user_id);

            $user = User::findOrFail($employee->user_id);

            $advance = AdvanceSalary::create([
                'employee_id' => $employee->id,
                'amount' => $request->advance_salary,
                'date' => $request->advance_date,
                'remarks' => $request->notes,
                'payment_mode' => $request->payment_mode

            ]);


            $employeeAdvanceAccount = EmployeesAccountTable::where('account_type', 'advance')->where('employee_id', $employee->id)->first();

            $journalEntry = $this->journalEntryService->createAdvancePaymentEntry([
                'date' => $request->advance_date,
                'reference_type' => 'App\\Models\\AdvanceSalary',
                'reference_id' => $advance->id,
                'description' => 'Advance Payment: ' . $employee->name,
                'amount' => $request->advance_salary,
                'user_id' => Auth::id(),
                'account_id' => $employeeAdvanceAccount->account_id,

                'payment_mode' => $request->payment_mode,
            ]);

            // // Accounts
            if ($journalEntry) {
                DB::commit();

                return successResponse($advance->load('employee'), 'Advance Payment Paid successfully', 0, 200);
            } else {
                return errorResponse(null, 'Failed to create journal entry for the Advance Payment', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }


    public function getAdvances(Request $request)
    {
        try {
            $query = AdvanceSalary::with(['employee', 'user']);

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('remarks', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($usr) use ($search) {
                            $usr->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'like', "%{$search}%");
                        });
                });
            }


            $advances = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Add staff name to each payroll
            $items = collect($advances->items())->map(function ($advance) {
                $advance->staff_name = $advance->user ? $advance->user->name : ($advance->employee ? $advance->employee->name : 'N/A');

                return $advance;
            });

            return successResponse([
                'advances' => $items,
                'pagination' => generatePaginationData($advances),
            ], 'Advances retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
}
