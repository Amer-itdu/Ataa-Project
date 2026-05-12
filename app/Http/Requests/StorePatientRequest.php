<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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

            // Patient
            'medical_condition'  => 'required|string',
            'required_amount'    => 'required|numeric|min:1',
            'medical_report'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'national_id_photo'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
