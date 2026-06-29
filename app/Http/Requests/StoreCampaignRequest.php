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
        $participationType = $this->input('participation_type');

        return [
            'title'       => 'required|string|max:255',
            'description' => 'required|string',

            'type' => 'required|in:educational,medical,humanitarian,environmental',

            'participation_type' => 'required|in:donation_only,volunteer_only,donation_and_volunteer',

            'amount_needed' => in_array($participationType, ['donation_only', 'donation_and_volunteer'])
                ? 'required|numeric|min:1'
                : 'prohibited',

            'volunteers_needed' => in_array($participationType, ['volunteer_only', 'donation_and_volunteer'])
                ? 'required|integer|min:1'
                : 'prohibited',

            'status' => 'required|in:open,closed,paused,cancelled',

            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after:start_date',

            'media' => 'required|array',
            'media.*' => 'required|image|mimes:jpg,jpeg,png,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Campaign title is required.',
            'description.required' => 'Campaign description is required.',

            'type.required' => 'Campaign type is required.',
            'type.in'        => 'Invalid campaign type must be one of: educational, medical, humanitarian, environmental.',

            'participation_type.required' => 'Participation type is required.',
          'participation_type.in'        => 'Invalid participation type must be one of: donation_only, volunteer_only, donation_and_volunteer.',
            'amount_needed.required'   => 'Required amount is required for this campaign type.',
            'amount_needed.numeric'    => 'Required amount must be a number.',
            'amount_needed.min'        => 'Required amount must be at least 1.',
            'amount_needed.prohibited' => 'Required amount is not allowed for volunteer-only campaigns.',

            'volunteers_needed.required'   => 'Number of volunteers needed is required for this campaign type.',
            'volunteers_needed.integer'    => 'Volunteers needed must be a number.',
            'volunteers_needed.min'        => 'Volunteers needed must be at least 1.',
            'volunteers_needed.prohibited' => 'Volunteers needed is not allowed for donation-only campaigns.',

            'status.required' => 'Campaign status is required.',
            'status.in'        => 'Invalid campaign status.',

            'start_date.required'       => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',

            'end_date.required' => 'End date is required.',
            'end_date.after'    => 'End date must be after the start date.',

            'media.*.image' => 'Each media file must be an image.',
        ];
    }
}