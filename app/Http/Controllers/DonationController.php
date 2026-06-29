<?php

namespace App\Http\Controllers;

use App\Http\Requests\DonateRequest;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\RequestModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{

    public function quickDonateToAssociation(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot donate.'
            ], 403);
        }

        $validated = $request->validate([
            'currency' => 'required|in:USD,EUR,SAR,AED,EGP,SYP',
            'amount' => 'required|numeric|min:1',
        ]);

        // تحويل المبلغ إلى دولار
        $amountInUSD = User::convertToUSD($validated['amount'], $validated['currency']);

        // جلب حساب الجمعية (الأدمن)
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin account not found.'
            ], 500);
        }

        // خصم رصيد المتبرع
        if (!$user->subtractBalance($validated['currency'], $validated['amount'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.'
            ], 400);
        }

        // إضافة الرصيد للجمعية بالدولار
        $admin->addBalance('USD', $amountInUSD);

        // إنشاء donor إذا غير موجود
        $donor = $user->donor ?? Donor::create([
            'user_id' => $user->id,
            'anonymous' => false,
        ]);

        // تسجيل التبرع
        $donation = Donation::create([
            'donor_id'          => $donor->id,
            'amount'            => $amountInUSD,
            'currency'          => 'USD',
            'original_amount'   => $validated['amount'],
            'original_currency' => $validated['currency'],
            'donationable_type' => User::class,
            'donationable_id'   => $admin->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Donation completed successfully.',
            'donation_id' => $donation->id,
        ], 200);
    }


    // ================================
    public function donate(DonateRequest $request, $type, $id)
    {
        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Admins cannot donate.'], 403);
        }

        $validated   = $request->validated();
        $amountInUSD = User::convertToUSD($validated['amount'], $validated['currency']);

        // خصم الرصيد أولاً (موحّد للحالتين)
        if (!$user->subtractBalance($validated['currency'], $validated['amount'])) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance.'], 400);
        }

        return match ($type) {
            'request'  => $this->donateToRequest($user, $validated, $amountInUSD, $id),
            'campaign' => $this->donateToCampaign($user, $validated, $amountInUSD, $id),
            default    => $this->refundAndFail($user, $validated, 'Invalid donation type.'),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | DONATE TO REQUEST (case) — خاصة، بتنادى من donate() فقط
    |--------------------------------------------------------------------------
    */
    private function donateToRequest($user, $validated, $amountInUSD, $id)
    {
        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return $this->refundAndFail($user, $validated, 'Request not found.');
        }

        $target = match ($requestModel->request_type) {
            'patient'    => $requestModel->patient,
            'orphan'     => $requestModel->orphan,
            'school'     => $requestModel->schoolStudent,
            'university' => $requestModel->universityStudent,
            default      => null,
        };

        if (!$target) {
            return $this->refundAndFail($user, $validated, 'Invalid request type.');
        }

        $donatedBefore = $target->donations()->sum('amount');
        $remaining     = $requestModel->required_amount - $donatedBefore;
        $amountToUse   = min($amountInUSD, $remaining);
        $extra         = $amountInUSD - $amountToUse;

        $requestModel->user->addBalance('USD', $amountToUse);

        if ($extra > 0) {
            $user->addBalance('USD', $extra);
        }

        $donor = $user->donor ?? Donor::create(['user_id' => $user->id, 'anonymous' => false]);

        $donation = $target->donations()->create([
            'donor_id'          => $donor->id,
            'amount'            => $amountToUse,
            'currency'          => 'USD',
            'original_amount'   => $validated['amount'],
            'original_currency' => $validated['currency'],
        ]);

        $donated  = $target->donations()->sum('amount');
        $required = $requestModel->required_amount;

        if ($donated >= $required) {
            $requestModel->update(['status_request' => 'closed']);
        }

        return response()->json([
            'success'                     => true,
            'message'                     => 'Donation completed successfully.',
            'donation_id'                 => $donation->id,
            'donated_amount'              => $donated,
            'required_amount'             => $required,
            'progress_percentage'         => $required > 0 ? round(($donated / $required) * 100, 2) : 0,
            'extra_returned_to_donor_usd' => $extra,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DONATE TO CAMPAIGN — خاصة، بتنادى من donate() فقط
    |--------------------------------------------------------------------------
    */
    private function donateToCampaign($user, $validated, $amountInUSD, $id)
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return $this->refundAndFail($user, $validated, 'Campaign not found.');
        }

        if (!$campaign->acceptsDonations()) {
            return $this->refundAndFail($user, $validated, 'This campaign does not accept donations.');
        }

        if ($campaign->status !== 'open') {
            return $this->refundAndFail($user, $validated, 'Campaign is not active.');
        }

        $remaining   = $campaign->amount_needed - $campaign->amount_collected;
        $amountToUse = min($amountInUSD, $remaining);
        $extra       = $amountInUSD - $amountToUse;

        if ($extra > 0) {
            $user->addBalance('USD', $extra);
        }

        $donor = $user->donor ?? Donor::create(['user_id' => $user->id, 'anonymous' => false]);

        $donation = $campaign->donations()->create([
            'donor_id'          => $donor->id,
            'amount'            => $amountToUse,
            'currency'          => 'USD',
            'original_amount'   => $validated['amount'],
            'original_currency' => $validated['currency'],
        ]);

        $campaign->increment('amount_collected', $amountToUse);
        $campaign->refresh();

        $this->checkCampaignCompletion($campaign);

        $required = $campaign->amount_needed;
        $donated  = $campaign->amount_collected;

        return response()->json([
            'success'                     => true,
            'message'                     => 'Donation to campaign completed successfully.',
            'donation_id'                 => $donation->id,
            'donated_amount'              => $donated,
            'required_amount'             => $required,
            'progress_percentage'         => $required > 0 ? round(($donated / $required) * 100, 2) : 0,
            'extra_returned_to_donor_usd' => $extra,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: إرجاع الرصيد + رسالة فشل (تجنب تكرار الكود)
    |--------------------------------------------------------------------------
    */
    private function refundAndFail($user, $validated, $message)
    {
        $user->addBalance($validated['currency'], $validated['amount']);

        return response()->json(['success' => false, 'message' => $message], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: تحديث حالة الحملة تلقائياً
    |--------------------------------------------------------------------------
    */
    private function checkCampaignCompletion(Campaign $campaign)
    {
        $donationsDone  = $campaign->amount_needed !== null
            && $campaign->amount_collected >= $campaign->amount_needed;

        $volunteersDone = $campaign->volunteers_needed !== null
            && $campaign->volunteers_joined >= $campaign->volunteers_needed;

        if ($donationsDone && $volunteersDone) {
            $campaign->update(['status' => 'completed_all']);
        } elseif ($donationsDone && $campaign->acceptsDonations() && !$campaign->acceptsVolunteers()) {
            $campaign->update(['status' => 'completed_donations']);
        } elseif ($volunteersDone && $campaign->acceptsVolunteers() && !$campaign->acceptsDonations()) {
            $campaign->update(['status' => 'completed_volunteers']);
        }
    }
    public function myDonationsSummary()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        $donor = $user->donor;

        if (!$donor) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_donated_usd' => 0,
                    'total_donations_count' => 0,
                    'cases_count' => 0,
                    'campaigns_count' => 0,
                    'total_supported' => 0,
                    'cases_by_type' => [],
                    'cases' => [],
                    'campaigns' => []
                ]
            ]);
        }

        // جميع التبرعات
        $donations = $donor->donations()->with('donationable')->get();

        // إجمالي المبلغ
        $totalDonated = $donations->sum('amount');

        // عدد التبرعات
        $totalCount = $donations->count();

        // ============================
        // 🔥 الحالات (Patient / Orphan / SchoolStudent / UniversityStudent)
        // ============================
        $cases = $donations->filter(function ($d) {
            return !($d->donationable instanceof \App\Models\Campaign);
        })->map(function ($d) {
            $case = $d->donationable;

            return [
                'donation_id' => $d->id,
                'case_id' => $case->id,
                'type' => class_basename($case), // Patient / Orphan / SchoolStudent / UniversityStudent
                'amount_usd' => $d->amount,
                'date' => $d->created_at->format('Y-m-d H:i')
            ];
        })->values();

        // عدد الحالات الفريدة
        $uniqueCasesCount = $cases->pluck('case_id')->unique()->count();

        // ============================
        // 🔥 عدد الحالات حسب النوع
        // ============================
        $casesByType = $cases
            ->groupBy('type')
            ->map(function ($group) {
                return $group->pluck('case_id')->unique()->count();
            });

        // ============================
        // 🔥 الحملات
        // ============================
        $campaigns = $donations->filter(function ($d) {
            return $d->donationable instanceof \App\Models\Campaign;
        })->map(function ($d) {
            $campaign = $d->donationable;

            return [
                'donation_id' => $d->id,
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'amount_usd' => $d->amount,
                'date' => $d->created_at->format('Y-m-d H:i')
            ];
        })->values();

        // عدد الحملات الفريدة
        $uniqueCampaignsCount = $campaigns->pluck('campaign_id')->unique()->count();

        // مجموع الحالات + الحملات
        $totalSupported = $uniqueCasesCount + $uniqueCampaignsCount;

        return response()->json([
            'success' => true,
            'data' => [
                'total_donated_usd' => number_format($totalDonated, 2, '.', ''),
                'total_donations_count' => $totalCount,
                'cases_count' => $uniqueCasesCount,
                'campaigns_count' => $uniqueCampaignsCount,
                'total_supported' => $totalSupported,
                'cases_by_type' => $casesByType,
                'cases' => $cases,
                'campaigns' => $campaigns
            ]
        ]);
    }
}
