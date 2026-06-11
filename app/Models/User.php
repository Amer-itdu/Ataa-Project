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

    private function normalizeBalances()
    {
        $balances = $this->balances;

        // NULL أو فارغ
        if (empty($balances)) {
            return [];
        }

        // إذا كانت JSON string
        if (is_string($balances)) {
            $decoded = json_decode($balances, true);
            return is_array($decoded) ? $decoded : [];
        }

        // إذا كانت Array
        return is_array($balances) ? $balances : [];
    }

    public function getBalance($currency)
    {
        $balances = $this->normalizeBalances();
        return $balances[$currency] ?? 0;
    }

    public function addBalance($currency, $amount)
    {
        $balances = $this->normalizeBalances();

        $balances[$currency] = ($balances[$currency] ?? 0) + $amount;

        $this->balances = $balances;
        $this->save();
    }

    public function subtractBalance($currency, $amount)
    {
        $balances = $this->normalizeBalances();

        if (($balances[$currency] ?? 0) < $amount) {
            return false;
        }

        $balances[$currency] -= $amount;

        $this->balances = $balances;
        $this->save();

        return true;
    }

    public function donor()
    {
        return $this->hasOne(Donor::class);
    }

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
}
