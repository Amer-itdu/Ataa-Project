<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donor extends Model
{
    protected $fillable = ['user_id', 'anonymous'];

    protected $casts = [
        'anonymous' => 'boolean',
    ];

    // ============================
    // 🔥 العلاقات
    // ============================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    // ============================
    // 🔥 الإحصائيات
    // ============================

    /**
     * مجموع التبرعات بالدولار
     */
    public function totalDonatedAmount()
    {
        return $this->donations()->sum('amount');
    }

   /// عدد الحالات التي تم دعمها (distinct donationable_id + donationable_type)
     
    public function donatedCasesCount()
    {
        return $this->donations()
            ->select('donationable_id', 'donationable_type')
            ->distinct()
            ->count();
    }
    // عدد التبرعات (عدد السجلات في جدول donations)

    
    public function donationsCount()
    {
        return $this->donations()->count();
    }

    /**
     * إرجاع كل الإحصائيات في Array واحدة
     */
    public function stats()
    {
        return [
            'total_donated_usd' => $this->totalDonatedAmount(),
            'cases_supported'   => $this->donatedCasesCount(),
            'donations_count'   => $this->donationsCount(),
        ];
    }
}
