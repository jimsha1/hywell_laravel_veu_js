<?php

namespace App\Console\Commands;

use App\Models\AccountBalance;
use App\Models\AutoIncrementMaster;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnPayment;
use App\Models\StockOriginal;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\JournalEntryService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountsEntry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:accounts-entry
        {--start_date= : Only process records on or after this date}
        {--end_date= : Only process records on or before this date}
        {--chunk=200 : Number of records to process per chunk}
        {--type=all : Process records of type all, purchase, sale, or payment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch process journal entries for account transactions in chunks';

    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        parent::__construct();
        $this->journalEntryService = $journalEntryService;
    }

    public function handle()
    {
        $this->info('Starting voucher migration...');

        $this->migrateVouchers2();
        $this->info('Voucher migration completed.');
        return 0;
        // $startDate = $this->option('start_date');
        // $endDate = $this->option('end_date');
        // $type = strtolower($this->option('type') ?? 'all');

        // if (!in_array($type, ['all', 'purchase', 'sale', 'outside_sale', 'payment', 'outside_payment', 'opening_payment', 'sales_return', 'sales_return_payment', 'purchase_payment', 'purchase_opening_payment', 'purchase_return'])) {
        //     $this->error('Invalid type. Use all, purchase, sale, outside_sale, outside_payment, opening_payment, or payment.');
        //     return 1;
        // }

        // $this->info("Loading transactions...");

        // // Load all transactions into a single collection
        // $transactions = $this->loadTransactions(
        //     $startDate,
        //     $endDate,
        //     $type
        // );


        // $total = $transactions->count();

        // $this->info("Total Transactions : {$total}");

        // $progress = $this->output->createProgressBar($total);
        // $progress->start();

        // foreach ($transactions as $transaction) {

        //     try {

        //         $this->processTransaction($transaction);
        //     } catch (\Exception $e) {

        //         $this->error(
        //             strtoupper($transaction['type']) .
        //                 " #{$transaction['id']} failed : " .
        //                 $e->getMessage()
        //         );

        //         Log::error($e);
        //     }

        //     $progress->advance();
        // }

        // $progress->finish();

        // $this->newLine();

        // $this->info('Accounts entry processing finished successfully.');

        // return 0;

        // $pendingVouchers = Voucher::where('voucher_status', 'pending')->where('voucher_type', 'payment')->get();

        // foreach ($pendingVouchers as $voucher) {


        //     $journalEntry = JournalEntry::with('lines')
        //         ->findOrFail($voucher->journal_entry_id);

        //     if ($journalEntry->status != 'draft') {
        //         return errorResponse(
        //             null,
        //             'Only pending vouchers can be updated',
        //             400
        //         );
        //     }
        //     $creditLine = $journalEntry->lines
        //         ->where('type', 'credit')
        //         ->first();

        //     if (!$creditLine) {
        //         return errorResponse(
        //             null,
        //             'Credit account not found in journal entry',
        //             400
        //         );
        //     }

        //     $latestBalance = AccountBalance::where(
        //         'account_id',
        //         $creditLine->account_id
        //     )
        //         ->orderBy('balance_date', 'desc')
        //         ->orderBy('id', 'desc')
        //         ->first();

        //     $journalEntry->status = 'posted';

        //     $journalEntry->save();

        //     foreach ($journalEntry->lines as $line) {
        //         if ($line->account) {

        //             $line->account->updateBalance($journalEntry->entry_date->toDateString(), $journalEntry->financial_year_id);
        //         }
        //     }

        //     $voucher->voucher_status = 'approved';
        //     $voucher->save();
        // }
    }


    protected function loadTransactions(
        ?string $startDate,
        ?string $endDate,
        string $type
    ) {
        $transactions = collect();

        // =========================
        // Purchases
        // =========================
        if ($type === 'all' || $type === 'purchase') {

            Purchase::whereNull('deleted_at')
                ->where('is_accounted', 0)
                ->when($startDate, fn($q) => $q->whereDate('purchase_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('purchase_date', '<', $endDate))
                ->get()
                ->each(function ($purchase) use ($transactions) {

                    $transactions->push([
                        'type'  => 'purchase',
                        'id'    => $purchase->id,
                        'date'  => $purchase->purchase_date,
                        'model' => $purchase,
                    ]);
                });
        }

        // =========================
        // Sales
        // =========================
        if ($type === 'all' || $type === 'sale') {

            Order::where('order_status', 'delivered')

                ->where('is_outside', 0)
                ->where('is_accounted', 0)
                ->where('order_type', '!=', 'Outside Sale')
                ->when($startDate, fn($q) => $q->whereDate('delivered_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('delivered_date', '<', $endDate))
                ->get()
                ->each(function ($order) use ($transactions) {

                    $transactions->push([
                        'type'  => 'sale',
                        'id'    => $order->id,
                        'date'  => $order->delivered_date,
                        'model' => $order,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'outside_sale') {

            Order::where('order_status', 'delivered')

                ->where('is_outside', 0)
                ->where('is_accounted', 0)
                ->where('order_type', 'Outside Sale')
                ->when($startDate, fn($q) => $q->whereDate('delivered_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('delivered_date', '<', $endDate))
                ->get()
                ->each(function ($order) use ($transactions) {

                    $transactions->push([
                        'type'  => 'outside_sale',
                        'id'    => $order->id,
                        'date'  => $order->delivered_date,
                        'model' => $order,
                    ]);
                });
        }

        // =========================
        // Payments
        // =========================
        if ($type === 'all' || $type === 'payment') {

            OrderPayment::where('is_accounted', 0)
                ->where('type', 'order')

                ->whereHas('order', function ($q) {
                    $q->where('is_outside', 0)->where('order_type', '!=', 'Outside Sale');
                })

                ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('payment_date', '<', $endDate))
                ->get()
                ->each(function ($payment) use ($transactions) {

                    $transactions->push([
                        'type'  => 'payment',
                        'id'    => $payment->id,
                        'date'  => $payment->payment_date,
                        'model' => $payment,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'purchase_payment') {

            PurchasePayment::where('is_accounted', 0)
                ->where('payment_mode', '!=', 'credit')
                ->where('type', '!=', 'opening_balance')
                ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))

                ->get()
                ->each(function ($purchase_payment) use ($transactions) {

                    $transactions->push([
                        'type'  => 'purchase_payment',
                        'id'    => $purchase_payment->id,
                        'date'  => $purchase_payment->payment_date,
                        'model' => $purchase_payment,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'purchase_opening_payment') {

            PurchasePayment::where('is_accounted', 0)
                ->where('payment_mode', '!=', 'credit')
                ->where('type', 'opening_balance')
                ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))

                ->get()
                ->each(function ($purchase_opening_payment) use ($transactions) {

                    $transactions->push([
                        'type'  => 'purchase_opening_payment',
                        'id'    => $purchase_opening_payment->id,
                        'date'  => $purchase_opening_payment->payment_date,
                        'model' => $purchase_opening_payment,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'outside_payment') {

            OrderPayment::where('is_accounted', 0)
                ->where('type', 'order')
                ->whereHas('order', function ($q) {
                    $q->where('order_type', '=', 'Outside Sale');
                })
                ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('payment_date', '<', $endDate))
                ->get()
                ->each(function ($payment) use ($transactions) {

                    $transactions->push([
                        'type'  => 'outside_payment',
                        'id'    => $payment->id,
                        'date'  => $payment->payment_date,
                        'model' => $payment,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'opening_payment') {

            OrderPayment::where('is_accounted', 0)
                ->where('type', 'opening_balance')

                ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('payment_date', '<', $endDate))
                ->get()
                ->each(function ($opening_payment) use ($transactions) {

                    $transactions->push([
                        'type'  => 'opening_payment',
                        'id'    => $opening_payment->id,
                        'date'  => $opening_payment->payment_date,
                        'model' => $opening_payment,
                    ]);
                });
        }
        if ($type === 'all' || $type === 'sales_return') {
            SalesReturn::where('is_accounted', 0)
                ->when($startDate, fn($q) => $q->whereDate('return_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('return_date', '<', $endDate))
                ->get()
                ->each(function ($sales_return) use ($transactions) {

                    $transactions->push([
                        'type'  => 'sales_return',
                        'id'    => $sales_return->id,
                        'date'  => $sales_return->return_date,
                        'model' => $sales_return,
                    ]);
                });
        }

        if ($type === 'all' || $type === 'purchase_return') {
            PurchaseReturn::where('is_accounted', 0)
                ->when($startDate, fn($q) => $q->whereDate('return_date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('return_date', '<', $endDate))
                ->get()
                ->each(function ($purchase_return) use ($transactions) {

                    $transactions->push([
                        'type'  => 'purchase_return',
                        'id'    => $purchase_return->id,
                        'date'  => $purchase_return->return_date,
                        'model' => $purchase_return,
                    ]);
                });
        }
        // if ($type === 'all' || $type === 'sales_return_payment') {
        //     SalesReturnPayment::where('is_accounted', 0)
        //         ->where('refund_mode', '!=', 'order_balance')
        //         ->when($startDate, fn($q) => $q->whereDate('refund_date', '>=', $startDate))
        //         ->when($endDate, fn($q) => $q->whereDate('refund_date', '<', $endDate))
        //         ->get()
        //         ->each(function ($sales_return_payment) use ($transactions) {

        //             $transactions->push([
        //                 'type'  => 'sales_return_payment',
        //                 'id'    => $sales_return_payment->id,
        //                 'date'  => $sales_return_payment->refund_date,
        //                 'model' => $sales_return_payment,
        //             ]);
        //         });
        // }
        // ======================================
        // Sort by date, then created_at, then id
        // ======================================
        return $transactions
            ->sort(function ($a, $b) {

                $dateCompare = strcmp($a['date'], $b['date']);

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                $createdCompare = strtotime($a['model']->created_at)
                    <=> strtotime($b['model']->created_at);

                if ($createdCompare !== 0) {
                    return $createdCompare;
                }

                return $a['id'] <=> $b['id'];
            })
            ->values();
    }

    protected function processTransaction(array $transaction): void
    {
        switch ($transaction['type']) {

            case 'purchase':

                $this->processPurchaseRecord(
                    $transaction['model']
                );

                break;

            case 'sale':

                $this->processSaleRecord(
                    $transaction['model']
                );

                break;

            case 'outside_sale':

                $this->processOutsideSaleRecord(
                    $transaction['model']
                );

                break;
            case 'payment':

                $this->processPaymentRecord(
                    $transaction['model']
                );

                break;
            case 'opening_payment':

                $this->processOpeningPaymentRecord(
                    $transaction['model']
                );

                break;
            case 'outside_payment':

                $this->processOutsidePaymentRecord(
                    $transaction['model']
                );

                break;
            case 'sales_return':

                $this->processSalesReturnRecord(
                    $transaction['model']
                );

                break;
            // case 'purchase_return':

            //     $this->processPurchaseReturnRecord(
            //         $transaction['model']
            //     );

            //     break;
            // case 'sales_return_payment':

            //     $this->processSalesReturnPaymentRecord(
            //         $transaction['model']
            //     );

            //     break;
            case 'purchase_payment':

                $this->processPurchasePaymentRecord(
                    $transaction['model']
                );

                break;
            case 'purchase_opening_payment':

                $this->processPurchaseOpeningPaymentRecord(
                    $transaction['model']
                );

                break;
            default:

                throw new \Exception(
                    'Unknown transaction type: ' . $transaction['type']
                );
        }
    }

    protected function processPurchaseRecord(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $supplier = Supplier::find($purchase->supplier_id);
            if (!$supplier) {
                throw new Exception('Supplier not found');
            }

            $journalEntry = $this->journalEntryService->createPurchaseEntry([
                'date' => $purchase->purchase_date,
                'total_amount' => $purchase->total,
                'reference_type' => 'App\\Models\\Purchase',
                'reference_id' => $purchase->id,
                'description' => 'Purchase - ' . $purchase->purchase_no,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,
                'user_id' => $purchase->user_id ?? 1,
            ]);
            Log::info('====================PURCHASE JOURNAL ENTRY STARTED====================');

            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }
            Log::info('====================PURCHASE JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            if ($journalEntry) {
                $purchase->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);

                Log::info('==========PURCHASE TABLE ACCOUNTED=================');
                $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($purchaseItems as $item) {
                    StockOriginal::create([
                        'ref_id' => $purchase->id,
                        'ref_no' => $purchase->purchase_no,
                        'product_id' => $item->product_id,
                        'quantity' => $item->qty,
                        'cost_price' => $item->unit_price,
                        'movement_type' => 'in',
                        'reason' => 'Purchase',
                        'date' => $purchase->purchase_date,
                        'created_by' => $purchase->user_id,
                    ]);

                    Log::info('==========STOCKORIGINAL TABLE UPDATED=================');
                    $item->update(['is_accounted' => 1]);
                    Log::info('==========PURCHASE ITEM TABLE UPDATED=================');
                }
            }
        });
    }

    protected function processSaleRecord(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $customer = Customer::find($order->customer_id);
            if (!$customer || $customer?->is_outside === 1) {
                throw new Exception('Customer not found');
            }

            $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                'date' => $order->delivered_date,
                'amount' => $order->total_amount,
                'reference_type' => 'App\\Models\\Order',
                'reference_id' => $order->id,
                'description' => 'Order ' . $order->order_no,
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'customer_type' => 'customer',
                'user_id' => $customer->user_id,
                'customer_code' => $customer->customer_code,
            ]);
            Log::info('====================ORDER JOURNAL ENTRY STARTED====================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================ORDER JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');
            if ($journalEntry) {
                $order->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);

                Log::info('=============ORDER TABLE ACCOUNTED===================');
                $orderItems = OrderItem::where('order_id', $order->id)->get();
                foreach ($orderItems as $item) {
                    $costPrice = StockOriginal::where('movement_type', 'in')
                        ->where('product_id', $item->product_id)
                        ->avg('cost_price');

                    StockOriginal::create([
                        'ref_id' => $order->id,
                        'ref_no' => $order->order_no,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $costPrice,
                        'movement_type' => 'out',
                        'reason' => 'Sale',
                        'date' => $order->delivered_date,
                        'created_by' => $order->user_id,
                    ]);
                    Log::info('==========STOCKORIGINAL TABLE UPDATED=================');
                    $item->update(['is_accounted' => 1]);
                    Log::info('==========ORDER ITEM TABLE UPDATED=================');
                }
            }
        });
    }

    protected function processOutsideSaleRecord(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $customer = User::find($order->customer_id);
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            $journalEntry = $this->journalEntryService->createSalesCreditEntry([
                'date' => $order->delivered_date,
                'amount' => $order->total_amount,
                'reference_type' => 'App\\Models\\Order',
                'reference_id' => $order->id,
                'description' => 'Order ' . $order->order_no,
                'customer_id' => $customer->id,
                'customer_type' => 'outside',
                'user_id' => $customer->user_id,

            ]);

            Log::info('====================OUTSIDE ORDER JOURNAL ENTRY STARTED====================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================OUTSIDE ORDER JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');
            if ($journalEntry) {
                $order->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);

                Log::info('=============OUTSIDE ORDER TABLE ACCOUNTED===================');
                $orderItems = OrderItem::where('order_id', $order->id)->get();
                foreach ($orderItems as $item) {
                    $costPrice = StockOriginal::where('movement_type', 'in')
                        ->where('product_id', $item->product_id)
                        ->avg('cost_price');

                    StockOriginal::create([
                        'ref_id' => $order->id,
                        'ref_no' => $order->order_no,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $costPrice,
                        'movement_type' => 'out',
                        'reason' => 'Sale',
                        'date' => $order->delivered_date,
                        'created_by' => $order->user_id,
                    ]);
                    Log::info('==========STOCKORIGINAL TABLE UPDATED=================');
                    $item->update(['is_accounted' => 1]);
                    Log::info('==========OUTSIDE ORDER ITEM TABLE UPDATED=================');
                }
            }
        });
    }
    protected function processPaymentRecord(OrderPayment $payment): void
    {
        DB::transaction(function () use ($payment) {

            $customer = Customer::find($payment->customer_id);
            if (!$customer || $customer->is_outside === 1) {
                throw new Exception('Customer2 not found');
            }

            $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
                'date' => $payment->payment_date,
                'amount' => $payment->paid_amount,
                'reference_type' => 'App\\Models\\OrderPayment',
                'reference_id' => $payment->id,
                'description' => 'Order Payment - Order ' . $payment->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'payment_mode' => $payment->payment_mode,
                'customer_type' => 'customer',
                'user_id' => $customer->user_id,
                'customer_code' => $customer->customer_code,
            ]);

            Log::info('=========ORDER PAYMENT JOURNAL ENTRY STARTED=================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================ORDER PAYMENT JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            if ($journalEntry) {
                $payment->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);
                Log::info('=============ORDER PAYMENT TABLE ACCOUNTED===================');
            }
        });
    }

    protected function processOpeningPaymentRecord(OrderPayment $opening_payment): void
    {
        DB::transaction(function () use ($opening_payment) {
            $customer = Customer::find($opening_payment->customer_id);
            // if (!$customer || $customer->is_outside === 1) {
            //     throw new Exception('Customer not found');
            // }

            $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
                'date' => $opening_payment->payment_date,
                'amount' => $opening_payment->paid_amount,
                'reference_type' => 'App\\Models\\OrderPayment',
                'reference_id' => $opening_payment->id,
                'description' => 'Opening balance payment from customer',
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'payment_mode' => $opening_payment->payment_mode,
                'customer_type' => 'customer',
                'user_id' => $customer->user_id,
                'customer_code' => $customer->customer_code,
            ]);

            Log::info('=========OPENING PAYMENT JOURNAL ENTRY STARTED=================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================OPENING PAYMENT JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            if ($journalEntry) {
                $opening_payment->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);
                Log::info('=============OPENING PAYMENT TABLE ACCOUNTED===================');
            }
        });
    }
    protected function processPurchaseOpeningPaymentRecord(PurchasePayment $purchase_opening_payment): void
    {
        DB::transaction(function () use ($purchase_opening_payment) {
            $supplier = Supplier::find($purchase_opening_payment->supplier_id);

            $journalEntry = $this->journalEntryService->createPurchasePaymentEntry([
                'date' => $purchase_opening_payment->payment_date,
                'amount' => $purchase_opening_payment->amount_taken,
                'reference_type' => 'App\\Models\\PurchaseOrderPayment',
                'reference_id' => $purchase_opening_payment->id,
                'description' => 'Opening balance payment',
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,
                'payment_mode' => $purchase_opening_payment->payment_mode,
                'user_id' => $purchase_opening_payment->created_by,
            ]);
            Log::info('=========OPENING PAYMENT JOURNAL ENTRY STARTED in purchase opening payment=================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================OPENING PAYMENT JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            if ($journalEntry) {
                $purchase_opening_payment->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);
                Log::info('=============OPENING PAYMENT TABLE ACCOUNTED purchase opening payment===================');
            }
        });
    }
    protected function processPurchasePaymentRecord(PurchasePayment $purchase_payment): void
    {
        DB::transaction(function () use ($purchase_payment) {
            $supplier = Supplier::find($purchase_payment->supplier_id);

            $journalEntry = $this->journalEntryService->createPurchasePaymentEntry([
                'date' => $purchase_payment->payment_date,
                'amount' => $purchase_payment->amount_taken,
                'reference_type' => 'App\\Models\\PurchaseOrderPayment',
                'reference_id' => $purchase_payment->id,
                'description' => 'Purchase Payment - ' . $purchase_payment->id,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,
                'payment_mode' => $purchase_payment->payment_mode,
                'user_id' => $purchase_payment->created_by,
            ]);
            Log::info('=========OPENING PAYMENT JOURNAL ENTRY STARTED in purchase payment=================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================OPENING PAYMENT JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');

            if ($journalEntry) {
                $purchase_payment->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);
                Log::info('=============OPENING PAYMENT TABLE ACCOUNTED purchase payment===================');
            }
        });
    }
    protected function processOutsidePaymentRecord(OrderPayment $outside_payment): void
    {
        DB::transaction(function () use ($outside_payment) {
            $customer = User::find($outside_payment->customer_id);
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            $correspondingOrder = Order::find($outside_payment->order_id);
            if ($correspondingOrder && $correspondingOrder->is_outside == 0) {


                $journalEntry = $this->journalEntryService->createSalesPaymentEntry([
                    'date' => $outside_payment->payment_date,
                    'amount' => $outside_payment->paid_amount,
                    'reference_type' => 'App\\Models\\OrderPayment',
                    'reference_id' => $outside_payment->id,
                    'description' => 'Order Payment - Order ' . $outside_payment->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->customer_name,
                    'payment_mode' => $outside_payment->payment_mode,
                    'customer_type' => 'outside',
                    'user_id' => $customer->user_id,
                    'customer_code' => $customer->customer_code,
                ]);
                Log::info('=========OUTSIDE PAYMENT JOURNAL ENTRY STARTED=================');
                if (!$journalEntry) {
                    throw new Exception('Journal entry creation failed');
                }

                Log::info('====================OUTSIDE PAYMENT JOURNAL ENTRY ====================');
                Log::info('Journal Entry ID : ' . $journalEntry->id);
                Log::info('Date             : ' . $journalEntry->entry_date);
                Log::info('Reference Type   : ' . $journalEntry->reference_type);
                Log::info('Reference ID     : ' . $journalEntry->reference_id);
                Log::info('Description      : ' . $journalEntry->description);
                foreach ($journalEntry->lines()->with('account')->get() as $line) {
                    Log::info(sprintf(
                        '%s | %s | %s | %.2f',
                        strtoupper($line->type),
                        $line->account->code ?? '',
                        $line->account->name ?? '',
                        $line->amount
                    ));
                }

                Log::info('======================================================');

                if ($journalEntry) {
                    $outside_payment->update([
                        'is_accounted' => 1,
                        'financial_year_id' => $journalEntry->financial_year_id,
                    ]);
                    Log::info('=============OUTSIDE PAYMENT TABLE ACCOUNTED===================');
                }
            }
        });
    }

    protected function processSalesReturnRecord(SalesReturn $sales_return): void
    {
        DB::transaction(function () use ($sales_return) {
            // $customer = Customer::find($sales_return->customer_id);
            // if (!$customer) {
            //     throw new Exception('Customer not found');
            // }


            $customer = User::find($sales_return->customer_id);
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            $journalEntry = $this->journalEntryService->createSalesReturnCreditEntry([
                'date' => $sales_return->return_date,
                'amount' => $sales_return->total_amount,
                'reference_type' => 'App\\Models\\SalesReturn',
                'reference_id' => $sales_return->id,
                'description' => 'Sales Return: ' . ($sales_return->return_no ?? $sales_return->id) . ' - ' . $customer->customer_name,
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'customer_type' => 'outside',
                'user_id' => 1,
                'customer_code' => $customer->customer_code,


            ]);

            // $customer->balance_amount -=  $sales_return->total_amount;
            // $customer->save();

            Log::info('====================SALES RETURN JOURNAL ENTRY STARTED====================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================SALES RETURN JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');
            if ($journalEntry) {
                $sales_return->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);

                Log::info('=============SALES RETURN TABLE ACCOUNTED===================');
                $returnItems = SalesReturnItem::where('sales_return_id', $sales_return->id)->get();
                foreach ($returnItems as $item) {
                    $costPrice = $item->price;

                    StockOriginal::create([
                        'ref_id' => $sales_return->id,
                        'ref_no' => $sales_return->return_no,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $costPrice,
                        'movement_type' => 'out',
                        'reason' => 'Sale',
                        'date' => $sales_return->return_date,
                        'created_by' => $sales_return->user_id,
                    ]);
                    Log::info('==========STOCKORIGINAL TABLE UPDATED=================');
                    // $item->update(['is_accounted' => 1]);
                    Log::info('==========SALES RETURN ITEM TABLE UPDATED=================');
                }
            }
        });
    }

    protected function processPurchaseReturnRecord(PurchaseReturn $purchase_return): void
    {
        DB::transaction(function () use ($purchase_return) {
            $supplier = Supplier::find($purchase_return->supplier_id);
            if (!$supplier) {
                throw new Exception('Supplier not found');
            }

            $journalEntry = $this->journalEntryService->createPurchaseReturnCreditEntry([
                'date' => $purchase_return->return_date,
                'amount' => $purchase_return->total_amount,
                'reference_type' => 'App\\Models\\PurchaseReturn',
                'reference_id' => $purchase_return->id,
                'description' => 'Purchase Return: ' . ($purchase_return->return_no ?? $purchase_return->id) . ' - ' . $supplier->supplier_name,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,

                'user_id' => 1,
                'supplier_code' => $supplier->supplier_code,
            ]);

            $supplier->balance -=  $purchase_return->total_amount;
            $supplier->save();

            Log::info('====================PURCHASE RETURN JOURNAL ENTRY STARTED====================');
            if (!$journalEntry) {
                throw new Exception('Journal entry creation failed');
            }

            Log::info('====================PURCHASE RETURN JOURNAL ENTRY ====================');
            Log::info('Journal Entry ID : ' . $journalEntry->id);
            Log::info('Date             : ' . $journalEntry->entry_date);
            Log::info('Reference Type   : ' . $journalEntry->reference_type);
            Log::info('Reference ID     : ' . $journalEntry->reference_id);
            Log::info('Description      : ' . $journalEntry->description);
            foreach ($journalEntry->lines()->with('account')->get() as $line) {
                Log::info(sprintf(
                    '%s | %s | %s | %.2f',
                    strtoupper($line->type),
                    $line->account->code ?? '',
                    $line->account->name ?? '',
                    $line->amount
                ));
            }

            Log::info('======================================================');
            if ($journalEntry) {
                $purchase_return->update([
                    'is_accounted' => 1,
                    'financial_year_id' => $journalEntry->financial_year_id,
                ]);

                Log::info('=============SALES RETURN TABLE ACCOUNTED===================');
                $returnItems = SalesReturnItem::where('sales_return_id', $purchase_return->id)->get();
                foreach ($returnItems as $item) {
                    $costPrice = $item->price;

                    StockOriginal::create([
                        'ref_id' => $purchase_return->id,
                        'ref_no' => $purchase_return->return_no,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $costPrice,
                        'movement_type' => 'out',
                        'reason' => 'Sale',
                        'date' => $purchase_return->return_date,
                        'created_by' => $purchase_return->user_id,
                    ]);
                    Log::info('==========STOCKORIGINAL TABLE UPDATED=================');
                    // $item->update(['is_accounted' => 1]);
                    Log::info('==========PURCHASE RETURN ITEM TABLE UPDATED=================');
                }
            }
        });
    }

    // protected function processSalesReturnPaymentRecord(SalesReturnPayment $sales_return_payment): void
    // {
    //     DB::transaction(function () use ($sales_return_payment) {
    //         $customer = Customer::find($sales_return_payment->customer_id);
    //         if (!$customer) {
    //             throw new Exception('Customer not found');
    //         }

    //         $journalEntry = $this->journalEntryService->createSalesReturnEntry([
    //             'date' => $sales_return_payment->refund_date,
    //             'amount' => $sales_return_payment->refund_amount,
    //             'reference_type' => 'App\\Models\\SalesReturnPayment',
    //             'reference_id' => $sales_return_payment->id,
    //             'description' => 'Sales Return Payment - Sales Return ' . $sales_return_payment->id,
    //             'customer_id' => $customer->id,
    //             'customer_name' => $customer->customer_name,
    //             'payment_mode' => $sales_return_payment->refund_mode,
    //             'customer_type' => 'customer',
    //             'user_id' => $customer->user_id,
    //             'customer_code' => $customer->customer_code,
    //         ]);
    //         Log::info('=========SALES RETURN PAYMENT JOURNAL ENTRY STARTED=================');
    //         if (!$journalEntry) {
    //             throw new Exception('Journal entry creation failed');
    //         }

    //         Log::info('====================SALES RETURN PAYMENT JOURNAL ENTRY ====================');
    //         Log::info('Journal Entry ID : ' . $journalEntry->id);
    //         Log::info('Date             : ' . $journalEntry->entry_date);
    //         Log::info('Reference Type   : ' . $journalEntry->reference_type);
    //         Log::info('Reference ID     : ' . $journalEntry->reference_id);
    //         Log::info('Description      : ' . $journalEntry->description);
    //         foreach ($journalEntry->lines()->with('account')->get() as $line) {
    //             Log::info(sprintf(
    //                 '%s | %s | %s | %.2f',
    //                 strtoupper($line->type),
    //                 $line->account->code ?? '',
    //                 $line->account->name ?? '',
    //                 $line->amount
    //             ));
    //         }

    //         Log::info('======================================================');

    //         if ($journalEntry) {
    //             $sales_return_payment->update([
    //                 'is_accounted' => 1,

    //             ]);
    //             Log::info('=============SALES RETURN PAYMENT TABLE ACCOUNTED===================');
    //         }
    //     });
    // }



    // private function migrateVouchers2()
    // {
    //     DB::beginTransaction();

    //     try {
    //         DB::table('vouchers2')
    //             ->orderBy('id')
    //             ->chunkById(100, function ($vouchers) {

    //                 foreach ($vouchers as $source) {

    //                     // Find financial year


    //                     // Create new voucher
    //                     $voucher = Voucher::create([
    //                         'voucher_no' => $this->genarateVoucherCode('contra'),
    //                         'voucher_type' => 'contra',
    //                         'voucher_date' => $source->voucher_date,
    //                         'amount' => $source->amount,
    //                         'narration' => $source->narration,
    //                         'reference_id' => $source->reference_id,
    //                         'created_by' => $source->created_by,
    //                         'financial_year_id' => 1,
    //                         'voucher_status' => 'pending',
    //                         'created_at' => $source->created_at,
    //                         'updated_at' => $source->updated_at,
    //                     ]);

    //                     $amount = (float) $source->amount;

    //                     // Debit SAB and credit Cash
    //                     $sectionDr = [
    //                         [
    //                             'account_id' => 6,
    //                             'debit_amount' => $amount,
    //                         ]
    //                     ];

    //                     $sectionCr = [
    //                         [
    //                             'account_id' => 4,
    //                             'credit_amount' => $amount,
    //                         ]
    //                     ];


    //                     // Create voucher entries
    //                     $this->createVoucherEntries(
    //                         $voucher->id,
    //                         $sectionDr,
    //                         $sectionCr
    //                     );

    //                     // Create journal entry and journal entry lines
    //                     $this->createJournalEntry(
    //                         $voucher,
    //                         $sectionDr,
    //                         $sectionCr
    //                     );
    //                 }
    //             });

    //         DB::commit();

    //         return successResponse(null, 'Vouchers migrated successfully.', 0, 200);
    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         return errorResponse(null, $e->getMessage(), 500);
    //     }
    // }

    private function migrateVouchers2()
    {
        DB::beginTransaction();

        try {
            $total = DB::table('vouchers2')->count();
            $processed = 0;

            $this->info("Total vouchers to migrate: {$total}");

            $this->output->progressStart($total);

            DB::table('vouchers2')
                ->orderBy('id')
                ->chunkById(100, function ($vouchers) use (&$processed) {

                    foreach ($vouchers as $source) {

                        $voucher = Voucher::create([
                            'voucher_no' => $this->genarateVoucherCode('contra'),
                            'voucher_type' => 'contra',
                            'voucher_date' => $source->voucher_date,
                            'amount' => $source->amount,
                            'narration' => $source->narration,
                            'reference_id' => $source->reference_id,
                            'created_by' => $source->created_by,
                            'financial_year_id' => 1,
                            'voucher_status' => 'pending',
                            'created_at' => $source->created_at,
                            'updated_at' => $source->updated_at,
                        ]);

                        $amount = (float) $source->amount;

                        $sectionDr = [
                            [
                                'account_id' => 6,
                                'debit_amount' => $amount,
                            ]
                        ];

                        $sectionCr = [
                            [
                                'account_id' => 4,
                                'credit_amount' => $amount,
                            ]
                        ];

                        $this->createVoucherEntries(
                            $voucher->id,
                            $sectionDr,
                            $sectionCr
                        );

                        $this->createJournalEntry(
                            $voucher,
                            $sectionDr,
                            $sectionCr
                        );

                        $processed++;

                        $this->output->progressAdvance();
                    }
                });

            DB::commit();

            $this->output->progressFinish();

            $this->info("Successfully migrated: {$processed} / {$total}");

            return successResponse(
                null,
                'Vouchers migrated successfully.',
                0,
                200
            );
        } catch (\Throwable $e) {

            DB::rollBack();

            $this->output->progressFinish();

            $this->error($e->getMessage());

            return errorResponse(null, $e->getMessage(), 500);
        }
    }


    private function createJournalEntry($voucher, $sectionDr, $sectionCr)
    {
        $journalEntry = $this->journalEntryService->createVoucherEntry([
            'date' => $voucher->voucher_date,
            'reference_type' => Voucher::class,
            'reference_id' => $voucher->id,
            'description' => $voucher->narration,
            'user_id' => 1,
            'sectionDr' => $sectionDr,
            'sectionCr' => $sectionCr,
            'voucher_no' => $voucher->voucher_no,
            'voucher_description' => $voucher->narration,
        ]);




        $voucher->update([
            'journal_entry_id' => $journalEntry->id
        ]);
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
}
