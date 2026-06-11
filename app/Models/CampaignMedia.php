<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMedia extends Model
{
    protected $table = 'campaign_media';

    protected $fillable = [
        'campaign_id',
        'image',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
