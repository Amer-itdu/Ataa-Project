<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'type' => 'required|in:educational,medical,humanitarian,environmental',

            'amount_needed' => 'required|numeric|min:0',

            'volunteers_needed' => 'nullable|integer|min:0',

            'status' => 'nullable|in:open,closed,paused,cancelled',

            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            'media' => 'nullable|array',
            'media.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Campaign title is required.',
            'type.required' => 'Campaign type is required.',
            'amount_needed.required' => 'Amount needed is required.',
            'media.*.image' => 'Each media file must be an image.',
        ];
    }
}
