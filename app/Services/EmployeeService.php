<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AutoIncrementMaster;
use App\Models\Employee;
use App\Models\EmployeesAccountTable;
use App\Models\FinancialYear;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeService
{
    public function getAllEmployee($data, $platform = 'web')
    {
        try {
            $query = Employee::with('createdBy', 'roles', 'user');

            if ($platform == 'leave') {
                $result = $query->orderBy('created_at', 'DESC')
                    ->where('is_active', 1)
                    ->get();

                return successResponse([
                    'employees' => $result,
                ], 'Employees retrieved successfully.', 0, 200);
            }

            if ($platform == 'payroll') {
                $result = $query->where('is_active', 1)
                    ->where('designation', '!=', 'outside_salesman')
                    ->orderBy('created_at', 'DESC')
                    ->get();

                return successResponse([
                    'employees' => $result,
                ], 'Employees retrieved successfully.', 0, 200);
            }
            if ($platform == 'attendance') {
                return $query->orderBy('created_at', 'DESC')->where('is_active', 1)->get();
            }
            // dd($data->all());

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('name', 'LIKE', "%{$data->search}%")
                        ->orWhere('phone', 'LIKE', "%{$data->search}%")
                        ->orWhere('email', 'LIKE', "%{$data->search}%")
                        ->orWhere('employee_code', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {

                if ($data->employee_type == 'in') {
                    $query = $query->where('designation', '!=', 'outside_salesman');
                } else {
                    $query = $query->where('designation', 'outside_salesman')
                        ->withSum('outsideSaleOrders as total_balance', 'balance_amount');;
                }
                if ($data->status !== null && $data->status !== '') {
                    $query->where('is_active', (int)$data->status);
                }
                // dd($query->toSql(), $query->getBindings(), $query->get());
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'employees' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];

                return successResponse($response, 'Employees retrieved successfully.', 0, 200);
            } else {

                $result = $query->orderBy('created_at', 'DESC')->where('is_active', 1)->get();

                $response = [
                    'employees' => $result,
                ];

                return successResponse($response, 'Employees retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function storeEmployee($data, $platform = 'web')
    {


        try {
            DB::beginTransaction();

            $requested_date = now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            // if (!$financialYear) {
            //     // throw new Exception('No financial year found for the selected date.');
            //     DB::rollBack();
            //     return errorResponse(null, 'No financial year found for the selected date.', 500);
            // }
            // if ($financialYear->is_closed == 1) {
            //     DB::rollBack();
            //     return errorResponse(null, 'Financial year closed for the selected date.', 500);
            // }

            if ($data->phone_number) {
                $existingUser = User::where('phone', $data->phone_number)->first();
                if ($existingUser) {
                    return errorResponse(null, 'Customer already exists.', 100, 200);
                }
            }

            if ($data->email) {
                $existingUser = User::where('email', $data->email)->first();
                if ($existingUser) {
                    return errorResponse(null, 'Customer already exists.', 100, 200);
                }
            }

            $user = User::create([
                'name' => $data->name,
                'username' => $data->name,
                'email' => $data->email ?? '',
                'user_type' => $data->designation,
                'phone' => $data->phone_number,
                // 'password' => Hash::make('123456'),
                // 'password_without_hash' => 123456,
                'password' => Hash::make($data->password),
                'password_without_hash' => $data->password,
                'status' => $data->status ?? 1,
            ]);

            // if ($data['designation'] == 'salesman') {

            //     $role = Role::where('name', 'sales_man')->first();
            // } else {
            // }
            $role = null;
            if ($data->designation == 'salesman') {
                $role = Role::where('name', 'salesman')->first();
            } elseif ($data->designation == 'sales_executive') {
                $role = Role::where('name', 'sales_executive')->first();
            } elseif ($data->designation == 'outside_salesman') {
                $role = Role::where('name', 'outside_salesman')->first();
            }


            if (! $role) {
                return errorResponse(null, 'Role does not exist', 100, 500);
            }

            $user->assignRole($role);

            // dd($data->password);
            $employee = Employee::Create([
                'employee_code' => $this->genarateEmpCode(),
                'name' => $data->name,
                'employee_name_ar' => $data->employee_name_ar, // Matches Blade formData key
                'email' => $data->email,
                'phone' => $data->phone_number,
                'designation' => $data->designation,
                'role_id' => $role->id,
                'basic_salary' => $data->basic_salary ?? 0,
                'allowance' => $data->allowances ?? 0,
                'bank_name' => $data->bank_name ?? 'NA',
                'bank_account_no' => $data->bank_account_no ?? 'NA',
                'bank_ifsc' => $data->bank_ifsc ?? 'NA',
                'is_active' => $data->status ?? 1,
                'credit_limit' => $data->credit_limit ?? 0,
                'created_by' => $data->logged_user_id,
                'user_id' => $user->id,
                'password' => Hash::make($data->password),
                'password_without_hash' => $data->password,
            ]);

            if ($data->hasFile('profile_image')) {
                $file = $data->file('profile_image');
                $ext = $file->extension();
                $ext = $file->getClientOriginalExtension();
                $filename = 'user_' . preg_replace('/\s+/', '', $data['name']) . '-' . time() . '.' . $ext;
                $file->storeAs('uploads', $filename, ['disk' => 'public']);

                $employee->profile_image = 'uploads/' . $filename;
                $employee->save();
            }
            if ($employee->designation != 'outside_salesman') {
                $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_LEDGER_CODE')->first();
                $next = $codeAuto->auto_increment_general_value;
                $code = 'E' . $next;
                $parent1 = Account::where('name', 'Accounts Payable(Creditors)')->first();

                $account1 = Account::create([
                    'account_type_id' => 2,
                    'name' => $employee->name . '_' . $employee->id . 'Salary Payable',
                    'parent_id' => $parent1->id,
                    'description' => $employee->name . '_' . $employee->id . 'Salary Payable Account',
                    'opening_balance' => 0,
                    'is_active' => $data->status ?? 1,
                    'is_system' => 1,
                    'code' => $code,
                    'reference_id' => $employee->id,
                    'reference_type' => 'employee',
                ]);
                $next = $next + 1;
                $code = 'E' . $next;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account1->id,
                    'account_type' => 'salary',
                ]);

                $parent2 = Account::where('name', 'Current Assets')->first();

                $account2 = Account::create([
                    'account_type_id' => 1,
                    'name' => $employee->name . '_' . $employee->id . 'Salary Advance',
                    'parent_id' => $parent2->id,
                    'description' => $employee->name . '_' . $employee->id . ' Salary Advance Account',
                    'opening_balance' => 0,

                    'is_active' => $data->status ?? 1,
                    'is_system' => 1,
                    'code' => $code,
                    'reference_id' => $employee->id,
                    'reference_type' => 'advance',
                ]);
                $next = $next + 1;
                $code = 'E' . $next;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account2->id,
                    'account_type' => 'advance',
                ]);

                $parent3 = Account::where('name', 'Accrued Expenses(Outstanding Expenses)')->first();

                $account3 = Account::create([
                    'account_type_id' => 2,
                    'name' => $employee->name . '_' . $employee->id . ' Commission Payable',
                    'parent_id' => $parent3->id,
                    'description' => $employee->name . '_' . $employee->id . ' Commission Payable Account',
                    'opening_balance' => 0,

                    'is_active' => $data->status ?? 1,
                    'is_system' => 1,
                    'code' => $code,
                    'reference_id' => $employee->id,
                    'reference_type' => 'commission',
                ]);
                $next = $next + 1;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();

                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account3->id,
                    'account_type' => 'commission',
                ]);
            } else {
                $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_CUSTOMER_LEDGER_CODE')->first();
                $next = $codeAuto->auto_increment_general_value;
                $code = 'E' . $next;
                $parent = Account::where('name', 'Accounts Receivable(Debitors)')->first();
                $account = Account::create([
                    'account_type_id' => 1,
                    'name' => $employee->name . '_' . $employee->id,
                    'parent_id' => $parent->id,
                    'description' => $employee->name . '_' . $employee->id . ' receivable',
                    'opening_balance' => 0,
                    'reference_id' => $employee->id,
                    'reference_type' => 'employee',
                    'is_active' => 1,
                    'is_system' => 1,
                    'code' => $code
                ]);
                $next = $next + 1;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account->id,
                    'account_type' => 'outside sale',
                ]);
            }
            DB::commit();

            return successResponse($employee, 'Employee created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            if (
                str_contains($e->getMessage(), 'credit_limit') ||
                str_contains($e->getMessage(), 'Out of range value')
            ) {
                return errorResponse(
                    null,
                    'Credit Limit exceeds the maximum allowed value.',
                    100,
                    200
                );
            }

            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function genarateEmpCode()
    {
        $auto_emp_code = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_CODE')->first();

        // ✅ SAFETY CHECK
        if (! $auto_emp_code) {
            throw new \Exception('Auto increment master not found for AI_EMPLOYEE_CODE');
        }
        $currentValue = $auto_emp_code->auto_increment_general_value;
        $auto_emp_code->auto_increment_general_value = $currentValue + 1;
        $auto_emp_code->save();

        return $auto_emp_code->auto_prefix . $currentValue;
    }

    public function updateEmployee($data, $id)
    {
        try {
            DB::beginTransaction();
            $requested_date = now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();


            $employee = Employee::find($id);

            if (!$employee) {
                return errorResponse(null, 'Employee not found.', 100, 200);
            }

            if ($employee->user_id) {
                User::where('id', $employee->user_id)->update([
                    'name'      => $data->name,
                    'email'     => $data->email ?? '',
                    'phone'     => $data->phone_number,
                    'user_type' => $data->designation,
                    'status' => (int) $data->status,
                    'password' => Hash::make($data->password),
                    'password_without_hash' => $data->password,
                ]);
            }

            $employeesAccounts = Account::where('reference_id', $employee->id)->whereIn('reference_type', ['employee', 'commission', 'advance'])->get();
            foreach ($employeesAccounts as $account) {
                $account->is_active = $data->status ?? 1;

                // Outside Salesman Account
                if ($employee->designation == 'outside_salesman' && $account->reference_type == 'employee') {

                    $account->name = $data->name . '_' . $employee->id;
                    $account->description = $data->name . '_' . $employee->id . ' receivable';
                } else {

                    if ($account->reference_type == 'employee') {

                        $account->name = $data->name . '_' . $employee->id . 'Salary Payable';
                        $account->description = $data->name . '_' . $employee->id . ' Salary Payable Account';
                    } elseif ($account->reference_type == 'commission') {

                        $account->name = $data->name . '_' . $employee->id . ' Commission Payable';
                        $account->description = $data->name . '_' . $employee->id . ' Commission Payable Account';
                    } elseif ($account->reference_type == 'advance') {

                        $account->name = $data->name . '_' . $employee->id . 'Salary Advance';
                        $account->description = $data->name . '_' . $employee->id . ' Salary Advance Account';
                    }
                }

                $account->save();
            }
            if ($employee->designation !== $data->designation) {

                $user = User::find($employee->user_id);

                if ($user) {

                    $user->syncRoles();

                    if ($data->designation == 'salesman') {
                        $role = Role::where('name', 'salesman')->first();
                    } elseif ($data->designation == 'sales_executive') {
                        $role = Role::where('name', 'sales_executive')->first();
                    } elseif ($data->designation == 'outside_salesman') {
                        $role = Role::where('name', 'outside_salesman')->first();
                    }

                    if (!$role) {
                        return errorResponse(null, 'Role does not exist.', 100, 200);
                    }

                    $user->assignRole($role);

                    $employee->role_id = $role->id;
                }
            }
            $employee->name             = $data->name;
            $employee->employee_name_ar = $data->employee_name_ar;
            $employee->email            = $data->email;
            $employee->phone            = $data->phone_number;
            $employee->designation      = $data->designation;
            $employee->basic_salary     = $data->basic_salary ?? 0;
            $employee->allowance        = $data->allowances ?? 0;
            $employee->bank_name        = $data->bank_name;
            $employee->bank_account_no  = $data->bank_account_no;
            $employee->bank_ifsc        = $data->bank_ifsc;
            $employee->credit_limit     = $data->credit_limit ?? 0;
            $employee->is_active        = (int) $data->status;
            $employee->password = Hash::make($data->password);
            $employee->password_without_hash = $data->password;

            if ($data->hasFile('profile_image')) {
                $file     = $data->file('profile_image');
                $ext      = $file->extension();
                $ext = $file->getClientOriginalExtension();
                $filename = 'user_' . preg_replace('/\s+/', '', $data->name) . '-' . time() . '.' . $ext;
                $file->storeAs('uploads', $filename, ['disk' => 'public']);
                $employee->profile_image = 'uploads/' . $filename;
            }

            $employee->save();

            if ($employee->user_id) {
                User::where('id', $employee->user_id)->update([
                    'name'      => $data->name,
                    'email'     => $data->email ?? '',
                    'phone'     => $data->phone_number,
                    'user_type' => $data->designation,
                    'status' => (int) $data->status,
                    'profile_image' => $employee->profile_image,
                    'password' => Hash::make($data->password),
                    'password_without_hash' => $data->password,
                ]);
            }

            // Account::where('reference_id', $employee->id)
            //     ->whereIn('reference_type', ['employee', 'employee commission'])
            //     ->update(['is_active' => (int) $data->status]);

            DB::commit();

            return successResponse($employee, 'Employee updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
