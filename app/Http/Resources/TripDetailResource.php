<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $members = $this->whenLoaded('members', function () {
            return $this->members->map(function ($member) {
                $totalPaid = $member->relationLoaded('expensesPaid') ? $member->expensesPaid->sum('amount') : 0;
                $totalShare = $member->relationLoaded('expenseSplits') ? $member->expenseSplits->sum('amount') : 0;
                return [
                    'id' => $member->id,
                    'trip_id' => $member->trip_id,
                    'user_id' => $member->user_id,
                    'name' => $member->name,
                    'initials' => $member->initials,
                    'avatar_color' => $member->avatar_color,
                    'is_owner' => $member->is_owner,
                    'total_paid' => round($totalPaid, 2),
                    'total_share' => round($totalShare, 2),
                    'net_balance' => round($totalPaid - $totalShare, 2),
                ];
            });
        });

        $totalExpenses = $this->relationLoaded('expenses') ? round($this->expenses->sum('amount'), 2) : 0;

        return [
            'id' => $this->id,
            'trip_name' => $this->trip_name,
            'group_name' => $this->group_name,
            'description' => $this->description,
            'status' => $this->status,
            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,
            'cover_color' => $this->cover_color,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'members_count' => $this->relationLoaded('members') ? $this->members->count() : 0,
            'total_expenses' => $totalExpenses,
            'members' => $members,
            'expenses' => $this->whenLoaded('expenses', fn() => ExpenseResource::collection($this->expenses)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
