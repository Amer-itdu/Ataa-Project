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
            'amount'   => 'required|numeric|min:1',
            'currency' => 'required|in:USD,EUR,SAR,AED,EGP,SYP',
        ];
    }

    public function messages(): array
    {
        return [
            'currency.required' => 'Currency is required.',
            'currency.in'        => 'The selected currency is not supported.',

            'amount.required' => 'Donation amount is required.',
            'amount.numeric'  => 'The donation amount must be a numeric value.',
            'amount.min'      => 'The minimum donation amount is 1.',
        ];
    }
}