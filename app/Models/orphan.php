<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Donor;

class Orphan extends Model
{
    protected $fillable = [
        'request_id', 'family_booklet', 'father_death_certificate',
        'is_sponsored', 'sponsor_id', 'sponsorship_amount', 
        'sponsored_at', 'next_monthly_deduction_at',
    ];

    protected $casts = [
        'is_sponsored' => 'boolean',
        'sponsorship_amount' => 'decimal:2',
        'sponsored_at' => 'datetime',
        'next_monthly_deduction_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(RequestModel::class, 'request_id');
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function donations()
    {
        return $this->morphMany(Donation::class, 'donationable');
    }

    public function sponsorOrphan(User $sponsor, string $currency = 'USD'): array
    {
        if ($this->is_sponsored) {
            return ['success' => false, 'message' => 'This orphan is already sponsored'];
        }

        $requestModel = $this->request;
        if (!$requestModel) {
            return ['success' => false, 'message' => 'Request not found'];
        }

        $sponsorshipAmount = (float) $requestModel->required_amount;
        if ($sponsorshipAmount <= 0) {
            $sponsorshipAmount = 1;
        }

        $sponsorBalance = $sponsor->getBalance($currency);
        if ($sponsorBalance < $sponsorshipAmount) {
            return ['success' => false, 'message' => "Insufficient balance. Your balance: {$sponsorBalance}, Required: {$sponsorshipAmount}"];
        }

        DB::beginTransaction();
        try {
            if (!$sponsor->subtractBalance($currency, $sponsorshipAmount)) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $donor = $sponsor->donor ?? Donor::create([
                'user_id' => $sponsor->id,
                'anonymous' => false,
            ]);

            $this->donations()->create([
                'donor_id' => $donor->id,
                'amount' => $sponsorshipAmount,
                'currency' => $currency,
                'original_amount' => $sponsorshipAmount,
                'original_currency' => $currency,
            ]);

            $this->update([
                'is_sponsored' => true,
                'sponsor_id' => $sponsor->id,
                'sponsorship_amount' => $sponsorshipAmount,
                'sponsored_at' => now(),
                'next_monthly_deduction_at' => now()->addMonth(),
            ]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Orphan sponsored successfully!',
                'sponsorship_amount' => $sponsorshipAmount,
                'next_monthly_deduction' => $this->next_monthly_deduction_at->format('Y-m-d'),
                'remaining_balance' => $sponsor->getBalance($currency),
                'orphan_id' => $this->id,
                'sponsor_id' => $sponsor->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sponsorship error: ' . $e->getMessage(), [
                'orphan_id' => $this->id,
                'sponsor_id' => $sponsor->id,
            ]);
            return ['success' => false, 'message' => 'Error during sponsorship: ' . $e->getMessage()];
        }
    }

    public function getRemainingAmount(): float
    {
        if (!$this->is_sponsored) return 0;
        $collected = $this->request->amount_collected ?? 0;
        $sponsorship = $this->sponsorship_amount ?? 0;
        return max(0, $collected - $sponsorship);
    }

    public static function processMonthlyDeductions(): int
    {
        $count = 0;
        $now = now();

        $activeOrphans = self::where('is_sponsored', true)
            ->whereNotNull('next_monthly_deduction_at')
            ->where('next_monthly_deduction_at', '<=', $now)
            ->get();

        foreach ($activeOrphans as $orphan) {
            try {
                $sponsor = $orphan->sponsor;
                if (!$sponsor) continue;

                $requestModel = $orphan->request;
                if (!$requestModel) continue;

                // The first payment covers only the current shortfall.
                // Each following monthly payment covers the full required amount.
                $amount = (float) $requestModel->required_amount;
                $currency = 'USD';

                $balance = $sponsor->getBalance($currency);
                if ($balance >= $amount) {
                    $sponsor->subtractBalance($currency, $amount);

                    $donor = $sponsor->donor ?? Donor::create([
                        'user_id' => $sponsor->id,
                        'anonymous' => false,
                    ]);

                    $orphan->donations()->create([
                        'donor_id' => $donor->id,
                        'amount' => $amount,
                        'currency' => $currency,
                        'original_amount' => $amount,
                        'original_currency' => $currency,
                    ]);

                    $collected = (float) $requestModel->amount_collected + (float) $amount;
                    $requestModel->update([
                        'amount_collected' => $collected,
                        'status_request' => $collected >= (float) $requestModel->required_amount
                            ? 'closed'
                            : $requestModel->status_request,
                    ]);

                    $orphan->update([
                        'next_monthly_deduction_at' => $orphan->next_monthly_deduction_at->addMonth()
                    ]);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error("Monthly deduction error: " . $e->getMessage());
            }
        }
        return $count;
    }
}