<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'currency' => 'required|in:USD,EUR,SAR,AED,EGP,SYP',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:campaign,request',
            'id' => 'required|integer|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'currency.required' => 'Currency is required.',
            'currency.in' => 'The selected currency is not supported.',

            'amount.required' => 'Donation amount is required.',
            'amount.numeric' => 'The donation amount must be a numeric value.',
            'amount.min' => 'The minimum donation amount is 1.',

            'type.required' => 'Donation type is required.',
            'type.in' => 'Donation type must be either campaign or request.',

            'id.required' => 'Target ID is required.',
            'id.integer' => 'Target ID must be a valid integer.',
            'id.gt' => 'Target ID must be greater than zero.',
        ];
    }
}
