<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\DisbursementLog;
use Illuminate\Support\Facades\Log;  // 🔥 أضيف هون


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
    // الحملة تعتبر مغلقة إذا:
    // 1. الـ status من الحالات المغلقة
    // 2. أو اكتمل المبلغ المطلوب
    
    $closedStatuses = [
        'completed', 
        'closed', 
        'ended', 
        'cancelled',
        'completed_donations',      // 🔥 أضيف
        'completed_all',
        'expired'            // 🔥 أضيف
    ];
    
    $isClosed = in_array($this->status, $closedStatuses);
    $amountCompleted = $this->amount_needed && $this->amount_collected >= $this->amount_needed;
    
    return $isClosed || $amountCompleted;
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
    public function disburseToAdmin(): array
    {
        // ✅ التحقق من أن الحملة مغلقة
        if (!$this->isClosed()) {
            return [
                'success' => false,
                'message' => 'Campaign is not closed or completed.'
            ];
        }

        // ✅ التحقق من عدم الصرف مسبقاً
        if (!$this->isPending()) {
            return [
                'success' => false,
                'message' => 'Campaign amount has already been disbursed.'
            ];
        }

        // ✅ التحقق من وجود مبلغ مجمع
        if ($this->amount_collected <= 0) {
            return [
                'success' => false,
                'message' => 'No amount collected in this campaign.'
            ];
        }

        // ✅ الصرف من رصيد الـ Admin
        $admin = $this->admin;
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Admin user not found.'
            ];
        }

        // تحقق من رصيد الأدمن
        $adminBalance = $admin->getBalance('USD');
        if ($adminBalance < $this->amount_collected) {
            return [
                'success' => false,
                'message' => "Insufficient admin balance. Available: {$adminBalance} USD, Needed: {$this->amount_collected} USD"
            ];
        }

        DB::beginTransaction();

        try {
            // خصم المبلغ من رصيد الـ Admin (USD فقط)
            $admin->subtractBalance('USD', $this->amount_collected);

            // تحديث حالة الصرف
            $this->update(['is_disbursed' => true]);

            try {
                \App\Models\DisbursementLog::create([
                    'admin_id' => $admin->id,
                    'amount' => $this->amount_collected,
                    'currency' => 'USD',
                    'type' => 'campaign',
                    'reference_id' => $this->id,
                    'campaign_title' => $this->title,
                    'status' => 'completed',
                ]);
            } catch (\Exception $logError) {
                Log::warning('Failed to create DisbursementLog: ' . $logError->getMessage());  // 🔥 بدل \Log
            }

            DB::commit();
            return [
                'success' => true,
                'message' => 'Campaign amount disbursed successfully.',
                'amount' => $this->amount_collected,
                'admin_balance_remaining' => $admin->getBalance('USD')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error during disbursement: ' . $e->getMessage()
            ];
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
