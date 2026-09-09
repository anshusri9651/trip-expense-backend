<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $period = in_array($request->query('period'), ['7', '30', '90', 'all'], true) ? $request->query('period') : '30';
        $search = trim((string) $request->query('q', ''));
        $trips = Trip::where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $user->id)))
            ->with(['members.expensesPaid', 'members.expenseSplits', 'expenses.paidBy'])->get();
        if ($search !== '') {
            $trips = $trips->filter(fn ($trip) => str_contains(strtolower($trip->trip_name.' '.$trip->group_name), strtolower($search)))->values();
        }
        $owed = 0; $owes = 0;
        foreach ($trips as $trip) {
            $member = $trip->members->firstWhere('user_id', $user->id);
            if ($member) { $balance = $member->expensesPaid->sum('amount') - $member->expenseSplits->sum('amount'); $balance > 0 ? $owed += $balance : $owes += abs($balance); }
        }
        $expenses = $trips->flatMap(fn ($trip) => $trip->expenses->map(function ($expense) use ($trip) { $expense->trip_name = $trip->trip_name; $expense->currency_symbol = $trip->currency_symbol; return $expense; }));
        if ($period !== 'all') {
            $expenses = $expenses->filter(fn ($expense) => $expense->expense_date && $expense->expense_date->gte(now()->subDays((int) $period)->startOfDay()));
        }
        if ($search !== '') {
            $expenses = $expenses->filter(fn ($expense) => str_contains(strtolower($expense->title.' '.$expense->trip_name), strtolower($search)));
        }
        $expenses = $expenses->sortByDesc('created_at')->take(8);
        return view('dashboard', [
            'user' => $user, 'trips' => $trips->where('status', 'active')->take(6), 'expenses' => $expenses, 'period' => $period, 'search' => $search,
            'stats' => ['trips' => $trips->count(), 'active' => $trips->where('status', 'active')->count(), 'spent' => $trips->sum(fn ($t) => $t->expenses->sum('amount')), 'owed' => $owed, 'owes' => $owes],
        ]);
    }
}
