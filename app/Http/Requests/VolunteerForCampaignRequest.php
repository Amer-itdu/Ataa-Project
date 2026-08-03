<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VolunteerForCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // السماح بالتحقق
    }

    public function rules(): array
    {
        return [
            'skills'         => 'nullable|string|max:500',
            'agreed_to_terms' => 'required|accepted',
            'notes'          => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'agreed_to_terms.required' => 'You must agree to the policy and terms.',
            'agreed_to_terms.accepted' => 'You must agree to the policy and terms to submit your application.',
        ];
    }
}
