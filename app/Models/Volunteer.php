<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    public const VALID_STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    protected $fillable = [
        'user_id',
        'phone',
        'gender',
        'occupation',
        'governorate_id',
        'skills',
        'availability',
        'description',
        'agreed_to_terms',
        'agreed_to_terms_at',
        'status',
        'general_application',
        'certificate_token',
        'certificate_issued_at',
    ];

    protected $casts = [
        'skills'              => 'array',
        'agreed_to_terms'     => 'boolean',
        'agreed_to_terms_at'  => 'datetime',
        'general_application' => 'boolean',
        'certificate_issued_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
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

    public function totalHours()
    {
        return $this->hours()->sum('hours');
    }

    public static function normalizeStatus(?string $status): string
    {
        if ($status === null) {
            return 'pending';
        }

        return match ($status) {
            'active', 'approved' => 'approved',
            'inactive', 'rejected' => 'rejected',
            'suspended' => 'suspended',
            default => 'pending',
        };
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = self::normalizeStatus($value);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    // ================================
    // 🔥 قائمة المهارات الثابتة — 20 مهارة (إنجليزي)
    // ================================
    public static function skillsList(): array
    {
        return [
            'design',
            'translation',
            'accounting',
            'hr',
            'photography',
            'video_editing',
            'counseling_mental_health',
            'child_psychosocial_support',
            'public_relations',
            'field_work',
            'first_aid',
            'medical_support',
            'teaching',
            'logistics',
            'event_management',
            'social_media',
            'fundraising',
            'legal_support',
            'it_support',
            'cooking_food_prep',
        ];
    }
}