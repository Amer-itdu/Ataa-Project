<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // ============================
    // 1) KPIs — إحصائيات عامة
    // ============================
    public function kpis()
    {
        $totalUsers = User::count();
        $totalCampaigns = Campaign::count();

        $totalApprovedDonations = Donation::count();
        $totalDonatedUsd = Donation::sum('amount');

        $totalPendingRequests = RequestModel::where('status', 'pending')->count();
        $totalAcceptedRequests = RequestModel::where('status', 'accepted')->count();
        $totalRejectedRequests = RequestModel::where('status', 'rejected')->count();

        $totalOpenRequests = RequestModel::where('status_request', 'open')->count();
        $totalClosedRequests = RequestModel::where('status_request', 'closed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_campaigns' => $totalCampaigns,
                'total_approved_donations' => $totalApprovedDonations,
                'total_donated_usd' => number_format($totalDonatedUsd, 2, '.', ''),
                'pending_requests' => $totalPendingRequests,
                'accepted_requests' => $totalAcceptedRequests,
                'rejected_requests' => $totalRejectedRequests,
                'open_requests' => $totalOpenRequests,
                'closed_requests' => $totalClosedRequests,
            ],
        ], 200);
    }

    // ============================================
    // 2) monthlyDonations — تبرعات آخر 12 شهر
    // ============================================
    public function monthlyDonations()
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $records = Donation::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total_amount')
            ->where('created_at', '>=', $start)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $period = $start->copy()->addMonths($i);
            $months[$period->format('Y-m')] = 0.00;
        }

        foreach ($records as $row) {
            $label = Carbon::createFromDate($row->year, $row->month, 1)->format('Y-m');
            $months[$label] = number_format($row->total_amount, 2, '.', '');
        }

        $monthlyData = array_map(function ($amount, $label) {
            return [
                'month' => $label,
                'amount_usd' => $amount
            ];
        }, $months, array_keys($months));

        return response()->json([
            'success' => true,
            'donations' => $monthlyData,
        ], 200);
    }

    // ============================================
    // 3) casesByStatus — الحالات حسب الحالة ونوعها
    // ============================================
    public function casesByStatus()
    {
        $statuses = RequestModel::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // 🔥 توزيع إضافي حسب نوع الحالة (patient/orphan/school/university)
        $byType = RequestModel::selectRaw('request_type, count(*) as count')
            ->groupBy('request_type')
            ->get()
            ->pluck('count', 'request_type')
            ->toArray();

        return response()->json([
            'success' => true,
            'cases_by_status' => [
                'pending' => $statuses['pending'] ?? 0,
                'accepted' => $statuses['accepted'] ?? 0,
                'rejected' => $statuses['rejected'] ?? 0,
            ],
            'cases_by_type' => [
                'patient' => $byType['patient'] ?? 0,
                'orphan' => $byType['orphan'] ?? 0,
                'school' => $byType['school'] ?? 0,
                'university' => $byType['university'] ?? 0,
            ],
        ], 200);
    }

    // ============================================
    // 4) casesByGovernorate — توزيع الحالات جغرافياً
    // ============================================
    public function casesByGovernorate()
    {
        $data = RequestModel::join('beneficiaries', 'requests.beneficiary_id', '=', 'beneficiaries.id')
            ->join('governorates', 'beneficiaries.governorate_id', '=', 'governorates.id')
            ->selectRaw('governorates.name as governorate, count(*) as total_cases')
            ->groupBy('governorates.name')
            ->orderByDesc('total_cases')
            ->get();

        return response()->json([
            'success' => true,
            'cases_by_governorate' => $data,
        ], 200);
    }

    // ======================================================
    // 5) recentDonations — آخر 10 تبرعات
    // ======================================================
    public function recentDonations()
    {
        $donations = Donation::with(['donor.user', 'donationable'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($donation) {

                $target = $donation->donationable;

                if ($target instanceof Campaign) {
                    $targetDetails = [
                        'type' => 'campaign',
                        'id' => $target->id,
                        'title' => $target->title,
                        'status' => $target->status,
                        'amount_needed' => $target->amount_needed,
                        'amount_collected' => $target->amount_collected,
                    ];
                } elseif ($target instanceof \App\Models\Patient
                    || $target instanceof \App\Models\Orphan
                    || $target instanceof \App\Models\SchoolStudent
                    || $target instanceof \App\Models\UniversityStudent
                ) {
                    $requestModel = $target->request;
                    $targetDetails = [
                        'type' => 'case',
                        'id' => $requestModel->id ?? null,
                        'request_type' => $requestModel->request_type ?? null,
                        'status' => $requestModel->status ?? null,
                        'description' => $requestModel->description ?? null,
                    ];
                } else {
                    $targetDetails = [
                        'type' => class_basename($donation->donationable_type),
                        'id' => $target->id ?? null,
                    ];
                }

                $donorUser = $donation->donor->user ?? null;

                return [
                    'donation_id' => $donation->id,
                    'amount_usd' => number_format($donation->amount, 2, '.', ''),
                    'original_amount' => number_format($donation->original_amount, 2, '.', ''),
                    'original_currency' => $donation->original_currency,
                    'donor' => $donation->donor && $donation->donor->anonymous
                        ? ['anonymous' => true]
                        : [
                            'id' => $donorUser->id ?? null,
                            'name' => $donorUser ? trim($donorUser->first_name . ' ' . $donorUser->last_name) : null,
                            'email' => $donorUser->email ?? null,
                        ],
                    'target' => $targetDetails,
                    'created_at' => $donation->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'recent_donations' => $donations,
        ], 200);
    }

    // ======================================================
    // 6) topCampaigns — أفضل 5 حملات حسب التبرعات
    // ======================================================
    public function topCampaigns()
    {
        $campaigns = Campaign::orderByDesc('amount_collected')
            ->take(5)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'type' => $campaign->type,
                    'status' => $campaign->status,
                    'amount_needed' => $campaign->amount_needed,
                    'amount_collected' => $campaign->amount_collected,
                    'progress' => $campaign->progress,
                    'start_date' => optional($campaign->start_date)->toDateString(),
                    'end_date' => optional($campaign->end_date)->toDateString(),
                ];
            });

        return response()->json([
            'success' => true,
            'top_campaigns' => $campaigns,
        ], 200);
    }
}