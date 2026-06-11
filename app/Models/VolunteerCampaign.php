<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VolunteerCampaign extends Pivot
{
    protected $table = 'volunteer_campaign';

    public $incrementing = true;

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'volunteer_id',
        'campaign_id',
        'assigned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
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
