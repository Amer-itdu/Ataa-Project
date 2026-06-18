<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'donor_id',
        'donationable_id',
        'donationable_type',
        'amount',               // المبلغ بالدولار بعد التحويل
        'currency',             // USD دائمًا
        'original_amount',      // المبلغ الأصلي
        'original_currency',    // SAR, AED, SYP, EGP, EUR, USD
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
    ];

    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }

    public function donationable()
    {
        return $this->morphTo();
    }
}
