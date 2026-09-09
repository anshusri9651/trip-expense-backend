<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'initials' => $this->initials,
            'avatar_color' => $this->avatar_color,
            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,
            'notifications_enabled' => $this->notifications_enabled,
            'theme' => $this->theme,
            'created_at' => $this->created_at,
        ];
    }
}
