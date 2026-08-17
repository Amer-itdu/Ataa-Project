<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\DisbursementLog;

class Campaign extends Model
{
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'participation_type',
        'amount_needed',
        'amount_collected',
        'volunteers_needed',
        'volunteers_joined',
        'status',
        'start_date',
        'end_date',
        'is_disbursed', // 🔥 إضافة حقل لتتبع الصرف
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function donations()
    {
        return $this->morphMany(Donation::class, 'donationable');
    }

    public function volunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'volunteer_campaign', 'campaign_id', 'volunteer_id')
            ->using(VolunteerCampaign::class)
            ->withPivot(['assigned_date', 'status', 'available_time', 'notes'])
            ->withTimestamps();
    }

    public function media()
    {
        return $this->hasMany(CampaignMedia::class);
    }

    // ================================
    // 🔥 الصرف من الحملة
    // ================================

    /**
     * التحقق من أن الحملة مغلقة/منتهية
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'closed', 'ended', 'cancelled']);
    }

    /**
     * التحقق من أن المبلغ لم يتم صرفه من قبل
     */
    public function isPending(): bool
    {
        return !$this->is_disbursed;
    }

    /**
     * صرف المبلغ المجمع للـ Admin
     * 
     * @return bool
     */
    public function disburseToAdmin(): bool
    {
        // ✅ التحقق من أن الحملة مغلقة
        if (!$this->isClosed()) {
            return false;
        }

        // ✅ التحقق من عدم الصرف مسبقاً
        if (!$this->isPending()) {
            return false;
        }

        // ✅ التحقق من وجود مبلغ مجمع
        if ($this->amount_collected <= 0) {
            return false;
        }

        // ✅ الصرف من رصيد الـ Admin
        $admin = $this->admin;
        if (!$admin) {
            return false;
        }

        DB::beginTransaction();

        try {
            // خصم المبلغ من رصيد الـ Admin (USD فقط)
            $admin->subtractBalance('USD', $this->amount_collected);

            // تحديث حالة الصرف
            $this->update(['is_disbursed' => true]);

            // تسجيل العملية
            DisbursementLog::create([
                'admin_id' => $admin->id,
                'amount' => $this->amount_collected,
                'currency' => 'USD',
                'type' => 'campaign',
                'reference_id' => $this->id,
                'campaign_title' => $this->title,
                'status' => 'completed',
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getProgressAttribute()
    {
        if ($this->amount_needed > 0) {
            return round(($this->amount_collected / $this->amount_needed) * 100, 2);
        }
        return 0;
    }

    public function getTimeRemainingAttribute()
    {
        if (!$this->end_date) {
            return null;
        }

        $now = Carbon::now();
        $end = Carbon::parse($this->end_date)->endOfDay();
        $seconds = $now->diffInSeconds($end, false);

        if ($seconds <= 0) {
            return [
                'expired' => true,
                'text' => 'Campaign ended',
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
            ];
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return [
            'expired' => false,
            'text' => sprintf('%sd %sh %sm %ss', $days, $hours, $minutes, $secs),
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $secs,
        ];
    }

    public function acceptsDonations(): bool
    {
        return in_array($this->participation_type, ['donation_only', 'donation_and_volunteer']);
    }

    public function acceptsVolunteers(): bool
    {
        return in_array($this->participation_type, ['volunteer_only', 'donation_and_volunteer']);
    }
}