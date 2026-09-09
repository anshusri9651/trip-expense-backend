<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'initials' => $this->initials,
            'avatar_color' => $this->avatar_color,
            'is_owner' => $this->is_owner,
            'total_paid' => $this->when($this->relationLoaded('expensesPaid'), fn() => round($this->expensesPaid->sum('amount'), 2)),
            'total_share' => $this->when($this->relationLoaded('expenseSplits'), fn() => round($this->expenseSplits->sum('amount'), 2)),
            'net_balance' => $this->when(
                $this->relationLoaded('expensesPaid') && $this->relationLoaded('expenseSplits'),
                fn() => round($this->expensesPaid->sum('amount') - $this->expenseSplits->sum('amount'), 2)
            ),
            'created_at' => $this->created_at,
        ];
    }
}
