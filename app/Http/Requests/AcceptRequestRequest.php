<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptRequestRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'required_amount'  => 'required|numeric|min:0',
            'personal_picture' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages()
    {
        return [
            'title.string'               => 'Title must be a string.',
            'title.max'                  => 'Title must not exceed 255 characters.',

            'description.string'         => 'Description must be a string.',
            'description.max'            => 'Description must not exceed 1000 characters.',

            'required_amount.numeric'    => 'Required amount must be a number.',
            'required_amount.min'        => 'Required amount must be 0 or greater.',

            'personal_picture.file'      => 'Personal picture must be a file.',
            'personal_picture.mimes'     => 'Personal picture must be jpg, jpeg, or png.',
            'personal_picture.max'       => 'Personal picture must not exceed 5MB.',
        ];
    }
}
