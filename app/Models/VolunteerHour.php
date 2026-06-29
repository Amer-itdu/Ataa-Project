<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerHour extends Model
{
    protected $fillable = [
        'volunteer_id',
        'campaign_id',
        'date',
        'hours',
        'activity_description',
    ];

    protected $casts = [
        'date'  => 'date',
        'hours' => 'decimal:2',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}