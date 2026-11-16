<?php

namespace App\Repositories\Implementation;

use App\Repositories\Interfaces\ProductWiseSalesRepositoryInterface;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductWiseSalesRepository implements ProductWiseSalesRepositoryInterface
{
    public function getProductWiseSalesReport(Request $request)
    {
        $exportType = $request->export_type ?? 'list';

        // Set default to today if no dates provided
        if (empty($request->start_date) && empty($request->end_date)) {
            $request->merge([
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString()
            ]);
        }



        $query = OrderItem::select(
            'order_items.product_id',
            DB::raw('MAX(products.product_code) as product_code'),
            DB::raw('MAX(products.product_name) as product_name'),
            DB::raw('MAX(products.unit_price) as unit_price'),
            DB::raw('MAX(products.selling_price) as selling_price'),
            DB::raw('MAX(product_categories.category_name) as category'),
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.free_quantity) as total_free_quantity'),
            DB::raw('SUM(order_items.returned_quantity) as total_returned_quantity'),
            DB::raw('SUM(order_items.total) as total_amount'),
            DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            // ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('customers', function ($join) {
                $join->on('customers.id', '=', 'orders.customer_id')
                    ->where('orders.order_type', '!=', 'Outside Sale');
            })

            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.customer_id')
                    ->where('orders.order_type', 'Outside Sale');
            })
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('orders.order_status', 'delivered')
            // ->where('orders.is_outside', 0)
            ->when(
                $request->filled('is_outside'),
                function ($query) use ($request) {
                    $query->where('orders.is_outside', $request->is_outside);
                },
                function ($query) {
                    // Default: show only inside orders
                    $query->where('orders.is_outside', 0);
                }
            )
            ->whereBetween('orders.delivered_date', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ])
            ->groupBy(
                'order_items.product_id',
                'products.id',
                'product_categories.id'
            );

        if (!empty($request->is_outside)) {
            $query->where('orders.is_outside', $request->is_outside);
        }

        if (!empty($request->customer_name)) {
            $query->where(function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('orders.order_type', '!=', 'Outside Sale')
                        ->where(function ($customer) use ($request) {
                            $customer->where('customers.customer_name', 'LIKE', '%' . $request->customer_name . '%')
                                ->orWhere('customers.customer_code', 'LIKE', '%' . $request->customer_name . '%');
                        });
                })->orWhere(function ($sub) use ($request) {
                    $sub->where('orders.order_type', '=', 'Outside Sale')
                        ->where(function ($user) use ($request) {
                            $user->where('users.name', 'LIKE', '%' . $request->customer_name . '%');
                        });
                });
            });
        }
        // Apply filters
        $query = $this->applyFilters($query, $request);

        // Get total summary
        $totalSummary = $this->getTotalSummary($request);


        // Handle different export types
        if ($exportType === 'pdf' || $exportType === 'excel') {
            // For PDF and Excel, get all results (with limit)
            $results = $query->limit(500)->get();
        } else {
            // For list, paginate results
            $perPage = config('settings.pagination.perpage.admin', 15);
            $results = $query->paginate($perPage);
        }


        if ($exportType === 'list') {
            $totals = [
                'total_quantity' => $totalSummary['grand_total_quantity'],
                'total_free_quantity' => $totalSummary['grand_total_free_quantity'],
                'total_returned_quantity' => $totalSummary['grand_total_returned_quantity'],
            ];

            return [
                'data' => $results->getCollection(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'totals' => $totals,
            ];
        } else {
            // For PDF/Excel, $results is a Collection, not Paginator
            // $mappedResults = $results->map($transformCallback);
            return [
                'data' => $results,
                // 'summary' => $totalSummary,
            ];
        }
    }

    private function applyFilters($query, Request $request)
    {
        // Date range filter - primarily based on order_date
        $startDate = $request->start_date ?? null;
        $endDate = $request->end_date ?? null;

        // if ($endDate && $startDate) {
        //         // Filter by order_date first, fallback to created_at if order_date is null
        //         $query->where(function ($q) use ($startDate, $endDate) {
        //             $q->whereBetween('orders.delivered_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        //                 ->orWhere(function ($subQ) use ($startDate, $endDate) {
        //                     $subQ->whereNull('orders.delivered_date')
        //                         ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        //                 });
        //         });
        // }

        // Product filter
        if (!empty($request->product_id)) {
            $query->where('order_items.product_id', $request->product_id);
        }

        // Category filter
        if (!empty($request->category_id)) {
            $query->where('products.category_id', $request->category_id);
        }

        // Product name search
        if (!empty($request->product_name)) {
            $query->where(function ($q) use ($request) {
                $q->where('products.product_name', 'LIKE', '%' . $request->product_name . '%')
                    ->orWhere('products.product_code', 'LIKE', '%' . $request->product_name . '%');
            });
        }

        // Customer filter
        // if (!empty($request->customer_name)) {
        //     // $query->where('customers.customer_name', $request->customer_name);
        //     $query->where(function ($q) use ($request) {
        //         $q->where('customers.customer_name', 'LIKE', '%' . $request->customer_name . '%')
        //             ->orWhere('customers.customer_code', 'LIKE', '%' . $request->customer_name . '%');
        //     });
        // }

        // Delivery Boy filter
        if (!empty($request->order_by)) {
            $query->where('orders.order_by', $request->order_by);
        }

        if (!empty($request->delivered_by)) {
            $query->where('orders.delivered_by', $request->delivered_by);
        }

        return $query;
    }

    // private function getTotalSummary(Request $request)
    // {
    //     $query = OrderItem::select(
    //         DB::raw('SUM(order_items.quantity) as grand_total_quantity'),
    //         DB::raw('SUM(order_items.free_quantity) as grand_total_free_quantity'),
    //         DB::raw('SUM(order_items.returned_quantity) as grand_total_returned_quantity'),
    //         DB::raw('SUM(order_items.total) as grand_total_amount'),
    //         DB::raw('COUNT(DISTINCT order_items.product_id) as total_products'),
    //         DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders')
    //     )
    //         ->join('orders', 'order_items.order_id', '=', 'orders.id')
    //         ->where('orders.order_status', '!=', 'draft');

    //     // Apply same filters for summary
    //     $startDate = $request->start_date ?? null;
    //     $endDate = $request->end_date ?? null;

    //     if ($endDate && $startDate) {
    //         $query->where(function ($q) use ($startDate, $endDate) {
    //             $q->whereBetween('orders.delivered_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
    //                 ->orWhere(function ($subQ) use ($startDate, $endDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->whereBetween('orders.order_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //                 })
    //                 ->orWhere(function ($subQ) use ($startDate, $endDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->whereNull('orders.order_date')
    //                         ->whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    //                 });
    //         });
    //     } elseif ($startDate) {
    //         $query->where(function ($q) use ($startDate) {
    //             $q->where('orders.delivered_date', '>=', $startDate . ' 00:00:00')
    //                 ->orWhere(function ($subQ) use ($startDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->where('orders.order_date', '>=', $startDate . ' 00:00:00');
    //                 })
    //                 ->orWhere(function ($subQ) use ($startDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->whereNull('orders.order_date')
    //                         ->where('orders.created_at', '>=', $startDate . ' 00:00:00');
    //                 });
    //         });
    //     } elseif ($endDate) {
    //         $query->where(function ($q) use ($endDate) {
    //             $q->where('orders.delivered_date', '<=', $endDate . ' 23:59:59')
    //                 ->orWhere(function ($subQ) use ($endDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->where('orders.order_date', '<=', $endDate . ' 23:59:59');
    //                 })
    //                 ->orWhere(function ($subQ) use ($endDate) {
    //                     $subQ->whereNull('orders.delivered_date')
    //                         ->whereNull('orders.order_date')
    //                         ->where('orders.created_at', '<=', $endDate . ' 23:59:59');
    //                 });
    //         });
    //     }

    //     if (!empty($request->product_id)) {
    //         $query->where('order_items.product_id', $request->product_id);
    //     }

    //     // Check if products table is already joined
    //     $hasProductsJoin = false;
    //     $joins = $query->getQuery()->joins ?? [];
    //     foreach ($joins as $join) {
    //         if ($join->table === 'products') {
    //             $hasProductsJoin = true;
    //             break;
    //         }
    //     }

    //     if (!empty($request->category_id)) {
    //         if (!$hasProductsJoin) {
    //             $query->join('products', 'order_items.product_id', '=', 'products.id');
    //             $hasProductsJoin = true;
    //         }
    //         $query->where('products.category_id', $request->category_id);
    //     }

    //     if (!empty($request->order_status)) {
    //         $query->where('orders.order_status', $request->order_status);
    //     } else {
    //         $query->where('orders.order_status', 'delivered');
    //     }

    //     if (!empty($request->product_name)) {
    //         if (!$hasProductsJoin) {
    //             $query->join('products', 'order_items.product_id', '=', 'products.id');
    //         }
    //         $query->where(function ($q) use ($request) {
    //             $q->where('products.product_name', 'LIKE', '%' . $request->product_name . '%')
    //                 ->orWhere('products.product_code', 'LIKE', '%' . $request->product_name . '%');
    //         });
    //     }

    //     if (!empty($request->customer_id)) {
    //         $query->where('orders.customer_id', $request->customer_id);
    //     }

    //     if (!empty($request->delivered_by)) {
    //         $query->where('orders.delivered_by', $request->delivered_by);
    //     }

    //     $summary = $query->first();

    //     return [
    //         'grand_total_quantity' => (int)($summary->grand_total_quantity ?? 0),
    //         'grand_total_free_quantity' => (int)($summary->grand_total_free_quantity ?? 0),
    //         'grand_total_returned_quantity' => (int)($summary->grand_total_returned_quantity ?? 0),
    //         'grand_net_quantity' => (int)($summary->grand_total_quantity ?? 0) - (int)($summary->grand_total_returned_quantity ?? 0),
    //         'grand_total_amount' => (float)($summary->grand_total_amount ?? 0),
    //         'total_products' => (int)($summary->total_products ?? 0),
    //         'total_orders' => (int)($summary->total_orders ?? 0),
    //     ];
    // }

    private function getTotalSummary(Request $request)
    {
        $query = OrderItem::select(
            DB::raw('SUM(order_items.quantity) as grand_total_quantity'),
            DB::raw('SUM(order_items.free_quantity) as grand_total_free_quantity'),
            DB::raw('SUM(order_items.returned_quantity) as grand_total_returned_quantity'),
            DB::raw('SUM(order_items.total) as grand_total_amount'),
            DB::raw('COUNT(DISTINCT order_items.product_id) as total_products'),
            DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')

            // Customer join
            ->leftJoin('customers', function ($join) {
                $join->on('customers.id', '=', 'orders.customer_id')
                    ->where('orders.order_type', '!=', 'Outside Sale');
            })

            // Outside salesman/user join
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.customer_id')
                    ->where('orders.order_type', 'Outside Sale');
            })

            // Product joins
            ->leftJoin(
                'products',
                'order_items.product_id',
                '=',
                'products.id'
            )

            ->leftJoin(
                'product_categories',
                'products.category_id',
                '=',
                'product_categories.id'
            )



            ->where('orders.order_status', 'delivered')


            ->when(
                $request->filled('is_outside'),
                function ($query) use ($request) {
                    $query->where(
                        'orders.is_outside',
                        $request->is_outside
                    );
                },
                function ($query) {
                    // Default: inside sales
                    $query->where('orders.is_outside', 0);
                }
            );


        $startDate = $request->start_date ?? null;
        $endDate = $request->end_date ?? null;

        if ($startDate && $endDate) {

            $query->whereBetween('orders.delivered_date', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        } elseif ($startDate) {

            $query->where(
                'orders.delivered_date',
                '>=',
                $startDate . ' 00:00:00'
            );
        } elseif ($endDate) {

            $query->where(
                'orders.delivered_date',
                '<=',
                $endDate . ' 23:59:59'
            );
        }



        if (!empty($request->product_id)) {

            $query->where(
                'order_items.product_id',
                $request->product_id
            );
        }




        if (!empty($request->category_id)) {

            $query->where(
                'products.category_id',
                $request->category_id
            );
        }



        if (!empty($request->product_name)) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'products.product_name',
                    'LIKE',
                    '%' . $request->product_name . '%'
                )

                    ->orWhere(
                        'products.product_code',
                        'LIKE',
                        '%' . $request->product_name . '%'
                    );
            });
        }



        if (!empty($request->customer_name)) {

            $query->where(function ($q) use ($request) {

                // Normal customer
                $q->where(function ($sub) use ($request) {

                    $sub->where(
                        'orders.order_type',
                        '!=',
                        'Outside Sale'
                    )
                        ->where(function ($customer) use ($request) {

                            $customer->where(
                                'customers.customer_name',
                                'LIKE',
                                '%' . $request->customer_name . '%'
                            )
                                ->orWhere(
                                    'customers.customer_code',
                                    'LIKE',
                                    '%' . $request->customer_name . '%'
                                );
                        });
                })

                    // Outside salesman
                    ->orWhere(function ($sub) use ($request) {

                        $sub->where(
                            'orders.order_type',
                            '=',
                            'Outside Sale'
                        )
                            ->where(
                                'users.name',
                                'LIKE',
                                '%' . $request->customer_name . '%'
                            );
                    });
            });
        }



        if (!empty($request->customer_id)) {

            $query->where(
                'orders.customer_id',
                $request->customer_id
            );
        }



        if (!empty($request->delivered_by)) {

            $query->where(
                'orders.delivered_by',
                $request->delivered_by
            );
        }



        if (!empty($request->order_by)) {

            $query->where(
                'orders.order_by',
                $request->order_by
            );
        }


        $summary = $query->first();


        return [
            'grand_total_quantity' =>
            (int) ($summary->grand_total_quantity ?? 0),

            'grand_total_free_quantity' =>
            (int) ($summary->grand_total_free_quantity ?? 0),

            'grand_total_returned_quantity' =>
            (int) ($summary->grand_total_returned_quantity ?? 0),

            'grand_net_quantity' =>
            (int) ($summary->grand_total_quantity ?? 0)
                - (int) ($summary->grand_total_returned_quantity ?? 0),

            'grand_total_amount' =>
            (float) ($summary->grand_total_amount ?? 0),

            'total_products' =>
            (int) ($summary->total_products ?? 0),

            'total_orders' =>
            (int) ($summary->total_orders ?? 0),
        ];
    }
}
