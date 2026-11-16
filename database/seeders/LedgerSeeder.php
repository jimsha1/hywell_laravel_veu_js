<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\AutoIncrementMaster;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeesAccountTable;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class LedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $customers = Customer::get();
        foreach ($customers as $customer) {
            $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_CUSTOMER_LEDGER_CODE')->first();
            $next = $codeAuto->auto_increment_general_value;
            $code = 'C' . $next;
            $parent = Account::where('name', 'Accounts Receivable(Debitors)')->first();
            $account = Account::create([
                'account_type_id' => 1,
                'name' => $customer->customer_name . '_' . $customer->customer_code,
                'parent_id' => $parent->id,
                'description' => $customer->customer_name . '_' . $customer->customer_code . ' receivable',
                'opening_balance' => 0,
                'reference_id' => $customer->id,
                'reference_type' => 'customer',
                'is_active' => 1,
                'is_system' => 1,
                'code' => $code
            ]);
            $next = $next + 1;
            $codeAuto->auto_increment_general_value = $next;
            $codeAuto->save();
        }

        $employees = Employee::get();
        foreach ($employees as $employee) {
            $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_LEDGER_CODE')->first();

            $next = $codeAuto->auto_increment_general_value;

            $code = 'E' . $next;

            $parent1 = Account::where('name', 'Accrued Expenses(Outstanding Expenses)')->first();

            Account::create([
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

            $parent2 = Account::where('name', 'Current Assets')->first();

            Account::create([
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


            $parent3 = Account::where('name', 'Accrued Expenses(Outstanding Expenses)')->first();

            Account::create([
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
        }

        $suppliers = Supplier::get();
        foreach ($suppliers as $supplier) {
            $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_SUPPLIER_LEDGER_CODE')->first();
            $next = $codeAuto->auto_increment_general_value;
            $code = 'S' . $next;
            $parent = Account::where('name', 'Accounts Payable(Creditors)')->first();
            if ($parent) {

                Account::create([
                    'account_type_id' => 2,
                    'name' => $supplier->supplier_name . '_' . $supplier->supplier_code,
                    'parent_id' => $parent->id,
                    'description' => $supplier->supplier_name . '_' . $supplier->supplier_code . ' payable',
                    'opening_balance' => 0,
                    'reference_id' => $supplier->id,
                    'reference_type' => 'supplier',
                    'is_active' => 1,
                    'is_system' => 1,
                    'code' => $code
                ]);
                $next = $next + 1;
                $codeAuto->auto_increment_general_value = $next;
                $codeAuto->save();
            }
        }



        $employeeAccounts = Account::where('reference_type', 'employee')->get();
        foreach ($employeeAccounts as $account) {
            $employee = Employee::find($account->reference_id);
            if ($employee) {
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account->id,
                    'account_type' => 'salary',
                ]);
            }
        }
        $employeeAccounts = Account::where('reference_type', 'advance')->get();
        foreach ($employeeAccounts as $account) {
            $employee = Employee::find($account->reference_id);
            if ($employee) {
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account->id,
                    'account_type' => 'advance',
                ]);
            }
        }
        $employeeAccounts = Account::where('reference_type', 'commission')->get();
        foreach ($employeeAccounts as $account) {
            $employee = Employee::find($account->reference_id);
            if ($employee) {
                EmployeesAccountTable::create([
                    'employee_id' => $employee->id,
                    'account_id' => $account->id,
                    'account_type' => 'commission',
                ]);
            }
        }


        $customerAccounts = Account::where('reference_type', 'customer')->get();
        foreach ($customerAccounts as $account) {
            $customer = Customer::find($account->reference_id);
            if ($customer) {
                $customer->account_id = $account->id;
                $customer->save();
            }
        }
        $supplierAccounts = Account::where('reference_type', 'supplier')->get();
        foreach ($supplierAccounts as $account) {
            $supplier = Supplier::find($account->reference_id);
            if ($supplier) {
                $supplier->account_id = $account->id;
                $supplier->save();
            }
        }
    }
}
