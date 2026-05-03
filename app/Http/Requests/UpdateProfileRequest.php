<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
public function rules()
{
    return [
        'first_name' => 'sometimes|string|max:255',
        'last_name'  => 'sometimes|string|max:255',
        'email'      => 'sometimes|email|unique:users,email,' . $this->user()->id,
        'phone'      => 'sometimes|string|unique:users,phone,' . $this->user()->id,
        'address'    => 'sometimes|string|max:255',
        'password'   => 'sometimes|string|min:6',
    ];
}



public function messages()
{
    return [
        'first_name.string' => 'First name must be a valid text.',
        'first_name.max' => 'First name may not be greater than 255 characters.',

        'last_name.string' => 'Last name must be a valid text.',
        'last_name.max' => 'Last name may not be greater than 255 characters.',

        'email.email' => 'Please enter a valid email address.',
        'email.unique' => 'This email is already taken.',

        'phone.string' => 'Phone number must be a valid text.',
        'phone.unique' => 'This phone number is already taken.',

        'address.string' => 'Address must be a valid text.',
        'address.max' => 'Address may not be greater than 255 characters.',

        'password.string' => 'Password must be a valid text.',
        'password.min' => 'Password must be at least 6 characters.',
    ];
}

}
