<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignDisbursal extends Model
{
    protected $fillable = [
        'campaign_id',
        'admin_id',
        'amount',
        'currency',
        'original_amount',
        'original_currency',
        'notes',
        'disbursed_by',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}