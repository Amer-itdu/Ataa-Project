<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\DisbursementLog;

class RequestModel extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'beneficiary_id',
        'request_type',
        'status',
        'description',
        'personal_picture',
        'required_amount',
        'status_request',
        'title',
        'amount_collected',  // 🔥 إضافة المبلغ المجمع
        'is_disbursed',      // 🔥 تتبع الصرف
    ];

    protected $casts = [
        'amount_collected' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class, 'request_id');
    }

    public function orphan()
    {
        return $this->hasOne(Orphan::class, 'request_id');
    }

    public function schoolStudent()
    {
        return $this->hasOne(SchoolStudent::class, 'request_id');
    }

    public function universityStudent()
    {
        return $this->hasOne(UniversityStudent::class, 'request_id');
    }

    public function donations()
    {
        return $this->morphMany(Donation::class, 'donationable');
    }

    // ================================
    // 🔥 الصرف من الحالة/الطلب
    // ================================

    /**
     * التحقق من أن الحالة مغلقة/منتهية
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'closed', 'ended', 'approved', 'finished']);
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
     */
    public function disburseToAdmin(): bool
    {
        // ✅ التحقق من أن الحالة مغلقة
        if (!$this->isClosed()) {
            return false;
        }

        // ✅ التحقق من عدم الصرف مسبقاً
        if (!$this->isPending()) {
            return false;
        }

        // ✅ التحقق من وجود مبلغ مجمع
        if (!$this->amount_collected || $this->amount_collected <= 0) {
            return false;
        }

        // ✅ الصرف من رصيد الـ Admin
        $admin = $this->user;
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
                'type' => 'request',
                'reference_id' => $this->id,
                'request_title' => $this->title,
                'status' => 'completed',
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}