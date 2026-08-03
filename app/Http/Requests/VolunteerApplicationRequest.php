<?php

namespace App\Http\Requests;

use App\Models\Volunteer;
use Illuminate\Foundation\Http\FormRequest;

class VolunteerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'          => 'required|regex:/^[0-9]+$/|max:20',
            'gender'         => 'required|in:male,female',
            'occupation'     => 'nullable|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',

            'skills'   => 'required|array|min:1|max:2',
            'skills.*' => 'in:' . implode(',', Volunteer::skillsList()),

            'availability' => 'nullable|string|max:255',
            'description'  => 'required|string|max:1000',

            'agreed_to_terms' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.regex'    => 'Phone number must contain only numbers.',

            'gender.required' => 'Gender is required.',
            'gender.in'        => 'Gender must be either male or female.',

            'governorate_id.required' => 'Governorate is required.',
            'governorate_id.exists'   => 'The selected governorate is invalid.',

            'skills.required' => 'Please select at least one skill.',
            'skills.min'       => 'Please select at least one skill.',
            'skills.max'       => 'You can select a maximum of 2 skills.',
            'skills.*.in'      => 'One or more selected skills are invalid.',

            'description.required' => 'Please tell us why you want to volunteer.',
            'description.max'      => 'This field cannot exceed 1000 characters.',

            'agreed_to_terms.required' => 'You must agree to the policy and terms.',
            'agreed_to_terms.accepted' => 'You must agree to the policy and terms to submit your application.',
        ];
    }
}
