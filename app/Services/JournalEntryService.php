<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AutoIncrementMaster;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeesAccountTable;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalEntryService
{
    /**
     * Account codes used in the system
     * These should match the accounts in your AccountSeeder
     */
    const ACCOUNT_CASH = '1100100';                    // Cash and Cash Equivalents
    const ACCOUNT_ACCOUNTS_RECEIVABLE = '1400';     // Accounts Receivable (Customer)
    const ACCOUNT_INVENTORY = '1500';               // Inventory
    const ACCOUNT_ACCOUNTS_PAYABLE = '3100';        // Accounts Payable (Supplier)
    const ACCOUNT_SALES_REVENUE = '6100';           // Product Sales
    const ACCOUNT_SALES_RETURNS = '6300';           // Sales Returns
    const ACCOUNT_COGS = '8100';                    // Cost of Goods Sold / Direct Materials
    const ACCOUNT_SALARIES = '9100';                // Salaries and Wages
    const ACCOUNT_RENT = '9200';                    // Rent Expense
    const ACCOUNT_UTILITIES = '9300';               // Utilities
    const ACCOUNT_MARKETING = '9400';               // Marketing and Advertising
    const ACCOUNT_OPERATING_EXPENSES = '9000';      // Operating Expenses (parent)
    const ACCOUNT_INPUT_TAX = '1600';                // Input Tax (GST/VAT)
    const ACCOUNT_PURCHASE_RETURNS = '11000';           // Purchase Returns

    const PURCHASE_ACCOUNT = '10000'; //purchase account
    const COMMISSION_ACCOUNT = '9500'; //commission expense account
    const STOCK_IN_HAND = 1700; //opening stock account


    public function createSalesCreditEntry(array $data): ?JournalEntry
    {
        try {


            // $salesAccount = $this->getAccountByCode(self::ACCOUNT_SALES_REVENUE);
            if ($data['customer_type'] != 'outside') {
                $customer = Customer::find($data['customer_id']);

                $receivableAccount = Account::find($customer->account_id);
            } else {

                $customer = Employee::where('user_id', $data['customer_id'])->first();

                $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();


                $receivableAccount = Account::find($receivableEmployeeAccount->account_id);
            }


            $salesAccount = $this->getAccountByCode(self::ACCOUNT_SALES_REVENUE);

            if (!$receivableAccount || !$salesAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales entry');
                return null;
            }

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            Log::info(' for date: ' . $date);
            Log::info('Financial Year: ' . $financialYear);
            Log::info('Financial Year: ' . $financialYear->id . ' for date: ' . $date);
            $journalEntry = JournalEntry::create([
                'entry_date' => $data['date'] ?? now()->toDateString(),
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\CreditOrdre',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Sales order created',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Accounts Receivable (customer owes money)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $receivableAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Sales- ' . ($data['customer_name'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Sales Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salesAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Sales revenue - Order ' . ($data['order_no'] ?? ''),
                'line_number' => 2,
            ]);



            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'sales', $data['reference_id']);

            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesCreditEntry error: ' . $e->getMessage());
            return null;
        }
    }
    public function createSalesEntry(array $data): ?JournalEntry
    {
        try {
            $this->createSalesCreditEntry($data);

            // $salesAccount = $this->getAccountByCode(self::ACCOUNT_SALES_REVENUE);
            if ($data['customer_type'] != 'outside') {
                $customer = Customer::find($data['customer_id']);

                $receivableAccount = Account::find($customer->account_id);
            } else {
                $customer = Employee::where('user_id', $data['customer_id'])->first();
                $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();

                $receivableAccount = Account::find($receivableEmployeeAccount->account_id);
            }


            $paymentMode = strtolower($data['payment_mode'] ?? 'cash');


            // Determine cash/bank account
            if ($paymentMode === 'cash') {
                $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } elseif ($paymentMode === 'bank transfer alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'bank transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'transfer alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'pos alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'pos sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            }

            if (!$receivableAccount || !$cashAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales entry');
                return null;
            }

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            $journalEntry = JournalEntry::create([
                'entry_date' => $data['date'] ?? now()->toDateString(),
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Order',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Sales order created',
                'status' => $data['journal_status'] ?? 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Accounts Receivable (customer owes money)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Sales - ' . ($data['customer_name'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Sales Revenue
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $receivableAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Sales revenue - Order ' . ($data['order_no'] ?? ''),
                'line_number' => 2,
            ]);

            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'receipt', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public function createSalesPaymentEntry(array $data): ?JournalEntry
    {

        try {



            // $customer = Customer::find($data['customer_id']);
            // // Customer Account
            // // $customerAccount = Account::where('reference_id', $data['customer_id'])
            // //     ->where('reference_type', 'customer')
            // //     ->first();
            // $customerAccount = Account::find($customer->account_id);
            // if (!$customerAccount) {
            //     Log::warning('Customer account not found');
            //     return null;
            // }


            if ($data['customer_type'] != 'outside') {
                $customer = Customer::find($data['customer_id']);

                $customerAccount = Account::find($customer->account_id);
            } else {
                $customer = Employee::where('user_id', $data['customer_id'])->first();
                $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();

                $customerAccount = Account::find($receivableEmployeeAccount->account_id);
            }
            if (!$customerAccount) {
                Log::warning('Customer account not found');
                return null;
            }
            $paymentMode = strtolower($data['payment_mode'] ?? 'cash');
            Log::info(' for date: ' . $data['date']);

            // Determine cash/bank account


            if ($paymentMode === 'cash') {
                $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } elseif ($paymentMode === 'bank transfer alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'bank transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'transfer alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'pos alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'pos sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            Log::info('Payment Financial Year: ' . $financialYear);
            Log::info('Financial Year: ' . $financialYear->id . ' for date: ' . $date);

            $journalEntry = JournalEntry::create([
                'entry_date' => $data['date'] ?? now()->toDateString(),
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\OrderPayment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Customer payment',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            Log::channel('sales payment')->info("------------journl entry created  ---------------");


            if (!$cashAccount) {
                Log::warning('Cash/Bank account not found');
                return null;
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Cash received',
                'line_number' => 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $customerAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Receipt',
                'line_number' => 2,
            ]);


            // $customerAccount->opening_balance = $customerAccount->opening_balance + $data['amount'];
            // $customerAccount->save();

            // $customerBalance = AccountBalance::where('account_id', $customerAccount->id)->orderBy('balance_date', 'desc')->first();
            // if ($customerBalance) {
            //     $customerBalance->debit_balance = $customerBalance->debit_balance - $data['amount'];
            //     $customerBalance->net_balance = $customerBalance->net_balance - $data['amount'];
            //     $customerBalance->save();
            // }

            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'receipt', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesPaymentEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public function createPurchasePaymentEntry(array $data): ?JournalEntry
    {


        try {

            $supplier = Supplier::find($data['supplier_id']);
            $payableAccount = Account::find($supplier->account_id);

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            Log::info('Financial Year: ' . $financialYear?->id . ' for date: ' . $date);


            if (!$financialYear) {
                throw new \Exception('Financial year not found');
            }

            $paymentMode = strtolower($data['payment_mode'] ?? 'cash');

            if (!$payableAccount) {

                Log::warning('JournalEntryService: Required accounts not found for purchase entry');
                return null;
            }

            // if ($paymentMode != 'credit') {

            $journalEntry = JournalEntry::create([
                'entry_date' => $data['date'] ?? now()->toDateString(),
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'description' => $data['description'],
                'status' => 'draft',
                'created_by' => $data['user_id'],
                'posted_by' => $data['user_id'],
                'posted_at' => now(),
            ]);
            Log::info('Journal Entry: ' . $journalEntry);

            if ($paymentMode === 'cash') {
                $paymentAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($paymentMode === 'transfer alrajhi') {
                $paymentAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'transfer sab') {
                $paymentAccount = Account::where('code', '1300300')->first();
            } else if ($paymentMode === 'pos alrajhi') {
                $paymentAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'pos sab') {
                $paymentAccount = Account::where('code', '1300300')->first();
            }

            if (!$paymentAccount) {
                return null;
            }
            Log::info('paymentAccount: ' . $paymentAccount);


            $currentBalance = $paymentAccount->calculateBalanceForDateRange($financialYear->start_date,  $data['date']);
            Log::info('currentBalance: ' . $currentBalance);


            $currentBalance = max(0, $paymentAccount->calculateBalanceForDateRange($financialYear->start_date, $data['date']));

            $payAmount = $data['amount'];

            // Amount actually paid
            // if ($currentBalance < $payAmount) {
            //     throw new \Exception(
            //         "Insufficient balance in {$paymentAccount->name}"
            //     );
            // }
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payableAccount->id,
                'type' => 'debit',
                'amount' => $payAmount,
                'line_number' => 1,

            ]);

            // if ($cashUsed > 0) {
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $paymentAccount->id,
                'type' => 'credit',
                'amount' => $payAmount,
                'line_number' => 2,
            ]);


            // }
            $this->updateAccountBalances($journalEntry);

            if ($data['reference_type'] == 'App\\Models\\PurchasePayment') {
                $narration = 'Purchase Payment to ' . $data['description'] . ' of ' . $data['supplier_name'];
            } else {
                $narration = 'Opening balance Payment to ' . $data['supplier_name'];
            }

            $this->voucherEntry($journalEntry, 'payment', $data['reference_id'], $narration);
            Log::info('Journal Entry: ' . $journalEntry);

            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('Purchase Payment Error: ' . $e->getMessage());
            return null;
        }
    }

    public function createSupplierCreditPaymentEntry(array $data): ?JournalEntry
    {

        try {

            $supplier = Supplier::find($data['supplier_id']);
            $payableAccount = Account::find($supplier->account_id);

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if (!$financialYear) {
                throw new \Exception('Financial year not found');
            }

            $paymentMode = strtolower($data['payment_mode'] ?? 'cash');

            if (!$payableAccount) {

                Log::warning('JournalEntryService: Required accounts not found for purchase entry');
                return null;
            }


            if ($paymentMode === 'cash') {

                $paymentAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($paymentMode === 'transfer alrajhi') {

                $paymentAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'alrajhi') {
                $paymentAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'sab') {
                $paymentAccount = Account::where('code', '1300300')->first();
            } else if ($paymentMode === 'pos alrajhi') {
                $paymentAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'pos sab') {
                $paymentAccount = Account::where('code', '1300300')->first();
            }

            if (!$paymentAccount) {
                return null;
            }

            $currentBalance = $paymentAccount->getCurrentBalance();


            if ($currentBalance < $data['amount']) {
                return null;
            }



            $currentBalance = max(0, $paymentAccount->getCurrentBalance());

            $payAmount = $data['amount'];

            // Amount actually paid
            $cashUsed = min($currentBalance, $payAmount);


            if ($cashUsed > 0) {

                $journalEntry = JournalEntry::create([
                    'entry_date' => $data['date'] ?? now()->toDateString(),
                    // 'entry_date' => now()->toDateString(),
                    'financial_year_id' => $financialYear->id,
                    'reference_type' => $data['reference_type'],
                    'reference_id' => $data['reference_id'],
                    'description' => $data['description'],
                    'status' => 'draft',
                    'created_by' => $data['user_id'],
                    'posted_by' => $data['user_id'],
                    'posted_at' => now(),
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $payableAccount->id,
                    'type' => 'debit',
                    'amount' => $payAmount,
                    'line_number' => 1,
                ]);

                // if ($cashUsed > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $paymentAccount->id,
                    'type' => 'credit',
                    'amount' => $payAmount,
                    'line_number' => 2,
                ]);
            } else {
                return null;
            }

            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('Purchase Payment Error: ' . $e->getMessage());
            return null;
        }
    }

    public function createPurchaseEntry(array $data): ?JournalEntry
    {


        try {
            $purchaseAccount = $this->getAccountByCode(self::PURCHASE_ACCOUNT);

            $supplier = Supplier::find($data['supplier_id']);
            $payableAccount = Account::find($supplier->account_id);

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            if (!$financialYear) {
                throw new \Exception('Financial year not found');
            }



            if (!$purchaseAccount ||  !$payableAccount) {

                Log::warning('JournalEntryService: Required accounts not found for purchase entry');
                return null;
            }
            $journalEntry = JournalEntry::create([
                'entry_date' => $data['date'] ?? now()->toDateString(),
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'description' => $data['description'], // Show invoice number directly without "Purchase - " prefix
                'status' => 'posted',
                'created_by' => $data['user_id'],
                'posted_by' => $data['user_id'],
                'posted_at' => now(),
            ]);

            // Debit Inventory
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $purchaseAccount->id,
                'type' => 'debit',
                'amount' => $data['total_amount'],
                'description' => $data['description'],
                'line_number' => 1,
            ]);

            // Credit Supplier
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payableAccount->id,
                'type' => 'credit',
                'amount' => $data['total_amount'],
                'description' => $data['description'],
                'line_number' => 2,
            ]);

            $this->updateAccountBalances($journalEntry);

            $this->voucherEntry($journalEntry, 'purchase', $data['reference_id']);


            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('Purchase Payment Error: ' . $e->getMessage());
            return null;
        }
    }


    public function createPurchaseReturnEntry(array $data): ?JournalEntry
    {

        try {


            $customer = Employee::where('user_id', $data['customer_id'])->first();
            $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();

            $salesReturnAccount = Account::find($receivableEmployeeAccount->account_id);




            $paymentMode = $data['payment_mode'];
            $paymentMode = strtolower($paymentMode ?? 'cash');

            if ($paymentMode === 'cash') {

                $creditAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($paymentMode === 'transfer alrajhi') {

                $creditAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'transfer sab') {
                $creditAccount = Account::where('code', '1300300')->first();
            } else if ($paymentMode === 'pos alrajhi') {
                $creditAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'pos sab') {
                $creditAccount = Account::where('code', '1300300')->first();
            }


            if (!$salesReturnAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales return');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\SalesReturnPayment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Sales return processed',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Sales Revenue (reducing revenue)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salesReturnAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash or Receivable
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 2,
            ]);

            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'receipt', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesReturnEntry error: ' . $e->getMessage());
            return null;
        }
    }




    public function createSalesReturnEntry(array $data): ?JournalEntry
    {

        try {


            if ($data['customer_type'] != 'outside') {
                $customer = Customer::find($data['customer_id']);

                $salesReturnAccount = Account::find($customer->account_id);
            } else {
                $customer = Employee::where('user_id', $data['customer_id'])->first();
                $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();

                $salesReturnAccount = Account::find($receivableEmployeeAccount->account_id);
            }



            $paymentMode = $data['payment_mode'];
            $paymentMode = strtolower($paymentMode ?? 'cash');

            if ($paymentMode === 'cash') {

                $creditAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($paymentMode === 'transfer alrajhi') {

                $creditAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'transfer sab') {
                $creditAccount = Account::where('code', '1300300')->first();
            } else if ($paymentMode === 'pos alrajhi') {
                $creditAccount = Account::where('code', '1200200')->first();
            } else if ($paymentMode === 'pos sab') {
                $creditAccount = Account::where('code', '1300300')->first();
            }


            if (!$salesReturnAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales return');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\SalesReturnPayment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Sales return processed',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Sales Revenue (reducing revenue)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salesReturnAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash or Receivable
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 2,
            ]);

            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesReturnEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public function createSalesReturnCreditEntry(array $data): ?JournalEntry
    {

        try {
            $salesReturnAccount = $this->getAccountByCode(self::ACCOUNT_SALES_RETURNS);

            if ($data['customer_type'] != 'outside') {
                $customer = Customer::find($data['customer_id']);

                $creditAccount = Account::find($customer->account_id);
            } else {
                $customer = Employee::where('user_id', $data['customer_id'])->first();
                $receivableEmployeeAccount = EmployeesAccountTable::where('employee_id', $customer->id)->first();

                $creditAccount = Account::find($receivableEmployeeAccount->account_id);
            }

            if (!$salesReturnAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales return');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\SalesReturn',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Sales return processed',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Sales Revenue (reducing revenue)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salesReturnAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash or Receivable
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Sales return - ' . ($data['return_no'] ?? ''),
                'line_number' => 2,
            ]);

            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'sales_return', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createSalesReturnEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public function createPurchaseReturnCreditEntry(array $data): ?JournalEntry
    {

        Log::channel('purchase_return')->info('Purchase return data', ['data' => $data]);

        try {
            $purchaseReturnAccount = $this->getAccountByCode(self::ACCOUNT_PURCHASE_RETURNS);


            $supplier = Supplier::find($data['supplier_id']);
            Log::channel('purchase_return')->info('Purchase return supplier', ['supplier' => $supplier]);
            $debitAccount = Account::find($supplier->account_id);

            if (!$purchaseReturnAccount || !$debitAccount) {
                Log::warning('JournalEntryService: Required accounts not found for sales return');
                return null;
            }
            // $date = Carbon::parse($data['date'])->toDateString();
            $date = $data['date'];
            Log::channel('purchase_return')->info('Purchase return date', ['date' => $date]);
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();
            Log::channel('purchase_return')->info('Purchase return financialYear', ['financialYear' => $financialYear]);
            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\PurchaseReturn',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'],
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? 1,
                'posted_by' => $data['user_id'] ?? 1,
                'posted_at' => now(),
            ]);
            Log::channel('purchase_return')->info('Purchase return journal entry created', ['journalEntry' => $journalEntry]);
            // Debit Sales Revenue (reducing revenue)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $debitAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Purchase return - ' . ($data['return_no'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash or Receivable
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $purchaseReturnAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Purchase return - ' . ($data['return_no'] ?? ''),
                'line_number' => 2,
            ]);
            Log::channel('purchase_return')->info('Purchase return journal entry created', ['journalEntry' => $journalEntry]);
            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'purchase_return', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createPurchaseReturnEntry error: ' . $e->getMessage());
            return null;
        }
    }
    public function createExpenseEntry(array $data): ?JournalEntry
    {

        try {
            // Get the expense account - use specific account if provided, otherwise use operating expenses

            $expenseAccount = Account::find($data['account_id']);


            // $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            // $payment_mode = strtolower($data['payment_mode']);

            // if ($payment_mode === 'cash') {

            //     $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            // } else if ($payment_mode === 'transfer alrajhi') {

            //     $cashAccount = Account::where('code', '1100200')->first();
            // } else if ($payment_mode === 'transfer sab') {
            //     $cashAccount = Account::where('code', '1100300')->first();
            // } else if ($payment_mode === 'pos alrajhi') {
            //     $cashAccount = Account::where('code', '1100400')->first();
            // } else if ($payment_mode === 'pos sab') {
            //     $cashAccount = Account::where('code', '1100500')->first();
            // }

            $creditAccount = Account::find($data['cr_account_id']);

            if (!$expenseAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for expense entry');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Expense',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Expense recorded',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Expense Account (expense increases)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $expenseAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => $data['expense_description'] ?? 'Expense - ' . ($data['category_name'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash (money going out)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
                'line_number' => 2,
            ]);

            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'journal', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createExpenseEntry error: ' . $e->getMessage());
            return null;
        }
    }


    public function createVoucherEntry(array $data): ?JournalEntry
    {

        try {
            // ── Balance validation ──────────────────────────────────────────
            $balanceErrors = $this->validateCreditBalances($data['sectionCr']);

            if (!empty($balanceErrors)) {
                // Throw so the controller's catch block rolls back and returns the error
                throw new \Exception(implode(' | ', $balanceErrors));
            }
            // ──

            $date = Carbon::parse($data['date'])->toDateString();
           
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            $voucher = Voucher::where('voucher_no', $data['voucher_no']);

            $expenseVoucher = $voucher->where('voucher_type', 'payment')

                ->first();

            if ($expenseVoucher) {
                $journal_status = 'draft';
            } else {
                $journal_status = 'posted';
            }
            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Expense',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Expense recorded',
                'status' => $journal_status,
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);
            $lineNumber = 1; // Line numbers start at 1
            foreach ($data['sectionDr'] as  $drEntry) {

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $drEntry['account_id'],
                    'type' => 'debit',
                    'amount' => $drEntry['debit_amount'],
                    'description' => $drEntry['description'] ?? '',
                    'line_number' => $lineNumber,
                ]);
                $lineNumber = $lineNumber + 1;
            }

            foreach ($data['sectionCr'] as  $crEntry) {
                // Credit Expense Account (expense increases)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $crEntry['account_id'],
                    'type' => 'credit',
                    'amount' => $crEntry['credit_amount'],
                    'description' => $crEntry['description'] ?? '',
                    'line_number' => $lineNumber,
                ]);
                $lineNumber = $lineNumber + 1;
            }

            // Update account balances
            $this->updateAccountBalances($journalEntry);

            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createExpenseEntry error: ' . $e->getMessage());
            return null;
        }
    }


    public function createExpensePaymentEntry(array $data): ?JournalEntry
    {


        try {
            // Get the expense account - use specific account if provided, otherwise use operating expenses
            $expenseAccount = null;
            if (!empty($data['account_id'])) {
                $expenseAccount = Account::find($data['account_id']);
            } elseif (!empty($data['account_code'])) {
                $expenseAccount = $this->getAccountByCode($data['account_code']);
            }

            if (!$expenseAccount) {
                $expenseAccount = $this->getAccountByCode(self::ACCOUNT_OPERATING_EXPENSES);
            }

            // $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            $payment_mode = strtolower($data['payment_mode']);

            if ($payment_mode === 'cash') {

                $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($payment_mode === 'alrajhi') {

                $cashAccount = Account::where('code', '1200200')->first();
            } else if ($payment_mode === 'sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            }

            if (!$expenseAccount || !$cashAccount) {
                Log::warning('JournalEntryService: Required accounts not found for expense entry');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Expense',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Expense recorded',
                'status' => 'draft',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            $currentBalance = $cashAccount->getCurrentBalance();

            $payAmount = $data['amount'];

            // $cashUsed = min($currentBalance, $payAmount);

            $remaining = $payAmount - $currentBalance;
            if ($remaining > 0) {

                Log::warning(
                    'Insufficient balance for full expense payment. Remaining payable: ' . $remaining
                );
                return null;
            }
            if ($payAmount > 0) {
                // Debit Expense Account (expense increases)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $expenseAccount->id,
                    'type' => 'debit',
                    'amount' => $payAmount,
                    'description' => $data['expense_description'] ?? 'Expense - ' . ($data['category_name'] ?? ''),
                    'line_number' => 1,
                ]);


                // Credit Cash (money going out)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $cashAccount->id,
                    'type' => 'credit',
                    'amount' => $payAmount,
                    'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
                    'line_number' => 2,
                ]);
            }


            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createExpensePaymentEntry error: ' . $e->getMessage());
            return null;
        }
    }


    public function createPayrollAdjustmentEntry(array $data): ?JournalEntry
    {
        try {

            DB::beginTransaction();

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if (!$financialYear) {
                throw new \Exception('Financial year not found');
            }

            $salaryPayableAccount = Account::find($data['account_id']);

            if (!$salaryPayableAccount) {
                throw new \Exception('Salary payable account not found');
            }

            $employeeAdvanceAccount = Account::find($data['advance_account_id']);


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\PayrollAdjustment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Payroll Payment Adjustment',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            $lineNumber = 1;

            $salaryAmount = (float) $data['total_salary'];
            $advanceAmount = (float) ($data['advance_amount'] ?? 0);



            if ($advanceAmount > 0 && $employeeAdvanceAccount) {

                $adjustAmount = min(
                    $salaryAmount,
                    $advanceAmount
                );

                // Salary Payable Dr
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $salaryPayableAccount->id,
                    'type' => 'debit',
                    'amount' => $adjustAmount,
                    'description' => 'Advance adjusted against salary',
                    'line_number' => $lineNumber++,
                ]);

                // Employee Advance Cr
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employeeAdvanceAccount->id,
                    'type' => 'credit',
                    'amount' => $adjustAmount,
                    'description' => 'Advance adjustment',
                    'line_number' => $lineNumber++,
                ]);

                $salaryAmount -= $adjustAmount;
            }

            $this->updateAccountBalances($journalEntry);

            DB::commit();

            return $journalEntry;
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Payroll payment entry error: ' . $e->getMessage()
            );

            return null;
        }
    }
    public function createPayrollPaymentEntry(array $data): ?JournalEntry
    {
        try {

            DB::beginTransaction();

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if (!$financialYear) {
                throw new \Exception('Financial year not found');
            }

            $salaryPayableAccount = Account::find($data['account_id']);

            if (!$salaryPayableAccount) {
                throw new \Exception('Salary payable account not found');
            }

            $employeeAdvanceAccount = Account::find($data['advance_account_id']);



            $payment_mode = strtolower($data['payment_mode']);

            if ($payment_mode === 'cash') {

                $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } elseif (
                $payment_mode === 'transfer alrajhi' ||
                $payment_mode === 'pos alrajhi'
            ) {

                $cashAccount = Account::where('code', '1200200')->first();
            } elseif (
                $payment_mode === 'transfer sab' ||
                $payment_mode === 'pos sab'
            ) {

                $cashAccount = Account::where('code', '1300300')->first();
            } else {

                throw new \Exception('Invalid payment mode');
            }


            /*
        |--------------------------------------------------------------------------
        | CREATE JOURNAL ENTRY
        |--------------------------------------------------------------------------
        */

            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\PayrollPayment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Payroll Payment',
                'status' => 'draft',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            $lineNumber = 1;

            $salaryAmount = (float) $data['amount'];
            $advanceAmount = (float) ($data['advance_amount'] ?? 0);
            // $balanceAmount=$salaryAmount-$advanceAmount;


            if ($salaryAmount > 0) {

                $currentBalance = $cashAccount->getCurrentBalance();

                if ($currentBalance < $salaryAmount) {

                    DB::rollBack();

                    throw new \Exception(
                        'Insufficient balance. Available: '
                            . $currentBalance
                            . ', Required: '
                            . $salaryAmount
                    );
                }

                // Salary Payable Dr
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $salaryPayableAccount->id,
                    'type' => 'debit',
                    'amount' => $salaryAmount,
                    'description' => 'Salary payment',
                    'line_number' => $lineNumber++,
                ]);

                // Cash/Bank Cr
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $cashAccount->id,
                    'type' => 'credit',
                    'amount' => $salaryAmount,
                    'description' => 'Salary paid through ' . $data['payment_mode'],
                    'line_number' => $lineNumber++,
                ]);
            }

            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            DB::commit();

            return $journalEntry;
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Payroll payment entry error: ' . $e->getMessage()
            );

            return null;
        }
    }


    public function createCommissionEntry(array $data): ?JournalEntry
    {
        try {
            // $expenseAccount = Account::find($data['account_id']);
            // $creditAccount = Account::find($data['cr_account_id']);


            $expenseAccount = $this->getAccountByCode(self::COMMISSION_ACCOUNT);
            $creditAccount = Account::find($data['cr_account_id']);
            if (!$expenseAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for expense entry');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Commission',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['payroll_description'] ?? 'Commission recorded',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Expense Account (expense increases)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $expenseAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => $data['payroll_description'] ?? 'Commission - ' . ($data['category_name'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash (money going out)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
                'line_number' => 2,
            ]);


            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'journal', $data['reference_id']);

            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createCommissionEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public function createCommissionPaymentEntry(array $data): ?JournalEntry
    {


        try {
            // Get the expense account - use specific account if provided, otherwise use operating expenses
            $expenseAccount = null;
            if (!empty($data['account_id'])) {
                $expenseAccount = Account::find($data['account_id']);
            } elseif (!empty($data['account_code'])) {
                $expenseAccount = $this->getAccountByCode($data['account_code']);
            }

            if (!$expenseAccount) {
                $expenseAccount = $this->getAccountByCode(self::ACCOUNT_OPERATING_EXPENSES);
            }

            // $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            $payment_mode = strtolower($data['payment_mode']);

            if ($payment_mode === 'cash') {

                $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
            } else if ($payment_mode === 'transfer alrajhi') {

                $cashAccount = Account::where('code', '1200200')->first();
            } else if ($payment_mode === 'transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } else if ($payment_mode === 'pos alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } else if ($payment_mode === 'pos sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            }

            if (!$expenseAccount || !$cashAccount) {
                Log::warning('JournalEntryService: Required accounts not found for payroll payment entry');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\CommissionPayment',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? 'Commission Payment recorded',
                'status' => 'draft',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            $currentBalance = $cashAccount->getCurrentBalance();

            $payAmount = $data['amount'];

            $cashUsed = min($currentBalance, $payAmount);

            $remaining = $payAmount - $cashUsed;
            if ($cashUsed > 0) {
                // Debit Expense Account (expense increases)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $expenseAccount->id,
                    'type' => 'debit',
                    'amount' => $cashUsed,
                    'description' => 'Comission - ' . ($data['employee_name'] ?? '') . ' - ' . ($data['period'] ?? ''),
                    'line_number' => 1,
                ]);

                // Credit Cash (money going out)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $cashAccount->id,
                    'type' => 'credit',
                    'amount' => $cashUsed,
                    'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
                    'line_number' => 2,
                ]);
            }


            if ($remaining > 0) {

                Log::warning(
                    'Insufficient balance for payroll payment. Remaining payable: ' . $remaining
                );
            }
            // Update account balances
            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createCommissionEntry error: ' . $e->getMessage());
            return null;
        }
    }


    public function createOpeningBalanceEntry(array $data): ?JournalEntry
    {

        try {

            $date = Carbon::parse($data['date'])->toDateString();
            // $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
            //     ->whereDate('end_date', '>=', $date)
            //     ->first();

            // if (!$financialYear) {
            //     Log::warning('Financial year not found for opening balance');
            //     return null;
            // }

            $account = $data['account'];

            DB::beginTransaction();



            if ($account->reference_type == 'customer') {

                $debitAccount = $account->id;

                $creditAccount = Account::where('code', '5400')->first();
                $creditAccount = $creditAccount->id;

                $lineDescription = 'Opening balance for customer ' . $account->name;
            } else if ($account->reference_type == 'supplier') {

                $debitAccount = Account::where('code', '5400')->first();
                $debitAccount = $debitAccount->id;
                $creditAccount = $account->id;
                $lineDescription = 'Opening balance for supplier ' . $account->name;
            } else {
                $debitAccount = $account->id;
                $creditAccount = Account::where('code', '5100')->first();
                $creditAccount = $creditAccount->id;
                $lineDescription = 'Opening balance ' . $account->name;
            }

            $journalEntry = JournalEntry::create([
                'entry_date' => "2025-08-31",
                'financial_year_id' => 1,
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'],
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);
            // dd('sadfj');
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $debitAccount,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => $lineDescription,
                'line_number' => 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => $lineDescription,
                'line_number' => 2,
            ]);

            $this->updateAccountBalances($journalEntry);
            // $this->voucherEntry($journalEntry, 'contra',$data['reference_id']);
            DB::commit();
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createOpeningBalanceEntry error: ' . $e->getMessage());
            return null;
        }
    }


    public function createAdvancePaymentEntry(array $data): ?JournalEntry
    {

        try {

            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if (!$financialYear) {
                Log::warning('Financial year not found for opening balance');
                return null;
            }

            $advanceAccount = $data['account_id']; // Employee Advance

            $cashAccount = $data['payment_mode'];



            DB::beginTransaction();


            $journalEntry = JournalEntry::create([
                'entry_date' => $date,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'],
                'status' => 'draft',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $advanceAccount,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => $data['description'],
                'line_number' => 1,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => $data['description'],
                'line_number' => 2,
            ]);


            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'payment', $data['reference_id']);
            DB::commit();
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createOpeningBalanceEntry error: ' . $e->getMessage());
            return null;
        }
    }


    protected function getAccountByCode(string $code): ?Account
    {

        return Account::where('code', $code)->where('is_active', true)->first();
    }

    protected function getAccountByName(string $name): ?Account
    {
        return Account::where('name', $name)->where('is_active', true)->first();
    }

    public function updateOpeningBalanceEntry($data): ?JournalEntry
    {

        $this->updateAccountBalances($data);

        return $data;
    }
    // protected function updateAccountBalances(JournalEntry $journalEntry): void
    // {

    //     try {
    //         $financialYear = FinancialYear::find($journalEntry->financial_year_id);

    //         foreach ($journalEntry->lines as $line) {

    //             if ($line->account) {
    //                 $line->account->updateBalance(now()->toDateString(), $journalEntry->financial_year_id);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
    //     }
    // }


    protected function updateAccountBalances(JournalEntry $journalEntry): void
    {
        try {

            $accountIds = $journalEntry->lines
                ->pluck('account_id')
                ->filter()
                ->unique();

            foreach ($accountIds as $accountId) {

                $account = Account::find($accountId);

                if (!$account) {
                    continue;
                }

                $account->recalculateBalancesFromDate(
                    $journalEntry->entry_date,
                    $journalEntry->financial_year_id
                );
            }
        } catch (\Exception $e) {

            Log::error(
                'JournalEntryService updateAccountBalances error: '
                    . $e->getMessage()
            );
        }
    }
    public function genarateVoucherCode($voucher_type)
    {

        $auto_voucher_code = AutoIncrementMaster::where('auto_code', 'AI_VOUCHER_CODE')->first();
        if ($voucher_type == 'payment') {
            $auto_prefix = 'PV';
        } elseif ($voucher_type == 'receipt') {
            $auto_prefix = 'RV';
        } elseif ($voucher_type == 'journal') {
            $auto_prefix = 'JV';
        } else {
            $auto_prefix = 'VOU';
        }
        $currentValue = $auto_voucher_code->auto_increment_general_value;
        $auto_voucher_code->auto_increment_general_value = $currentValue + 1;
        $auto_voucher_code->save();

        return $auto_prefix . $currentValue;
    }

    /**
     * Create a reverse entry for a journal entry
     * Returns the reversal entry in draft status for review before posting
     *
     * @param JournalEntry $journalEntry The journal entry to reverse
     * @param string|null $description Custom description for the reversal
     * @param string|null $reversalDate Date for the reversal entry (defaults to today)
     * @return JournalEntry The created reversal entry in draft status
     * @throws \Exception If the entry is not in posted status
     */
    public function createReverseEntry(JournalEntry $journalEntry, ?string $description = null, ?string $reversalDate = null): JournalEntry
    {
        if ($journalEntry->status !== 'posted') {
            throw new \Exception('Only posted entries can be reversed. Current status: ' . $journalEntry->status);
        }

        try {
            // Use provided reversal date or default to today
            $reversalDate = $reversalDate ?? now()->toDateString();
            $financialYear = null;

            // Get financial year for reversal date (if enabled)
            if (
                config('accounting.enable_financial_year') &&
                config('accounting.auto_assign_financial_year')
            ) {
                $financialYearService = app(\App\Services\FinancialYearService::class);
                $fy = $financialYearService->getFinancialYearForDate($reversalDate);
                $financialYear = $fy ? $fy->id : null;
            }

            // Create the reversal entry in draft status
            $reversal = JournalEntry::create([
                'entry_date' => $reversalDate,
                // 'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear,
                'description' => $description ?? 'Reversal of ' . $journalEntry->entry_number,
                'notes' => 'Reversal of entry: ' . $journalEntry->entry_number,
                'status' => 'draft',
                'created_by' => Auth::id() ?? 1,
            ]);

            // Create reversed lines (debits become credits and vice versa)
            foreach ($journalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'type' => $line->type === 'debit' ? 'credit' : 'debit',
                    'amount' => $line->amount,
                    'description' => 'Reversal: ' . ($line->description ?? ''),
                    'line_number' => $line->line_number,
                ]);
            }
            $this->updateAccountBalances($reversal);
            return $reversal;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createReverseEntry error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create and immediately post a reverse entry
     *
     * @param JournalEntry $journalEntry The journal entry to reverse
     * @param string|null $description Custom description for the reversal
     * @param string|null $reversalDate Date for the reversal entry (defaults to today)
     * @return JournalEntry The created and posted reversal entry
     * @throws \Exception If the entry is not in posted status
     */
    public function reverseAndPostEntry(JournalEntry $journalEntry, ?string $description = null, ?string $reversalDate = null): JournalEntry
    {
        return DB::transaction(function () use ($journalEntry, $description, $reversalDate) {
            // Create the reversal entry (in draft status)
            $reversal = $this->createReverseEntry($journalEntry, $description, $reversalDate);

            // Post the reversal entry
            $reversal->post(Auth::id() ?? 1);

            // Mark original entry as reversed
            $journalEntry->update(['status' => 'reversed']);

            // Update account balances for the reversal
            $this->updateAccountBalances($reversal);

            return $reversal;
        });
    }

    private function voucherEntry(JournalEntry $journalEntry, String $voucher_type, String $reference_id = null, String $narration = NULL)
    {

        $journalEntryLineDebit = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
            ->where('type', 'debit')
            ->get();

        $journalEntryLineCredit = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
            ->where('type', 'credit')
            ->get();

        $voucher = Voucher::create([
            'voucher_no' => $this->genarateVoucherCode($voucher_type),
            'voucher_type' => $voucher_type,
            'voucher_date' => $journalEntry->entry_date,
            'journal_entry_id' => $journalEntry->id,
            'narration' => $narration ?? $journalEntry->description,
            'reference_id' => $reference_id
        ]);


        $total = 0;
        foreach ($journalEntryLineDebit as $drEntry) {
            VoucherEntry::create([
                'voucher_id' => $voucher->id,
                'account_id' => $drEntry['account_id'],
                'debit' => $drEntry['amount'],
                'credit' => 0,
                // 'remarks' => $drEntry['remarks'] ?? null,
            ]);
        }
        foreach ($journalEntryLineCredit as $crEntry) {
            VoucherEntry::create([
                'voucher_id' => $voucher->id,
                'account_id' => $crEntry['account_id'],
                'debit' => 0,
                'credit' => $crEntry['amount'],
                // 'remarks' => $crEntry['remarks'] ?? null,
            ]);
            $total += $crEntry['amount'];
        }
        $voucher->amount = $total;
        $voucher->save();
    }



    public function createPayrollEntry(array $data): ?JournalEntry
    {
        try {
            $expenseAccount = Account::find($data['account_id']);
            $creditAccount = Account::find($data['cr_account_id']);

            if (!$expenseAccount || !$creditAccount) {
                Log::warning('JournalEntryService: Required accounts not found for expense entry');
                return null;
            }
            $date = Carbon::parse($data['date'])->toDateString();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();


            $journalEntry = JournalEntry::create([
                // 'entry_date' => $date,
                'entry_date' => now()->toDateString(),
                'financial_year_id' => $financialYear->id,
                'reference_type' => $data['reference_type'] ?? 'App\\Models\\Payroll',
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['payroll_description'] ?? 'Payroll recorded',
                'status' => 'posted',
                'created_by' => $data['user_id'] ?? null,
                'posted_by' => $data['user_id'] ?? null,
                'posted_at' => now(),
            ]);

            // Debit Expense Account (expense increases)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $expenseAccount->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => $data['payroll_description'] ?? 'Payroll - ' . ($data['category_name'] ?? ''),
                'line_number' => 1,
            ]);

            // Credit Cash (money going out)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccount->id,
                'type' => 'credit',
                'amount' => $data['amount'],
                'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
                'line_number' => 2,
            ]);


            $this->updateAccountBalances($journalEntry);
            $this->voucherEntry($journalEntry, 'journal', $data['reference_id']);
            return $journalEntry;
        } catch (\Exception $e) {
            Log::error('JournalEntryService createExpenseEntry error: ' . $e->getMessage());
            return null;
        }
    }

    // public function editOrderPaymentEntry(JournalEntry $journalEntry): void
    // {
    //     $this->updateAccountBalances($journalEntry);
    // }
    // public function createReverseSalesPaymentEntry(array $data): ?JournalEntry
    // {

    //     try {

    //         // Customer Account
    //         // $customerAccount = Account::where('reference_id', $data['customer_id'])
    //         //     ->where('reference_type', 'customer')
    //         //     ->first();
    //         $customer = Customer::find($data['customer_id']);
    //         $customerAccount = Account::find($customer->account_id);
    //         if (!$customerAccount) {
    //             Log::warning('Customer account not found');
    //             return null;
    //         }

    //         $paymentMode = strtolower($data['payment_mode'] ?? 'cash');

    //         // Determine cash/bank account
    //         // if ($paymentMode === 'cash') {
    //         //     $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         // } elseif ($paymentMode === 'transfer alrajhi') {
    //         //     $cashAccount = Account::where('code', '11100')->first();
    //         // } elseif ($paymentMode === 'transfer sab') {
    //         //     $cashAccount = Account::where('code', '11200')->first();
    //         // } elseif ($paymentMode === 'pos alrajhi') {
    //         //     $cashAccount = Account::where('code', '11300')->first();
    //         // } elseif ($paymentMode === 'pos sab') {
    //         //     $cashAccount = Account::where('code', '11400')->first();
    //         // } else {
    //         //     $cashAccount = $this->getAccountByCode(self::ACCOUNT_BANK);
    //         // }

    //         if ($paymentMode === 'cash') {

    //             $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         } else if ($paymentMode === 'transfer alrajhi') {

    //             $cashAccount = Account::where('code', '1200200')->first();
    //         } else if ($paymentMode === 'transfer sab') {
    //             $cashAccount = Account::where('code', '1300300')->first();
    //         } else if ($paymentMode === 'pos alrajhi') {
    //             $cashAccount = Account::where('code', '1200200')->first();
    //         } else if ($paymentMode === 'pos sab') {
    //             $cashAccount = Account::where('code', '1300300')->first();
    //         }

    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();
    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'] ?? null,
    //             'description' => $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'] ?? null,
    //             'posted_by' => $data['user_id'] ?? null,
    //             'posted_at' => now(),
    //         ]);

    //         // CREDIT SALE (customer owes money)
    //         if ($paymentMode === 'credit') {

    //             $salesAccount = Account::where('id', 18)->first(); // your sales ledger

    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $customerAccount->id,
    //                 'type' => 'credit',
    //                 'amount' => $data['amount'],
    //                 'description' => 'Credit sale to customer',
    //                 'line_number' => 1,
    //             ]);

    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $salesAccount->id,
    //                 'type' => 'debit',
    //                 'amount' => $data['amount'],
    //                 'description' => 'Sales revenue',
    //                 'line_number' => 2,
    //             ]);
    //         }
    //         // CASH / BANK PAYMENT
    //         else {

    //             if (!$cashAccount) {
    //                 Log::warning('Cash/Bank account not found');
    //                 return null;
    //             }

    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $cashAccount->id,
    //                 'type' => 'credit',
    //                 'amount' => $data['amount'],
    //                 'description' => 'Cash received reversed',
    //                 'line_number' => 1,
    //             ]);

    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $customerAccount->id,
    //                 'type' => 'debit',
    //                 'amount' => $data['amount'],
    //                 'description' => 'Customer payment reversed',




    //                 'line_number' => 2,
    //             ]);
    //         }


    //         $this->updateAccountBalances($journalEntry);

    //         $customerAccount->opening_balance = $customerAccount->opening_balance - $data['amount'];
    //         $customerAccount->save();

    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService createReverseSalesPaymentEntry error: ' . $e->getMessage());
    //         return null;
    //     }
    // }


    // public function createPurchaseEntry(array $data): ?JournalEntry
    // {

    //     try {

    //         $purchaseAccount = $this->getAccountByCode(self::PURCHASE_ACCOUNT);

    //         // $taxAccount       = $this->getAccountByCode(self::ACCOUNT_INPUT_TAX);


    //         // $inventoryAccount = $this->getAccountByCode(self::ACCOUNT_INVENTORY);
    //         $capitalAccount = Account::where('code', '5100')->first();
    //         // $payableAccount = $this->getAccountByName($data['supplier_name'] . '_' . $data['supplier_code']);

    //         // $payableAccount = Account::where('reference_id', $data['supplier_id'])
    //         //     ->where('reference_type', 'supplier')->first();

    //         $supplier = Supplier::find($data['supplier_id']);
    //         $payableAccount = Account::find($supplier->account_id);

    //         if (!$purchaseAccount ||  !$payableAccount) {
    //             Log::warning('JournalEntryService: Required accounts not found for purchase entry');
    //             return null;
    //         }
    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();
    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'],
    //             'description' => $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'],
    //             'posted_by' => $data['user_id'],
    //             'posted_at' => now(),
    //         ]);

    //         // Debit Inventory
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $purchaseAccount->id,
    //             'type' => 'debit',
    //             'amount' => $data['net_amount'],
    //             'line_number' => 1,
    //         ]);



    //         // Credit Supplier
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $payableAccount->id,
    //             'type' => 'credit',
    //             'amount' => $data['net_amount'],
    //             'line_number' => 2,
    //         ]);


    //         $this->updateAccountBalances($journalEntry);

    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return null;
    //     }
    // }

    // public function createPurchasePaymentEntry(array $data): ?JournalEntry
    // {
    //     try {


    //         $payableAccount = Account::where('reference_id', $data['supplier_id'])
    //             ->where('reference_type', 'supplier')->first();


    //         $paymentMode = strtolower($data['payment_mode'] ?? 'cash');
    //         if ($paymentMode === 'cash') {

    //             $paymentAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         } else if ($paymentMode === 'transfer alrajhi') {

    //             $paymentAccount = Account::where('code', '1200')->first();
    //         } else if ($paymentMode === 'transfer sab') {
    //             $paymentAccount = Account::where('code', '1300')->first();
    //         } else if ($paymentMode === 'pos alrajhi') {
    //             $paymentAccount = Account::where('code', '1200')->first();
    //         } else if ($paymentMode === 'pos sab') {
    //             $paymentAccount = Account::where('code', '1300')->first();
    //         }

    //         if (!$payableAccount || !$paymentAccount) {
    //             return null;
    //         }
    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();
    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => $data['date'],
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'],
    //             'description' => $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'],
    //             'posted_by' => $data['user_id'],
    //             'posted_at' => now(),
    //         ]);

    //         // Debit Supplier
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $payableAccount->id,
    //             'type' => 'debit',
    //             'amount' => $data['amount'],
    //             'line_number' => 1,
    //         ]);

    //         // Credit Cash/Bank
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $paymentAccount->id,
    //             'type' => 'credit',
    //             'amount' => $data['amount'],
    //             'line_number' => 2,
    //         ]);

    //         $this->updateAccountBalances($journalEntry);
    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return null;
    //     }
    // }

    // public function createSalesInventoryEntry(array $data): ?JournalEntry
    // {
    //     try {
    //         $purchaseAccount = $this->getAccountByCode(self::PURCHASE_ACCOUNT);
    //         // $inventoryAccount = $this->getAccountByCode(self::ACCOUNT_INVENTORY);
    //         $cogsAccount = $this->getAccountByCode(self::ACCOUNT_COGS);

    //         if (!$purchaseAccount || !$cogsAccount) {
    //             Log::warning('COGS Entry: Required accounts missing');
    //             return null;
    //         }

    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();

    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'],
    //             'description' => 'COGS - ' . $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'],
    //             'posted_by' => $data['user_id'],
    //             'posted_at' => now(),
    //         ]);


    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $cogsAccount->id,
    //             'type' => 'debit',
    //             'amount' => $data['amount'],
    //             'line_number' => 1,
    //         ]);


    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $purchaseAccount->id,
    //             'type' => 'credit',
    //             'amount' => $data['amount'],
    //             'line_number' => 2,
    //         ]);

    //         $this->updateAccountBalances($journalEntry);

    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return null;
    //     }
    // }
    // public function createCustomerCreditPaymentEntry(array $data): ?JournalEntry
    // {
    //     $data['description'] = $data['description'] ?? 'Credit payment received from customer';
    //     return $this->createSalesPaymentEntry($data);
    // }

   
    // public function createPayrollPaymentEntry(array $data): ?JournalEntry
    // {

    //     try {
    //         // Get the expense account - use specific account if provided, otherwise use operating expenses
    //         $expenseAccount = null;
    //         if (!empty($data['account_id'])) {
    //             $expenseAccount = Account::find($data['account_id']);
    //         } elseif (!empty($data['account_code'])) {
    //             $expenseAccount = $this->getAccountByCode($data['account_code']);
    //         }

    //         if (!$expenseAccount) {
    //             $expenseAccount = $this->getAccountByCode(self::ACCOUNT_OPERATING_EXPENSES);
    //         }

    //         // $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         $payment_mode = strtolower($data['payment_mode']);

    //         if ($payment_mode === 'cash') {

    //             $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         } else if ($payment_mode === 'transfer alrajhi') {

    //             $cashAccount = Account::where('code', '1200')->first();
    //         } else if ($payment_mode === 'transfer sab') {
    //             $cashAccount = Account::where('code', '1300')->first();
    //         } else if ($payment_mode === 'pos alrajhi') {
    //             $cashAccount = Account::where('code', '1200')->first();
    //         } else if ($payment_mode === 'pos sab') {
    //             $cashAccount = Account::where('code', '1300')->first();
    //         }

    //         if (!$expenseAccount || !$cashAccount) {
    //             Log::warning('JournalEntryService: Required accounts not found for payroll payment entry');
    //             return null;
    //         }
    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();


    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'] ?? 'App\\Models\\PayrollPayment',
    //             'reference_id' => $data['reference_id'] ?? null,
    //             'description' => $data['description'] ?? 'Payroll Payment recorded',
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'] ?? null,
    //             'posted_by' => $data['user_id'] ?? null,
    //             'posted_at' => now(),
    //         ]);

    //         $currentBalance = $cashAccount->getCurrentBalance();

    //         $payAmount = $data['amount'];

    //         $cashUsed = min($currentBalance, $payAmount);

    //         $remaining = $payAmount - $cashUsed;
    //         if ($cashUsed > 0) {
    //             // Debit Expense Account (expense increases)
    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $expenseAccount->id,
    //                 'type' => 'debit',
    //                 'amount' => $cashUsed,
    //                 'description' => 'Salary - ' . ($data['employee_name'] ?? '') . ' - ' . ($data['period'] ?? ''),
    //                 'line_number' => 1,
    //             ]);

    //             // Credit Cash (money going out)
    //             JournalEntryLine::create([
    //                 'journal_entry_id' => $journalEntry->id,
    //                 'account_id' => $cashAccount->id,
    //                 'type' => 'credit',
    //                 'amount' => $cashUsed,
    //                 'description' => 'Cash paid - ' . ($data['payment_mode'] ?? 'Cash'),
    //                 'line_number' => 2,
    //             ]);
    //         }
    //         if ($remaining > 0) {

    //             Log::warning(
    //                 'Insufficient balance for payroll payment. Remaining payable: ' . $remaining
    //             );
    //         }
    //         // Update account balances
    //         $this->updateAccountBalances($journalEntry);


    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService createExpensePaymentEntry error: ' . $e->getMessage());
    //         return null;
    //     }
    // }

    // public function createSalesCommissionPaymentEntry(array $data): ?JournalEntry
    // {

    //     try {

    //         $commissionAccount = Account::where('reference_id', $data['employee_id'])
    //             ->where('reference_type', 'commission')
    //             ->first();


    //         $paymentMode = strtolower($data['payment_mode'] ?? 'cash');

    //         // Determine cash/bank account
    //         // if ($paymentMode === 'cash') {
    //         //     $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         // } elseif ($paymentMode === 'transfer alrajhi') {
    //         //     $cashAccount = Account::where('code', '11100')->first();
    //         // } elseif ($paymentMode === 'transfer sab') {
    //         //     $cashAccount = Account::where('code', '11200')->first();
    //         // } elseif ($paymentMode === 'pos alrajhi') {
    //         //     $cashAccount = Account::where('code', '11300')->first();
    //         // } elseif ($paymentMode === 'pos sab') {
    //         //     $cashAccount = Account::where('code', '11400')->first();
    //         // } else {
    //         //     $cashAccount = $this->getAccountByCode(self::ACCOUNT_BANK);
    //         // }

    //         if ($paymentMode === 'cash') {

    //             $cashAccount = $this->getAccountByCode(self::ACCOUNT_CASH);
    //         } else if ($paymentMode === 'transfer alrajhi') {

    //             $cashAccount = Account::where('code', '1200200')->first();
    //         } else if ($paymentMode === 'transfer sab') {
    //             $cashAccount = Account::where('code', '1300300')->first();
    //         } else if ($paymentMode === 'pos alrajhi') {
    //             $cashAccount = Account::where('code', '1200200')->first();
    //         } else if ($paymentMode === 'pos sab') {
    //             $cashAccount = Account::where('code', '1300300')->first();
    //         }

    //         if (!$commissionAccount || !$cashAccount) {
    //             Log::warning('JournalEntryService: Required accounts not found for payroll entry');
    //             return null;
    //         }
    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();
    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'] ?? 'App\\Models\\Commission',
    //             'reference_id' => $data['reference_id'] ?? null,
    //             'description' => $data['description'] ?? 'Commission payment',
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'] ?? null,
    //             'posted_by' => $data['user_id'] ?? null,
    //             'posted_at' => now(),
    //         ]);

    //         // Debit Salaries and Wages (expense increases)
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $commissionAccount->id,
    //             'type' => 'debit',
    //             'amount' => $data['amount'],
    //             'description' => 'Commission - ' . ($data['employee_name'] ?? '') . ' - ' . ($data['period'] ?? ''),
    //             'line_number' => 1,
    //         ]);

    //         // Credit Cash (money going out)
    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $cashAccount->id,
    //             'type' => 'credit',
    //             'amount' => $data['amount'],
    //             'description' => 'Commission paid via ' . ($data['payment_mode'] ?? 'Cash'),
    //             'line_number' => 2,
    //         ]);

    //         // Update account balances
    //         $this->updateAccountBalances($journalEntry);

    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService createCommissionEntry error: ' . $e->getMessage());
    //         return null;
    //     }
    // }
    // public function createOpeningStockEntry(array $data): ?JournalEntry
    // {

    //     try {

    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();

    //         if (!$financialYear) {
    //             Log::warning('Financial year not found for opening balance');
    //             return null;
    //         }

    //         $account = $this->getAccountByCode(self::STOCK_IN_HAND);



    //         DB::beginTransaction();


    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'] ?? null,
    //             'description' => $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'] ?? null,
    //             'posted_by' => $data['user_id'] ?? null,
    //             'posted_at' => now(),
    //         ]);


    //         $creditAccount = Account::where('name', 'SAB')->first();
    //         $creditAccount = $creditAccount->id;
    //         $lineDescription = 'Opening Entry ' . $account->name;

    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $account->id,
    //             'type' => 'debit',
    //             'amount' => $data['amount'],
    //             'description' => $lineDescription,
    //             'line_number' => 1,
    //         ]);

    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $creditAccount,
    //             'type' => 'credit',
    //             'amount' => $data['amount'],
    //             'description' => $lineDescription,
    //             'line_number' => 2,
    //         ]);


    //         $this->updateAccountBalances($journalEntry);
    //         DB::commit();
    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService createOpeningStockEntry error: ' . $e->getMessage());
    //         return null;
    //     }
    // }


    // public function createAdvanceEntry(array $data): ?JournalEntry
    // {

    //     try {

    //         $financialYear = FinancialYear::where('start_date', '<=', $data['date'])
    //             ->where('end_date', '>=', $data['date'])
    //             ->first();

    //         if (!$financialYear) {
    //             Log::warning('Financial year not found for opening balance');
    //             return null;
    //         }

    //         $advanceAccount = $data['account_id']; // Employee Advance

    //         $cashAccount = $data['payment_mode'];



    //         DB::beginTransaction();


    //         $journalEntry = JournalEntry::create([
    //             'entry_date' => now()->toDateString(),
    //             'financial_year_id' => $financialYear->id,
    //             'reference_type' => $data['reference_type'],
    //             'reference_id' => $data['reference_id'] ?? null,
    //             'description' => $data['description'],
    //             'status' => 'posted',
    //             'created_by' => $data['user_id'] ?? null,
    //             'posted_by' => $data['user_id'] ?? null,
    //             'posted_at' => now(),
    //         ]);

    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $advanceAccount,
    //             'type' => 'debit',
    //             'amount' => $data['amount'],
    //             'description' => $data['description'],
    //             'line_number' => 1,
    //         ]);

    //         JournalEntryLine::create([
    //             'journal_entry_id' => $journalEntry->id,
    //             'account_id' => $cashAccount,
    //             'type' => 'credit',
    //             'amount' => $data['amount'],
    //             'description' => $data['description'],
    //             'line_number' => 2,
    //         ]);


    //         $this->updateAccountBalances($journalEntry);
    //         DB::commit();
    //         return $journalEntry;
    //     } catch (\Exception $e) {
    //         Log::error('JournalEntryService createOpeningBalanceEntry error: ' . $e->getMessage());
    //         return null;
    //     }
    // }


    /**
     * Validate that all credit-side accounts have sufficient balance.
     * Returns an array of error messages, or empty array if all pass.
     */
    public function validateCreditBalances(array $sectionCr): array
    {
        $errors = [];

        foreach ($sectionCr as $index => $crEntry) {
            $account = Account::find($crEntry['account_id']);

            if (!$account) {
                $errors[] = "Credit account at row " . ($index + 1) . " not found.";
                continue;
            }

            // Only validate balance for asset-type accounts (cash, bank, etc.)
            // Skip liability/equity/income accounts — they can go negative legitimately
            $accountType = $account->accountType?->name ?? '';

            $skipTypes = ['liability', 'equity', 'income', 'revenue', 'capital'];

            if (in_array(strtolower($accountType), $skipTypes)) {
                continue;
            }

            $currentBalance = $account->getCurrentBalance();
            $creditAmount   = (float) $crEntry['credit_amount'];

            if ($currentBalance < $creditAmount) {
                $errors[] = "Insufficient balance in account '{$account->name}' "
                    . "(Available: " . number_format($currentBalance, 2)
                    . ", Required: " . number_format($creditAmount, 2) . ").";
            }
        }

        return $errors;
    }
}
