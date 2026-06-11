<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerHour extends Model
{
    protected $fillable = [
        'volunteer_id',
        'date',
        'hours',
        'activity_description',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }
}
