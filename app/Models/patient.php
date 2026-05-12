<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class patient extends Model
{
    protected $fillable = [
        'request_id',
        'medical_condition',
        'required_amount',
        'medical_report',
        'national_id_photo',
    ];

    public function request()
    {
        return $this->belongsTo(RequestModel::class);
    }
}


