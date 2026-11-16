<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AutoIncrementMaster;
use App\Models\CommisionTypeMaster;
use App\Models\CommissionHistory;
use App\Models\CommissionMaster;
use App\Models\CommissionPayroll;
use App\Models\CommissionTypeItem;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\Incentive;
use App\Models\OpeningBalance;
use App\Models\Order;

use App\Models\OrderPayment;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Exception;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }
    // public function voucherStore($request)
    // {
    //     try {

    //         DB::beginTransaction();
    //         // dd($request);

    //         $voucher = Voucher::create([
    //             'voucher_no' => $this->genarateVoucherCode($request->voucher_type),
    //             'voucher_type' => $request->voucher_type,
    //             'voucher_date' => $request->voucher_date,
    //             'narration'    => $request->description,

    //         ]);

    //         $sectionDr = json_decode($request->sectionDr, true);

    //         $sectionCr = json_decode($request->sectionCr, true);
    //         $total = 0;

    //         foreach ($sectionDr as $drEntry) {


    //             VoucherEntry::create([
    //                 'voucher_id' => $voucher->id,
    //                 'account_id' => $drEntry['account_id'],
    //                 'debit' => $drEntry['debit_amount'],
    //                 'credit' => 0,
    //                 // 'remarks' => $drEntry['remarks'] ?? null,
    //             ]);
    //         }

    //         foreach ($sectionCr as $crEntry) {

    //             VoucherEntry::create([
    //                 'voucher_id' => $voucher->id,
    //                 'account_id' => $crEntry['account_id'],
    //                 'debit' => 0,
    //                 'credit' => $crEntry['credit_amount'],
    //                 // 'remarks' => $crEntry['remarks'] ?? null,
    //             ]);
    //             $total += $crEntry['credit_amount'];
    //         }

    //         $voucher->amount = $total;
    //         //  dd($total);
    //         $voucher->save();

    //         if ($request->voucher_type == 'payment') {

    //             $drAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                 ->find($sectionDr[0]['account_id']);

    //             if ($drAccount->reference_type == 'supplier') {
    //                 $supplier = Supplier::select('id', 'balance')->find($drAccount->reference_id);
    //                 $supplierId = $supplier->id;

    //                 foreach ($sectionCr as $row) {


    //                     $paymentAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                         ->find($row['account_id']);
    //                     $paymentMode = strtolower($paymentAccount->name);
    //                     $paidAmount = $row['credit_amount'];


    //                     if ($supplier->balance < $total) {

    //                         DB::rollBack();
    //                         return errorResponse(null, "Supplier balance is low, can't create voucher.", 400);
    //                     }
    //                     // $supplier->balance -= $paidAmount;
    //                     // $supplier->save();

    //                     $opening_balance = 0;
    //                     $openeing_total_amount = SupplierOpeningBalance::where('supplier_id', $supplierId)
    //                         ->sum('amount');

    //                     $openeing_paid_amount = SupplierOpeningBalance::where('supplier_id', $supplierId)
    //                         ->sum('paid_amount');


    //                     $opening_balance = $openeing_total_amount - $openeing_paid_amount;
    //                     if ($opening_balance > 0) {

    //                         $applied = min($paidAmount, $opening_balance);

    //                         // Update Opening Balance
    //                         SupplierOpeningBalance::where('supplier_id', $supplierId)
    //                             ->increment('paid_amount', $applied);

    //                         $purchasePayment = PurchasePayment::create([

    //                             'amount_taken' => $applied,
    //                             'payment_mode' => $paymentMode,
    //                             'created_by'   => 1,
    //                             'payment_date' => $voucher->voucher_date,
    //                             'description' => $voucher->description,
    //                             'type' => 'opening_balance',
    //                         ]);

    //                         $supplier->balance -= $applied;
    //                         $supplier->save();
    //                     } else {
    //                         // 2. Get supplier pending purchases
    //                         $purchases = Purchase::where('supplier_id', $supplierId)
    //                             ->where('payment_status', 'pending')
    //                             ->orderBy('created_at', 'asc')
    //                             ->get();

    //                         foreach ($purchases as $purchase) {
    //                             if ($paidAmount <= 0) break;

    //                             $remaining = $purchase->total - $purchase->payment_total;

    //                             if ($paidAmount >= $remaining) {
    //                                 // Full payment
    //                                 $purchase->payment_total += $remaining;
    //                                 $purchase->payment_status = 'completed';
    //                                 $appliedAmount = $remaining;
    //                                 $paidAmount -= $remaining;
    //                             } else {
    //                                 // Partial payment
    //                                 $purchase->payment_total += $paidAmount;
    //                                 $purchase->payment_status = 'pending';
    //                                 $appliedAmount = $paidAmount;
    //                                 $paidAmount = 0;
    //                             }

    //                             $purchase->save();

    //                             // 3. Record in purchase_payments
    //                             PurchasePayment::create([
    //                                 'purchase_id'  => $purchase->id,
    //                                 'amount_taken' => $appliedAmount,
    //                                 'payment_mode' => $paymentMode,
    //                                 'created_by'   => 1,
    //                                 'payment_date' => $voucher->voucher_date,
    //                                 'description' => $voucher->description,
    //                             ]);
    //                         }

    //                         $supplier->balance -= $paidAmount;
    //                         $supplier->save();
    //                     }
    //                 }
    //             } else if ($drAccount->reference_type == 'employee') {

    //                 foreach ($sectionCr as $row) {

    //                     $paymentAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                         ->find($row['account_id']);
    //                     $paymentMode = strtolower($paymentAccount->name);
    //                     $paidAmount = $row['credit_amount'];
    //                     $pendingPayrolls = Payroll::where('status', 'draft')
    //                         ->orderBy('created_at', 'asc')
    //                         ->with(['user', 'employee'])->get();

    //                     $balance = $drAccount->getCurrentBalance();

    //                     if ($balance < $total) {

    //                         DB::rollBack();
    //                         return errorResponse(null, "Employee balance is low, can't create voucher.", 400);
    //                     }

    //                     foreach ($pendingPayrolls as $payroll) {


    //                         $remaining = $payroll->net_salary - $total;

    //                         while ($balance > 0) {
    //                             if ($remaining > 0) {
    //                                 $payroll->status = 'partial';
    //                                 $payroll->save();
    //                             } else {
    //                                 $payroll->status = 'paid';
    //                                 $payroll->save();
    //                             }
    //                             $balance += $remaining;
    //                             $total += $remaining;
    //                         }
    //                     }
    //                 }
    //             } else if ($drAccount->referece_type == 'commission') {


    //                 foreach ($sectionCr as $row) {

    //                     $paymentAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                         ->find($row['account_id']);
    //                     $paymentMode = strtolower($paymentAccount->name);
    //                     $paidAmount = $row['credit_amount'];
    //                     $pendingPayrolls = CommissionPayroll::where('status', 'draft')
    //                         ->orderBy('created_at', 'asc')
    //                         ->with(['user', 'employee'])->get();

    //                     $balance = $drAccount->getCurrentBalance();

    //                     if ($balance < $total) {

    //                         DB::rollBack();
    //                         return errorResponse(null, "Employee commission is low, can't create voucher.", 400);
    //                     }

    //                     foreach ($pendingPayrolls as $payroll) {


    //                         $remaining = $payroll->commission - $total;

    //                         while ($balance > 0) {
    //                             if ($remaining > 0) {
    //                                 $payroll->status = 'partial';
    //                                 $payroll->save();
    //                             } else {
    //                                 $payroll->status = 'paid';
    //                                 $payroll->save();
    //                             }
    //                             $balance += $remaining;
    //                             $total += $remaining;
    //                         }
    //                     }
    //                 }
    //             }
    //         } else if ($request->voucher_type == 'receipt') {
    //             $customerAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                 ->find($sectionCr[0]['account_id']);
    //             if ($customerAccount->reference_type == 'customer') {
    //                 $customer = Customer::select('id', 'balance_amount')
    //                     ->find($customerAccount->reference_id);
    //                 $customerId = $customer->id;
    //                 if ($customer->balance_amount < $total) {

    //                     DB::rollBack();
    //                     return errorResponse(null, "Customer has no credit, can't create voucher.", 400);
    //                 }

    //                 // if customer have opening balance, pay that first
    //                 $openeing_balance = 0;
    //                 $openeing_total_amount = OpeningBalance::where('customer_id', $customerId)
    //                     ->sum('amount');

    //                 $openeing_paid_amount = OpeningBalance::where('customer_id', $customerId)
    //                     ->sum('paid_amount');


    //                 $openeing_balance = $openeing_total_amount - $openeing_paid_amount;

    //                 if ($openeing_balance > 0) {
    //                     foreach ($sectionDr as $row) {

    //                         $paymentAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
    //                             ->find($row['account_id']);

    //                         $paymentMode = strtolower($paymentAccount->name);
    //                         $paidAmount = $row['debit_amount'];

    //                         // foreach ($paymentBreakdown as $key => &$payment) {
    //                         if ($paidAmount <= 0) continue;

    //                         $applied = min($paidAmount, $openeing_balance);

    //                         // Update Opening Balance
    //                         OpeningBalance::where('customer_id', $customerId)
    //                             ->increment('paid_amount', $applied);


    //                         OrderPayment::create([
    //                             // 'order_id' => $prevOrder->id,
    //                             'customer_id' => $customerId,
    //                             'user_id' => 1,
    //                             'total_amount' => $customer->balance_amount,
    //                             'paid_amount' => $applied,
    //                             'balance_amount' => $customer->balance_amount - $applied,
    //                             'type' => "opening_balance",
    //                             'payment_mode' => $paymentMode, // or "Adjustment"
    //                             'payment_date' => $voucher->voucher_date ?? now(),
    //                             'description' => $voucher->description,
    //                         ]);

    //                         $customer->balance_amount -= $applied;
    //                         $customer->save();
    //                         Log::info('OPENING BALANCE PAYMENT APPLIED', [
    //                             'applied_amount' => $applied,
    //                             'new_balance' => $customer->balance_amount
    //                         ]);


    //                         $openeing_balance -= $applied;
    //                         $paidAmount -= $applied;
    //                     }
    //                 } else {

    //                     $previousOrders = Order::with('orderItems')
    //                         ->where('customer_id', $customerId)
    //                         ->whereRaw('LOWER(payment_status) != ?', ['paid'])
    //                         ->where('order_status', 'delivered')
    //                         ->orderBy('created_at', 'asc')
    //                         ->get();
    //                     Log::info('PREVIOUS ORDERS COUNT', [
    //                         'count' => $previousOrders->count()
    //                     ]);


    //                     foreach ($previousOrders as $prevOrder) {
    //                         $remaining = $prevOrder->balance_amount;

    //                         if ($remaining <= 0) continue;

    //                         foreach ($sectionDr as $row) {
    //                             if ($row['debit_amount'] <= 0) continue;

    //                             $applied = min($row['debit_amount'], $remaining);

    //                             $prevOrder->update([
    //                                 'paid_amount' => $prevOrder->paid_amount + $applied,
    //                                 'balance_amount' => $remaining - $applied,
    //                                 'payment_status' => ($remaining - $applied) <= 0 ? "Paid" : "Partial",
    //                             ]);

    //                             $receivedAccount = Account::find($row['account_id']);

    //                             $paymentMode = strtolower($receivedAccount->name);
    //                             // Save with correct payment mode
    //                             OrderPayment::create([
    //                                 'order_id' => $prevOrder->id,
    //                                 'customer_id' => $customerId,
    //                                 'user_id' => $userId ?? 1,
    //                                 'total_amount' => $prevOrder->total_amount,
    //                                 'paid_amount' => $applied,
    //                                 'balance_amount' => $prevOrder->balance_amount,
    //                                 'payment_mode' => $paymentMode, // Correct mode
    //                                 'payment_date' => $voucher->voucher_date ?? now(),
    //                                 'description' => $voucher->description,
    //                             ]);
    //                             Log::info('BEFORE CUSTOMER SAVE', [
    //                                 'customer_balance_before' => $customer->balance_amount,
    //                                 'applied' => $applied,
    //                             ]);

    //                             $customer->balance_amount -= $applied;
    //                             $customer->save();
    //                             Log::info('AFTER CALCULATION', [
    //                                 'customer_balance_after' => $customer->balance_amount,
    //                             ]);


    //                             // $customer->refresh();

    //                             Log::info('AFTER SAVE', [
    //                                 'db_balance' => $customer->balance_amount,
    //                             ]);

    //                             // Calculate commission
    //                             if ($prevOrder->order_by === $prevOrder->delivered_by) {
    //                                 $this->handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customerId, $voucher->voucher_date);
    //                             } else {
    //                                 $this->handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customerId, $voucher->voucher_date);
    //                             }

    //                             // Reduce from this payment mode
    //                             $row['debit_amount'] -= $applied;

    //                             $remaining -= $applied;

    //                             if ($remaining <= 0) break;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //         $journalEntry = $this->journalEntryService->createVoucherEntry([
    //             'date' => $voucher->voucher_date,
    //             'reference_type' => 'App\\Models\\Voucher',
    //             'reference_id' => $voucher->id,
    //             'description' => 'Voucher: ' . $voucher->voucher_no . ' - ' . $voucher->id,

    //             'user_id' => Auth::id(),
    //             'sectionDr' => $sectionDr,
    //             'sectionCr' => $sectionCr,
    //             'voucher_no' =>  $voucher->voucher_no,
    //             'voucher_description' => $voucher->description ?? $voucher->voucher_no,
    //             // 'payment_mode' => $voucher->payment_mode,
    //         ]);

    //         if ($journalEntry) {

    //             $voucher->journal_entry_id = $journalEntry->id;
    //             $voucher->save();
    //             DB::commit();
    //             return successResponse($voucher, 'Voucher created successfully with journal entry', 0, 200);
    //         } else {
    //             DB::rollBack();
    //             return errorResponse(null, 'Failed to create vocuher entry', 500);
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 500);
    //     }
    // }


    public function voucherStore($request)
    {
        DB::beginTransaction();

        try {

            $sectionDr = json_decode($request->sectionDr, true);
            $sectionCr = json_decode($request->sectionCr, true);


            $debitAccountNames = Account::whereIn(
                'id',
                collect($sectionDr)->pluck('account_id')
            )
                ->pluck('name')
                ->implode(', ');

            $request->merge([
                'description' => $debitAccountNames
                    ? $debitAccountNames . ' - ' . ($request->description ?? '')
                    : ($request->description ?? ''),
            ]);
            $voucher = $this->createVoucher($request, $sectionCr);

            $this->createVoucherEntries($voucher->id, $sectionDr, $sectionCr);
            $this->createJournalEntry($voucher, $sectionDr, $sectionCr);
            switch ($request->voucher_type) {

                case 'payment':
                    $this->processPaymentVoucher($voucher, $sectionDr, $sectionCr);
                    break;

                case 'receipt':
                    $this->processReceiptVoucher($voucher, $sectionDr, $sectionCr);
                    break;
            }



            DB::commit();

            return successResponse(
                $voucher->fresh(),
                'Voucher created successfully',
                0,
                200
            );
        } catch (\Throwable $e) {

            DB::rollBack();

            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    private function createVoucher($request, array $sectionCr)
    {
        $total = collect($sectionCr)->sum('credit_amount');
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

        return Voucher::create([
            'voucher_no'   => $this->genarateVoucherCode($request->voucher_type),
            'voucher_type' => $request->voucher_type,
            'voucher_date' => $request->voucher_date,
            'narration'    => $request->description,
            'amount'       => $total,
            'financial_year_id' => $financialYear->id,
        ]);
    }

    private function createVoucherEntries($voucherId, $sectionDr, $sectionCr)
    {
        $entries = [];

        foreach ($sectionDr as $row) {

            $entries[] = [
                'voucher_id' => $voucherId,
                'account_id' => $row['account_id'],
                'debit'      => $row['debit_amount'],
                'credit'     => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($sectionCr as $row) {

            $entries[] = [
                'voucher_id' => $voucherId,
                'account_id' => $row['account_id'],
                'debit'      => 0,
                'credit'     => $row['credit_amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        VoucherEntry::insert($entries);
    }

    private function processPaymentVoucher($voucher, $sectionDr, $sectionCr)
    {
        $drAccount = Account::select(
            'id',
            'name',
            'reference_id',
            'reference_type'
        )->findOrFail($sectionDr[0]['account_id']);

        switch ($drAccount->reference_type) {

            case 'supplier':
                $this->processSupplierPayment($voucher, $drAccount, $sectionCr);
                break;

            case 'employee':
                $this->processEmployeePayment($voucher, $drAccount, $sectionCr);
                break;

            case 'commission':
                $this->processCommissionPayment($voucher, $drAccount, $sectionCr);
                break;
            case 'customer':
                $this->processCustomerPayment($voucher, $drAccount, $sectionCr);
                break;
        }
    }

    private function processCustomerPayment($voucher, $drAccount, array $sectionCr)
    {
        $customer = Customer::select('id', 'balance_amount')
            ->findOrFail($drAccount->reference_id);

        $totalAmount = collect($sectionCr)->sum('credit_amount');

        if ($customer->balance_amount < $totalAmount) {
            throw new \Exception("Customer balance is low, can't create voucher.");
        }

        foreach ($sectionCr as $row) {

            $paymentAccount = Account::select(
                'id',
                'name',
                'reference_id',
                'reference_type'
            )->findOrFail($row['account_id']);

            $paymentMode = strtolower($paymentAccount->name);
            $paidAmount  = $row['credit_amount'];

            // 1. Adjust customer opening balance first
            $paidAmount = $this->settleCustomerOpeningBalancePaidAmount(
                $customer,
                $voucher,
                $paymentMode,
                $paidAmount
            );

            // 2. If balance remains, adjust pending purchases
            if ($paidAmount > 0) {

                $this->settleCustomerSales(
                    $customer,
                    $voucher,
                    $paymentMode,
                    $paidAmount
                );
            }
        }
    }

    private function processSupplierPayment($voucher, $drAccount, array $sectionCr)
    {
        $supplier = Supplier::select('id', 'balance')
            ->findOrFail($drAccount->reference_id);

        $totalAmount = collect($sectionCr)->sum('credit_amount');

        if ($supplier->balance < $totalAmount) {
            throw new \Exception("Supplier balance is low, can't create voucher.");
        }

        foreach ($sectionCr as $row) {

            $paymentAccount = Account::select(
                'id',
                'name',
                'reference_id',
                'reference_type'
            )->findOrFail($row['account_id']);

            $paymentMode = strtolower($paymentAccount->name);
            $paidAmount  = $row['credit_amount'];

            // 1. Adjust supplier opening balance first
            $paidAmount = $this->settleSupplierOpeningBalance(
                $supplier,
                $voucher,
                $paymentMode,
                $paidAmount
            );

            // 2. If balance remains, adjust pending purchases
            if ($paidAmount > 0) {

                $this->settleSupplierPurchases(
                    $supplier,
                    $voucher,
                    $paymentMode,
                    $paidAmount
                );
            }
        }
    }

    private function settleCustomerOpeningBalancePaidAmount(Customer $customer, Voucher $voucher, string $paymentMode, float $paidAmount)
    {
        // Implementation for settling customer opening balance paid amount
        $opening_balance = 0;
        $openeing_total_amount = OpeningBalance::where('customer_id', $customer->id)
            ->sum('amount');

        $openeing_paid_amount = OpeningBalance::where('customer_id', $customer->id)
            ->sum('paid_amount');

        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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
        $opening_balance = $openeing_total_amount - $openeing_paid_amount;

        if ($opening_balance > 0) {

            $applied = min($paidAmount, $opening_balance);


            // Update Opening Balance
            OpeningBalance::where('customer_id', $customer->id)
                ->increment('paid_amount', $applied);


            OrderPayment::create([

                'total_amount' => $customer->balance_amount,
                'paid_amount' => $applied,
                'balance_amount' => $customer->balance_amount - $applied,
                'user_id' => 1,
                'customer_id' => $customer->id,
                'payment_mode' => $paymentMode,
                'type' => "opening_balance",
                'created_by'   => $userId ?? 1,
                'payment_date' => $voucher->voucher_date,
                'description' => $voucher->description,

                'financial_year_id' => $financialYear->id,
                'journal_entry_id' => $voucher->journal_entry_id ?? null,
            ]);


            $customer->balance_amount -= $applied;
            $customer->save();
            $paidAmount -= $applied;
        }
        return $paidAmount;
    }

    private function settleSupplierOpeningBalance(
        Supplier $supplier,
        Voucher $voucher,
        string $paymentMode,
        float $paidAmount
    ) {
        $opening_balance = 0;
        $openeing_total_amount = SupplierOpeningBalance::where('supplier_id', $supplier->id)
            ->sum('amount');

        $openeing_paid_amount = SupplierOpeningBalance::where('supplier_id', $supplier->id)
            ->sum('paid_amount');

        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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
        $opening_balance = $openeing_total_amount - $openeing_paid_amount;

        if ($opening_balance > 0) {

            $applied = min($paidAmount, $opening_balance);


            // Update Opening Balance
            SupplierOpeningBalance::where('supplier_id', $supplier->id)
                ->increment('paid_amount', $applied);


            PurchasePayment::create([

                'amount_taken' => $applied,
                'supplier_id' => $supplier->id,
                'payment_mode' => $paymentMode,
                'created_by'   => $userId ?? 1,
                'payment_date' => $voucher->voucher_date,
                'description' => $voucher->description,
                'type' => 'opening_balance',
                'financial_year_id' => $financialYear->id,
                'journal_entry_id' => $voucher->journal_entry_id ?? null,
            ]);

            $supplier->balance -= $applied;
            $supplier->save();
            $paidAmount -= $applied;
        }
        return $paidAmount;
    }
    private function settleSupplierPurchases(
        Supplier $supplier,
        Voucher $voucher,
        string $paymentMode,
        float $paidAmount
    ) {
        $purchases = Purchase::where('supplier_id', $supplier->id)
            ->where('payment_status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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
        foreach ($purchases as $purchase) {
            if ($paidAmount <= 0) break;

            $remaining = $purchase->total - $purchase->payment_total;

            if ($paidAmount >= $remaining) {
                // Full payment
                $purchase->payment_total += $remaining;
                $purchase->payment_status = 'completed';
                $appliedAmount = $remaining;
                $paidAmount -= $remaining;
            } else {
                // Partial payment
                $purchase->payment_total += $paidAmount;
                $purchase->payment_status = 'pending';
                $appliedAmount = $paidAmount;
                $paidAmount = 0;
            }

            $purchase->save();

            // 3. Record in purchase_payments
            PurchasePayment::create([
                'purchase_id'  => $purchase->id,
                'supplier_id'  => $supplier->id,
                'amount_taken' => $appliedAmount,
                'payment_mode' => $paymentMode,
                'created_by'   => $userId ?? 1,
                'payment_date' => $voucher->voucher_date,
                'description' => $voucher->description,
                'financial_year_id' => $financialYear->id,
                'journal_entry_id' => $voucher->journal_entry_id ?? null,
            ]);
            $supplier->balance -= $appliedAmount;
            $supplier->save();
            $paidAmount -= $appliedAmount;
        }

        return;
    }

    private function settleCustomerSales(
        Customer $customer,
        Voucher $voucher,
        string $paymentMode,
        float $paidAmount
    ) {
        $sales = Order::where('customer_id', $customer->id)
            ->where('payment_status', 'Partial')
            ->where('order_status', 'delivered')
            ->where('is_outside', 0)
            ->orderBy('created_at', 'asc')
            ->get();
        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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
        foreach ($sales as $sale) {
            if ($paidAmount <= 0) break;

            $remaining = $sale->balance_amount;

            if ($paidAmount >= $remaining) {
                // Full payment
                $sale->paid_amount += $remaining;
                $sale->payment_status = 'completed';
                $appliedAmount = $remaining;
                $paidAmount -= $remaining;
            } else {
                // Partial payment
                $sale->paid_amount += $paidAmount;
                $sale->payment_status = 'pending';
                $appliedAmount = $paidAmount;
                $paidAmount = 0;
            }

            $sale->save();

            // 3. Record in sale_payments
            OrderPayment::create([
                'order_id'  => $sale->id,
                'customer_id'  => $customer->id,
                'total_amount' => $sale->total_amount,
                'paid_amount' => $appliedAmount,
                'payment_mode' => $paymentMode,
                'created_by'   => $userId ?? 1,
                'balance_amount' => $sale->total_amount - $appliedAmount,
                'payment_date' => $voucher->voucher_date,
                'description' => $voucher->description,
                'financial_year_id' => $financialYear->id,
                'journal_entry_id' => $voucher->journal_entry_id ?? null,
                'user_id' => $userId ?? 1,
            ]);


            $customer->balance -= $appliedAmount;
            $customer->save();
            $paidAmount -= $appliedAmount;
        }

        return;
    }
    private function processEmployeePayment(Voucher $voucher, Account $drAccount, array $sectionCr)
    {

        $balance = $drAccount->getCurrentBalance();

        $totalAmount = collect($sectionCr)->sum('credit_amount');

        if ($balance < $totalAmount) {
            throw new \Exception("Employee balance is low, can't create voucher.");
        }

        $this->settleEmployeePayroll($sectionCr, $drAccount);
    }
    private function settleEmployeePayroll(array $sectionCr, Account $drAccount)
    {

        foreach ($sectionCr as $row) {
            $paidAmount = $row['credit_amount'];

            $pendingPayrolls = Payroll::whereIn('status', ['draft', 'partial'])
                ->where('employee_id', $drAccount->reference_id)
                ->with(['user', 'employee'])
                ->get();

            foreach ($pendingPayrolls as $payroll) {
                if ($paidAmount <= 0) {
                    break;
                }
                $remaining = $payroll->net_salary - $paidAmount;


                if ($remaining > 0) {
                    $payroll->status = 'partial';
                    $payroll->net_salary -= $paidAmount;
                    $payroll->save();
                    $paidAmount -= $remaining;
                } else {
                    $payroll->status = 'paid';
                    $payroll->save();
                    $paidAmount = (-1) * $remaining;
                }
            }
        }
    }
    private function processCommissionPayment(
        Voucher $voucher,
        Account $drAccount,
        array $sectionCr
    ) {
        // $balance = $drAccount->getCurrentBalance();

        // $totalAmount = collect($sectionCr)->sum('credit_amount');

        // if ($balance < $totalAmount) {
        //     throw new \Exception("Employee commission balance is low, can't create voucher.");
        // }

        // foreach ($sectionCr as $row) {

        //     $paymentAccount = Account::select(
        //         'id',
        //         'name'
        //     )->findOrFail($row['account_id']);

        //     $paymentMode = strtolower($paymentAccount->name);

        //     $paidAmount = $row['credit_amount'];

        //     $this->settleCommissionPayroll($sectionCr, $drAccount);

        // }

        $balance = $drAccount->getCurrentBalance();

        $totalAmount = collect($sectionCr)->sum('credit_amount');

        if ($balance < $totalAmount) {
            throw new \Exception("Employee balance is low, can't create voucher.");
        }

        $this->settleCommissionPayroll($sectionCr, $drAccount);
    }
    private function settleCommissionPayroll(array $sectionCr, Account $drAccount)
    {

        // $pendingPayrolls = CommissionPayroll::where('status', 'draft')
        //     ->with(['user', 'employee'])
        //     ->orderBy('created_at', 'asc')
        //     ->get();

        // foreach ($pendingPayrolls as $payroll) {

        //     if ($paidAmount <= 0) {
        //         break;
        //     }

        //     // TODO:
        //     // Calculate remaining commission
        //     // Update payroll status (partial / paid)
        //     // Save commission payment record (if applicable)
        //     // Reduce $paidAmount
        // }

        foreach ($sectionCr as $row) {
            $paidAmount = $row['credit_amount'];

            $pendingPayrolls = CommissionPayroll::whereIn('status', ['draft', 'partial'])
                ->where('employee_id', $drAccount->reference_id)
                ->with(['user', 'employee'])
                ->get();

            foreach ($pendingPayrolls as $payroll) {
                if ($paidAmount <= 0) {
                    break;
                }
                $remaining = $payroll->commission - $paidAmount;


                if ($remaining > 0) {
                    $payroll->status = 'partial';
                    $payroll->commission -= $paidAmount;
                    $payroll->save();
                    $paidAmount -= $remaining;
                } else {
                    $payroll->status = 'paid';
                    $payroll->save();
                    $paidAmount = (-1) * $remaining;
                }
            }
        }
    }

    private function processReceiptVoucher($voucher, $sectionDr, $sectionCr)
    {
        $customerAccount = Account::select(
            'id',
            'reference_id',
            'reference_type'
        )->findOrFail($sectionCr[0]['account_id']);

        if ($customerAccount->reference_type != 'customer') {
            return;
        }

        $this->processCustomerReceipt(
            $voucher,
            $customerAccount,
            $sectionDr
        );
    }
    private function createJournalEntry($voucher, $sectionDr, $sectionCr)
    {
        $journalEntry = $this->journalEntryService->createVoucherEntry([
            'date' => $voucher->voucher_date,
            'reference_type' => Voucher::class,
            'reference_id' => $voucher->id,
            'description' => $voucher->narration,
            'user_id' => Auth::id(),
            'sectionDr' => $sectionDr,
            'sectionCr' => $sectionCr,
            'voucher_no' => $voucher->voucher_no,
            'voucher_description' => $voucher->narration,
        ]);



        $voucher->update([
            'journal_entry_id' => $journalEntry->id
        ]);
    }

    private function processCustomerReceipt(
        Voucher $voucher,
        Account $customerAccount,
        array $sectionDr
    ) {
        $customer = Customer::select(
            'id',
            'balance_amount'
        )->findOrFail($customerAccount->reference_id);

        $totalAmount = collect($sectionDr)->sum('debit_amount');

        if ($customer->balance_amount < $totalAmount) {
            throw new \Exception("Customer has no credit, can't create voucher.");
        }

        // 1. Adjust customer opening balance
        $remainingAmount = $this->settleCustomerOpeningBalance(
            $customer,
            $voucher,
            $sectionDr
        );

        // 2. Adjust previous pending orders
        if ($remainingAmount > 0) {
            $this->settleCustomerPreviousOrders(
                $customer,
                $voucher,
                $sectionDr,
                $remainingAmount
            );
        }
    }
    private function settleCustomerOpeningBalance(
        Customer $customer,
        Voucher $voucher,
        array $sectionDr
    ) {
        $remainingAmount = collect($sectionDr)->sum('debit_amount');

        $opening_balance = 0;
        $openeing_total_amount = OpeningBalance::where('customer_id', $customer->id)
            ->sum('amount');

        $openeing_paid_amount = OpeningBalance::where('customer_id', $customer->id)
            ->sum('paid_amount');


        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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
        $opening_balance = $openeing_total_amount - $openeing_paid_amount;

        // if ($opening_balance > 0) {

        //     $applied = min($paidAmount, $opening_balance);


        //     // Update Opening Balance
        //     SupplierOpeningBalance::where('supplier_id', $supplier->id)
        //         ->increment('paid_amount', $applied);


        //     PurchasePayment::create([

        //         'amount_taken' => $applied,
        //         'supplier_id' => $supplier->id,
        //         'payment_mode' => $paymentMode,
        //         'created_by'   => $userId ?? 1,
        //         'payment_date' => $voucher->voucher_date,
        //         'description' => $voucher->description,
        //         'type' => 'opening_balance',
        //     ]);

        //     $supplier->balance -= $applied;
        //     $supplier->save();
        //     $paidAmount -= $applied;
        // }

        if ($opening_balance > 0) {
            foreach ($sectionDr as $row) {

                $paymentAccount = Account::select('id', 'name', 'reference_id', 'reference_type')
                    ->find($row['account_id']);

                $paymentMode = strtolower($paymentAccount->name);
                $paidAmount = $row['debit_amount'];

                // foreach ($paymentBreakdown as $key => &$payment) {
                if ($paidAmount <= 0) continue;

                $applied = min($paidAmount, $opening_balance);

                // Update Opening Balance
                OpeningBalance::where('customer_id', $customer->id)
                    ->increment('paid_amount', $applied);


                OrderPayment::create([
                    // 'order_id' => $prevOrder->id,
                    'customer_id' => $customer->id,
                    'user_id' => 1,
                    'total_amount' => $customer->balance_amount,
                    'paid_amount' => $applied,
                    'balance_amount' => $customer->balance_amount - $applied,
                    'type' => "opening_balance",
                    'payment_mode' => $paymentMode, // or "Adjustment"
                    'payment_date' => $voucher->voucher_date ?? now(),
                    'description' => $voucher->description,
                    'financial_year_id'  => $financialYear->id,
                ]);

                $customer->balance_amount -= $applied;
                $customer->save();

                Log::info('OPENING BALANCE PAYMENT APPLIED', [
                    'applied_amount' => $applied,
                    'new_balance' => $customer->balance_amount
                ]);


                $opening_balance -= $applied;
                $paidAmount -= $applied;
            }
        }
        return $paidAmount;
    }
    private function settleCustomerPreviousOrders(
        Customer $customer,
        Voucher $voucher,
        array $sectionDr,
        float $remainingAmount
    ) {
        // $previousOrders = Order::with('orderItems')
        //     ->where('customer_id', $customer->id)
        //     ->whereRaw('LOWER(payment_status) != ?', ['paid'])
        //     ->where('order_status', 'delivered')
        //     ->orderBy('created_at')
        //     ->get();

        // foreach ($previousOrders as $order) {



        // TODO:
        // Apply payment to order
        // Update order payment status
        // Create OrderPayment
        // Update customer balance
        // Handle sales/sales executive commission
        // }


        $financialYear = FinancialYear::whereDate('start_date', '<=', $voucher->voucher_date)
            ->whereDate('end_date', '>=', $voucher->voucher_date)
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

        $previousOrders = Order::with('orderItems')
            ->where('customer_id', $customer->id)
            ->whereRaw('LOWER(payment_status) != ?', ['paid'])
            ->where('order_status', 'delivered')
            ->orderBy('created_at', 'asc')
            ->get();
        Log::info('PREVIOUS ORDERS COUNT', [
            'count' => $previousOrders->count()
        ]);


        foreach ($previousOrders as $prevOrder) {

            if ($remainingAmount <= 0) {
                break;
            }
            $remaining = $prevOrder->balance_amount;

            if ($remaining <= 0) continue;

            foreach ($sectionDr as $row) {
                if ($row['debit_amount'] <= 0) continue;

                $applied = min($row['debit_amount'], $remaining);

                $prevOrder->update([
                    'paid_amount' => $prevOrder->paid_amount + $applied,
                    'balance_amount' => $remaining - $applied,
                    'payment_status' => ($remaining - $applied) <= 0 ? "Paid" : "Partial",
                ]);

                $receivedAccount = Account::find($row['account_id']);

                $paymentMode = strtolower($receivedAccount->name);
                // Save with correct payment mode
                OrderPayment::create([
                    'order_id' => $prevOrder->id,
                    'customer_id' => $customer->id,
                    'user_id' => $userId ?? 1,
                    'total_amount' => $prevOrder->total_amount,
                    'paid_amount' => $applied,
                    'balance_amount' => $prevOrder->balance_amount,
                    'payment_mode' => $paymentMode, // Correct mode
                    'payment_date' => $voucher->voucher_date ?? now(),
                    'description' => $voucher->description,
                    'financial_year_id'  => $financialYear->id,
                ]);
                Log::info('BEFORE CUSTOMER SAVE', [
                    'customer_balance_before' => $customer->balance_amount,
                    'applied' => $applied,
                ]);

                $customer->balance_amount -= $applied;
                $customer->save();
                Log::info('AFTER CALCULATION', [
                    'customer_balance_after' => $customer->balance_amount,
                ]);


                // $customer->refresh();

                Log::info('AFTER SAVE', [
                    'db_balance' => $customer->balance_amount,
                ]);

                // Calculate commission
                if ($prevOrder->order_by === $prevOrder->delivered_by) {
                    $this->handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customer->id, $voucher->voucher_date);
                } else {
                    $this->handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customer->id, $voucher->voucher_date);
                }

                // Reduce from this payment mode
                $row['debit_amount'] -= $applied;

                $remaining -= $applied;

                if ($remaining <= 0) break;
            }
        }
    }
    protected function handleSalesManCommissionPreviousOrder($prevOrder, $applied, $customerId, $date = null)
    {
        try {
            $orderItems = $prevOrder->orderItems;

            foreach ($orderItems as $item) {


                // if customer have special price that product, then take special price, otherwise take product price

                $commissionRate = $this->getCommissionRate($prevOrder, $item, 'salesman');

                // Full possible commission for this item (if fully paid)
                $fullCommission = $item->quantity * $commissionRate;

                // Ratio of how much of this order got paid in THIS applied portion
                $paidRatio = $applied / $prevOrder->total_amount;

                // Final commission for this applied payment
                $commissionAmount = $fullCommission * $paidRatio;

                // --- Save in CommissionMaster ---
                if ($prevOrder->order_status === 'delivered') {
                    // store sales man commission
                    $this->saveDataToCommissionMaster($item, $commissionAmount, $prevOrder->delivered_by, $prevOrder, $date);
                }
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    protected function saveDataToCommissionMaster($item, $commissionAmount, $userId, $order = null, $date = null)
    {
        try {

            // DB::beginTransaction();

            $commissionAmount = round($commissionAmount, 2);

            if ($commissionAmount <= 0) {
                return;
            }
            Log::channel('commission_history')->info("------------saveDataToCommissionMaster---------------");

            Log::channel('commission_history')->info('Commission History Details', [
                'order_id' => $order->id ?? null,
                'product_id' => $item->product_id,
                'user_id' => $userId,
                'amount' => $commissionAmount,
                'date' => $date ?? "No date provided",
            ]);


            $commissionMaster = CommissionMaster::where('user_id', $userId)
                ->where('product_id', $item->product_id)
                ->first();

            if ($commissionMaster) {
                $commissionMaster->update([
                    'commission_amount' => $commissionMaster->commission_amount + $commissionAmount,
                    'updated_by' => $userId,
                ]);
            } else {
                $commissionMaster = CommissionMaster::create([
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                    'commission_amount' => $commissionAmount,
                    'created_by' => $userId,
                ]);
            }

            // --- Incentive update ---
            $incentive = Incentive::where('user_id', $userId)->first();
            if ($incentive) {
                $incentive->update([
                    'total_commission' => $incentive->total_commission + $commissionAmount,
                ]);
            } else {
                Incentive::create([
                    'user_id' => $userId,
                    'total_commission' => $commissionAmount,
                ]);
            }

            // --- History ---
            $commission = CommissionHistory::create([
                'user_id' => $userId,
                'commision_master_id' => $commissionMaster->id,
                'order_id' => $order->id ?? null,
                'product_id' => $item->product_id,
                'product_quantity' => $item->quantity,
                'product_price' => $item->price,
                'total_amount' => $item->total,
                'commission_amount' => $commissionAmount,
                'created_by' => $userId,
                'date' => $date ?? $order->order_date ?? now(),
            ]);


            $employee = Employee::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($employee) {
                $employee->increment('commission', $commissionAmount);
                $employee->save();
            } else {
                Log::warning('Employee not found for commission update', [
                    'user_id' => $userId,
                    'order_id' => $order->id ?? null,
                    'commission_amount' => $commissionAmount,
                ]);
            }


            Log::channel('commission_history')->info('Commission History updated');

            return;
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('commission_history')->error('Error in saving Commission History', [
                'error' => $e->getMessage(),
            ]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    protected function handleSalesExecutiveCommissionPreviousOrder($prevOrder, $applied, $customerId, $date = null)
    {
        try {
            $orderItems = $prevOrder->orderItems;

            foreach ($orderItems as $item) {

                // if customer have special price that product, then take special price, otherwise take product price

                $commissionRate = $this->getCommissionRate($prevOrder, $item, 'executive');

                // Full possible commission for this item (if fully paid)
                $fullCommission = $item->quantity * $commissionRate;

                // Ratio of how much of this order got paid in THIS applied portion
                $paidRatio = $applied / $prevOrder->total_amount;

                // Final commission for this applied payment
                $commissionAmount = $fullCommission * $paidRatio;

                // --- Save in CommissionMaster ---
                $this->saveDataToCommissionMaster($item, $commissionAmount, $prevOrder->order_by, $prevOrder, $date);
            }
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    protected function getCommissionRate($order, $item, $type = 'executive')
    {


        try {
            $commissionRate = 0;



            // take commission_type from order table
            $commission_type_id = $order->commission_type_id;

            if (!$commission_type_id) {
                return 0;
            }

            // get commission type master
            $commission_type = CommisionTypeMaster::where('id',  $commission_type_id)->first();


            if (!$commission_type) {
                return 0;
            }

            // get product type
            $product_type = $item->product->product_type;

            if (!$product_type) {
                return 0;
            }

            // get commission type item
            $commission_type_item = CommissionTypeItem::where('commission_type_master_id', $commission_type_id)
                ->where('product_type', $product_type)
                ->first();


            if ($commission_type_item) {
                switch ($type) {
                    case 'executive':

                        $commissionRate = $commission_type_item->executive_commission;

                        break;
                    case 'salesman':
                        $commissionRate = $commission_type_item->salesman_commission;

                        break;
                    case 'delivery_vehicle':
                        $commissionRate = $commission_type_item->delivery_vehicle_commission;

                        break;
                    default:

                        $commissionRate = 0;
                }

                return $commissionRate;
            } else {
                return 0;
            }
        } catch (Exception $e) {

            return errorResponse(null, $e->getMessage(), 100, 500);
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
}
