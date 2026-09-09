<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'trip_name' => 'sometimes|string|max:255',
            'group_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:active,completed',
            'currency' => 'sometimes|string|max:10',
            'currency_symbol' => 'sometimes|string|max:5',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ];
    }
}
