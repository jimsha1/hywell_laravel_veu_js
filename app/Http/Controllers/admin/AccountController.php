<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\AutoIncrementMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index()
    {
        return view('admin.pages.accounts_master.accounts');
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {

        $request->validate([

            'name' => 'required',
            'account_type_id' => 'required',
            'status' => 'required|string',

        ]);

        DB::beginTransaction();
        try {

            // if ($request->reference_type) {
            //     if ($request->reference_type == 'customer') {
            //         $next = AutoIncrementMaster::where('auto_code', 'AI_CUSTOMER_LEDGER_CODE')->first();

            //         $nextCode = 'C' . $next->auto_increment_general_value;
            //     } else if ($request->reference_type == 'employee' || $request->reference_type == 'commission' || $request->reference_type == 'advance') {

            //         $next = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_LEDGER_CODE')->first();
            //         $nextCode = 'E' . $next->auto_increment_general_value;
            //     } else if ($request->reference_type == 'supplier') {
            //         $next = AutoIncrementMaster::where('auto_code', 'AI_SUPPLIER_LEDGER_CODE')->first();

            //         $nextCode = 'S' . $next->auto_increment_general_value;
            //     }
            // } else {
            $next = AutoIncrementMaster::where('auto_code', 'AI_LEDGER_CODE')->first();

            $nextCode = 'A' . $next->auto_increment_general_value;
            // }
            $next->auto_increment_general_value = $next->auto_increment_general_value + 1;
            $next->save();

            // if ($request->parent_id) {
            //     $firstAccount = Account::where('id', $request->parent_id)->withTrashed()->orderBy('id', 'desc')->first();

            //     $lastChildAccount = Account::where('parent_id', $request->parent_id)->withTrashed()->orderBy('id', 'desc')->first();
            //     dd($lastChildAccount);
            //     if ($lastChildAccount) {
            //         $nextId = $lastChildAccount->code + 1;
            //     } else {
            //         $nextId = $firstAccount->code + 1;
            //     }

            //     // $nextId = $lastAccount->code + 1;

            // } else {
            //     $lastAccount = Account::where('parent_id', NULL)->withTrashed()->orderBy('id', 'desc')->first();
            //     $nextId = $lastAccount->code + 1000;
            // }

            $account = Account::create([
                'code' => $nextCode,
                'name' => $request->name,
                'account_type_id' => $request->account_type_id,
                'parent_id' => $request->parent_id,
                'description' => $request->description,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'created_by' => Auth::id(),
                'is_system' => false,
                'is_active' => 1, // Auto-approve expenses
            ]);



            DB::commit();
            return successResponse($account, 'Account created successfully', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    public function genarateAccountCode()
    {
        $auto_emp_code = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_CODE')->first();
        $currentValue = $auto_emp_code->auto_increment_general_value;
        $auto_emp_code->auto_increment_general_value = $currentValue + 1;
        $auto_emp_code->save();

        return $auto_emp_code->auto_prefix . $currentValue;
    }
    /**
     * Display the specified account.
     */
    public function show(Request $request)
    {
        $accounts = Account::with(['accountType', 'parent', 'children']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $accounts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('accountType', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('parent', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Account Type Filter
        if ($request->filled('account_type')) {
            $accounts->where('account_type_id', $request->account_type);
        }

        // Parent Account Filter
        if ($request->filled('parent_account')) {
            $accounts->where('parent_id', $request->parent_account);
        }

        // Status Filter
        if ($request->status !== null && $request->status !== '') {
            $accounts->where('is_active', $request->status);
        }

        $accounts = $accounts
            ->orderBy('code', 'ASC')
            ->paginate(config('settings.pagination.perpage.admin'));

        // Calculate current balance
        $collection = $accounts->getCollection()->transform(function ($acc) {
            $acc->current_balance = $acc->getCurrentBalance();
            $acc->reference_name = $acc->reference_name;

            return $acc;
        });

        // Balance Filter
        if ($request->filled('balance_from') || $request->filled('balance_to')) {

            $from = $request->balance_from;
            $to = $request->balance_to;

            $collection = $collection->filter(function ($acc) use ($from, $to) {

                if ($from !== null && $from !== '' && $acc->current_balance < $from) {
                    return false;
                }

                if ($to !== null && $to !== '' && $acc->current_balance > $to) {
                    return false;
                }

                return true;
            })->values();
        }

        $accounts->setCollection($collection);

        return response()->json([
            'success' => true,
            'data' => $accounts->items(),
            'pagination' => generatePaginationData($accounts),
        ]);
    }

    /**
     * Update the specified account.
     */
    // public function update(StoreAccountRequest $request, Account $account): JsonResponse
    // {

    //     dd();
    //     // Prevent editing system accounts
    //     if ($account->is_system && $request->has('code')) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Cannot modify system account code.',
    //         ], Response::HTTP_UNPROCESSABLE_ENTITY);
    //     }

    //     $account->update($request->validated());

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Account updated successfully.',
    //         'data' => $account->fresh()->load(['accountType', 'parent', 'children']),
    //     ]);
    // }

    public function update(Request $request)
    {

        $account = Account::with('journalEntryLines')->findOrFail($request->id);
        // Prevent editing system accounts
        if ($account->is_system) {

            return errorResponse(null, 'Cannot modify system accounts.', 500);
        } else {

            if ($request->has('account_type_id') && $account->journalEntryLines()->exists()) {
                return errorResponse(null, 'Account type cannot be changed because transactions already exist.', 500);
            }
        }
        $request->validate([
            'id' => 'required|exists:accounts,id',
            'name' => 'required',
            'account_type_id' => 'required|exists:account_types,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'status' => 'required',
        ]);
        // $account->update($request->validated());

        $account->update([
            'name' => $request->name,
            'account_type_id' => $request->account_type_id,
            'parent_id' => $request->parent_id,
            'is_active' => $request->status,

        ]);
        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'code' => 0,
            'data' => $account->fresh()->load(['accountType', 'parent', 'children']),
        ]);
    }
    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): JsonResponse
    {
        // Prevent deleting system accounts
        if ($account->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system accounts.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if account has children
        if ($account->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account that has child accounts. Please delete or reassign child accounts first.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if account has journal entries
        if ($account->journalEntryLines()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account that has journal entries. Please use soft delete instead.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Get account types for dropdown/select
     */
    public function getAccountTypes(): JsonResponse
    {
        $accountTypes = AccountType::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'normal_balance', 'is_system']);

        return response()->json([
            'success' => true,
            'data' => $accountTypes,
        ]);
    }

    /**
     * Get parent accounts for dropdown/select
     */
    public function getParentAccounts(Request $request): JsonResponse
    {
        $query = Account::active()
            ->whereNull('reference_type');

        // Exclude current account if editing
        if ($request->has('exclude_id')) {
            $query->where('id', '!=', $request->exclude_id);
        }

        // Filter by account type if provided
        if ($request->has('account_type_id')) {
            $query->where('account_type_id', $request->account_type_id);
        }

        $accounts = $query->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type_id']);

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }
}
