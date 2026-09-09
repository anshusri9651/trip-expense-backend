<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripMemberResource;
use App\Models\Trip;
use App\Models\TripMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripMemberController extends Controller
{
    public function index(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeTrip($request->user(), $trip);

        $members = $trip->members()->get();

        return response()->json([
            'success' => true,
            'data' => TripMemberResource::collection($members),
        ]);
    }

    public function store(Request $request, Trip $trip): JsonResponse
    {
        $this->authorizeOwner($request->user(), $trip);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $colors = ['#4F46E5', '#0D9488', '#DC2626', '#D97706', '#059669', '#7C3AED', '#DB2777', '#EA580C', '#0891B2'];
        $colorIndex = $trip->members()->count() % count($colors);

        $member = TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => null,
            'name' => $request->name,
            'avatar_color' => $colors[$colorIndex],
            'is_owner' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully',
            'data' => new TripMemberResource($member),
        ], 201);
    }

    public function update(Request $request, Trip $trip, TripMember $member): JsonResponse
    {
        $this->authorizeOwner($request->user(), $trip);

        if ($member->trip_id !== $trip->id) {
            abort(404, 'Member not found in this trip.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $member->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully',
            'data' => new TripMemberResource($member),
        ]);
    }

    public function destroy(Request $request, Trip $trip, TripMember $member): JsonResponse
    {
        $this->authorizeOwner($request->user(), $trip);

        if ($member->trip_id !== $trip->id) {
            abort(404, 'Member not found in this trip.');
        }

        if ($member->is_owner) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove the trip owner.',
            ], 422);
        }

        // Check if member has paid any expenses
        if ($member->expensesPaid()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove a member who has paid expenses.',
            ], 422);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully',
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
