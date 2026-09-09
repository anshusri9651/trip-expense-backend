<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'title' => $this->title,
            'amount' => round((float) $this->amount, 2),
            'category' => $this->category,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'expense_time' => $this->expense_time,
            'notes' => $this->notes,
            'paid_by' => new TripMemberResource($this->whenLoaded('paidBy')),
            'splits' => $this->whenLoaded('splits', fn () => $this->splits->map(fn ($split) => [
                'id' => $split->id,
                'member' => new TripMemberResource($split->member),
                'amount' => round((float) $split->amount, 2),
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
