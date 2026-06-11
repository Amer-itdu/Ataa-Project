<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
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
        return $this->belongsTo(RequestModel::class, 'request_id');
    }

    public function donations()
    {
        return $this->morphMany(Donation::class, 'donationable');
    }
}