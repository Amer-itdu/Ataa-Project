<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'full_name',
        'governorate_id',
        'region_id',
        'national_id',
        'email',
        'phone',
    ];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function requests()
    {
        return $this->hasMany(RequestModel::class);
    }
}