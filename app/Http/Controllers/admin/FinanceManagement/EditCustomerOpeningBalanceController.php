<?php

namespace App\Http\Controllers\admin\FinanceManagement;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OpeningBalance;
use App\Models\User;
use App\Repositories\interfaces\FinanceManagement\EditCustomerOpeningBalanceRepositoryInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class EditCustomerOpeningBalanceController extends Controller
{
    private $editCustomerOpeningBalanceRepository;
    public $log;

    public function __construct(EditCustomerOpeningBalanceRepositoryInterface $editCustomerOpeningBalanceRepository)
    {
        $this->editCustomerOpeningBalanceRepository = $editCustomerOpeningBalanceRepository;
        $this->log = Log::channel('edit_opening_balance');
    }

    public function index()
    {
        return view('admin.pages.admin_order.edit_opening_balance_payment');
    }

    public function getOpeningBalancePayments(Request $request, $platform = 'web')
    {
        try {
            $query = $this->editCustomerOpeningBalanceRepository->get_all($request);

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'payments' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Opening balance payments retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Opening balance payments retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function verifyPassword(Request $data)
    {
        try {
            $data->validate([
                'user_id' => 'required',
                'password' => 'required',
            ]);

            $userId = $data->user_id;
            $password = $data->password;
            $user = User::find($userId);
            if (!$user) {
                return errorResponse(null, 'User not found.', 404, 404);
            }

            if (!Hash::check($password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }

            return successResponse(null, 'Password verified successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function updateOpeningPayment(Request $request)
    {

        $log = $this->log;

        DB::beginTransaction();
        try {
            $log->info("[ payment_id = {$request->payment_id} ] | ---------------------------------------------------------------------------------------------");
            // Verify password
            $user = User::find($request->user_id);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return errorResponse(null, 'Invalid password.', 401, 401);
            }
            $log->info("[ payment_id = {$request->payment_id} ] | Password verified | req_user_id={$request->user_id} | ip={$request->ip()}");
            $log->info("[ payment_id = {$request->payment_id} ] | Opening balance update initiated | req_user_id={$request->user_id} | Request data | paid_amount={$request->paid_amount} | payment_mode={$request->payment_mode}");
            $payment = $this->editCustomerOpeningBalanceRepository->get_payment_by_id($request);

            if (!$payment) {
                $log->info("[ payment_id = {$request->payment_id} ] | Payment not found");
                return errorResponse(null, 'Payment not found.', 404, 404);
            }
            $log->info("[ payment_id = {$payment->id} ] | Payment record found");

            $oldPaidAmount = $payment->paid_amount;
            $newPaidAmount = (float)($request->paid_amount ?? 0);
            $difference = $newPaidAmount - $oldPaidAmount;
            $log->info("[ payment_id = {$payment->id} ] | Amount change | old_paid_amount={$oldPaidAmount} | new_paid_amount={$newPaidAmount} | difference={$difference}");

            $financialYear = FinancialYear::whereDate('start_date', '<=', $request->payment_date)
                ->orWhereDate('start_date', '<=', $payment->payment_date)
                ->whereDate('end_date', '>=', $request->payment_date)
                ->first();

            if (!$financialYear) {
                // throw new Exception('No financial year found for the selected date.');
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 0) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }

            // $financialYear1 = FinancialYear::whereDate('start_date', '<=', $payment->payment_date)
            //     ->whereDate('end_date', '>=', $payment->payment_date)
            //     ->first();

            // if (!$financialYear1) {
            //     // throw new Exception('No financial year found for the selected date.');
            //     DB::rollBack();
            //     return errorResponse(null, 'No financial year found for the selected date.', 100, 500);
            // }
            // if ($financialYear1->is_closed == 0) {
            //     DB::rollBack();
            //     return errorResponse(null, 'Financial year closed for the selected date.', 100, 500);
            // }
            // Update payment
            $payment->update([
                'paid_amount' => $newPaidAmount,
                'payment_mode' => $request->payment_mode ?? $payment->payment_mode,
                'payment_date' => $request->payment_date ?? $payment->payment_date,
                'description' => $request->description ?? $payment->description,
                'updated_by' => $request->user_id ?? 1
            ]);
            $log->info("[ payment_id = {$payment->id} ] | Payment updated successfully");

            // Update order totals (only if payment is linked to an order)
            if ($payment->order_id) {
                $order = Order::find($payment->order_id);
                if ($order) {
                    $log->info("[ payment_id = {$payment->id} ] | order balance before update | order_id={$order->id} | balance_amount={$order->balance_amount} | paid_amount={$order->paid_amount}");
                    $order->paid_amount = ($order->paid_amount - $oldPaidAmount) + $newPaidAmount;
                    $order->balance_amount = $order->total_amount - $order->paid_amount;
                    $order->payment_status = $order->balance_amount <= 0 ? 'Paid' : 'Partial';
                    $order->save();
                    $log->info("[ payment_id = {$payment->id} ] | order balance after updated | order_id={$order->id} | balance_amount={$order->balance_amount} | paid_amount={$order->paid_amount}");
                }
            }

            // Update customer balance if needed
            if ($payment->customer_id) {
                $customer = Customer::find($payment->customer_id);
                if ($customer) {
                    // For opening balance payments, adjust customer balance
                    // If we increase payment, customer balance decreases
                    // If we decrease payment, customer balance increases
                    $opening_balance = OpeningBalance::where('customer_id', $customer->id)->first();
                    if ($opening_balance) {
                        $log->info("[ payment_id = {$payment->id} ] | Opening balance before update | customer_id={$customer->id} | already_paid_amount={$opening_balance->paid_amount} ");
                        $new_opening_balance =  $opening_balance->paid_amount + $difference;
                        if ($new_opening_balance > $opening_balance->amount) {
                            $log->info("[ payment_id = {$payment->id} ] | Paid amount is greater than opening balance");
                            DB::rollBack();
                            return errorResponse(null, 'Paid amount is greater than opening balance', 404, 404);
                        }
                        $opening_balance->paid_amount =  $new_opening_balance;
                        $opening_balance->save();
                        $log->info("[ payment_id = {$payment->id} ] | Opening balance after updated | customer_id={$customer->id} | now_paid_amount={$opening_balance->paid_amount} ");
                    }
                    $log->info("[ payment_id = {$payment->id} ] | Customer balance before update | customer_id={$customer->id} | balance_amount={$customer->balance_amount}");
                    $customer->balance_amount -= $difference;
                    $customer->save();
                    $log->info("[ payment_id = {$payment->id} ] | Customer balance after updated | customer_id={$customer->id} | balance_amount={$customer->balance_amount}");
                }
            }
            $journalEntry = JournalEntry::where('reference_type', 'App\Models\OrderPayment')
                ->where('reference_id', $request->payment_id)->first();

            $journalEntryLine = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
                ->where('type', 'debit')->first();

            $paymentMode = strtolower($request->payment_mode ?? 'cash');

            // Determine cash/bank account
            if ($paymentMode === 'cash') {
                $cashAccount = Account::where('code', '1100100')->first();
            } elseif ($paymentMode === 'transfer alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'transfer sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            } elseif ($paymentMode === 'pos alrajhi') {
                $cashAccount = Account::where('code', '1200200')->first();
            } elseif ($paymentMode === 'pos sab') {
                $cashAccount = Account::where('code', '1300300')->first();
            }
            $journalEntryLine = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
                ->where('type', 'debit')->first();


            if (($journalEntryLine->account_id != $cashAccount->id) && ($journalEntryLine->amount != (float)($request->paid_amount))) {


                $debitAccount = AccountBalance::where('account_id', $journalEntryLine->account_id)->orderBy('id', 'desc')->first();

                // $debitTotal = $debitAccount->debit_balance - $journalEntryLine->amount;
                // $creditTotal = $debitAccount->credit_balance;
                // $netBalance = $debitAccount->net_balance - $journalEntryLine->amount;

                if ($journalEntryLine->amount < (float)($request->paid_amount)) {


                    $debitTotal = $debitAccount->debit_balance - (float)($request->paid_amount);
                    $creditTotal = $debitAccount->credit_balance;
                    $netBalance = $debitAccount->net_balance - (float)($request->paid_amount);
                }
                if ($journalEntryLine->amount > (float)($request->paid_amount)) {


                    $debitTotal = $debitAccount->debit_balance + (float)($request->paid_amount);
                    $creditTotal = $debitAccount->credit_balance;
                    $netBalance = $debitAccount->net_balance + (float)($request->paid_amount);
                }
                $oldaccount = AccountBalance::updateOrCreate(
                    [
                        'balance_date' => now()->toDateString(),
                        'account_id' => $journalEntryLine->account_id
                    ],
                    [
                        'debit_balance' => (float) $debitTotal,
                        'credit_balance' => (float) $creditTotal,
                        'net_balance' => $netBalance,
                    ]
                );


                $journalEntryLine->account_id = $cashAccount->id;
                $journalEntryLine->amount = (float)($request->paid_amount);
                $journalEntryLine->save();

                try {
                    foreach ($journalEntry->lines as $line) {
                        if ($line->account) {
                            $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
                }


                $journalEntryLineCredit = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
                    ->where('type', 'credit')->first();


                $journalEntryLineCredit->amount = (float)($request->paid_amount);
                $journalEntryLineCredit->save();
                try {
                    foreach ($journalEntry->lines as $line) {
                        if ($line->account) {
                            $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
                }
            } else if ($journalEntryLine->account_id != $cashAccount->id) {

                $debitAccount = AccountBalance::where('account_id', $journalEntryLine->account_id)->orderBy('id', 'desc')->first();

                $debitTotal = $debitAccount->debit_balance - (float)($request->paid_amount);

                $creditTotal = $debitAccount->credit_balance;
                $netBalance = $debitAccount->net_balance - (float)($request->paid_amount);

                $account = AccountBalance::updateOrCreate(
                    [
                        'balance_date' => now()->toDateString(),
                        'account_id' => $journalEntryLine->account_id
                    ],
                    [
                        'debit_balance' => (float) $debitTotal,
                        'credit_balance' => (float) $creditTotal,
                        'net_balance' => $netBalance,
                    ]
                );

                $journalEntryLine->account_id = $cashAccount->id;
                $journalEntryLine->amount = (float)($request->paid_amount);
                $journalEntryLine->save();


                try {
                    foreach ($journalEntry->lines as $line) {
                        if ($line->account) {
                            $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
                }
            } else if ($journalEntryLine->amount != (float)($request->paid_amount)) {



                $debitAccount = AccountBalance::where('account_id', $journalEntryLine->account_id)->orderBy('id', 'desc')->first();
                if ($journalEntryLine->amount < (float)($request->paid_amount)) {


                    $debitTotal = $debitAccount->debit_balance - (float)($request->paid_amount);
                    $creditTotal = $debitAccount->credit_balance;
                    $netBalance = $debitAccount->net_balance - (float)($request->paid_amount);
                }
                if ($journalEntryLine->amount > (float)($request->paid_amount)) {


                    $debitTotal = $debitAccount->debit_balance + (float)($request->paid_amount);
                    $creditTotal = $debitAccount->credit_balance;
                    $netBalance = $debitAccount->net_balance + (float)($request->paid_amount);
                }
                $account = AccountBalance::updateOrCreate(
                    [
                        'balance_date' => now()->toDateString(),
                        'account_id' => $journalEntryLine->account_id
                    ],
                    [
                        'debit_balance' => (float) $debitTotal,
                        'credit_balance' => (float) $creditTotal,
                        'net_balance' => $netBalance,
                    ]
                );

                $journalEntryLine->amount = (float)($request->paid_amount);
                $journalEntryLine->save();


                try {
                    foreach ($journalEntry->lines as $line) {
                        if ($line->account) {
                            $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
                }

                $journalEntryLineCredit = JournalEntryLine::where('journal_entry_id', $journalEntry->id)
                    ->where('type', 'credit')->first();

                $creditAccount = AccountBalance::where('account_id', $journalEntryLineCredit->account_id)->orderBy('id', 'desc')->first();

                $debitTotal = $creditAccount->debit_balance;
                $creditTotal = $creditAccount->credit_balance - (float)($request->paid_amount);
                $netBalance = $creditAccount->net_balance - (float)($request->paid_amount);

                $account = AccountBalance::updateOrCreate(
                    [
                        'balance_date' => now()->toDateString(),
                        'account_id' => $journalEntryLineCredit->account_id
                    ],
                    [
                        'debit_balance' => (float) $debitTotal,
                        'credit_balance' => (float) $creditTotal,
                        'net_balance' => $netBalance,
                    ]
                );

                $journalEntryLineCredit->amount = (float)($request->paid_amount);
                $journalEntryLineCredit->save();
                try {
                    foreach ($journalEntry->lines as $line) {
                        if ($line->account) {
                            $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('JournalEntryService updateAccountBalances error: ' . $e->getMessage());
                }
            }
            if ($journalEntry->entry_date != $request->payment_date) {

                $journalEntry->entry_date = $request->payment_date;
                $journalEntry->save();
            }





            DB::commit();
            $log->info("[ payment_id = {$payment->id} ] | DB transaction committed successfully");
            return successResponse($payment->fresh(['order', 'customer', 'user']), 'Payment updated successfully.', 0, 200);
        } catch (Exception $e) {
            $log->error("[ payment_id = {$request->payment_id} ] | Opening balance update failed | user_id={$request->user_id} | error={$e->getMessage()}");
            DB::rollBack();
            return errorResponse(null, 'Something went wrong. Please contact admin.' . $e, 100, 500);
        }
    }
}
