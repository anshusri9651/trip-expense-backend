<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Trip;
use App\Services\ExpenseCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseCalculationService $calculationService) {}

    public function index(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        $query = $trip->expenses()
            ->with(['paidBy', 'splits.member'])
            ->latest('expense_date')
            ->latest('created_at');

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $expenses = $query->get();

        return response()->json([
            'success' => true,
            'data' => ExpenseResource::collection($expenses),
        ]);
    }

    public function store(StoreExpenseRequest $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        // Verify payer is a member of this trip
        $payer = $trip->members()->find($request->paid_by_member_id);
        if (!$payer) {
            return response()->json([
                'success' => false,
                'message' => 'Payer must be a member of this trip.',
            ], 422);
        }

        // Verify all split members belong to this trip
        $memberIds = $request->split_member_ids;
        $validMemberCount = $trip->members()->whereIn('id', $memberIds)->count();
        if ($validMemberCount !== count($memberIds)) {
            return response()->json([
                'success' => false,
                'message' => 'All split participants must be members of this trip.',
            ], 422);
        }

        $expense = Expense::create([
            'trip_id' => $trip->id,
            'paid_by_member_id' => $request->paid_by_member_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'category' => $request->category,
            'expense_date' => $request->expense_date,
            'expense_time' => $request->expense_time,
            'notes' => $request->notes,
        ]);

        // Calculate and save splits
        $splits = $this->calculationService->calculateEqualSplits($request->amount, $memberIds);
        foreach ($splits as $memberId => $splitAmount) {
            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'trip_member_id' => $memberId,
                'amount' => $splitAmount,
            ]);
        }

        $expense->load(['paidBy', 'splits.member']);

        return response()->json([
            'success' => true,
            'message' => 'Expense added successfully',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    public function show(Request $request, Trip $trip, Expense $expense): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        if ($expense->trip_id !== $trip->id) {
            abort(404, 'Expense not found in this trip.');
        }

        $expense->load(['paidBy', 'splits.member']);

        return response()->json([
            'success' => true,
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Trip $trip, Expense $expense): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        if ($expense->trip_id !== $trip->id) {
            abort(404, 'Expense not found in this trip.');
        }

        // Verify payer
        $payer = $trip->members()->find($request->paid_by_member_id);
        if (!$payer) {
            return response()->json([
                'success' => false,
                'message' => 'Payer must be a member of this trip.',
            ], 422);
        }

        $memberIds = $request->split_member_ids;
        $validMemberCount = $trip->members()->whereIn('id', $memberIds)->count();
        if ($validMemberCount !== count($memberIds)) {
            return response()->json([
                'success' => false,
                'message' => 'All split participants must be members of this trip.',
            ], 422);
        }

        $expense->update([
            'paid_by_member_id' => $request->paid_by_member_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'category' => $request->category,
            'expense_date' => $request->expense_date,
            'expense_time' => $request->expense_time,
            'notes' => $request->notes,
        ]);

        // Recalculate splits
        $expense->splits()->delete();
        $splits = $this->calculationService->calculateEqualSplits($request->amount, $memberIds);
        foreach ($splits as $memberId => $splitAmount) {
            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'trip_member_id' => $memberId,
                'amount' => $splitAmount,
            ]);
        }

        $expense->load(['paidBy', 'splits.member']);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function destroy(Request $request, Trip $trip, Expense $expense): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        if ($expense->trip_id !== $trip->id) {
            abort(404, 'Expense not found in this trip.');
        }

        $expense->splits()->delete();
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
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
