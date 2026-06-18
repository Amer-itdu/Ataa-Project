<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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

            // is_self = string: "true" أو "false"
            'is_self'   => $isAdmin
                ? 'nullable|string|in:true,false'
                : 'required|string|in:true,false',

            'full_name' => 'required|string|max:255',
            'address'   => 'required|string|max:255',
            
            'title' => $isAdmin
                ? 'required|string|max:255'
                : 'prohibited',


            // الإدمن يدخل رقم أو إيميل واحد على الأقل
            // المستخدم العادي يقدم لشخص آخر → نفس الشي
            'email' => $isAdmin
                ? 'nullable|required_without:phone|email'
                : 'nullable|email|required_without:phone|prohibited_if:is_self,true',

            'phone' => $isAdmin
                ? 'nullable|required_without:email|regex:/^[0-9]+$/'
                : 'nullable|regex:/^[0-9]+$/|required_without:email|prohibited_if:is_self,true',


            'description' => 'required|string',

            // required_amount للجميع
            // الأدمن → required
            // اليوزر → optional
            'required_amount' => $isAdmin
                ? 'required|numeric|min:1'
                : 'nullable|numeric|min:1',

            // personal_picture فقط للأدمن
            'personal_picture' => $isAdmin
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'prohibited',

            'medical_report' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'national_id'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function messages()
    {
        return [
            'is_self.required' => 'this field is required.',
            'is_self.in' => 'this field must be true or false.',

            'full_name.required' => 'this field is required.',
            'address.required' => 'this field is required.',

            'email.required_without' => 'either email or phone is required.',
            'email.email' => 'this field must be a valid email address.',

            'phone.required_without' => 'either phone or email is required.',
            'phone.regex' => 'this field must contain only numbers.',

            'description.required' => 'this field is required.',

            'required_amount.required' => 'this field is required for admins.',
            'required_amount.numeric' => 'this field must be a number.',
            'required_amount.min' => 'this field must be a positive number.',

            'personal_picture.file' => 'this field must be a file.',
            'personal_picture.mimes' => 'this field must be an image (jpg, jpeg, png).',
            'personal_picture.max' => 'this field must not exceed 5MB.',

            'medical_report.required' => 'this field is required.',
            'medical_report.file' => 'this field must be a file.',
            'medical_report.mimes' => 'this field must be of type jpg, jpeg, png, pdf.',
            'medical_report.max' => 'this field must not exceed 5MB.',

            'national_id.required' => 'this field is required.',
            'national_id.file' => 'this field must be a file.',
            'national_id.mimes' => 'this field must be of type jpg, jpeg, png, pdf.',
            'national_id.max' => 'this field must not exceed 5MB.',
        ];
    }
}
