<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUniversityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = $this->user();
        $isAdmin = $user && $user->role === 'admin';

        return [

            'full_name' => 'required|string|max:255',
            'address'   => 'required|string|max:255',

            // title فقط للأدمن
            'title' => $isAdmin
                ? 'required|string|max:255'
                : 'prohibited',

            // الإدمن يدخل رقم أو إيميل واحد على الأقل
            // اليوزر ممنوع يدخلهم
            'email' => $isAdmin
                ? 'nullable|required_without:phone|email'
                : 'prohibited',

            'phone' => $isAdmin
                ? 'nullable|required_without:email|regex:/^[0-9]+$/'
                : 'prohibited',

            'description' => 'required|string',

            'academic_year'       => 'required|string|max:255',
            'support_type'        => 'required|string|in:laptopsupport,tuitionassistance',
            'university_id_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // required_amount فقط للأدمن
            'required_amount' => $isAdmin
                ? 'required|numeric|min:1'
                : 'prohibited',

            // personal_picture فقط للأدمن
            'personal_picture' => $isAdmin
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'prohibited',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'The full name is required.',
            'address.required' => 'The address is required.',

            'title.required' => 'Title is required for admins.',
            'title.string' => 'Title must be a string.',
            'title.max' => 'Title must not exceed 255 characters.',
            'title.prohibited' => 'Regular users cannot set a title.',

            'email.required_without' => 'Either email or phone is required.',
            'phone.required_without' => 'Either phone or email is required.',
            'phone.regex' => 'The phone must contain only numbers.',

            'description.required' => 'The description is required.',
            'academic_year.required' => 'The academic year is required.',
            'support_type.required' => 'The support type is required.',
            'university_id_photo.required' => 'The university ID photo is required.',

            'required_amount.required' => 'The required amount is required for admins.',
            'personal_picture.required' => 'The personal picture is required for admins.',
        ];
    }
}
