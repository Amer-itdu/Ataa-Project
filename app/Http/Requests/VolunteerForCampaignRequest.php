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
            'skills' => 'nullable|string|max:500',
            'available_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'skills.string' => 'Skills must be a valid text.',
            'skills.max' => 'Skills cannot exceed 500 characters.',

            'available_time.string' => 'Available time must be a valid text.',
            'available_time.max' => 'Available time cannot exceed 255 characters.',

            'notes.string' => 'Notes must be a valid text.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
