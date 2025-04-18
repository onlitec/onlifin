<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'status' => 'required|in:paid,pending',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'account_id' => 'required|exists:accounts,id',
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'recurrence_type' => 'nullable|in:none,fixed,installment',
            'installment_number' => 'nullable|required_if:recurrence_type,installment|integer|min:1',
            'total_installments' => 'nullable|required_if:recurrence_type,installment|integer|min:1',
            'next_date' => 'nullable|required_unless:recurrence_type,none|date',
        ];
    }
}
