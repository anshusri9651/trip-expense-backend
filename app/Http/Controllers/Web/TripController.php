<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\User;
use App\Services\BalanceCalculationService;
use App\Services\ExpenseCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController extends Controller
{
    public function show(Request $request, Trip $trip, BalanceCalculationService $balanceService): View
    {
        $this->authorizeTrip($request, $trip);
        $trip->load(['members.expensesPaid', 'members.expenseSplits', 'expenses.paidBy', 'expenses.splits.member']);
        $balances = $trip->members->map(fn ($member) => ['id' => $member->id, 'name' => $member->name, 'avatar_color' => $member->avatar_color, 'initials' => $member->initials, 'balance' => round($member->expensesPaid->sum('amount') - $member->expenseSplits->sum('amount'), 2)])->all();
        return view('trips.show', ['user' => $request->user(), 'trip' => $trip, 'settlements' => $balanceService->calculateSettlements($balances)]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Trip::where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('members', fn ($memberQuery) => $memberQuery->where('user_id', $user->id)));
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('trip_name', 'like', '%'.$request->query('q').'%')->orWhere('group_name', 'like', '%'.$request->query('q').'%'));
        if (in_array($request->query('status'), ['active', 'completed'], true)) $query->where('status', $request->query('status'));
        return view('trips.index', ['user' => $user, 'trips' => $query->with('members')->withCount('expenses')->withSum('expenses', 'amount')->latest()->paginate(10)->withQueryString(), 'search' => $request->query('q', ''), 'status' => $request->query('status', 'all')]);
    }

    public function create(Request $request): View { return view('trips.create', ['user' => $request->user()]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['trip_name' => ['required', 'string', 'max:255'], 'group_name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:1000'], 'currency_symbol' => ['required', 'string', 'max:5'], 'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date']]);
        $colors = ['#4F46E5', '#0D9488', '#D97706', '#7C3AED', '#DB2777'];
        $trip = Trip::create([...$data, 'user_id' => $request->user()->id, 'currency' => 'INR', 'status' => 'active', 'cover_color' => $colors[array_rand($colors)] ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $request->user()->id, 'name' => $request->user()->name, 'avatar_color' => $request->user()->avatar_color, 'is_owner' => true]);
        return redirect()->route('trips.index')->with('status', 'Trip created successfully.');
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        abort_unless($trip->user_id === $request->user()->id, 403);
        $trip->update($request->validate(['trip_name' => ['required', 'string', 'max:255'], 'group_name' => ['required', 'string', 'max:255'], 'status' => ['required', 'in:active,completed'], 'description' => ['nullable', 'string', 'max:1000'], 'currency_symbol' => ['sometimes', 'string', 'max:5']]));
        return back()->with('status', 'Trip updated successfully.');
    }

    public function destroy(Request $request, Trip $trip): RedirectResponse
    {
        abort_unless($trip->user_id === $request->user()->id, 403);
        $trip->delete();
        return redirect()->route('trips.index')->with('status', 'Trip deleted.');
    }

    public function addMember(Request $request, Trip $trip): RedirectResponse
    {
        $this->authorizeOwner($request, $trip);
        $data = $request->validate(['email' => ['nullable', 'email', 'exists:users,email'], 'name' => ['nullable', 'string', 'max:255']]);
        $identifier = $data['email'] ?? $data['name'] ?? '';
        if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) return back()->withErrors(['email' => 'Enter the email address of a registered app user.']);
        $user = User::where('email', $identifier)->first();
        if (! $user) return back()->withErrors(['email' => 'No registered app user was found with that email.']);
        if ($trip->members()->where('user_id', $user->id)->exists()) return back()->withErrors(['email' => 'This app user is already a member of the trip.']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $user->id, 'name' => $user->name, 'avatar_color' => $user->avatar_color, 'is_owner' => false]);
        return back()->with('status', 'Member added successfully.');
    }

    public function deleteMember(Request $request, Trip $trip, TripMember $member): RedirectResponse
    {
        $this->authorizeOwner($request, $trip); abort_unless($member->trip_id === $trip->id && ! $member->is_owner, 404);
        if ($member->expensesPaid()->exists() || $member->expenseSplits()->exists()) return back()->withErrors(['member' => 'This member has expenses or balances. Settle or reassign those expenses before removing them.']);
        $member->delete(); return back()->with('status', 'Member removed.');
    }

    public function updateMember(Request $request, Trip $trip, TripMember $member): RedirectResponse
    {
        $this->authorizeOwner($request, $trip);
        abort_unless($member->trip_id === $trip->id && ! $member->is_owner, 404);
        $member->update($request->validate(['name' => ['required', 'string', 'max:255']]));
        return back()->with('status', 'Member updated successfully.');
    }

    public function addExpense(Request $request, Trip $trip, ExpenseCalculationService $calculator): RedirectResponse
    {
        $this->authorizeTrip($request, $trip);
        $data = $this->validatedExpense($request, $trip);
        $expense = Expense::create([...$data, 'trip_id' => $trip->id]);
        foreach ($calculator->calculateEqualSplits((float) $data['amount'], $data['split_member_ids']) as $memberId => $amount) ExpenseSplit::create(['expense_id' => $expense->id, 'trip_member_id' => $memberId, 'amount' => $amount]);
        return back()->with('status', 'Expense added and split equally.');
    }

    public function deleteExpense(Request $request, Trip $trip, Expense $expense): RedirectResponse
    {
        $this->authorizeTrip($request, $trip); abort_unless($expense->trip_id === $trip->id, 404);
        $expense->splits()->delete(); $expense->delete(); return back()->with('status', 'Expense deleted.');
    }

    public function updateExpense(Request $request, Trip $trip, Expense $expense, ExpenseCalculationService $calculator): RedirectResponse
    {
        $this->authorizeTrip($request, $trip);
        abort_unless($expense->trip_id === $trip->id, 404);
        $data = $this->validatedExpense($request, $trip);
        $expense->update([...$data, 'trip_id' => $trip->id]);
        $expense->splits()->delete();
        foreach ($calculator->calculateEqualSplits((float) $data['amount'], $data['split_member_ids']) as $memberId => $amount) ExpenseSplit::create(['expense_id' => $expense->id, 'trip_member_id' => $memberId, 'amount' => $amount]);
        return back()->with('status', 'Expense updated and splits recalculated.');
    }

    private function validatedExpense(Request $request, Trip $trip): array
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'min:0.01'], 'category' => ['required', 'in:food,hotel,transport,shopping,entertainment,tickets,fuel,other'], 'paid_by_member_id' => ['required', 'integer'], 'expense_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:1000'], 'split_member_ids' => ['required', 'array', 'min:1'], 'split_member_ids.*' => ['integer']]);
        $validIds = $trip->members()->whereIn('id', $data['split_member_ids'])->pluck('id')->all(); abort_unless(count($validIds) === count($data['split_member_ids']), 422, 'All participants must be trip members.'); abort_unless($trip->members()->whereKey($data['paid_by_member_id'])->exists(), 422, 'Payer must be a trip member.'); return $data;
    }

    private function authorizeTrip(Request $request, Trip $trip): void { abort_unless($trip->user_id === $request->user()->id || $trip->members()->where('user_id', $request->user()->id)->exists(), 403); }
    private function authorizeOwner(Request $request, Trip $trip): void { abort_unless($trip->user_id === $request->user()->id, 403); }
}
