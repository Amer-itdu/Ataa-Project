<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $participationType = $this->input('participation_type');

        return [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',

            'type' => 'sometimes|required|in:educational,medical,humanitarian,environmental',

            'participation_type' => 'sometimes|required|in:donation_only,volunteer_only,donation_and_volunteer',

            // 🔥 شرطية فقط لو participation_type انبعت بهاد الطلب
            'amount_needed' => $participationType
                ? (in_array($participationType, ['donation_only', 'donation_and_volunteer'])
                    ? 'required|numeric|min:1'
                    : 'prohibited')
                : 'sometimes|numeric|min:1',

            'volunteers_needed' => $participationType
                ? (in_array($participationType, ['volunteer_only', 'donation_and_volunteer'])
                    ? 'required|integer|min:1'
                    : 'prohibited')
                : 'sometimes|integer|min:1',

            'status' => 'sometimes|required|in:open,closed,paused,cancelled',

            // 🔥 ممنوع تعديل start_date بعد الإنشاء (بيتشال من الكونترولر بعدين كحماية إضافية)
            'start_date' => 'prohibited',

            // 🔥 end_date لازم تكون بالمستقبل، بدون شرط after:start_date لأن start_date مش موجودة بالطلب
            'end_date' => 'sometimes|required|date|after:today',

            'media' => 'nullable|array',
            'media.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Campaign title is required.',
            'description.required' => 'Campaign description is required.',

            'type.required' => 'Campaign type is required.',
            'type.in'        => 'Invalid campaign type.',

            'participation_type.required' => 'Participation type is required.',
            'participation_type.in'        => 'Invalid participation type.',

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

            'start_date.prohibited' => 'Start date cannot be modified after creation.',

            'end_date.required' => 'End date is required.',
            'end_date.after'    => 'End date must be a future date.',

            'media.*.image' => 'Each media file must be an image.',
        ];
    }
}