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

    public function getMonthlyDonations($year, $month)
    {
        if ((int) $month < 1 || (int) $month > 12 || (int) $year < 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year or month.',
            ], 400);
        }

        $donations = Donation::with(['donor.user', 'donationable'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($donation) {
                $target = $donation->donationable;
                $requestModel = $target?->request;

                return [
                    'donation_id' => $donation->id,
                    'donor' => $donation->donor?->user,
                    'amount_usd' => $donation->amount,
                    'original_amount' => $donation->original_amount,
                    'original_currency' => $donation->original_currency,
                    'donationable_type' => class_basename($donation->donationable_type),
                    'donationable_id' => $donation->donationable_id,
                    'request_id' => $requestModel?->id,
                    'beneficiary' => $requestModel?->beneficiary,
                    'date' => $donation->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'period' => "$month/$year",
            'donations_count' => $donations->count(),
            'total_amount_usd' => round($donations->sum('amount_usd'), 2),
            'data' => $donations->values(),
        ]);
    }

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
        DB::beginTransaction();

        $requestModel = RequestModel::lockForUpdate()->find($id);

        if (!$requestModel) {
            DB::rollBack();
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
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'Invalid request type.');
        }

        $required = (float) $requestModel->required_amount;
        $collected = (float) $requestModel->amount_collected;
        $remaining = max($required - $collected, 0);

        if ($remaining <= 0 || $requestModel->status_request === 'closed') {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'This request is already fully funded.');
        }

        $amountToUse = min((float) $amountInUSD, $remaining);
        $extra = (float) $amountInUSD - $amountToUse;

        try {
            $donor = $user->donor ?? Donor::create([
                'user_id' => $user->id,
                'anonymous' => false,
            ]);

            $donation = $target->donations()->create([
                'donor_id'          => $donor->id,
                'amount'            => $amountToUse,
                'currency'          => 'USD',
                'original_amount'   => $validated['amount'],
                'original_currency' => $validated['currency'],
            ]);

            $requestModel->update([
                'amount_collected' => $collected + $amountToUse,
                'status_request' => ($collected + $amountToUse) >= $required ? 'closed' : 'open',
            ]);

            if ($requestModel->user) {
                $requestModel->user->addBalance('USD', $amountToUse);
            }

            if ($extra > 0) {
                $user->addBalance('USD', $extra);
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            return $this->refundAndFail($user, $validated, 'Donation could not be completed.');
        }

        $donated = $collected + $amountToUse;

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
        DB::beginTransaction();

        $campaign = Campaign::lockForUpdate()->find($id);

        if (!$campaign) {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'Campaign not found.');
        }

        if (!$campaign->acceptsDonations()) {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'This campaign does not accept donations.');
        }

        if ($campaign->status !== 'open') {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'Campaign is not active.');
        }

        $admin = User::find(1);

        if (!$admin) {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'Admin account not found.');
        }

        $required    = (float) $campaign->amount_needed;
        $collected   = (float) $campaign->amount_collected;
        $remaining   = max($required - $collected, 0);

        if ($remaining <= 0) {
            DB::rollBack();
            return $this->refundAndFail($user, $validated, 'This campaign is already fully funded.');
        }

        $amountToUse = min($amountInUSD, $remaining);
        $extra       = $amountInUSD - $amountToUse;

        try {
            $donor = $user->donor ?? Donor::create([
                'user_id' => $user->id,
                'anonymous' => false,
            ]);

            $donation = $campaign->donations()->create([
                'donor_id'          => $donor->id,
                'amount'            => $amountToUse,
                'currency'          => 'USD',
                'original_amount'   => $validated['amount'],
                'original_currency' => $validated['currency'],
            ]);

            $campaign->update([
                'amount_collected' => $collected + $amountToUse,
            ]);

            // Transfer the used donation amount to the association admin.
            $admin->addBalance('USD', $amountToUse);

            if ($extra > 0) {
                $user->addBalance('USD', $extra);
            }

            $campaign->refresh();
            $this->checkCampaignCompletion($campaign);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            return $this->refundAndFail($user, $validated, 'Donation could not be completed.');
        }

        $donated = (float) $campaign->amount_collected;

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
  public function getAllDonations(Request $request)
{
    $request->validate([
        'donationable_type' => 'nullable|in:request,campaign',
        'sort_by'           => 'nullable|in:created_at,amount',
        'sort_dir'          => 'nullable|in:asc,desc',
    ]);

    $query = Donation::with([
        'donor.user:id,first_name,last_name,email',
        'donationable'
    ]);

    // 🔥 فلترة حسب النوع
    if ($request->filled('donationable_type')) {
        if ($request->donationable_type === 'request') {
            $query->where(function ($q) {
                $q->whereIn('donationable_type', [
                    'App\Models\Patient',
                    'App\Models\Orphan',
                    'App\Models\SchoolStudent',
                    'App\Models\UniversityStudent'
                ]);
            });
        } elseif ($request->donationable_type === 'campaign') {
            $query->where('donationable_type', 'App\Models\Campaign');
        }
    }

    $sortBy  = $request->get('sort_by', 'created_at');
    $sortDir = $request->get('sort_dir', 'desc');
    $query->orderBy($sortBy, $sortDir);

    $donations = $query->get();

    // تحويل البيانات
    $donations = $donations->map(function ($donation) {
        $donorName = 'Anonymous';
        if ($donation->donor && !$donation->donor->anonymous) {
            $donorName = trim($donation->donor->user->first_name . ' ' . $donation->donor->user->last_name);
        }

        $target = null;
        if ($donation->donationable_type === 'App\Models\Campaign') {
            $target = $donation->donationable->title;
            $type = 'campaign';
        } else {
            $type = 'request';
            $target = match ($donation->donationable_type) {
                'App\Models\Patient'           => 'Patient: ' . ($donation->donationable->patient_name ?? 'N/A'),
                'App\Models\Orphan'            => 'Orphan: ' . ($donation->donationable->orphan_name ?? 'N/A'),
                'App\Models\SchoolStudent'     => 'School: ' . ($donation->donationable->school_student_name ?? 'N/A'),
                'App\Models\UniversityStudent' => 'University: ' . ($donation->donationable->university_student_name ?? 'N/A'),
                default                        => 'Unknown',
            };
        }

        return [
            'id'                => $donation->id,
            'donor_name'        => $donorName,
            'donor_anonymous'   => $donation->donor?->anonymous ?? false,
            'amount_usd'        => $donation->amount,
            'original_amount'   => $donation->original_amount,
            'original_currency' => $donation->original_currency,
            'target_type'       => $type,
            'target_name'       => $target,
            'donated_at'        => $donation->created_at,
        ];
    });

    $totalAmount = Donation::sum('amount');

    return response()->json([
        'success'        => true,
        'total_donated'  => $totalAmount,
        'donations_count'=> $donations->count(),
        'donations'      => $donations,
    ], 200);
}
public function disburseCampaignCollectedAmount($campaignId)
{
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Only admins can disburse funds.'
        ], 403);
    }

    $campaign = Campaign::find($campaignId);

    if (!$campaign) {
        return response()->json([
            'success' => false,
            'message' => 'Campaign not found.'
        ], 404);
    }

    $amountToDisburse = (float) $campaign->amount_collected;

    if ($amountToDisburse == 0) {
        return response()->json([
            'success' => false,
            'message' => 'Campaign has no collected amount to disburse.'
        ], 400);
    }

    $admin = \App\Models\User::find(1);
    $adminBalance = $admin->getBalance('USD');

    if ($adminBalance < $amountToDisburse) {
        return response()->json([
            'success' => false,
            'message' => "Insufficient balance. Available: {$adminBalance} USD"
        ], 400);
    }

    DB::beginTransaction();

    try {
        // اخصم من الأدمن
        $admin->subtractBalance('USD', $amountToDisburse);

        // سجّل الصرف
        \App\Models\CampaignDisbursal::create([
            'campaign_id'       => $campaign->id,
            'admin_id'          => $user->id,
            'amount'            => $amountToDisburse,
            'currency'          => 'USD',
            'original_amount'   => $amountToDisburse,
            'original_currency' => 'USD',
            'notes'             => 'Campaign disbursement',
            'disbursed_by'      => $user->first_name . ' ' . $user->last_name,
        ]);

        // صفّر amount_collected
        $campaign->update(['amount_collected' => 0]);

        DB::commit();

        return response()->json([
            'success'       => true,
            'message'       => 'Disbursed successfully.',
            'disbursed'     => $amountToDisburse,
            'campaign'      => [
                'id'    => $campaign->id,
                'title' => $campaign->title,
            ],
            'admin_balance' => round($admin->getBalance('USD'), 2),
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}
}
