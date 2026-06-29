<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddVolunteerHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية (field_worker فقط) متحققة بالكونترولر
    }

    public function rules(): array
    {
        return [
            'date'                  => 'required|date|before_or_equal:today',
            'hours'                 => 'required|numeric|min:0.5|max:24',
            'activity_description'  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'         => 'The date is required.',
            'date.before_or_equal'  => 'The date cannot be in the future.',

            'hours.required' => 'The number of hours is required.',
            'hours.numeric'  => 'Hours must be a number.',
            'hours.min'      => 'Hours must be at least 0.5.',
            'hours.max'      => 'Hours cannot exceed 24 per entry.',

            'activity_description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}