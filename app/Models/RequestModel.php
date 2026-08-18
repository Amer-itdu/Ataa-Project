<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestModel extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'beneficiary_id',
        'request_type',
        'status',
        'status_request',
        'title',
        'description',
        'personal_picture',
        'required_amount',
        'amount_collected',
        'is_disbursed',
        'rejection_reason',
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

    // 🔥 الطلب مغلق أو منتهي أو معتمد
    public function isClosed(): bool
    {
        return in_array($this->status, ['accepted', 'rejected'])
            || $this->status_request === 'closed';
    }
    // 🔥 ما تم الصرف بعد
    public function isPending(): bool
    {
        return !$this->is_disbursed;
    }

    // 🔥 صرف المبلغ المجمع للـ Admin
    public function disburseToAdmin(): array
    {
        // 🔥 التحقق: الطلب من إنشاء الأدمن (user_id = 1)
        if ($this->user_id != 1) {
            return [
                'success' => false,
                'message' => 'Only requests created by admin can be disbursed.'
            ];
        }

        // التحقق من أن الطلب مغلق
        if (!$this->isClosed()) {
            return [
                'success' => false,
                'message' => 'Request is not closed or approved.'
            ];
        }

        // التحقق من عدم الصرف مسبقاً
        if (!$this->isPending()) {
            return [
                'success' => false,
                'message' => 'Request amount has already been disbursed.'
            ];
        }

        // التحقق من وجود مبلغ مجمع
        if ($this->amount_collected <= 0) {
            return [
                'success' => false,
                'message' => 'No amount collected in this request.'
            ];
        }

        // الصرف من رصيد الـ Admin
        $admin = User::find(1);
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

            // تسجيل العملية
            try {
                \App\Models\DisbursementLog::create([
                    'admin_id' => $admin->id,
                    'amount' => $this->amount_collected,
                    'currency' => 'USD',
                    'type' => 'request',
                    'reference_id' => $this->id,
                    'campaign_title' => $this->title,
                    'status' => 'completed',
                ]);
            } catch (\Exception $logError) {
                Log::warning('Failed to create DisbursementLog: ' . $logError->getMessage());
            }

            DB::commit();
            return [
                'success' => true,
                'message' => 'Request amount disbursed successfully.',
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
}
