<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrphanRequest extends FormRequest
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

            // phone إجباري للجميع
            'phone' => 'required|regex:/^[0-9]+$/',

            // email ممنوع للجميع
            'email' => 'prohibited',

            'description' => 'required|string',

            // required_amount فقط للأدمن
            'required_amount' => $isAdmin
                ? 'required|numeric|min:1'
                : 'prohibited',

            // personal_picture فقط للأدمن
            'personal_picture' => $isAdmin
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'prohibited',

            'family_booklet' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'father_death_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
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

            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone format is invalid.',

            'description.required' => 'The description is required.',

            'required_amount.required' => 'The required amount is required for admins.',

            'personal_picture.required' => 'The personal picture is required for admins.',

            'family_booklet.required' => 'The family booklet is required.',
            'father_death_certificate.required' => 'The father death certificate is required.',
        ];
    }
}
