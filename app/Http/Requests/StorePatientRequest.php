<?php

namespace App\Http\Requests;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePatientRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        $user = $this->user();
        $isAdmin = $user && $user->role === 'admin';

        return [

            'is_self'   => $isAdmin
                ? 'nullable|string|in:true,false'
                : 'required|string|in:true,false',

            'full_name'      => 'required|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id'      => 'required|exists:regions,id',

            // ✔ national_id يتحقق من beneficiaries فقط
            'national_id'    => 'required|string|max:50|unique:beneficiaries,national_id',

            'title' => $isAdmin ? 'required|string|max:255' : 'prohibited',

            'email' => $isAdmin
                ? 'nullable|required_without:phone|email'
                : 'nullable|email|required_without:phone|prohibited_if:is_self,true',

            'phone' => $isAdmin
                ? 'nullable|required_without:email|regex:/^[0-9]+$/'
                : 'nullable|regex:/^[0-9]+$/|required_without:email|prohibited_if:is_self,true',

            'description' => 'required|string',

            'required_amount' => $isAdmin ? 'required|numeric|min:1' : 'nullable|numeric|min:1',

            'personal_picture' => $isAdmin
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'prohibited',

            'medical_report' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'national_id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
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
            'governorate_id.required' => 'The governorate is required.',
            'governorate_id.exists' => 'The selected governorate is invalid.',
            'region_id.required' => 'The region is required.',
            'region_id.exists' => 'The selected region is invalid.',
            'national_id.required' => 'The national ID is required.',
            'national_id.string' => 'The national ID must be a string.',
            'national_id.max' => 'The national ID may not be greater than 50 characters.',
            'national_id.unique' => 'The national ID has already been taken.',
            'title.required' => 'The title is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'phone.required' => 'The phone number is required.',
            'phone.regex' => 'The phone number must be a valid number.',
            'email.required' => 'The email is required.',
            'email.email' => 'The email must be a valid email address.',
            'description.required' => 'The description is required.',
            'description.string' => 'The description must be a string.',
            'required_amount.required' => 'The required amount is required.',
            'required_amount.numeric' => 'The required amount must be a number.',
            'required_amount.min' => 'The required amount must be at least 1.',
            'personal_picture.required' => 'The personal picture is required.',
            'personal_picture.file' => 'The personal picture must be a file.',
            'personal_picture.mimes' => 'The personal picture must be a file of type: jpg, jpeg, png.',
            'personal_picture.max' => 'The personal picture may not be greater than 5120 kilobytes.',
            'medical_report.required' => 'The medical report is required.',
            'medical_report.file' => 'The medical report must be a file.',
            'medical_report.mimes' => 'The medical report must be a file of type: jpg, jpeg, png, pdf.',
            'medical_report.max' => 'The medical report may not be greater than 5120 kilobytes.',
            'national_id_document.required' => 'The national ID document is required.',
            'national_id_document.file' => 'The national ID document must be a file.',
            'national_id_document.mimes' => 'The national ID document must be a file of type: jpg, jpeg, png, pdf.',
            'national_id_document.max' => 'The national ID document may not be greater than 5120 kilobytes.',
        ];
    }
}
