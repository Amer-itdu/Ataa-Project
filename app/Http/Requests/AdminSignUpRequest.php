<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminSignUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
   public function authorize(): bool
{
    return true; // لاحقاً ممكن نربطها بصلاحيات الأدمن
}

public function rules(): array
{
    return [
        'first_name' => 'required|string|max:50',
        'last_name'  => 'required|string|max:50',

        'email' => 'nullable|required_without:phone|email|unique:users,email',
        'phone' => 'nullable|required_without:email|string|unique:users,phone',

        // لازم واحد منهم فقط
        'password' => 'required|string|min:6|confirmed',

        'role' => 'required|in:admin,sub_admin,field_worker',
        'status' => 'required|in:pending,approved,rejected',

        'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    ];
}

public function messages(): array
{
    return [
        'first_name.required' => 'First name is required.',
        'last_name.required'  => 'Last name is required.',
        'role.in' => 'Invalid role type.',
        'status.in' => 'Invalid status type.',
        'email.required_without' => 'Email or phone number is required.',
        'phone.required_without' => 'Phone number or email is required.',
        'email.email' => 'Email must be a valid email address.',
        'email.unique' => 'Email is already taken.',
        'phone.unique' => 'Phone number is already taken.',
        'password.required' => 'Password is required.',
        'password.min' => 'Password must be at least 6 characters.',
        'password.confirmed' => 'Password confirmation does not match.',
        'profile_image.required' => 'Profile image is required.',
    ];
}
}
