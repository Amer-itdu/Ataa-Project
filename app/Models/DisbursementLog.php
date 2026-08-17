<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisbursementLog extends Model
{
    protected $fillable = [
        'admin_id',
        'amount',
        'currency',
        'type',           // campaign, request
        'reference_id',   // campaign_id أو request_id
        'campaign_title',
        'request_title',
        'status',         // completed, failed
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}