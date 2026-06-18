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

            'academic_grade'    => 'required|string|max:255',
            'school_name'       => 'required|string|max:255',
            'family_book_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

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
            'email.email' => 'The email must be a valid email address.',

            'phone.required_without' => 'Either phone or email is required.',
            'phone.regex' => 'The phone must contain only numbers.',

            'description.required' => 'The description is required.',

            'academic_grade.required' => 'The academic grade is required.',
            'school_name.required' => 'The school name is required.',
            'family_book_photo.required' => 'The family book photo is required.',

            'required_amount.required' => 'The required amount is required for admins.',
            'required_amount.numeric' => 'The required amount must be a number.',
            'required_amount.min' => 'The required amount must be at least 1.',

            'personal_picture.required' => 'The personal picture is required for admins.',
            'personal_picture.mimes' => 'The personal picture must be jpg, jpeg, or png.',
            'personal_picture.max' => 'The personal picture may not exceed 5MB.',
        ];
    }
}
