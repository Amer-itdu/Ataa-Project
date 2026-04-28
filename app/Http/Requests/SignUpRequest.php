<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
     */
public function rules(): array
{
    return [
        'first_name'    => 'required|string|regex:/^[^\d\s,]+$/',
        'last_name'     => 'required|string|regex:/^[^\d\s,]+$/',

        // phone must be string, not integer
        'phone'         => 'required_without:email|string|regex:/^[0-9]+$/|unique:users,phone',

        'email'         => 'required_without:phone|email|unique:users,email',

        'password'      => 'required|string|min:8|confirmed',

        'address'       => 'nullable|string|max:255',

        'date_of_birth' => 'required|date|before_or_equal:' . now()->subYears(18)->toDateString(),

        // make profile_image optional for testing
        'profile_image' => 'nullable|image|mimes:png,jpg,jpeg,gif',

        'national_id'   => 'required_without:international_passport|image|mimes:png,jpg,jpeg,gif',
        'international_passport' => 'required_without:national_id|image|mimes:png,jpg,jpeg,gif',

        'account_balance' => 'nullable|integer|min:0',
    ];
}


    public function messages(): array
    {
        return [
            'first_name.required'     => 'First name is required.',
            'first_name.regex'        => 'First name must not contain numbers, spaces, or commas.',

            'last_name.required'      => 'Last name is required.',
            'last_name.regex'         => 'Last name must not contain numbers, spaces, or commas.',

            'phone.required'          => 'Phone number or email is required.',
            'phone.integer'           => 'Phone number must contain digits only.',
            'phone.unique'            => 'Phone number has already been taken.',

            'email.required'          => 'Email or phone number is required.',
            'email.email'             => 'Email must be a valid email address.',
            'email.unique'            => 'Email has already been taken.',

            'password.required'       => 'Password is required.',
            'password.min'            => 'Password must be at least 8 characters.',
            'password.confirmed'      => 'Password confirmation does not match.',

            'date_of_birth.required'  => 'Date of birth is required.',
            'date_of_birth.date'      => 'Date of birth must be a valid date.',
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old to register.',

            'profile_image.required'  => 'Profile image is required.',
            'profile_image.image'     => 'Profile image must be an image file.',
            'profile_image.mimes'     => 'Profile image must be a file of type: png, jpg, jpeg, gif.',  

            'national_id.required_without'       => 'National ID or International passport is required.',
            'national_id.image'       => 'National ID must be an image file.',
            'national_id.mimes'       => 'National ID must be a file of type: png, jpg, jpeg, gif.',
 
            'international_passport.required_without' => 'International passport or National ID is required.',
            'international_passport.image' => 'International passport must be an image file.',
            'international_passport.mimes' => 'International passport must be a file of type: png, jpg, jpeg, gif.',

           
            'account_balance.integer' => 'Account balance must be an integer.',
            'account_balance.min'     => 'Account balance must be at least 0.',

            'address.string'         => 'Address must be a string.',
            'address.max'            => 'Address must not exceed 255 characters.',  

        ];
    }
}
