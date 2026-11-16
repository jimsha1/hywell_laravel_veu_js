<?php

namespace App\Services;

use App\Models\AutoIncrementMaster;
use App\Models\FinancialYear;
use App\Models\Products;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\StockOriginal;
use App\Models\Supplier;
use App\Models\User;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PurchaseReturnService
{
    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService = null)
    {
        $this->journalEntryService = $journalEntryService ?? app(JournalEntryService::class);
    }

    /**
     * Create a purchase return record
     */
    public function createPurchaseReturn($data)
    {
        try {
            DB::beginTransaction();

            Log::channel('purchase_return')->info('---------------- createPurchaseReturn --------------');
            Log::channel('purchase_return')->info(
                'creating purchase return',
                [
                    'purchase_id' => $data->purchase_id,
                    'supplier_id' => $data->supplier_id ?? null,
                    'user_id' => $data->user_id ?? Auth::id(),
                    'return_items' => $data->return_items ?? []
                ]
            );

            // 1) Validate purchase
            $purchase = Purchase::with(['purchaseItem.product', 'supplier'])->lockForUpdate()->find($data->purchase_id);
            if (!$purchase) {
                Log::channel('purchase_return')->info('Purchase not found', ['purchase_id' => $data->purchase_id]);
                DB::rollBack();
                return errorResponse(null, 'Purchase not found.', 404, 404);
            }

            // if (PurchaseReturn::where('purchase_id', $purchase->id)->whereNotIn('return_status', ['approved', 'rejected'])->exists()) {
            //     DB::rollBack();
            //     Log::channel('purchase_return')->info('Please Accept or Reject the existing Purchase Return of this purchase', ['purchase_id' => $data->purchase_id]);
            //     return errorResponse(null, 'Please Accept or Reject the existing Purchase Return of this purchase.', 400, 400);
            // }

            // 2) Auth check if password is provided
            if (isset($data->user_id) && isset($data->password)) {
                $user = User::find($data->user_id);
                if (!$user || !Hash::check($data->password, $user->password)) {
                    DB::rollBack();
                    return errorResponse(null, 'Invalid password.', 401, 401);
                }
            }

            $returnItems = is_string($data->return_items) ? json_decode($data->return_items, true) : $data->return_items;
            if (empty($returnItems)) {
                Log::channel('purchase_return')->info('No items to return', ['data' => $data]);
                DB::rollBack();
                return errorResponse(null, 'No items to return.', 400, 400);
            }

            // 3) Validate each item & compute totals
            $totalQty = 0;
            $totalAmount = 0;
            $validatedItems = [];

            foreach ($returnItems as $item) {
                $purchaseItem = PurchaseItem::with('product')->where('id', $item['purchase_item_id'])->lockForUpdate()->first();
                if (!$purchaseItem) {
                    DB::rollBack();
                    return errorResponse(null, 'Purchase item not found.', 404, 404);
                }

                if (isset($item['product_id']) && $item['product_id'] != $purchaseItem->product_id) {
                    DB::rollBack();
                    return errorResponse(null, 'Product mismatch for purchase item ID ' . $purchaseItem->id, 400, 400);
                }

                $returnQty = (int)$item['return_quantity'];
                if ($returnQty <= 0) {
                    DB::rollBack();
                    return errorResponse(null, 'Return quantity must be greater than 0', 400, 400);
                }

                $productStock = (int)($purchaseItem->product->total_stock ?? 0);
                if ($returnQty > $productStock) {
                    DB::rollBack();
                    $productName = $purchaseItem->product ? $purchaseItem->product->product_name : 'Product ID ' . $purchaseItem->product_id;
                    return errorResponse(null, 'The available stock of ' . $productName . ' is ' . $productStock . '.', 400, 400);
                }

                // Check already returned quantity for this purchase item across approved returns
                $alreadyReturned = (int)PurchaseReturnItem::where('purchase_item_id', $purchaseItem->id)
                    ->whereHas('purchaseReturn', function ($q) {
                        $q->whereIn('return_status', ['approved', 'pending']);
                    })->sum('return_quantity');

                $maxReturnable = $purchaseItem->qty - $alreadyReturned;

                if ($returnQty > $maxReturnable) {
                    DB::rollBack();
                    $productName = $purchaseItem->product ? $purchaseItem->product->product_name : 'Product ID ' . $purchaseItem->product_id;
                    return errorResponse(null, "Return quantity ({$returnQty}) cannot exceed available purchased quantity ({$maxReturnable}) for {$productName}.", 400, 400);
                }

                $price = (float)($item['price'] ?? $purchaseItem->unit_price);
                if (!isset($item['price']) || $item['price'] === null || $item['price'] === '') {
                    if ($purchaseItem->qty > 0 && (float)$purchaseItem->total > 0) {
                        $price = (float)$purchaseItem->total / (float)$purchaseItem->qty;
                    } elseif ((float)$purchaseItem->tax_amount > 0 && $purchaseItem->qty > 0) {
                        $price = ((float)$purchaseItem->unit_price * (float)$purchaseItem->qty + (float)$purchaseItem->tax_amount) / (float)$purchaseItem->qty;
                    }
                }
                $lineTotal = $returnQty * $price;

                $totalQty += $returnQty;
                $totalAmount += $lineTotal;

                $validatedItems[] = [
                    'purchase_item' => $purchaseItem,
                    'product_id' => $purchaseItem->product_id,
                    'return_quantity' => $returnQty,
                    'price' => $price,
                    'total' => $lineTotal,
                ];
            }

            // 4) Determine return type (full or partial)
            $purchaseTotalQty = $purchase->purchaseItem->sum('qty');
            $returnType = ($totalQty >= $purchaseTotalQty) ? 'full' : 'partial';

            $returnDate = isset($data->return_date)
                ? Carbon::parse($data->return_date)->toDateString()
                : Carbon::now()->toDateString();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $returnDate)
                ->whereDate('end_date', '>=', $returnDate)
                ->first();

            if (!$financialYear) {
                DB::rollBack();
                Log::channel('purchase_return')->info('No financial year found for return date', ['return_date' => $returnDate]);
                return errorResponse(null, 'No financial year found for the selected return date.', 500);
            }

            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                Log::channel('purchase_return')->info('Financial year is closed for return date', ['return_date' => $returnDate]);
                return errorResponse(null, 'Financial year is closed for the selected return date.', 500);
            }

            $autoIncrement = AutoIncrementMaster::where('auto_code', 'AI_PURCHASE_RETURN_CODE')->first();
            if (!$autoIncrement) {
                $autoIncrement = new AutoIncrementMaster();
                $autoIncrement->auto_code = 'AI_PURCHASE_RETURN_CODE';
                $autoIncrement->auto_name = 'purchase return code';
                $autoIncrement->auto_prefix = 'PR';
                $autoIncrement->auto_increment_general_value = 1001;
                $autoIncrement->save();
            }

            // 5) Create PurchaseReturn
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'financial_year_id' => $financialYear->id,
                'return_no' => null,
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
                'return_status' => 'pending',
                'return_type' => $returnType,
                'return_reason' => $data->return_reason ?? null,
                'returned_by' => $data->returned_by ?? Auth::id() ?? $data->user_id ?? null,
                'return_date' => isset($data->return_date) ? Carbon::parse($data->return_date) : Carbon::now(),
            ]);

            generateAutoCode($purchaseReturn, 'return_no', 'AI_PURCHASE_RETURN_CODE');

            // 6) Create PurchaseReturnItems
            foreach ($validatedItems as $it) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $it['purchase_item']->id,
                    'product_id' => $it['product_id'],
                    'return_quantity' => $it['return_quantity'],
                    'price' => $it['price'],
                    'total' => $it['total'],
                ]);
            }

            Log::channel('purchase_return')->info('Purchase return created successfully', ['purchase_return_id' => $purchaseReturn->id, 'return_no' => $purchaseReturn->return_no]);

            DB::commit();
            return successResponse($purchaseReturn->load('returnItems.product', 'purchase', 'supplier'), 'Purchase return created successfully.', 0, 200);
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error creating purchase return', ['error' => $e->getMessage()]);
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Get all purchase returns
     */
    public function getAllPurchaseReturns($data, $platform = 'web')
    {
        try {
            $query = PurchaseReturn::with(['supplier', 'purchase', 'returnedBy', 'approvedBy', 'rejectedBy', 'returnItems.product']);

            if (isset($data->search) && $data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('return_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('supplier', function ($sq) use ($searchTerm) {
                            $sq->where('supplier_name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('supplier_code', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('purchase', function ($pq) use ($searchTerm) {
                            $pq->where('purchase_no', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if (isset($data->return_status) && $data->return_status) {
                $query->where('return_status', $data->return_status);
            }

            if ($platform == 'web') {
                $perPage = config('settings.pagination.perpage.admin') ?? 10;
                $result = $query->orderBy('created_at', 'DESC')->paginate($perPage);
                $response = [
                    'purchase_returns' => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Purchase returns retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Purchase returns retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error retrieving purchase returns', ['error' => $e->getMessage()]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Get purchase return details
     */
    public function getPurchaseReturnDetails($returnId)
    {
        try {
            $purchaseReturn = PurchaseReturn::with([
                'supplier',
                'purchase.purchaseItem.product',
                'returnItems.product',
                'returnedBy',
                'approvedBy',
                'rejectedBy'
            ])->find($returnId);

            if (!$purchaseReturn) {
                return errorResponse(null, 'Purchase return not found.', 404, 404);
            }

            return successResponse($purchaseReturn, 'Purchase return details retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error getting purchase return details', ['error' => $e->getMessage()]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Get purchases available for return
     */
    public function getPurchasesForReturn($data, $platform = 'web')
    {
        try {
            $query = Purchase::with(['supplier', 'purchaseItem.product']);

            $query->whereDoesntHave('purchaseReturns', function ($q) {
                $q->where('return_type', 'full')
                    ->whereIn('return_status', ['pending', 'approved']);
            });

            if (isset($data->search) && $data->search) {
                $searchTerm = $data->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('purchase_no', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('id', $searchTerm)
                        ->orWhereHas('supplier', function ($sq) use ($searchTerm) {
                            $sq->where('supplier_name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('supplier_code', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if (isset($data->supplier_id) && $data->supplier_id) {
                $query->where('supplier_id', $data->supplier_id);
            }

            $perPage = isset($data->per_page) ? (int)$data->per_page : 50;
            $page = isset($data->page) ? (int)$data->page : 1;

            $result = $query->orderBy('created_at', 'DESC')->paginate($perPage, ['*'], 'page', $page);

            $purchases = $result->items();
            foreach ($purchases as $purchase) {
                if (!empty($purchase->purchaseItem)) {
                    foreach ($purchase->purchaseItem as $item) {
                        $returnedQty = (int) PurchaseReturnItem::where('purchase_item_id', $item->id)
                            ->whereHas('purchaseReturn', function ($q) {
                                $q->whereIn('return_status', ['pending', 'approved']);
                            })
                            ->sum('return_quantity');

                        $item->available_qty = max(0, (int) $item->qty - $returnedQty);
                    }
                }
            }

            if ($platform == 'web') {
                $response = [
                    'purchases' => $purchases,
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Purchases retrieved successfully.', 0, 200);
            } else {
                $response = [
                    'purchases' => $purchases,
                    'pagination' => [
                        'more' => $result->hasMorePages()
                    ]
                ];
                return successResponse($response, 'Purchases retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error getting purchases for return', ['error' => $e->getMessage()]);
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    /**
     * Approve purchase return (godown only). This approves the return,
     * deducts stock from godown, creates stock movements & original stock out records,
     * creates journal entry, and processes supplier refunds / balance adjustments.
     */
    public function approvePurchaseReturn($data)
    {
        try {
            DB::beginTransaction();

            Log::channel('purchase_return')->info('---------------- approvePurchaseReturn --------------');
            Log::channel('purchase_return')->info('approving purchase return', [
                'return_id' => $data->return_id,
                'user_id' => $data->user_id ?? Auth::id(),
                'refund_rows' => $data->refund_rows ?? null
            ]);

            $purchaseReturn = PurchaseReturn::with(['returnItems', 'purchase', 'supplier'])->lockForUpdate()->find($data->return_id);
            if (!$purchaseReturn) {
                DB::rollBack();
                Log::channel('purchase_return')->info('Purchase return not found', ['return_id' => $data->return_id]);
                return errorResponse(null, 'Purchase return not found.', 404, 404);
            }

            if ($purchaseReturn->return_status === 'approved') {
                DB::rollBack();
                return errorResponse(null, 'Purchase return already approved.', 400, 400);
            }

            // Password check if provided
            if (isset($data->user_id) && isset($data->password)) {
                $user = User::find($data->user_id);
                if (!$user || !Hash::check($data->password, $user->password)) {
                    DB::rollBack();
                    return errorResponse(null, 'Invalid password.', 401, 401);
                }
            }

            $userId = $data->user_id ?? Auth::id();
            $returnDestination = 'godown'; // Purchase return is done from only godown

            // Check Financial Year for approval date
            $rDate = Carbon::now();
            $financialYear = FinancialYear::whereDate('start_date', '<=', $rDate)
                ->whereDate('end_date', '>=', $rDate)
                ->first();

            if (!$financialYear) {
                DB::rollBack();
                return errorResponse(null, 'No financial year found for the selected date.', 500);
            }
            if ($financialYear->is_closed == 1) {
                DB::rollBack();
                return errorResponse(null, 'Financial year closed for the selected date.', 500);
            }

            // Approve return
            $purchaseReturn->update([
                'return_status' => 'approved',
                'approved_by' => $userId,
                'approved_date' => Carbon::now(),
                // 'return_destination' => $returnDestination,
                'return_no' => $purchaseReturn->return_no ?? ($purchaseReturn->purchase ? $purchaseReturn->purchase->purchase_no : null),
            ]);

            $purchase = Purchase::lockForUpdate()->find($purchaseReturn->purchase_id);
            if ($purchase) {
                $purchase->is_returned = 1;
                $purchase->save();
            }

            // Create Journal Entry for Purchase Return

            $supplier = $purchaseReturn->supplier ?? Supplier::find($purchaseReturn->supplier_id);
            $supplierName = $supplier ? $supplier->supplier_name : 'Supplier';

            $journalEntry = $this->journalEntryService->createPurchaseReturnCreditEntry([
                'date' => Carbon::now()->toDateString(),
                'reference_type' => 'App\\Models\\PurchaseReturn',
                'reference_id' => $purchaseReturn->id,
                'description' => 'Purchase Return: ' . ($purchaseReturn->return_no ?? $purchaseReturn->id) . ' - ' . $supplierName,
                'amount' => $purchaseReturn->total_amount,
                'user_id' => $userId,
                'supplier_id' => $purchaseReturn->supplier_id,
                'supplier_name' => $supplierName,
            ]);

            if (!$journalEntry) {
                Log::channel('purchase_return')->warning('Failed to create journal entry for purchase return', ['return_id' => $purchaseReturn->id]);
                return errorResponse(null, 'Failed to create journal entry', 500);
            } else {
                $purchaseReturn->journal_entry_id = $journalEntry->id;
                $purchaseReturn->save();
                Log::channel('purchase_return')->info('Journal entry created for purchase return', ['purchase_return_id' => $purchaseReturn->id]);
            }

            // 1) Deduct stock from godown & create stock movements
            foreach ($purchaseReturn->returnItems as $returnItem) {
                $totalReturnQty = (int)$returnItem->return_quantity;

                // Decrement central product stock in godown
                Products::where('id', $returnItem->product_id)->decrement('total_stock', $totalReturnQty);

                // Create stock movement (movement_type: 'out')
                StockMovement::create([
                    'product_id' => $returnItem->product_id,
                    'movement_type' => 'out',
                    'quantity' => $totalReturnQty,
                    'reason' => 'Purchase Return',
                    'ref_id' => $purchaseReturn->id,
                    'ref_no' => $purchaseReturn->return_no ?? ($purchaseReturn->purchase ? $purchaseReturn->purchase->purchase_no : null),
                    'date' => Carbon::now(),
                    'created_by' => $userId,
                    'created_at' => Carbon::now(),
                ]);

                // Record original stock ledger (movement_type: 'out')
                $purchaseCostPrice = StockOriginal::where('ref_id', $purchaseReturn->purchase_id)
                    ->where('product_id', $returnItem->product_id)->first();
                $cost_price = $purchaseCostPrice ? $purchaseCostPrice->cost_price : ($returnItem->price ?? 0);

                StockOriginal::create([
                    'ref_id' => $purchaseReturn->id,
                    'ref_no' => $purchaseReturn->return_no ?? ($purchaseReturn->purchase ? $purchaseReturn->purchase->purchase_no : null),
                    'product_id' => $returnItem->product_id,
                    'quantity' => $totalReturnQty,
                    'cost_price' => $cost_price,
                    'movement_type' => 'out',
                    'reason' => 'Purchase Return',
                    'date' => $purchaseReturn->return_date ?? Carbon::now(),
                    'created_by' => $userId,
                ]);
            }

            // 2) Apply the return amount to purchase balances before requesting cash from the supplier.
            $refundRows = is_string($data->refund_rows) ? json_decode($data->refund_rows, true) : ($data->refund_rows ?? []);
            if (!is_array($refundRows)) {
                DB::rollBack();
                return errorResponse(null, 'Invalid refund rows.', 422, 422);
            }

            $purchase = Purchase::lockForUpdate()->find($purchaseReturn->purchase_id);
            if (!$purchase) {
                DB::rollBack();
                return errorResponse(null, 'Purchase not found.', 404, 404);
            }

            $supplierId = $purchaseReturn->supplier_id ?? $purchase->supplier_id;
            $supplier = $supplierId ? Supplier::lockForUpdate()->find($supplierId) : null;

            // The returned purchase is always cleared first. Then apply the credit to this
            // supplier's pending purchases, oldest creation first. Exclude the returned
            // purchase so it cannot be allocated a second time.
            $purchasesToClear = collect([$purchase]);
            if ($supplierId) {
                $purchasesToClear = $purchasesToClear->merge(
                    Purchase::where('supplier_id', $supplierId)
                        ->where('payment_status', 'pending')
                        ->where('id', '!=', $purchase->id)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get()
                );
            }

            $totalRefund = 0;
            foreach ($refundRows as $row) {
                $refundAmount = (float) ($row['refund_amount'] ?? 0);
                if ($refundAmount <= 0) {
                    continue;
                }

                $totalRefund += $refundAmount;
                $remainingRefund = $refundAmount;
                $amountFromPurchaseBalances = 0;

                foreach ($purchasesToClear as $purchaseToClear) {
                    if ($remainingRefund <= 0) {
                        break;
                    }

                    $currentBalance = max(0, (float) $purchaseToClear->total
                        - (float) $purchaseToClear->payment_total
                        - (float) $purchaseToClear->refund_amount_from_order_balance);
                    if ($currentBalance <= 0) {
                        continue;
                    }

                    $amountApplied = min($remainingRefund, $currentBalance);
                    $purchaseToClear->refund_amount_from_order_balance =
                        (float) $purchaseToClear->refund_amount_from_order_balance + $amountApplied;
                    $purchaseToClear->balance_amount = max(0, $currentBalance - $amountApplied);
                    $purchaseToClear->payment_status = $purchaseToClear->balance_amount <= 0 ? 'completed' : 'pending';
                    $purchaseToClear->save();

                    $remainingRefund -= $amountApplied;
                    $amountFromPurchaseBalances += $amountApplied;
                }
            }

            if ($supplier && $totalRefund > 0) {
                $supplier->balance = (float) $supplier->balance - $totalRefund;
                $supplier->save();
            }

            Log::channel('purchase_return')->info('Purchase return approved successfully', ['purchase_return_id' => $purchaseReturn->id]);

            DB::commit();
            return successResponse($purchaseReturn->fresh(['returnItems.product', 'purchase', 'supplier']), 'Purchase return approved successfully.', 0, 200);
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error approving purchase return', ['error' => $e->getMessage()]);
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }



    /**
     * Reject purchase return
     */
    public function rejectPurchaseReturn($data)
    {
        try {
            DB::beginTransaction();

            Log::channel('purchase_return')->info('---------------- rejectPurchaseReturn --------------');
            Log::channel('purchase_return')->info('rejecting purchase return', [
                'return_id' => $data->return_id,
                'user_id' => $data->user_id ?? Auth::id(),
                'rejection_reason' => $data->rejection_reason ?? null
            ]);

            $purchaseReturn = PurchaseReturn::find($data->return_id);
            if (!$purchaseReturn) {
                DB::rollBack();
                return errorResponse(null, 'Purchase return not found.', 404, 404);
            }

            if ($purchaseReturn->return_status === 'approved') {
                DB::rollBack();
                return errorResponse(null, 'Cannot reject an already approved purchase return.', 400, 400);
            }

            // Password check if provided
            if (isset($data->user_id) && isset($data->password)) {
                $user = User::find($data->user_id);
                if (!$user || !Hash::check($data->password, $user->password)) {
                    DB::rollBack();
                    return errorResponse(null, 'Invalid password.', 401, 401);
                }
            }

            $purchaseReturn->update([
                'return_status' => 'rejected',
                'rejected_by' => $data->user_id ?? Auth::id(),
                'rejected_date' => Carbon::now(),
                'rejection_reason' => $data->rejection_reason ?? null,
            ]);

            Log::channel('purchase_return')->info('Purchase return rejected successfully', ['purchase_return_id' => $purchaseReturn->id]);

            DB::commit();
            return successResponse($purchaseReturn->fresh(), 'Purchase return rejected successfully.', 0, 200);
        } catch (Exception $e) {
            Log::channel('purchase_return')->error('Error rejecting purchase return', ['error' => $e->getMessage()]);
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
