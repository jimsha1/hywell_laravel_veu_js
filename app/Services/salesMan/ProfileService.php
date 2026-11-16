<?php

namespace App\Services\salesMan;

use App\Models\CommissionHistory;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VehicleLoadStock;
use Exception;

class ProfileService
{
    public function getSalesManProfile($request)
    {
        try {
            $user = User::with('latestSalesManOrders.customer')
                ->where('id', $request['user_id'])
                ->where('status', 1)
                ->first();

            if (!$user) {
                return errorResponse(null, "User not found", 200);
            }

            // // Get latest vehicle load with delivered qty %
            // $vehicleLoad = VehicleLoadStock::with([
            //     'vehicleLoadStockItems.product'
            // ])
            //     ->where("user_id", $request['user_id'])
            //     ->where('day_status', 'started')
            //     ->where('load_status', 'approved')
            //     ->orderBy('created_at', 'desc')
            //     ->first();


            $vehicleLoad = VehicleLoadStock::with([
                'vehicleLoadStockDetails' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('product');
                },

                'vehicleLoadStockItems.product'
            ])

                ->where("user_id", $request['user_id'])
                ->where('day_status', 'started')
                ->where('load_status', 'approved')
                ->orderBy('created_at', 'desc')
                ->first();


            if ($vehicleLoad) {

                $approvedProductIds = $vehicleLoad->vehicleLoadStockDetails
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                $filteredItems = $vehicleLoad->vehicleLoadStockItems
                    ->filter(function ($item) use ($approvedProductIds) {

                        return $approvedProductIds->contains(
                            $item->product_id
                        );
                    })
                    ->values();
                foreach ($filteredItems as $product) {

                    $product->delivered_qty =
                        $product->total_qty -
                        $product->available_qty;


                    if ($product->total_qty > 0) {

                        $product->delivered_percentage = round(
                            (
                                $product->delivered_qty /
                                $product->total_qty
                            ) * 100,
                            0
                        );
                    } else {

                        $product->delivered_percentage = 0;
                    }
                }
                $vehicleLoad->setRelation(
                    'vehicleLoadStockItems',
                    $filteredItems
                );

                // foreach ($vehicleLoad->vehicleLoadStockItems as $product) {


                //     $product->delivered_qty = $product->total_qty - $product->available_qty;
                //     // $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);

                //     if ($product->total_qty > 0) {
                //         $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);
                //     } else {
                //         $product->delivered_percentage = 0; // avoid divide by zero
                //     }
                // }
            } else {
                $vehicleLoad = null;
            }

            // Current month start and end
            $currentMonthStart = now()->startOfMonth();
            $currentMonthEnd   = now()->endOfMonth();
            $currentMonth      = now()->month;
            $currentYear       = now()->year;

            // Calculate commissions for all salesman and sales_executive users to determine rank and top scorer
            $usersCommissions = User::whereIn('user_type', ['salesman', 'sales_executive'])
                ->get()
                ->map(function ($u) use ($currentMonthStart, $currentMonthEnd, $currentMonth, $currentYear) {
                    $historySum = \App\Models\CommissionHistory::where('user_id', $u->id)
                        ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                        ->sum('commission_amount') ?: 0;

                    $employee = \App\Models\Employee::where('user_id', $u->id)->first();
                    $paidPayrollSum = 0;
                    if ($employee) {
                        $paidPayrollSum = \App\Models\CommissionPayroll::where('employee_id', $employee->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->where('status', 'paid')
                            ->sum('commission') ?: 0;
                    }

                    return [
                        'user_id' => $u->id,
                        'total_commission' => (float)$historySum - (float)$paidPayrollSum
                    ];
                })
                ->sortByDesc('total_commission')
                ->values();

            // Current user's commission
            $userCommission = $usersCommissions->firstWhere('user_id', $request['user_id'])['total_commission'] ?? 0;

            // Rank based on current month
            $user_rank = $usersCommissions->where('total_commission', '>', $userCommission)->count() + 1;

            // Top scorer this month
            $topScorerItem = $usersCommissions->first();
            $topScorerUser = $topScorerItem ? User::find($topScorerItem['user_id']) : null;


            $creditLimit = Employee::where('user_id', $request['user_id'])->first();
            $totalCredit = Order::where('customer_id', $request['user_id'])
                ->where('order_type', 'Outside Sale')
                ->where('is_returned', 0)
                ->where('order_status', 'delivered')
                ->sum('balance_amount');

            // Orders count
            $orders_count = Order::where('delivered_by', $request['user_id'])
                ->where('order_status', 'requested')
                ->count();

            $response = [
                'sales_man' => $user,
                'orders_count' => $orders_count,
                'vehicleLoad' => $vehicleLoad,
                'user_score' => $userCommission,
                'user_rank' => $user_rank,
                'credit_limit' => $creditLimit->credit_limit,
                'total_credit' => $totalCredit,
                'top_scorer' => $topScorerItem && $topScorerUser ? [
                    'user_id' => $topScorerUser->id,
                    'user_name' => $topScorerUser->name,
                    'profile_image' => $topScorerUser->profile_image ?? "",
                    'total_commission' => $topScorerItem['total_commission']
                ] : [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'profile_image' => $user->profile_image ?? "",
                    'total_commission' => 0
                ]
            ];

            return successResponse($response, 'profile fetched successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 102, 500);
        }
    }


    // public function getSalesManProfile($request)
    // {
    //     try {

    //         // $user = User::find($request['user_id'])->where('status', 1)->first();
    //         $user = User::with('latestSalesManOrders.customer')->where('id', $request['user_id'])
    //             ->where('status', 1)
    //             ->first();


    //         if (!$user) {
    //             return errorResponse(null, "User not found", 200);
    //         }



    //         $vehicleLoad = VehicleLoadStock::with([
    //             'vehicleLoadStockItems.product'
    //         ])->where("user_id", $request['user_id'])
    //             ->where('day_status', 'started')
    //             ->where('load_status', 'approved')
    //             ->orderBy('created_at', 'desc')
    //             ->first();


    //         if ($vehicleLoad) {
    //             foreach ($vehicleLoad->vehicleLoadStockItems as $product) {
    //                 $product->delivered_qty = $product->total_qty - $product->available_qty;
    //                 $product->delivered_percentage = round(($product->delivered_qty / $product->total_qty) * 100, 0);
    //             }
    //         } else {
    //             $vehicleLoad = null;
    //         }


    //         // Get current user incentive
    //         $userIncentive = Incentive::where('user_id', $request['user_id'])->first();

    //         // Default commission if no record
    //         $userCommission = $userIncentive ? $userIncentive->total_commission : 0;

    //         // Calculate rank: number of users with higher commission + 1
    //         $user_rank = Incentive::where('total_commission', '>', $userCommission)->count() + 1;


    //         // Get top scorer
    //         $topScorer = Incentive::with('user')
    //             ->orderByDesc('total_commission')
    //             ->orderBy('updated_at')
    //             ->first();

    //         $orders_count = Order::where('delivered_by', $request['user_id'])
    //             ->where('order_status', 'requested')->count();


    //         $response = [
    //             'sales_man' => $user,
    //             'orders_count' => $orders_count,
    //             'vehicleLoad' => $vehicleLoad,
    //             'user_score' => $userIncentive ? $userIncentive->total_commission : 0,
    //             'user_rank' => $user_rank,
    //             'top_scorer' => $topScorer ? [
    //                 'user_id' => $topScorer->user->id,
    //                 'user_name'    => $topScorer->user->name,
    //                 'profile_image'    => $topScorer->user->profile_image ?? "",
    //                 'total_commission'   => $topScorer->total_commission
    //             ] : [
    //                 'user_id' => $user->id,
    //                 'user_name'    => $user->name,
    //                 'total_commission'   => 0
    //             ]
    //         ];

    //         return successResponse($response, 'profile fetched successfully', 0);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 102, 500);
    //     }
    // }


}
