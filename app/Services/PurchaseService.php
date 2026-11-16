<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\FinancialYear;
use App\Models\GlobalTax;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Products;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    protected JournalEntryService $journalEntryService;


    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    //   public function purchase_store($data = null, $platform = 'web')
    // {
    //     try {
    //         DB::beginTransaction();

    //         $productRows = json_decode($data->product_rows, true) ?? [];
    //         $paymentRows = json_decode($data->payment_rows, true) ?? [];

    //         $total = 0;
    //         $totalTax = 0;


    //         foreach ($productRows as $row) {


    //             $unitPrice = (float)$row['unit_price'];
    //             $qty       = (float)$row['qty'];
    //             $lineTotal = $unitPrice * $qty;

    //             $taxAmount = 0;
    //             $taxId     = !empty($row['tax_id']) ? $row['tax_id'] : null;

    //             if ($taxId) {
    //                 $tax = GlobalTax::find($taxId);
    //                 if ($tax) {
    //                     $taxAmount = ($lineTotal * $tax->tax) / 100;
    //                 }
    //             }

    //             $total += $lineTotal;
    //             $totalTax += $taxAmount;
    //             $payment_status = 'completed';
    //             $payment_total  = 0;
    //             foreach ($paymentRows as $row) {

    //                 if ($row['payment_mode'] === 'credit') {
    //                     $payment_status = 'pending';
    //                 } else {
    //                     $payment_total += (float)$row['amount_taken'];
    //                 }
    //             }
    //         }

    //         $grandTotal = $total + $totalTax;

    //         // =============================
    //         // CREATE PURCHASE
    //         // =============================

    //         $financialYear = FinancialYear::where('start_date', '<=', $data->purchase_date)
    //             ->where('end_date', '>=', $data->purchase_date)
    //             ->first();

    //         $purchase = Purchase::create([
    //             'purchase_no'   => $data->purchase_no,
    //             'supplier_id'   => $data->supplier_id,
    //             'purchase_date' => $data->purchase_date,
    //             'financial_year_id' => $financialYear->id,
    //             'total'         => $grandTotal,
    //             'remarks'       => $data->remarks,
    //             'created_by'    => $data->user_id,
    //             'payment_total' => $payment_total,
    //             'payment_status' => $payment_status,

    //         ]);



    //         // =============================
    //         // SAVE ITEMS + STOCK
    //         // =============================
    //         foreach ($productRows as $row) {

    //             $unitPrice = (float)$row['unit_price'];
    //             $qty       = (float)$row['qty'];
    //             $lineTotal = $unitPrice * $qty;

    //             $taxAmount = 0;
    //             $taxId     = !empty($row['tax_id']) ? $row['tax_id'] : null;

    //             if ($taxId) {
    //                 $tax = GlobalTax::find($taxId);
    //                 if ($tax) {
    //                     $taxAmount = ($lineTotal * $tax->tax) / 100;
    //                 }
    //             }

    //             PurchaseItem::create([
    //                 'purchase_id' => $purchase->id,
    //                 'product_id'  => $row['product_id'],
    //                 'qty'         => $qty,
    //                 'tax_id'      => $taxId,
    //                 'tax_amount'  => $taxAmount,
    //                 'unit_price'  => $unitPrice,
    //                 'total'       => $lineTotal + $taxAmount,
    //                 'created_by'  => $data->user_id,
    //             ]);

    //             Products::where('id', $row['product_id'])
    //                 ->increment('total_stock', $qty);

    //             StockMovement::create([
    //                 'ref_id'        => $purchase->id,
    //                 'ref_no'        => $purchase->purchase_no,
    //                 'product_id'    => $row['product_id'],
    //                 'quantity'      => $qty,

    //                 'movement_type' => 'in',
    //                 'reason'        => 'Purchase',
    //                 'created_by'    => $data->user_id,
    //                 'date'          => $data->purchase_date,
    //             ]);
    //         }

    //         $supplier = Supplier::find($data->supplier_id);
    //         $supplierName = $supplier ? $supplier->supplier_name : '';
    //         $supplierCode = $supplier ? $supplier->supplier_code : '';
    //         // =============================
    //         // CREATE PURCHASE JOURNAL ENTRY
    //         // =============================
    //         $this->journalEntryService->createPurchaseEntry([
    //             'date' => $data->purchase_date,
    //             'net_amount' => $total,
    //             'tax_amount' => $totalTax,
    //             'grand_total' => $grandTotal,
    //             'reference_type' => 'App\\Models\\Purchase',
    //             'reference_id' => $purchase->id,
    //             'description' => 'Purchase - ' . $purchase->purchase_no,
    //             'supplier_id' => $supplier->id,
    //             'supplier_name' => $supplier->supplier_name,
    //             'supplier_code' => $supplier->supplier_code,
    //             'user_id' => $data->user_id,
    //         ]);

    //         // =============================
    //         // PAYMENT ENTRIES
    //         // =============================
    //         foreach ($paymentRows as $row) {

    //             $paymentAmount = (float)$row['amount_taken'];
    //             $paymentMode   = $row['payment_mode'];



    //             $purchasePayment = PurchasePayment::create([
    //                 'purchase_id'  => $purchase->id,
    //                 'amount_taken' => $paymentAmount,
    //                 'created_by'   => $data->user_id,
    //                 'payment_date' => $data->purchase_date,
    //             ]);

    //             if ($paymentMode === 0) {
    //                 // continue;

    //                 $supplier->increment('balance', $paymentAmount);
    //             } else {

    //                 $jo = $this->journalEntryService->createPurchasePaymentEntry([
    //                     'date' => $data->purchase_date,
    //                     'amount' => $paymentAmount,
    //                     'reference_type' => 'App\\Models\\PurchasePayment',
    //                     'reference_id' => $purchasePayment->id,
    //                     'description' => 'Payment - ' . $purchase->purchase_no,
    //                     'supplier_id' => $supplier->id,
    //                     'supplier_name' => $supplier->supplier_name,
    //                     'supplier_code' => $supplier->supplier_code,
    //                     'payment_mode' => $paymentMode,
    //                     'user_id' => $data->user_id,
    //                 ]);
    //             }
    //         }

    //         DB::commit();
    //         return successResponse($purchase, 'Purchase created successfully.', 0, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    // private function normalizeInvoiceNumber($value): string
    // {
    //     $rawValue = trim((string) ($value ?? ''));
    //     if ($rawValue === '') {
    //         throw new Exception('Invoice number is required.');
    //     }

    //     $normalized = strtoupper($rawValue);
    //     $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);

    //     if (preg_match('/^IN(?:VOICE)?(\d+)$/', $normalized, $matches)) {
    //         return 'IN' . $matches[1];
    //     }

    //     if (preg_match('/^(\d+)$/', $normalized, $matches)) {
    //         return 'IN' . $matches[1];
    //     }

    //     $digits = preg_replace('/\D/', '', $normalized);
    //     if ($digits !== '') {
    //         return 'IN' . $digits;
    //     }

    //     return 'IN' . $normalized;
    // }

    // private function findDuplicatePurchaseInvoice(string $invoiceNumber, $ignoreId = null)
    // {
    //     $query = Purchase::query()->where(function ($q) use ($invoiceNumber) {
    //         $q->where('purchase_no', $invoiceNumber)
    //             ->orWhere('invoice_number', $invoiceNumber);
    //     });

    //     if ($ignoreId) {
    //         $query->where('id', '!=', $ignoreId);
    //     }

    //     return $query->first();
    // }


    private function generatePurchaseNo()
    {
        $lastPurchase = Purchase::orderBy('id', 'desc')->first();

        if ($lastPurchase && preg_match('/PO(\d+)/', $lastPurchase->purchase_no, $matches)) {
            $lastNumber = (int) $matches[1];
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 101;
        }

        return 'PO' . $nextNumber;
    }

    public function purchase_store($data = null, $platform = 'web')
    {
        // dd($data);
        try {
            DB::beginTransaction();

            // Decode JSON strings to arrays
            $productRows = json_decode($data->product_rows, true);
            $paymentRows = json_decode($data->payment_rows, true);

            $total = 0;
            $totalTax = 0;



            foreach ($productRows as $row) {


                $unitPrice = (float)$row['unit_price'];
                $qty       = (float)$row['qty'];
                $lineTotal = $unitPrice * $qty;

                $taxAmount = 0;
                $taxId     = !empty($row['tax_id']) ? $row['tax_id'] : null;

                if ($taxId) {
                    $tax = GlobalTax::find($taxId);
                    if ($tax) {
                        $taxAmount = ($lineTotal * $tax->tax) / 100;
                    }
                }

                $total += $lineTotal;
                $totalTax += $taxAmount;
                $payment_status = 'completed';
                $payment_total  = 0;
            }
            $grandTotal = $total + $totalTax;
            $payment_status = 'completed';
            // Calculate total payment
            $payment_total = 0;
            $total_refunded_amount = 0;
            $supplier = Supplier::find($data->supplier_id);
            foreach ($paymentRows as $row) {

                if (strtolower($row['payment_mode']) == 'credit') {

                    if ($supplier->balance < 0) {
                        $supplierCreditBalance = $supplier->balance * (-1);

                        if ($supplierCreditBalance > $row['amount_taken']) {
                            $total_refunded_amount = $row['amount_taken'];
                            $payment_status = 'completed';
                        } else {
                            $total_refunded_amount = $supplierCreditBalance;
                            $payment_status = 'pending';
                        }
                    } else
                        $payment_status = 'pending';
                } else {
                    $payment_total += $row['amount_taken'];
                }
            }

            $financialYear = FinancialYear::whereDate('start_date', '<=', $data->purchase_date)
                ->whereDate('end_date', '>=', $data->purchase_date)
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
            $balance = $grandTotal - $payment_total - $total_refunded_amount;
            $purchase = Purchase::Create([
                'purchase_no' => $data->purchase_no,
                // 'invoice_number' => $invoiceNumber,
                'supplier_id' => $data->supplier_id,
                'purchase_date' => $data->purchase_date,
                'financial_year_id' => $financialYear->id,
                'total' => $grandTotal,
                'payment_total' => $payment_total,
                'total_refund_amount' => $total_refunded_amount,
                'balance_amount' => $balance,
                'payment_status' => $payment_status,
                'remarks' => $data->remarks,
                'created_by' => $data->user_id,
            ]);



            // Store each product item
            foreach ($productRows as $row) {
                $lineTotal = $row['unit_price'] * $row['qty'];
                $taxAmount = 0;
                if (!empty($row['tax_id'])) {
                    $tax = GlobalTax::find($row['tax_id']);
                    if ($tax) {
                        $taxAmount = ($lineTotal * $tax->tax) / 100;
                        $lineTotal += $taxAmount;
                    }
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $row['product_id'],
                    'qty'         => $row['qty'],
                    'tax_id'      => $row['tax_id'] ?? null,
                    'tax_amount'  => $taxAmount,
                    'unit_price'  => $row['unit_price'],
                    'total'       => $lineTotal,
                    'created_by'  => $data->user_id,
                ]);

                // Update stock in products table
                Products::where('id', $row['product_id'])
                    ->increment('total_stock', $row['qty']);


                // Insert into stock_movements
                StockMovement::create([
                    'ref_id'       => $purchase->id,
                    'ref_no'       => $purchase->purchase_no,
                    'product_id'   => $row['product_id'],
                    'quantity'     => $row['qty'],
                    'movement_type' => 'in',
                    'reason'       => 'Purchase',
                    'created_by'   => $data->user_id,
                    'date' => $purchase->purchase_date,
                ]);

                $cost_price = $lineTotal / $row['qty'];
                StockOriginal::create([
                    'ref_id'       => $purchase->id,
                    'ref_no'       => $purchase->purchase_no,
                    'product_id'   => $row['product_id'],
                    'quantity'     => $row['qty'],
                    'cost_price' => $cost_price,
                    'movement_type' => 'in',
                    'reason'       => 'Purchase',
                    'date' => $data->purchase_date,
                    'created_by'   => $data->user_id,
                ]);
            }
            // =============================
            // CREATE PURCHASE JOURNAL ENTRY
            // =============================
            $jo = $this->journalEntryService->createPurchaseEntry([
                'date' => $data->purchase_date,
                'total_amount' => $grandTotal,
                'reference_type' => 'App\\Models\\Purchase',
                'reference_id' => $purchase->id,
                'description' => $purchase->purchase_no,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,
                'user_id' => $data->user_id,
            ]);
            if (!$jo) {

                DB::rollback();
                return errorResponse(null, 'Failed to create journal entry. Purchase creation cancelled.', 1, 500);
            }

            $purchase->journal_entry_id = $jo->id;
            $purchase->save();
            // Store each payment row
            foreach ($paymentRows as $k => $row) {

                $paymentMode = $paymentRows[$k]['payment_mode'] ?? '';

                $amount = $paymentRows[$k]['amount_taken'] ?? 0;

                $purchasePayment = PurchasePayment::create([
                    'purchase_id'  => $purchase->id,
                    'supplier_id'  => $data->supplier_id,
                    'amount_taken'       => $row['amount_taken'],
                    'payment_mode' => $row['payment_mode'],
                    'created_by' => $data->user_id,
                    'payment_date' => $data->purchase_date,
                    'financial_year_id' => $financialYear->id,
                ]);


                // If payment is credit, update supplier credit balance
                if (strtolower($row['payment_mode']) === 'credit') {
                    // Supplier::where('id', $data->supplier_id)
                    //     ->increment('balance', $row['amount_taken']);
                    // continue;

                    $supplier->balance += $row['amount_taken'];
                    $supplier->save();
                    continue;
                }
                // =============================
                // CREATE PURCHASE PAYMENT JOURNAL ENTRY
                // =============================

                $jo1 = $this->journalEntryService->createPurchasePaymentEntry([
                    'date' => $data->purchase_date,
                    'total_amount' => $grandTotal,
                    'amount' => $amount,
                    'reference_type' => 'App\\Models\\PurchasePayment',
                    'reference_id' => $purchasePayment->id,
                    'description' => $purchase->purchase_no,
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->supplier_name,
                    'supplier_code' => $supplier->supplier_code,
                    'payment_mode' => $paymentMode,
                    'user_id' => $data->user_id,
                ]);
                if (!$jo1) {

                    DB::rollback();
                    return errorResponse(null, 'Failed to create journal entry. Purchase creation cancelled.', 1, 500);
                }
                $purchasePayment->journal_entry_id = $jo1->id;
                $purchasePayment->save();
            }


            DB::commit();
            return successResponse($purchase, 'Purchase created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // public function purchase_store($data = null, $platform = 'web')
    // {
    //     try {
    //         DB::beginTransaction();

    //         $productRows = json_decode($data->product_rows, true) ?? [];
    //         $paymentRows = json_decode($data->payment_rows, true) ?? [];

    //         $total = 0;
    //         foreach ($productRows as $row) {
    //             $total += (float)$row['unit_price'] * (float)$row['qty'];
    //         }

    //         $payment_status = 'completed';
    //         $payment_total  = 0;
    //         foreach ($paymentRows as $row) {
    //             if (strtolower($row['payment_mode']) === 'credit') {
    //                 $payment_status = 'pending';
    //             } else {
    //                 $payment_total += (float)$row['amount_taken'];
    //             }
    //         }

    //         $purchase = Purchase::create([
    //             'purchase_no'   => $data->purchase_no,
    //             'supplier_id'   => $data->supplier_id,
    //             'purchase_date' => $data->purchase_date,
    //             'total'         => $total,
    //             'payment_total' => $payment_total,
    //             'payment_status' => $payment_status,
    //             'remarks'       => $data->remarks,
    //             'created_by'    => $data->user_id,
    //         ]);

    //         foreach ($productRows as $row) {
    //             $unitPrice = (float)$row['unit_price'];
    //             $qty       = (float)$row['qty'];
    //             $lineTotal = $unitPrice * $qty;

    //             $taxAmount = 0;
    //             $taxId     = !empty($row['tax_id']) ? $row['tax_id'] : null;

    //             if ($taxId) {
    //                 $tax = GlobalTax::find($taxId);
    //                 if ($tax) {
    //                     $taxAmount = ($lineTotal * $tax->tax) / 100;
    //                     $lineTotal += $taxAmount;
    //                 }
    //             }

    //             PurchaseItem::create([
    //                 'purchase_id' => $purchase->id,
    //                 'product_id'  => $row['product_id'],
    //                 'qty'         => $qty,
    //                 'tax_id'      => $taxId,
    //                 'tax_amount'  => $taxAmount,
    //                 'unit_price'  => $unitPrice,
    //                 'total'       => $lineTotal,
    //                 'created_by'  => $data->user_id,
    //             ]);

    //             Products::where('id', $row['product_id'])
    //                 ->increment('total_stock', $qty);

    //             // Record stock movement
    //             StockMovement::create([
    //                 'ref_id'       => $purchase->id,
    //                 'ref_no'       => $purchase->purchase_no,
    //                 'product_id'   => $row['product_id'],
    //                 'quantity'     => $qty,
    //                 'movement_type' => 'in',
    //                 'reason'       => 'Purchase',
    //                 'created_by'   => $data->user_id,
    //                 'created_at'   =>  $data->purchase_date,
    //                 'date'   =>  $data->purchase_date,
    //             ]);
    //         }

    //         // Get supplier name for journal entry
    //         $supplier = Supplier::find($data->supplier_id);
    //         $supplierName = $supplier ? $supplier->supplier_name : '';
    //         $supplierCode = $supplier ? $supplier->supplier_code : '';


    //         foreach ($paymentRows as $row) {
    //             $paymentAmount = (float)$row['amount_taken'];
    //             $paymentMode = $row['payment_mode'];

    //             $purchasePayment = PurchasePayment::create([
    //                 'purchase_id'  => $purchase->id,
    //                 'amount_taken' => $paymentAmount,
    //                 'payment_mode' => $paymentMode,
    //                 'created_by'   => $data->user_id,
    //                 'payment_date' => $data->purchase_date,
    //             ]);

    //             if (strtolower($paymentMode) === 'credit') {
    //                 Supplier::where('id', $data->supplier_id)
    //                     ->increment('balance', $paymentAmount);
    //             } else {
    //                 // Create journal entry for cash/bank payment
    //                 $this->journalEntryService->createPurchasePaymentEntry([
    //                     'date' => $data->purchase_date,
    //                     'amount' => $paymentAmount,
    //                     'reference_type' => 'App\\Models\\PurchasePayment',
    //                     'reference_id' => $purchasePayment->id,
    //                     'description' => 'Purchase payment - ' . $purchase->purchase_no,
    //                     'supplier_name' => $supplierName,
    //                     'supplier_code' => $supplierCode,
    //                     'payment_mode' => $paymentMode,
    //                     'user_id' => $data->user_id,
    //                 ]);
    //             }
    //         }

    //         // Create journal entry for the purchase (inventory in, payable out)
    //         $this->journalEntryService->createPurchaseEntry([
    //             'date' => $data->purchase_date,
    //             'amount' => $total,
    //             'reference_type' => 'App\\Models\\Purchase',
    //             'reference_id' => $purchase->id,
    //             'description' => 'Purchase from supplier - ' . $purchase->purchase_no,
    //             'supplier_name' => $supplierName,
    //             'supplier_code' => $supplierCode,
    //             'purchase_no' => $purchase->purchase_no,
    //             'user_id' => $data->user_id,
    //         ]);

    //         DB::commit();
    //         return successResponse($purchase, 'Purchase created successfully.', 0, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }



    public function genaratePurchaseCode()
    {
        $auto_invoice_code = AutoIncrementMaster::where('auto_code', 'AI_PURCHASE_CODE')->first();
        $currentValue = $auto_invoice_code->auto_increment_general_value;
        $auto_invoice_code->auto_increment_general_value = $currentValue + 1;
        $auto_invoice_code->save();

        return $auto_invoice_code->auto_prefix . $currentValue;
    }

    public function purchase_update($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            // Find the existing purchase
            $purchase = Purchase::findOrFail($data['id']);

            $financialYear = FinancialYear::whereDate('start_date', '<=', $data['purchase_date'])
                ->whereDate('end_date', '>=', $data['purchase_date'])
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

            // Find the new supplier
            $supplier = Supplier::find($data['supplier_id']);

            // Decode JSON strings to arrays
            $productRows = json_decode($data['product_rows'], true);
            $paymentRows = json_decode($data['payment_rows'], true);


            $total = 0;

            // Revert stock from old purchase items
            $oldItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

            foreach ($oldItems as $oldItem) {
                Products::where('id', $oldItem->product_id)
                    ->decrement('total_stock', $oldItem->qty);
            }

            // Delete old records
            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            // delete purchase journal and voucher entries
            $oldPurchaseJournals = JournalEntry::where('reference_type', 'App\Models\Purchase')
                ->where('reference_id', $purchase->id)
                ->get();
            foreach ($oldPurchaseJournals as $oldPJ) {

                // JournalEntryLine::where('journal_entry_id', $oldPJ->id)->delete();

                // Voucher entries delete
                $voucher = \App\Models\Voucher::where('journal_entry_id', $oldPJ->id)->first();
                if ($voucher) {
                    \App\Models\VoucherEntry::where('voucher_id', $voucher->id)->delete();
                    $voucher->delete();
                }

                // Reverse the original journal entry

                $oldPJ->status = 'reversed';
                $oldPJ->save();

                $this->updateAccountBalances($oldPJ);

                // if ($oldPJ->status === 'posted') {
                //     $this->journalEntryService->createReverseEntry($oldPJ, 'Reversal due to purchase update');
                // }
            }

            // Revert old payments (including credit balance)
            $oldPayments = PurchasePayment::where('purchase_id', $purchase->id)->get();
            foreach ($oldPayments as $oldPay) {
                if (strtolower($oldPay->payment_mode) === 'credit') {
                    Supplier::where('id', $purchase->supplier_id)
                        ->decrement('balance', $oldPay->amount_taken);
                }

                // PurchasePayment reference journal entries delete

                $oldJournals = JournalEntry::where('reference_type', 'App\Models\PurchasePayment')
                    ->where('reference_id', $oldPay->id)
                    ->get();

                foreach ($oldJournals as $oldJournal) {
                    // JournalEntryLine::where('journal_entry_id', $oldJournal->id)->delete();
                    $voucher = \App\Models\Voucher::where('journal_entry_id', $oldJournal->id)->first();
                    if ($voucher) {
                        \App\Models\VoucherEntry::where('voucher_id', $voucher->id)->delete();
                        $voucher->delete();
                    }

                    // Reverse the original journal entry

                    $oldJournal->status = 'reversed';
                    $oldJournal->save();
                    $this->updateAccountBalances($oldJournal);

                    // if ($oldJournal->status === 'posted') {
                    //     $this->journalEntryService->reverseAndPostEntry($oldJournal, 'Reversal due to purchase update');
                    // }
                }

                $oldPay->delete();
            }



            StockMovement::where('ref_id', $purchase->id)
                ->where('ref_no', $purchase->purchase_no)
                ->delete();
            StockOriginal::where('ref_id', $purchase->id)
                ->where('ref_no', $purchase->purchase_no)
                ->delete();

            foreach ($productRows as $row) {
                $unitPrice = (float)$row['unit_price'];
                $qty       = (float)$row['qty'];
                $lineTotal = $unitPrice * $qty;

                $taxAmount = 0;
                $taxId     = !empty($row['tax_id']) ? $row['tax_id'] : null;
                if ($taxId) {
                    $tax = GlobalTax::find($taxId);
                    if ($tax) {
                        $taxAmount = ($lineTotal * $tax->tax) / 100;
                        $lineTotal += $taxAmount;
                    }
                }
                $total += $lineTotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $row['product_id'],
                    'qty'         => $qty,
                    'tax_id'      => $taxId,
                    'tax_amount'  => $taxAmount,
                    'unit_price'  => $unitPrice,
                    'total'       => $lineTotal,
                    'updated_by'  => $data['user_id'],
                ]);

                // Increase stock
                Products::where('id', $row['product_id'])
                    ->increment('total_stock', $qty);

                StockMovement::create([
                    'ref_id'       => $purchase->id,
                    'ref_no'       => $purchase->purchase_no,
                    'product_id'   => $row['product_id'],
                    'quantity'     => $qty,

                    'movement_type' => 'in',
                    'reason'       => 'Purchase',
                    'updated_by'  => $data['user_id'],
                    'created_at'   =>  $data->purchase_date,
                    'date'   =>  $data->purchase_date,
                ]);

                $cost_price = $lineTotal / $row['qty'];
                StockOriginal::create([
                    'ref_id'       => $purchase->id,
                    'ref_no'       => $purchase->purchase_no,
                    'product_id'   => $row['product_id'],
                    'quantity'     => $row['qty'],
                    'cost_price' => $cost_price,
                    'movement_type' => 'in',
                    'reason'       => 'Purchase',
                    'date' => $data->purchase_date,
                    'created_by'   => $data->user_id,
                ]);
            }

            //create new journal and voucher entry
            $jo = $this->journalEntryService->createPurchaseEntry([
                'date' => $data->purchase_date,
                'total_amount' => $total,
                'reference_type' => 'App\\Models\\Purchase',
                'reference_id' => $purchase->id,
                'description' => 'Purchase - ' . $purchase->purchase_no,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,

                'user_id' => $data->user_id,
            ]);

            if (!$jo) {

                DB::rollBack();
                return errorResponse(null, 'Failed to create journal entry for purchase.', 100, 500);
            }
            $purchase->journal_entry_id = $jo->id;
            $purchase->save();

            // Handle payments
            $payment_total = 0;
            $payment_status = $purchase->payment_status;
            foreach ($paymentRows as $row) {

                $amount = round(floatval($row['amount_taken']), 4);

                $purchasePayment = PurchasePayment::create([
                    'purchase_id'  => $purchase->id,
                    'supplier_id'  => $data->supplier_id,
                    'payment_mode' => $row['payment_mode'],
                    'amount_taken' => $amount,
                    'updated_by'   => $data['user_id'],
                    'payment_date'   =>  $data->purchase_date,
                    'financial_year_id' => $financialYear->id,
                ]);
                if (strtolower($row['payment_mode']) === 'credit') {
                    // Supplier::where('id', $data['supplier_id'])
                    //     ->increment('balance', $amount);
                    // continue;

                    $supplier->balance += $row['amount_taken'];
                    $supplier->save();
                    continue;
                }
                $payment_total += $amount;

                $jo1 = $this->journalEntryService->createPurchasePaymentEntry([
                    'date' => $data->purchase_date,
                    'amount' => $amount,
                    'reference_type' => 'App\\Models\\PurchasePayment',
                    'reference_id' => $purchasePayment->id,
                    'description' => $purchase->purchase_no,
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->supplier_name,
                    'supplier_code' => $supplier->supplier_code,
                    'payment_mode' => $row['payment_mode'],
                    'user_id' => $data->user_id,
                ]);



                if (!$jo1) {

                    DB::rollBack();
                    return errorResponse(null, 'Failed to create journal entry for purchase payment.', 100, 500);
                }
                $purchasePayment->journal_entry_id = $jo1->id;
                $purchasePayment->save();
            }

            if ($total == $payment_total) {
                $payment_status = 'completed';
            }
            $balance = $total - $payment_total;
            // Update purchase record
            $purchase->update([

                'supplier_id'    => $data['supplier_id'],
                'purchase_date'  => $data['purchase_date'],
                'total'          => $total,
                'payment_total'  => $payment_total,
                'balance_amount' => $balance,
                'payment_status' => $payment_status,
                'remarks'        => $data['remarks'],
                'updated_by'     => $data['user_id'],
            ]);

            DB::commit();
            return successResponse($purchase, 'Purchase updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
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
    public function getAllPurchase($data, $platform = 'web')
    {
        try {

            $query = Purchase::with('supplier', 'purchaseItem.product', 'purchasePayment');

            if (!empty($data->search)) {
                $query->where(function ($q) use ($data) {
                    $q->where('purchase_no', 'LIKE', "%{$data->search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($data) {
                            $supplierQuery->where('supplier_name', 'LIKE', "%{$data->search}%")
                                ->orWhere('supplier_code', 'LIKE', "%{$data->search}%");
                        });
                });
            }
            if (!empty($data->financial_year_id)) {
                $query->where('financial_year_id', $data->financial_year_id);
            } else {
                $today = now();

                $financialYear = FinancialYear::where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                if ($financialYear) {
                    $query->where('financial_year_id', $financialYear->id);
                }
            }
            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $result->getCollection()->transform(function ($purchase) {
                    $purchase->paid_amount = $purchase->purchasePayment
                        ->where('payment_mode', '!=', 'credit')
                        ->sum('amount_taken');

                    return $purchase;
                });
                $response = [
                    'purchases'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Purchase retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Purchase retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function purchaseDelete($id)
    {
        try {
            DB::beginTransaction();
            $purchase = Purchase::findOrFail($id);
            if ($purchase) {
                $purchase->delete();
                DB::commit();
                return successResponse(null, 'Purchase deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Purchase not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function generateNextPurchaseRefCode()
    {
        try {
            $lastPurchase = Purchase::orderBy('id', 'desc')->first();

            if ($lastPurchase && preg_match('/PO(\d+)/', $lastPurchase->purchase_no, $matches)) {
                $lastNumber = (int) $matches[1];
                $nextNumber = $lastNumber + 1;
            } else {
                // Starting number if no purchase exists
                $nextNumber = 101;
            }
            $newOrderCode = 'PO' . $nextNumber;
            $response = [
                'purchase_ref_code' => $newOrderCode
            ];
            // return apiResponse(0, 'success', $response);
            return successResponse($response, 'Purchase reference code successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 500);
        }
    }

    public function purchaseCreditPayment($request)
    {


        try {
            DB::beginTransaction();

            $supplierId = $request->supplier_id;
            $paidAmount = $request->amount;


            $paymentMode = $request->payment_mode ?? 'cash';
            $userId = $request->user_id; // logged-in user
            $description = $request->description ?? '';

            $requested_date = isset($request->date) && !empty($request->date)
                ? \Carbon\Carbon::parse($request->date)->format('Y-m-d H:i:s')
                : now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
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
            // 1. Reduce supplier balance
            $supplier = Supplier::findOrFail($supplierId);


            if ($supplier->balance < $paidAmount) {
                DB::rollBack();
                return errorResponse(null, "Insufficient supplier balance.", 400);
            }
            // $supplier->balance -= $paidAmount;
            // $supplier->save();


            //2. reduce from opening balance
            $opening_balance = 0;
            $openeing_total_amount = SupplierOpeningBalance::where('supplier_id', $supplierId)
                ->sum('amount');

            $openeing_paid_amount = SupplierOpeningBalance::where('supplier_id', $supplierId)
                ->sum('paid_amount');


            $opening_balance = $openeing_total_amount - $openeing_paid_amount;

            if ($opening_balance > 0) {

                $applied = min($paidAmount, $opening_balance);


                // Update Opening Balance
                SupplierOpeningBalance::where('supplier_id', $supplierId)
                    ->increment('paid_amount', $applied);



                $purchasePayment = PurchasePayment::create([

                    'amount_taken' => $applied,
                    'supplier_id' => $supplierId,
                    'payment_mode' => $paymentMode,
                    'created_by'   => $userId,
                    'payment_date' => $requested_date,
                    'description' => $description,
                    'type' => 'opening_balance',
                    'financial_year_id' => $financialYear->id,
                ]);
                $supplier->balance -= $applied;
                $supplier->save();

                $journalEntry = $this->journalEntryService->createPurchasePaymentEntry([
                    'date' => $requested_date,
                    'amount' => $applied,
                    'reference_type' => 'App\\Models\\PurchaseOpeningbalancePayment',
                    'reference_id' => $purchasePayment->id,
                    'description' => 'Opening balance payment to supplier' . $supplier->supplier_name,
                    'supplier_id' => $supplier->id ?? '',
                    'supplier_name' => $supplier->supplier_name ?? '',
                    'supplier_code' => $supplier->supplier_code ?? '',
                    'payment_mode' => $paymentMode,
                    'user_id' => $userId,
                ]);

                if (!$journalEntry) {
                    return errorResponse(null, 'Failed to create journal entry', 500);
                }
                $paidAmount -= $applied;
                $purchasePayment->journal_entry_id = $journalEntry->id;
                $purchasePayment->save();
            }
            // 2. Apply remaining amount to pending credit purchases
            if ($paidAmount > 0) {

                $purchases = Purchase::where('supplier_id', $supplierId)
                    ->where('payment_status', 'pending')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($purchases as $purchase) {

                    if ($paidAmount <= 0) {
                        break;
                    }

                    $remaining = $purchase->total - $purchase->payment_total;

                    if ($remaining <= 0) {
                        continue;
                    }

                    if ($paidAmount >= $remaining) {

                        // Full payment
                        $appliedAmount = $remaining;

                        $purchase->payment_total += $remaining;
                        $purchase->balance_amount = 0;
                        $purchase->payment_status = 'completed';

                        $paidAmount -= $remaining;
                    } else {

                        // Partial payment
                        $appliedAmount = $paidAmount;

                        $purchase->payment_total += $paidAmount;
                        $purchase->balance_amount -= $paidAmount;

                        $purchase->payment_status = 'pending';

                        $paidAmount = 0;
                    }

                    $purchase->save();

                    // Record purchase payment
                    $purchasePayment = PurchasePayment::create([
                        'purchase_id'  => $purchase->id,
                        'supplier_id'  => $supplier->id,
                        'amount_taken' => $appliedAmount,
                        'payment_mode' => $paymentMode,
                        'created_by'   => $userId,
                        'payment_date' => $requested_date,
                        'description'  => $description,
                        'financial_year_id' => $financialYear->id,
                    ]);
                    Log::info('Payment mode for Entry jo1: ' . $paymentMode);

                    // Reduce supplier balance
                    $supplier->balance -= $appliedAmount;
                    $supplier->save();
                    Log::info('Before  Journal Entry jo: ');
                    // Create journal entry
                    $jo = $this->journalEntryService->createPurchasePaymentEntry([
                        'date' => $requested_date,
                        'amount' => $appliedAmount,
                        'reference_type' => 'App\\Models\\PurchasePayment',
                        'reference_id' => $purchasePayment->id,
                        'description' => 'Supplier credit payment - ' . $purchase->purchase_no,
                        'supplier_id' => $supplier->id ?? '',
                        'supplier_name' => $supplier->supplier_name ?? '',
                        'supplier_code' => $supplier->supplier_code ?? '',
                        'payment_mode' => $paymentMode,
                        'user_id' => $userId,
                    ]);

                    if (!$jo) {
                        Log::info('Journal Entry jo: ' . $jo);

                        return errorResponse(null, 'Failed to create journal entry2', 500);
                    }
                    $purchasePayment->journal_entry_id = $jo->id;
                    $purchasePayment->save();
                }
            }
            // } else {

            //     // 2. Get supplier pending purchases
            //     $purchases = Purchase::where('supplier_id', $supplierId)
            //         ->where('payment_status', 'pending')
            //         ->orderBy('created_at', 'asc')
            //         ->get();

            //     foreach ($purchases as $purchase) {
            //         if ($paidAmount <= 0) break;

            //         $remaining = $purchase->total - $purchase->payment_total;

            //         if ($paidAmount >= $remaining) {
            //             // Full payment
            //             $purchase->payment_total += $remaining;
            //             $purchase->payment_status = 'completed';
            //             $appliedAmount = $remaining;
            //             $paidAmount -= $remaining;
            //         } else {
            //             // Partial payment
            //             $purchase->payment_total += $paidAmount;
            //             $purchase->payment_status = 'pending';
            //             $appliedAmount = $paidAmount;
            //             $paidAmount = 0;
            //         }

            //         $purchase->save();

            //         // 3. Record in purchase_payments
            //         $purchasePayment = PurchasePayment::create([
            //             'purchase_id'  => $purchase->id,
            //             'supplier_id'  => $supplier->id,
            //             'amount_taken' => $appliedAmount,
            //             'payment_mode' => $paymentMode,
            //             'created_by'   => $userId,
            //             'payment_date' => $requested_date,
            //             'description' => $description,
            //             'financial_year_id' => $financialYear->id,
            //         ]);

            //         $supplier->balance -= $appliedAmount;
            //         $supplier->save();
            //         // 4. Create journal entry for the payment
            //         $journalEntry = $this->journalEntryService->createPurchasePaymentEntry([
            //             'date' => $requested_date,
            //             'amount' => $appliedAmount,
            //             'reference_type' => 'App\\Models\\PurchasePayment',
            //             'reference_id' => $purchasePayment->id,
            //             'description' => 'Supplier credit payment - ' . $purchase->purchase_no,
            //             'supplier_id' => $supplier->id ?? '',
            //             'supplier_name' => $supplier->supplier_name ?? '',
            //             'supplier_code' => $supplier->supplier_code ?? '',
            //             'payment_mode' => $paymentMode,
            //             'user_id' => $userId,
            //         ]);
            //         if (!$journalEntry) {
            //             return errorResponse(null, 'Failed to create journal entry', 500);
            //         }
            //     }
            // }
            DB::commit();
            return successResponse($purchasePayment, 'Payment applied successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 500);
        }
    }
}
