<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $members = $this->whenLoaded('members');
        $expenses = $this->whenLoaded('expenses');
        $currentUserMember = $this->current_user_member ?? null;

        $totalExpenses = $this->relationLoaded('expenses') ? round($this->expenses->sum('amount'), 2) : 0;
        $myBalance = null;

        if ($currentUserMember && $this->relationLoaded('members')) {
            $member = $this->members->where('user_id', optional($request->user())->id)->first();
            if ($member && $member->relationLoaded('expensesPaid') && $member->relationLoaded('expenseSplits')) {
                $myBalance = round($member->expensesPaid->sum('amount') - $member->expenseSplits->sum('amount'), 2);
            }
        }

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
            'my_balance' => $myBalance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
