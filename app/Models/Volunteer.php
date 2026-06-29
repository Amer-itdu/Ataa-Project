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

    public function volunteerCampaigns()
    {
        return $this->hasMany(VolunteerCampaign::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'volunteer_campaign', 'volunteer_id', 'campaign_id')
                    ->using(VolunteerCampaign::class)
                    ->withPivot(['assigned_date', 'status', 'available_time', 'notes'])
                    ->withTimestamps();
    }

    public function hours()
    {
        return $this->hasMany(VolunteerHour::class);
    }

    // ================================
    // 🔥 helper: مجموع ساعات التطوع
    // ================================
    public function totalHours()
    {
        return $this->hours()->sum('hours');
    }
}