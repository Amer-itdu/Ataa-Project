<?php

namespace App\Models;

use App\Models\Donor;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'date_of_birth',
        'profile_image',
        'national_id',
        'international_passport',
        'balances',
        'address',
        'role',
        'user_category',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balances' => 'array',
    ];

    // ================================
    // 🔥 نظام الرصيد متعدد العملات
    // ================================

    private function normalizeBalances(): array
    {
        $raw = $this->getRawOriginal('balances') ?? $this->attributes['balances'] ?? null;

        if (empty($raw)) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        return [];
    }

    public function getBalance($currency)
    {
        $balances = $this->normalizeBalances();
        return $balances[$currency] ?? 0;
    }

    public function addBalance($currency, $amount)
    {
        if ($amount <= 0) return false;

        // 🔥 refresh من DB لتجنب stale data
        $this->refresh();

        $balances = $this->normalizeBalances();
        $balances[$currency] = ($balances[$currency] ?? 0) + $amount;

        $this->balances = $balances;
        $this->save();

        return true;
    }

    public function subtractBalance($currency, $amount)
    {
        if ($amount <= 0) return false;

        // 🔥 refresh من DB لتجنب stale data
        $this->refresh();

        $balances = $this->normalizeBalances();
        $current = $balances[$currency] ?? 0;

        if ($current < $amount) return false;

        $balances[$currency] = $current - $amount;

        $this->balances = $balances;
        $this->save();

        return true;
    }
    // ================================
    // 🔥 علاقة المتبرع
    // ================================

    public function donor()
    {
        return $this->hasOne(Donor::class);
    }

    // ================================
    // 🔥 أسعار العملات
    // ================================

    public static function currencyRates()
    {
        return [
            'USD' => 1,
            'EUR' => 1.07,
            'SAR' => 0.27,
            'AED' => 0.27,
            'EGP' => 0.020,
            'SYP' => 0.00040,
        ];
    }

    public static function convertToUSD($amount, $currency)
    {
        $rates = self::currencyRates();
        return $amount * ($rates[$currency] ?? 1);
    }
    public function getOrCreateDonor()
    {
        if ($this->donor) {
            return $this->donor;
        }

        return Donor::create([
            'user_id'   => $this->id,
            'anonymous' => false,
        ]);
    }
    // في User.php
    public function volunteer()
    {
        return $this->hasOne(Volunteer::class);
    }
}
