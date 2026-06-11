<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'anonymous' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Donation amount is required.',
            'amount.numeric' => 'Donation amount must be numeric.',
            'amount.min' => 'Donation amount must be at least 0.01.',
            'anonymous.boolean' => 'Anonymous must be true or false.',
        ];
    }
}
