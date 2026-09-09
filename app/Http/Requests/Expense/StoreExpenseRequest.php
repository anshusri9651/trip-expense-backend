<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:food,hotel,transport,shopping,entertainment,tickets,fuel,other',
            'paid_by_member_id' => 'required|integer|exists:trip_members,id',
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'split_member_ids' => 'required|array|min:1',
            'split_member_ids.*' => 'integer|exists:trip_members,id',
        ];
    }
}
