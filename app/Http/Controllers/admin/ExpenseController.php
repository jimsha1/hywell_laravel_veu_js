<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AutoIncrementMaster;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\JournalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nette\Schema\Expect;

class ExpenseController extends Controller
{
    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Display the expense management page
     */
    public function index()
    {
        return view('admin.pages.expenses.index');
    }

    /**
     * Get list of expenses (API)
     */
    public function list(Request $request)
    {

        try {
            $query = Expense::with(['account', 'cr_account', 'creator']);

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('expense_no', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->start_date && $request->end_date) {
                $query->dateRange($request->start_date, $request->end_date);
            }


            $total_expenses = $query->get()->sum('amount');
            $expenses = $query->orderBy('expense_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);


            return successResponse([
                'expenses' => $expenses->items(),
                'total_expenses' => $total_expenses,
                'pagination' => generatePaginationData($expenses),
            ], 'Expenses retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Store a new expense
     */
    public function store(Request $request)
    {
        $request->validate([

            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string',
            'description' => 'nullable|string',

        ]);

        DB::beginTransaction();
        try {



            $expense = Expense::create([
                'expense_no' => $this->genarateExpenseCode(),
                'expense_type' => $request->expense_type,
                'account_id' => $request->dr_account_id,
                'cr_account_id' => $request->cr_account_id,
                'expense_date' => $request->expense_date,
                'amount' => $request->amount,
                'payment_mode' => $request->payment_mode,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'status' => 'pending', // Auto-approve expenses
            ]);

            $journalEntry = $this->journalEntryService->createExpenseEntry([
                'date' => $expense->expense_date->toDateString(),
                'reference_type' => 'App\\Models\\Expense',
                'reference_id' => $expense->id,
                'description' => 'Expense Payment: ' . $expense->expense_no . ' - ' . $expense->id,
                'amount' => $expense->amount,
                'user_id' => Auth::id(),
                'account_id' => $expense->account_id,
                'cr_account_id' => $expense->cr_account_id,
                'expense_no' =>  $expense->expense_no,
                'expense_description' => $expense->description ?? $expense->expense_no,
                // 'payment_mode' => $expense->payment_mode,
            ]);
            if ($journalEntry) {
                $expense->update(['journal_entry_id' => $journalEntry->id]);
                $expense->save();
                DB::commit();
                return successResponse($expense->load('category'), 'Expense created successfully with journal entry', 0, 200);
            } else {
                DB::rollBack();
                return errorResponse(null, 'Failed to create journal entry for the expense', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    public function updateExpenses(Request $request)
    {


        DB::beginTransaction();
        try {

            $expense = Expense::findOrFail($request->id);


            $expense->account_id = $request->account_id;
            $expense->expense_date = $request->expense_date;
            $expense->amount = $request->amount;
            $expense->payment_mode = $request->payment_mode;
            $expense->description = $request->description;

            $expense->save();
            DB::commit();
            return successResponse($expense->load('category'), 'Expense created successfully with journal entry', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }


    // public function updateExpenseStatus(Request $request)
    // {

    //     $request->validate([
    //         'expense_id' => 'required|exists:expenses,id',
    //         'updated_status' => 'required|string|in:approved,rejected',
    //     ]);
    //     try {

    //         $expense = Expense::where('id', $request->expense_id)->first();
    //         if ($expense->status != 'pending') {
    //             return errorResponse(null, 'Only pending expenses can be updated', 400);
    //         }
    //         if ($request->updated_status == 'rejected') {
    //             $journalEntry = JournalEntry::where('id', $expense->journal_entry_id)->first();

    //             $this->journalEntryService->createReverseEntry($journalEntry);
    //             $expense->status = 'rejected';
    //             $expense->save();
    //             return successResponse($expense, 'Expense Status updated successfully', 0, 200);
    //         } else {

    //             // Create Journal Entry for the expense
    //             $journalEntry = $this->journalEntryService->createExpensePaymentEntry([
    //                 'date' => $expense->expense_date->toDateString(),
    //                 'reference_type' => 'App\\Models\\ExpensePayment',
    //                 'reference_id' => $expense->id,
    //                 'description' => 'Expense: ' . $expense->expense_no . ' - ' . $expense->id,
    //                 'amount' => $expense->amount,
    //                 'user_id' => Auth::id(),
    //                 'account_id' => $expense->cr_account_id,
    //                 'expense_no' =>  $expense->expense_no,
    //                 'expense_description' => $expense->description ?? $expense->expense_no,
    //                 'payment_mode' => $expense->payment_mode,
    //             ]);


    //             if ($journalEntry) {
    //                 $expense->status = 'approved';
    //                 $expense->save();

    //                 return successResponse($expense, 'Expense Status updated successfully', 0, 200);
    //             } else {
    //                 return errorResponse(null, 'Failed to create journal entry for the expense', 500);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 500);
    //     }
    // }


    public function updateExpenseStatus(Request $request)
    {
        
        $request->validate([
            'voucher_id'     => 'required|exists:vouchers,id',
            'updated_status' => 'required|string|in:approved,rejected',
        ]);

        try {

            $voucher = Voucher::findOrFail($request->voucher_id);

            $journalEntry = JournalEntry::with('lines')
                ->findOrFail($voucher->journal_entry_id);

            if ($journalEntry->status != 'draft') {
                return errorResponse(
                    null,
                    'Only pending vouchers can be updated',
                    400
                );
            }

            // Reject Voucher
            if ($request->updated_status == 'rejected') {

                $voucher->voucher_status = 'rejected';
                $voucher->save();

                $purchasePayment = PurchasePayment::where('journal_entry_id', $voucher->journal_entry_id)->first();
                if ($purchasePayment) {
                    $purchase = Purchase::where('id', $purchasePayment->purchase_id)->first();
                    $purchase->payment_total -= $purchasePayment?->amount_taken;
                    $purchase->balance_amount += $purchasePayment?->amount_taken;
                    $purchase->payment_status = 'pending';
                    $purchase->save();
                    $supplier = Supplier::where('id', $purchase->supplier_id)->first();
                    $supplier->balance += $purchasePayment?->amount_taken;
                    $supplier->save();
                    $purchasePayment?->delete();
                } 


                return successResponse(
                    $voucher,
                    'Expense Status updated successfully',
                    0,
                    200
                );
            }

            // ==========================
            // BALANCE VALIDATION
            // ==========================

            $creditLine = $journalEntry->lines
                ->where('type', 'credit')
                ->first();

            if (!$creditLine) {
                return errorResponse(
                    null,
                    'Credit account not found in journal entry',
                    400
                );
            }

            $latestBalance = AccountBalance::where(
                'account_id',
                $creditLine->account_id
            )
                ->orderBy('balance_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            // $availableBalance = $latestBalance?->net_balance ?? 0;

            // if ($availableBalance < $creditLine->amount) {

            //     return errorResponse(
            //         null,
            //         'Insufficient balance. Available balance: ' .
            //             number_format($availableBalance, 2),
            //         400
            //     );
            // }

            // ==========================
            // APPROVE VOUCHER
            // ==========================

            $journalEntry->status = 'posted';
            // $journalEntry->posted_by = auth()->id();
            // $journalEntry->posted_at = now();
            $journalEntry->save();

            $this->updateAccountBalances($journalEntry);

            $voucher->voucher_status = 'approved';
            $voucher->save();

            return successResponse(
                $voucher,
                'Expense Status updated successfully',
                0,
                200
            );
        } catch (\Exception $e) {

            return errorResponse(
                null,
                $e->getMessage(),
                500
            );
        }
    }
    protected function updateAccountBalances(JournalEntry $journalEntry): void
    {

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

    public function payExpense(Request $request)
    {


        $request->validate([
            'id' => 'required|exists:expenses,id',
            'payment_mode' => 'required|string',
        ]);
        try {

            $expense = Expense::where('id', $request->id)->first();


            //Create Journal Entry for the expense
            $journalEntry = $this->journalEntryService->createExpensePaymentEntry([
                'date' => $expense->expense_date->toDateString(),
                'reference_type' => 'App\\Models\\ExpensePayment',
                'reference_id' => $expense->id,
                'description' => 'Expense: ' . $expense->expense_no . ' - ' . $expense->id,
                'amount' => $expense->amount,
                'user_id' => Auth::id(),
                'account_id' => $expense->cr_account_id,
                'expense_no' =>  $expense->expense_no,
                'expense_description' => $expense->description ?? $expense->expense_no,
                'payment_mode' => $request->payment_mode,
            ]);

            if ($journalEntry) {
                $expense->update([
                    'journal_entry_id' => $journalEntry->id,
                    'payment_mode' => $request->payment_mode,
                    'status' => 'paid',

                ]);
                return successResponse($expense, 'Expense Journal updated successfully', 0, 200);
            } else {
                return errorResponse(null, 'Failed to create journal entry for the expense payment', 500);
            }
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    public function genarateExpenseCode()
    {
        $auto_exp_code = AutoIncrementMaster::where('auto_code', 'AI_EXPENSE_CODE')->first();
        $currentValue = $auto_exp_code->auto_increment_general_value;
        $auto_exp_code->auto_increment_general_value = $currentValue + 1;
        $auto_exp_code->save();

        return $auto_exp_code->auto_prefix . $currentValue;
    }

    /**
     * Get a single expense
     */
    public function show($id)
    {
        try {
            $expense = Expense::with(['category', 'account', 'creator', 'approver', 'journalEntry.lines'])
                ->findOrFail($id);
            return successResponse($expense, 'Expense retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Delete an expense
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $expense = Expense::findOrFail($id);

            // If there's a journal entry, reverse it
            if ($expense->journal_entry_id && $expense->journalEntry) {
                // Create reversal entry
                $expense->journalEntry->reverse('Expense deleted: ' . $expense->expense_no);
            }

            $expense->delete();

            DB::commit();
            return successResponse(null, 'Expense deleted successfully', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get expense categories
     */
    public function getCategories()
    {
        try {
            $categories = ExpenseCategory::with('account')->active()->orderBy('name')->get();
            return successResponse($categories, 'Categories retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Store a new category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:expense_categories,code',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        try {
            $category = ExpenseCategory::create([
                'name' => $request->name,
                'code' => $request->code,
                'account_id' => $request->account_id,
                'description' => $request->description,
                'is_active' => true,
            ]);

            return successResponse($category, 'Category created successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get expense summary/report
     */
    public function getSummary(Request $request)
    {
        try {
            $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
            $endDate = $request->end_date ?? now()->toDateString();

            // Total expenses by category
            $byCategory = Expense::approved()
                ->dateRange($startDate, $endDate)
                ->select('expense_category_id', DB::raw('SUM(amount) as total'))
                ->groupBy('expense_category_id')
                ->with('category')
                ->get();

            // Total expenses
            $totalExpenses = Expense::approved()
                ->dateRange($startDate, $endDate)
                ->sum('amount');

            // By payment mode
            $byPaymentMode = Expense::approved()
                ->dateRange($startDate, $endDate)
                ->select('payment_mode', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_mode')
                ->get();

            return successResponse([
                'total_expenses' => $totalExpenses,
                'by_category' => $byCategory,
                'by_payment_mode' => $byPaymentMode,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ], 'Expense summary retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get accounts for expense categories
     */
    public function getExpenseAccounts()
    {
        try {
            // Get expense type accounts
            $accounts = Account::whereHas('accountType', function ($q) {
                $q->whereIn('code', ['EXPENSE']);
            })->active()->orderBy('code')->get(['id', 'code', 'name']);

            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    public function getLiabilityAccounts()
    {
        try {
            // Get expense type accounts
            $accounts = Account::whereHas('accountType', function ($q) {
                $q->whereIn('code', ['LIABILITY']);
            })->active()->orderBy('code')->get(['id', 'code', 'name']);

            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
}
