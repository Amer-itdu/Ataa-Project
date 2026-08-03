<?php

namespace App\Http\Requests;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrphanRequest extends FormRequest
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

            'full_name'      => 'required|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id'      => 'required|exists:regions,id',

            // ✔ national_id يتحقق من beneficiaries فقط
            'national_id'    => 'required|string|max:50|unique:beneficiaries,national_id',

            'title' => $isAdmin ? 'required|string|max:255' : 'prohibited',

            'phone' => 'required|regex:/^[0-9]+$/',
            'email' => 'prohibited',

            'description' => 'required|string',

            'family_booklet'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'father_death_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

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
            'email.prohibited' => 'The email field is prohibited.',
            'description.required' => 'The description is required.',
            'description.string' => 'The description must be a string.',
            'family_booklet.required' => 'The family booklet is required.',
            'family_booklet.file' => 'The family booklet must be a file.',
            'family_booklet.mimes' => 'The family booklet must be a file of type: jpg, jpeg, png, pdf.',
            'family_booklet.max' => 'The family booklet may not be greater than 5MB.',

            'father_death_certificate.required' => 'The father death certificate is required.',
            'father_death_certificate.file' => 'The father death certificate must be a file.',
            'father_death_certificate.mimes' => 'The father death certificate must be a file of type: jpg, jpeg, png, pdf.',
            'father_death_certificate.max' => 'The father death certificate may not be greater than 5MB.',

        ];
    }   
}
