<?php

namespace App\Http\Requests;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

            'full_name'      => 'required|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id'      => 'required|exists:regions,id',

            // ✔ national_id يتحقق من beneficiaries فقط
            'national_id'    => 'required|string|max:50|unique:beneficiaries,national_id',

            'title' => $isAdmin ? 'required|string|max:255' : 'prohibited',

            'email' => $isAdmin ? 'nullable|required_without:phone|email' : 'prohibited',

            'phone' => $isAdmin
                ? 'nullable|required_without:email|regex:/^[0-9]+$/'
                : 'prohibited',

            'description' => 'required|string',

            'academic_year'       => 'required|string|max:255',
            'support_type'        => 'required|string|in:laptopsupport,tuitionassistance',
            'university_id_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

            'required_amount' => $isAdmin ? 'required|numeric|min:1' : 'prohibited',

            'personal_picture' => $isAdmin
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'prohibited',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $region = Region::find($this->region_id);

            if ($region && $region->governorate_id != $this->governorate_id) {
                $validator->errors()->add(
                    'region_id',
                    'The selected region does not belong to the selected governorate.'
                );
            }
        });
    }
    public function messages()
    {
        return [
            'full_name.required' => 'The full name is required.',
            'full_name.string' => 'The full name must be a string.',
            'full_name.max' => 'The full name may not be greater than 255 characters.',

            'governorate_id.required' => 'The governorate field is required.',
            'governorate_id.exists' => 'The selected governorate is invalid.',

            'region_id.required' => 'The region field is required.',
            'region_id.exists' => 'The selected region is invalid.',

            'national_id.required' => 'The national ID is required.',
            'national_id.string' => 'The national ID must be a string.',
            'national_id.max' => 'The national ID may not be greater than 50 characters.',
            'national_id.unique' => 'The national ID has already been taken.',
            'title.required' => 'The title is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'email.required_without' => 'The email field is required when phone is not present.',
            'email.email' => 'The email must be a valid email address.',
            'phone.required_without' => 'The phone field is required when email is not present.',
            'phone.regex' => 'The phone must be a valid number.',
            'description.required' => 'The description field is required.',
            'description.string' => 'The description must be a string.',
            'academic_year.required' => 'The academic year field is required.',
            'academic_year.string' => 'The academic year must be a string.',
            'academic_year.max' => 'The academic year may not be greater than 255 characters.',
            'support_type.required' => 'The support type field is required.',
            'support_type.string' => 'The support type must be a string.',
            'support_type.in' => 'The selected support type is invalid.',
            'university_id_photo.required' => 'The university ID photo field is required.',
            'university_id_photo.file' => 'The university ID photo must be a file.',
            'university_id_photo.mimes' => 'The university ID photo must be a file of type: jpg, jpeg, png, pdf.',
            'university_id_photo.max' => 'The university ID photo may not be greater than 5120 kilobytes.',
            'required_amount.required' => 'The required amount is required.',
            'required_amount.numeric' => 'The required amount must be a number.',
            'required_amount.min' => 'The required amount must be at least 1.',
            'personal_picture.required' => 'The personal picture is required.',
            'personal_picture.file' => 'The personal picture must be a file.',
            'personal_picture.mimes' => 'The personal picture must be a file of type: jpg, jpeg, png.',
            'personal_picture.max' => 'The personal picture may not be greater than 5120 kilobytes.',

            // Add more custom messages for other fields as needed
        ];
    }
}
