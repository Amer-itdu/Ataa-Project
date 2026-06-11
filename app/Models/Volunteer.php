<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    protected $fillable = [
        'user_id',
        'skills',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة مباشرة مع جدول pivot
    public function volunteerCampaigns()
    {
        return $this->hasMany(VolunteerCampaign::class);
    }

    // Many To Many مع الحملات باستخدام Pivot Model
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'volunteer_campaign', 'volunteer_id', 'campaign_id')
                    ->using(VolunteerCampaign::class)
                    ->withPivot(['assigned_date', 'status'])
                    ->withTimestamps();
    }

    public function hours()
    {
        return $this->hasMany(VolunteerHour::class);
    }
}
