<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\BalanceCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(private BalanceCalculationService $balanceService) {}

    public function summary(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        $trip->load(['members.expensesPaid', 'members.expenseSplits', 'expenses']);

        $totalExpenses = $trip->expenses->sum('amount');
        $membersData = [];

        foreach ($trip->members as $member) {
            $totalPaid = $member->expensesPaid->sum('amount');
            $totalShare = $member->expenseSplits->sum('amount');
            $netBalance = $totalPaid - $totalShare;

            $membersData[] = [
                'id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials,
                'avatar_color' => $member->avatar_color,
                'is_owner' => $member->is_owner,
                'total_paid' => round($totalPaid, 2),
                'total_share' => round($totalShare, 2),
                'net_balance' => round($netBalance, 2),
                'status' => $netBalance > 0.01 ? 'gets' : ($netBalance < -0.01 ? 'owes' : 'settled'),
            ];
        }

        // Find current user's member record
        $currentUserMember = $trip->members->where('user_id', $request->user()->id)->first();
        $myBalance = null;
        if ($currentUserMember) {
            $myPaid = $currentUserMember->expensesPaid->sum('amount');
            $myShare = $currentUserMember->expenseSplits->sum('amount');
            $myBalance = [
                'total_paid' => round($myPaid, 2),
                'total_share' => round($myShare, 2),
                'net_balance' => round($myPaid - $myShare, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip' => [
                    'id' => $trip->id,
                    'trip_name' => $trip->trip_name,
                    'group_name' => $trip->group_name,
                    'status' => $trip->status,
                    'currency' => $trip->currency,
                    'currency_symbol' => $trip->currency_symbol,
                ],
                'total_expenses' => round($totalExpenses, 2),
                'total_members' => $trip->members->count(),
                'total_transactions' => $trip->expenses->count(),
                'my_balance' => $myBalance,
                'members' => $membersData,
            ],
        ]);
    }

    public function balances(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);
        $trip->load(['members.expensesPaid', 'members.expenseSplits']);

        $balances = [];
        foreach ($trip->members as $member) {
            $totalPaid = $member->expensesPaid->sum('amount');
            $totalShare = $member->expenseSplits->sum('amount');
            $balances[] = [
                'member_id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials,
                'avatar_color' => $member->avatar_color,
                'total_paid' => round($totalPaid, 2),
                'total_share' => round($totalShare, 2),
                'net_balance' => round($totalPaid - $totalShare, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $balances,
        ]);
    }

    public function statistics(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);
        $trip->load('expenses');

        $expenses = $trip->expenses;
        $total = $expenses->sum('amount');
        $count = $expenses->count();

        // Category breakdown
        $byCategory = $expenses->groupBy('category')->map(function ($group) use ($total) {
            $catTotal = $group->sum('amount');
            return [
                'total' => round($catTotal, 2),
                'count' => $group->count(),
                'percentage' => $total > 0 ? round(($catTotal / $total) * 100, 1) : 0,
            ];
        });

        // Daily breakdown (last 30 days)
        $byDate = $expenses->groupBy(function ($e) {
            return $e->expense_date->format('Y-m-d');
        })->map(function ($group) {
            return round($group->sum('amount'), 2);
        })->sortKeys();

        return response()->json([
            'success' => true,
            'data' => [
                'total_expenses' => round($total, 2),
                'total_transactions' => $count,
                'average_expense' => $count > 0 ? round($total / $count, 2) : 0,
                'highest_expense' => $count > 0 ? round($expenses->max('amount'), 2) : 0,
                'lowest_expense' => $count > 0 ? round($expenses->min('amount'), 2) : 0,
                'by_category' => $byCategory,
                'by_date' => $byDate,
            ],
        ]);
    }

    public function settlements(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);
        $trip->load(['members.expensesPaid', 'members.expenseSplits']);

        $balances = [];
        foreach ($trip->members as $member) {
            $totalPaid = $member->expensesPaid->sum('amount');
            $totalShare = $member->expenseSplits->sum('amount');
            $balances[$member->id] = [
                'id' => $member->id,
                'name' => $member->name,
                'avatar_color' => $member->avatar_color,
                'initials' => $member->initials,
                'balance' => round($totalPaid - $totalShare, 2),
            ];
        }

        $settlements = $this->balanceService->calculateSettlements($balances);

        return response()->json([
            'success' => true,
            'data' => $settlements,
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all trips user is part of
        $trips = Trip::where('user_id', $user->id)
            ->orWhereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['members.expensesPaid', 'members.expenseSplits', 'expenses'])
            ->get();

        $totalTrips = $trips->count();
        $activeTrips = $trips->where('status', 'active')->count();
        $totalExpenses = $trips->sum(fn($t) => $t->expenses->sum('amount'));

        // Calculate overall balance for user
        $totalOwed = 0;
        $totalOwes = 0;

        foreach ($trips as $trip) {
            $myMember = $trip->members->where('user_id', $user->id)->first();
            if ($myMember) {
                $paid = $myMember->expensesPaid->sum('amount');
                $share = $myMember->expenseSplits->sum('amount');
                $balance = $paid - $share;
                if ($balance > 0) $totalOwed += $balance;
                else $totalOwes += abs($balance);
            }
        }

        // Recent expenses across all trips
        $recentExpenses = collect();
        foreach ($trips as $trip) {
            foreach ($trip->expenses->take(5) as $expense) {
                $expense->trip_name = $trip->trip_name;
                $expense->currency_symbol = $trip->currency_symbol;
            }
            $recentExpenses = $recentExpenses->merge($trip->expenses);
        }
        $recentExpenses = $recentExpenses->sortByDesc('created_at')->take(10);

        // Active trip cards
        $activeTripsData = $trips->where('status', 'active')->take(5)->map(function ($trip) use ($user) {
            $myMember = $trip->members->where('user_id', $user->id)->first();
            $myBalance = 0;
            if ($myMember) {
                $paid = $myMember->expensesPaid->sum('amount');
                $share = $myMember->expenseSplits->sum('amount');
                $myBalance = round($paid - $share, 2);
            }
            return [
                'id' => $trip->id,
                'trip_name' => $trip->trip_name,
                'group_name' => $trip->group_name,
                'members_count' => $trip->members->count(),
                'total_expenses' => round($trip->expenses->sum('amount'), 2),
                'currency_symbol' => $trip->currency_symbol,
                'cover_color' => $trip->cover_color,
                'my_balance' => $myBalance,
                'status' => $trip->status,
                'created_at' => $trip->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_trips' => $totalTrips,
                    'active_trips' => $activeTrips,
                    'total_expenses' => round($totalExpenses, 2),
                    'you_get' => round($totalOwed, 2),
                    'you_owe' => round($totalOwes, 2),
                ],
                'active_trips' => $activeTripsData,
                'recent_expenses' => $recentExpenses->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'title' => $expense->title,
                        'amount' => $expense->amount,
                        'category' => $expense->category,
                        'expense_date' => $expense->expense_date,
                        'trip_name' => $expense->trip_name ?? '',
                        'currency_symbol' => $expense->currency_symbol ?? '₹',
                        'paid_by' => $expense->paidBy ? $expense->paidBy->name : 'Unknown',
                        'created_at' => $expense->created_at,
                    ];
                })->values(),
            ],
        ]);
    }

    private function authorizeTrip($user, Trip $trip): void
    {
        $isMember = $trip->members()->where('user_id', $user->id)->exists();
        $isOwner = $trip->user_id === $user->id;
        if (!$isOwner && !$isMember) {
            abort(403, 'You are not authorized to access this trip.');
        }
    }
}
