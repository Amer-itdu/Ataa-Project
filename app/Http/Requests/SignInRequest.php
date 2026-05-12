<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'login' => $this->email ?? $this->phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'email'    => ['required_without:phone', 'string', 'email'],
            'phone'    => ['required_without:email', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Please enter your email or phone.',
            'phone.required_without' => 'Please enter your phone or email.',
            'password.required'      => 'Please enter your password.',
            'password.min'           => 'Password must be at least 8 characters.',
        ];
    }
}
