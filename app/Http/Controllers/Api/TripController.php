<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Http\Resources\TripDetailResource;
use App\Models\Trip;
use App\Models\TripMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get trips where user is owner OR a member
        $trips = Trip::where('user_id', $user->id)
            ->orWhereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['members', 'expenses'])
            ->withCount('members')
            ->latest()
            ->get();

        // Attach user's member record for balance
        $trips->each(function ($trip) use ($user) {
            $trip->current_user_member = $trip->members->where('user_id', $user->id)->first();
        });

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips),
        ]);
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $colors = ['#4F46E5', '#0D9488', '#DC2626', '#D97706', '#059669', '#7C3AED', '#DB2777', '#EA580C'];

        $trip = Trip::create([
            'user_id' => $request->user()->id,
            'trip_name' => $request->trip_name,
            'group_name' => $request->group_name,
            'description' => $request->description,
            'status' => 'active',
            'currency' => $request->currency ?? 'INR',
            'currency_symbol' => $request->currency_symbol ?? '₹',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'cover_color' => $colors[array_rand($colors)],
        ]);

        // Add the owner as first member
        $memberColors = ['#4F46E5', '#0D9488', '#DC2626', '#D97706', '#059669', '#7C3AED', '#DB2777', '#EA580C', '#0891B2', '#65A30D'];
        $ownerMember = TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'avatar_color' => $memberColors[0],
            'is_owner' => true,
        ]);

        // Add other members
        $members = $request->input('members', []);
        foreach ($members as $index => $member) {
            if (!empty($member['name'])) {
                TripMember::create([
                    'trip_id' => $trip->id,
                    'user_id' => null,
                    'name' => $member['name'],
                    'avatar_color' => $memberColors[($index + 1) % count($memberColors)],
                    'is_owner' => false,
                ]);
            }
        }

        $trip->load(['members', 'expenses']);

        return response()->json([
            'success' => true,
            'message' => 'Trip created successfully',
            'data' => new TripDetailResource($trip),
        ], 201);
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        $trip->load(['members.expensesPaid', 'members.expenseSplits', 'expenses.paidBy', 'expenses.splits.member']);

        return response()->json([
            'success' => true,
            'data' => new TripDetailResource($trip),
        ]);
    }

    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        $this->authorizeOwner($request->user(), $trip);

        $trip->update($request->validated());

        $trip->load(['members', 'expenses']);

        return response()->json([
            'success' => true,
            'message' => 'Trip updated successfully',
            'data' => new TripDetailResource($trip),
        ]);
    }

    public function destroy(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeOwner($request->user(), $trip);

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully',
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

    private function authorizeOwner($user, Trip $trip): void
    {
        if ($trip->user_id !== $user->id) {
            abort(403, 'Only the trip owner can perform this action.');
        }
    }
}
