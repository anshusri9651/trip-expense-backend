<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->user()->id,
            'currency' => 'sometimes|string|max:10',
            'currency_symbol' => 'sometimes|string|max:5',
            'notifications_enabled' => 'sometimes|boolean',
            'theme' => 'sometimes|string|in:light,dark',
            'avatar_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
