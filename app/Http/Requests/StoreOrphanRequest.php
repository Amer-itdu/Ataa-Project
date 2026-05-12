<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrphanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Beneficiary
            'full_name'   => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
            'address'     => 'nullable|string|max:255',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string|max:20',

            // Request
            'description' => 'required|string',

            // Orphan
            'family_booklet'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'father_death_certificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
