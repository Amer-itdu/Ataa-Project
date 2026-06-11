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
        $totalApprovedDonations = Donation::where('status', 'approved')->count();
        $totalDonatedUsd = Donation::where('status', 'approved')->sum('amount');

        $totalPendingRequests = RequestModel::where('status', 'pending')->count();
        $totalAcceptedRequests = RequestModel::where('status', 'accepted')->count();
        $totalRejectedRequests = RequestModel::where('status', 'rejected')->count();

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
            ->where('status', 'approved')
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
    // 3) casesByStatus — الحالات حسب الحالة
    // ============================================
    public function casesByStatus()
    {
        $statuses = RequestModel::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'cases_by_status' => [
                'pending' => $statuses['pending'] ?? 0,
                'accepted' => $statuses['accepted'] ?? 0,
                'rejected' => $statuses['rejected'] ?? 0,
            ],
        ], 200);
    }

    // ======================================================
    // 4) recentDonations — آخر 10 تبرعات approved
    // ======================================================
    public function recentDonations()
    {
        $donations = Donation::where('status', 'approved')
            ->with(['donor.user', 'donationable'])
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
                } elseif ($target instanceof RequestModel) {
                    $targetDetails = [
                        'type' => 'case',
                        'id' => $target->id,
                        'request_type' => $target->request_type,
                        'status' => $target->status,
                        'description' => $target->description,
                    ];
                } else {
                    $targetDetails = [
                        'type' => class_basename($donation->donationable_type),
                        'id' => $target->id ?? null,
                    ];
                }

                return [
                    'donation_id' => $donation->id,
                    'amount_usd' => number_format($donation->amount, 2, '.', ''),
                    'original_amount' => number_format($donation->original_amount, 2, '.', ''),
                    'original_currency' => $donation->original_currency,
                    'donor' => [
                        'id' => $donation->donor->user->id ?? null,
                        'name' => $donation->donor->user->first_name ?? null,
                        'email' => $donation->donor->user->email ?? null,
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
    // 5) topCampaigns — أفضل 5 حملات حسب التبرعات
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
