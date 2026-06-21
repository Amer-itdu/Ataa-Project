<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role' => 'required|string|in:sub_admin,field_worker',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'The new role is required.',
            'role.in'        => 'The role must be either sub_admin or field_worker.',
        ];
    }
}