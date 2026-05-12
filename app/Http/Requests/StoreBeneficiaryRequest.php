<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
      public function rules(): array
{

    return [
        'full_name'   => 'required|string|max:255',
        'mother_name' => 'required|string|max:255',
        'address'     => 'nullable|string|max:255',
        'email'       => 'nullable|email|max:255',
        'phone'       => 'nullable|string|max:20',
    ];


}

public function messages(): array
{
    return [
        'full_name.required' => 'Full name is required.',
        'full_name.string'   => 'Full name must be a string.',
        'full_name.max'      => 'Full name may not exceed 255 characters.',

        'mother_name.required' => 'Mother name is required.',
        'mother_name.string'   => 'Mother name must be a string.',
        'mother_name.max'      => 'Mother name may not exceed 255 characters.',

        'address.string'     => 'Address must be a string.',
        'address.max'        => 'Address may not exceed 255 characters.',

        'email.email'        => 'Email must be a valid email address.',
        'email.max'          => 'Email may not exceed 255 characters.',

        'phone.string'       => 'Phone must be a string.',
        'phone.max'          => 'Phone may not exceed 20 characters.',
    ];
}

}
