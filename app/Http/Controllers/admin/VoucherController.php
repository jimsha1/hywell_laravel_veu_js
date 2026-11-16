<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\JournalEntry;

use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\JournalEntryService;
use App\Services\VoucherService;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    protected JournalEntryService $journalEntryService;
    protected VoucherService $voucherService;

    public function __construct(JournalEntryService $journalEntryService, VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
        $this->journalEntryService = $journalEntryService;
    }
    public function index()
    {
        return view('admin.pages.vouchers.index');
    }

    public function store(Request $request)
    {


        $this->validate($request, [
            'voucher_date' => 'required|date',
            'voucher_type' => 'required|string',
            // 'payment_mode' => 'required|string',
            'description' => 'required|string',
        ]);
        return $this->voucherService->voucherStore($request);
    }




    public function updateVoucher(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:vouchers,id',
            'voucher_date' => 'required|date',
            'voucher_type' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $id = $request->id;

            // Find the voucher
            $voucher = Voucher::findOrFail($id);

            // Find the related journal entry
            // $originalJournalEntry = JournalEntry::where('reference_type', 'App\\Models\\Voucher')
            //     ->where('reference_id', $voucher->id)
            //     ->first();

            $originalJournalEntry = JournalEntry::find($voucher->journal_entry_id);

            if (!$originalJournalEntry) {
                throw new \Exception('Journal entry not found for this voucher');
            }
            $financialYear = FinancialYear::whereDate('start_date', '<=', $request->voucher_date)
                ->whereDate('end_date', '>=', $request->voucher_date)
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
            // Reverse the original journal entry
            // if ($originalJournalEntry->status === 'posted') {
            //     $this->journalEntryService->reverseAndPostEntry($originalJournalEntry, 'Voucher: ' . $voucher->voucher_no . ' - ' . $voucher->id . ' (Updated)',$request->voucher_date);
            // }

            $originalJournalEntry->update(['status' => 'reversed']);

            $accountIds = $originalJournalEntry->lines
                ->pluck('account_id')
                ->filter()
                ->unique();

            foreach ($accountIds as $accountId) {

                $account = Account::find($accountId);

                if (!$account) {
                    continue;
                }

                $account->recalculateBalancesFromDate(
                    $originalJournalEntry->entry_date,
                    $originalJournalEntry->financial_year_id
                );
            }
            // Update voucher basic info
            $voucher->update([
                'voucher_type' => $request->voucher_type,
                'voucher_date' => $request->voucher_date,
                'narration'    => $request->description,
            ]);

            // Delete old voucher entries
            VoucherEntry::where('voucher_id', $voucher->id)->delete();

            // Create new voucher entries
            $sectionDr = json_decode($request->sectionDr, true);
            $sectionCr = json_decode($request->sectionCr, true);

            $description = null;
            foreach ($sectionDr as $drEntry) {
                VoucherEntry::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $drEntry['account_id'],
                    'debit' => $drEntry['debit_amount'],
                    'credit' => 0,
                ]);
                $account = Account::find($drEntry['account_id']);

                if ($account) {
                    $description .= $account->name . ', ';
                }
            }

            $description = rtrim($description, ', ');
            foreach ($sectionCr as $crEntry) {
                VoucherEntry::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $crEntry['account_id'],
                    'debit' => 0,
                    'credit' => $crEntry['credit_amount'],
                ]);
            }

            // Create new journal entry with updated data
            $journalEntry = $this->journalEntryService->createVoucherEntry([
                'date' => $voucher->voucher_date,
                'reference_type' => 'App\\Models\\Voucher',
                'reference_id' => $voucher->id,
                'description' => $description
                    ? $description . ' -- ' . ($voucher->narration ?? $voucher->voucher_no)
                    : ($voucher->narration ?? $voucher->voucher_no),
                'user_id' => Auth::id(),
                'sectionDr' => $sectionDr,
                'sectionCr' => $sectionCr,
                'voucher_no' => $voucher->voucher_no,
                'voucher_description' => $voucher->description ?? $voucher->voucher_no,
            ]);
            if ($journalEntry) {
                $voucher->journal_entry_id = $journalEntry->id;
                $voucher->save();
                DB::commit();
                return successResponse($voucher, 'Voucher updated successfully with reversed and new journal entry', 0, 200);
            } else {

                return errorResponse(null, 'Failed to create journal entry for the Voucher update', 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }


    public function getVoucherAccounts(Request $request)
    {
        try {

            $accounts = Account::orderBy('code')->get(['id', 'code', 'name', 'reference_type']);

            return successResponse($accounts, 'Accounts retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }


    public function list(Request $request)
    {

        try {
            $query = Voucher::with('entries', 'entries.account', 'journalEntry:id,status');



            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('voucher_no', 'like', "%{$search}%");
                });
            }

            if ($request->start_date && $request->end_date) {
                $query->dateRange($request->start_date, $request->end_date);
            }



            if ($request->type) {
                $query->where('voucher_type', $request->type);
            }
            $vouchers = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 15);


            $vouchersData = collect($vouchers->items())->map(function ($voucher) {

                // $voucher->voucher_date = Carbon::parse($voucher->voucher_date)
                // ->format('Y-m-d');
                $voucher->total_debit = collect($voucher->entries)->sum(function ($entry) {
                    return (float) $entry->debit;
                });

                $voucher->total_credit = collect($voucher->entries)->sum(function ($entry) {
                    return (float) $entry->credit;
                });

                $voucher->status = match ($voucher->journalEntry?->status) {
                    'draft' => 'pending',
                    'posted' => 'approved',
                    default => $voucher->journalEntry?->status,
                };

                return $voucher;
            });
            return successResponse([
                'vouchers' => $vouchersData,
                'total_amount' => $vouchers->sum('amount'),
                'pagination' => generatePaginationData($vouchers),
            ], 'Vouchers retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $expense = Voucher::with(['entries'])
                ->findOrFail($id);
            return successResponse($expense, 'Voucher retrieved successfully', 0, 200);
        } catch (\Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
    // // Get credit amount
    // public function getCreditAmount(Request $request)
    // {
    //     dd($request);
    //     $response = GeneralLedger::select([
    //         'chart_of_account_id',
    //         DB::raw("case when SUM(debit-credit) <0 then 0 else SUM(debit-credit) end as total_debit"),
    //         DB::raw("case when SUM(credit-debit) <0 then 0 else SUM(credit-debit) end as total_credit")
    //     ])
    //         ->groupBy('chart_of_account_id')
    //         ->where('chart_of_account_id', $request->account_id)
    //         ->first();

    //     return response()->json($response);
    // }
}
