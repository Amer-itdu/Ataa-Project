<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignInRequest extends FormRequest
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
        'Email' => ['required_without:Phone', 'email', 'string', 'exists:users,email'],
        'Phone' => ['required_without:Email', 'regex:/^[0-9]+$/', 'string', 'exists:users,phone'],
        'password'   => ['required', 'min:8'],
    ];
}

public function messages(): array
{
    return [
        'Email.required' => 'Please enter your email.',
        'Phone.required' => 'Please enter your phone number.',
        'password.required'   => 'Please enter your password.',
        'password.min'        => 'The password must be at least 8 characters.',
    ];
}

}
