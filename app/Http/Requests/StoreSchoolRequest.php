<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Beneficiary
            'full_name'   => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
            'address'     => 'nullable|string|max:255',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',

            // Request
            'description' => 'required|string',

            // School Student
            'academic_grade'    => 'required|string|max:255',
            'school_name'       => 'required|string|max:255',
            'family_book_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
