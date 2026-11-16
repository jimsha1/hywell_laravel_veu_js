<?php

namespace App\Services\salesExecutive;

use App\Models\CommissionHistory;
use App\Models\Customer;
use App\Models\Incentive;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Exception;

class ProfileService
{
    // public function getSalesExecutiveProfile($request)
    // {
    //     try {
    //         $user = User::with('latestSalesExecutiveOrders.customer')->find($request['user_id']);

    //         if (!$user) {
    //             return errorResponse(null, "User not found", 200);
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

    //         $response = [
    //             'user' => $user,
    //             'user_score' => $userIncentive ? $userIncentive->total_commission : 0,
    //             'user_rank' => $user_rank,
    //             'top_scorer' => $topScorer ? [
    //                 'user_id' => $topScorer->user->id,
    //                 'user_name'    => $topScorer->user->name,
    //                 'profile_image'    => $topScorer->user->profile_image ?? "",
    //                 'total_commission'   => $topScorer->total_commission
    //             ] : null
    //         ];

    //         return successResponse($response, 'profile fetched successfully', 0);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 102, 200);
    //     }
    // }


    public function getSalesExecutiveProfile($request)
    {
        try {
            $user = User::with('latestSalesExecutiveOrders.customer')->find($request['user_id']);

            if (!$user) {
                return errorResponse(null, "User not found", 200);
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

            // Calculate rank based on this month only
            $user_rank = $usersCommissions->where('total_commission', '>', $userCommission)->count() + 1;

            // Get top scorer of this month
            $topScorerItem = $usersCommissions->first();
            $topScorerUser = $topScorerItem ? User::find($topScorerItem['user_id']) : null;

            $response = [
                'user' => $user,
                'user_score' => $userCommission,
                'user_rank' => $user_rank,
                'top_scorer' => $topScorerItem && $topScorerUser ? [
                    'user_id' => $topScorerUser->id,
                    'user_name' => $topScorerUser->name,
                    'profile_image' => $topScorerUser->profile_image ?? "",
                    'total_commission' => $topScorerItem['total_commission']
                ] : null
            ];

            return successResponse($response, 'profile fetched successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 102, 200);
        }
    }



    public function updateProfile($request)
    {
        try {
            $user = User::find($request['user_id']);
            if (!$user) {
                return errorResponse(null, 'User not found', 101, 200);
            }
            $user->update([
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
            ]);
            return successResponse($user, 'Profile updated successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 102, 200);
        }
    }

    public function updateProfileImage($request)
    {
        try {
            $user = User::find($request['user_id']);
            if (!$user) {
                return errorResponse(null, 'User not found', 101, 200);
            }
            $user->update([
                'profile_image' => $request['profile_image']
            ]);
            return successResponse($user, 'Profile image updated successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 102, 200);
        }
    }
}
