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
            'skills' => 'required|string|max:500',
            'available_time' => 'required|string|max:255',
            'notes' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'skills.required' => 'Please describe your relevant skills.',
            'skills.string'   => 'Skills must be a text description.',
            'skills.max'      => 'Skills description cannot exceed 500 characters.',

            'available_time.required' => 'Please specify your available time for volunteering.',
            'available_time.string'   => 'Available time must be a text description.',
            'available_time.max'      => 'Available time description cannot exceed 255 characters.',

            'notes.required' => 'Please provide any additional notes or information.',
            'notes.string'   => 'Notes must be a text description.',
            'notes.max'      => 'Notes cannot exceed 500 characters.',
        ];
    }
}
