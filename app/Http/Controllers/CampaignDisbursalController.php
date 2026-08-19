<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\RequestModel;
use App\Models\DisbursementLog;
use Illuminate\Support\Facades\Auth;

class CampaignDisbursalController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1) صرف حملة واحدة — donation_only أو donation_and_volunteer فقط
    |--------------------------------------------------------------------------
    */
    public function disburseCampaign($campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        // فقط donation_only أو donation_and_volunteer
        if (!in_array($campaign->participation_type, ['donation_only', 'donation_and_volunteer'])) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign does not accept donations.'
            ], 400);
        }

        // 🔥 استخدم النتيجة من الدالة الجديدة
        $result = $campaign->disburseToAdmin();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /*
    |--------------------------------------------------------------------------
    | 2) الحملات المعلقة الصرف — donation_only أو donation_and_volunteer فقط
    |--------------------------------------------------------------------------
    */
    public function getPendingCampaignDisbursements()
    {
        $campaigns = Campaign::where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->whereIn('participation_type', ['donation_only', 'donation_and_volunteer'])
            ->where(function ($query) {
                // 🔥 الشروط الاختيارية (إما هذا أو ذاك)
                $query->whereIn('status', ['completed', 'closed', 'ended', 'cancelled', 'completed_all', 'completed_donations'])
                    ->orWhere(function ($q) {
                        $q->whereNotNull('amount_needed')
                            ->whereRaw('amount_collected >= amount_needed');
                    });
            })
            ->with('admin:id,first_name,last_name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => [
                'campaign_id' => $c->id,
                'title' => $c->title,
                'admin_name' => trim($c->admin->first_name . ' ' . $c->admin->last_name),
                'admin_email' => $c->admin->email,
                'amount_collected' => $c->amount_collected,
                'amount_needed' => $c->amount_needed,
                'participation_type' => $c->participation_type,
                'status' => $c->status,
                'is_disbursed' => $c->is_disbursed,
                'created_at' => $c->created_at,
            ]);

        return response()->json([
            'success' => true,
            'count' => $campaigns->count(),
            'data' => $campaigns
        ], 200);
    }
    /*
    |--------------------------------------------------------------------------
    | 3) صرف طلب واحد
    |--------------------------------------------------------------------------
    */
    public function disburseRequest($requestId)
    {
        $request = RequestModel::find($requestId);

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.'
            ], 404);
        }

        // 🔥 استخدم النتيجة من الدالة الجديدة
        $result = $request->disburseToAdmin();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function getPendingRequestDisbursements()
    {
        // 🔥 عرض الطلبات اللي الأدمن انشأها فقط وجاهزة للصرف
        $requests = RequestModel::where('user_id', 1)  // الأدمن فقط
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->where('status', 'accepted')  // معتمد
            ->where('status_request', 'closed')  // مغلق
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => [
                'request_id' => $r->id,
                'title' => $r->title,
                'request_type' => $r->request_type,
                'admin_name' => trim($r->user->first_name . ' ' . $r->user->last_name),
                'admin_email' => $r->user->email,
                'amount_collected' => $r->amount_collected,
                'required_amount' => $r->required_amount,
                'status' => $r->status,
                'status_request' => $r->status_request,
                'is_disbursed' => $r->is_disbursed,
                'created_at' => $r->created_at,
            ]);

        return response()->json([
            'success' => true,
            'count' => $requests->count(),
            'data' => $requests
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 5) صرف جميع الحملات المعلقة دفعة واحدة
    |--------------------------------------------------------------------------
    */
    public function disburseCampaigns()
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can disburse funds.'
            ], 403);
        }

        $campaigns = Campaign::where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->whereIn('participation_type', ['donation_only', 'donation_and_volunteer'])
            ->where(function ($query) {
                $query->whereIn('status', ['completed', 'closed', 'ended', 'cancelled', 'completed_all', 'completed_donations'])
                    ->orWhere(function ($q) {
                        $q->whereNotNull('amount_needed')
                            ->whereRaw('amount_collected >= amount_needed');
                    });
            })
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($campaigns as $campaign) {
            $result = $campaign->disburseToAdmin();

            if ($result['success']) {
                $results['success']++;
                $results['details'][] = [
                    'type' => 'campaign',
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'amount' => $campaign->amount_collected,
                    'status' => 'success'
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'type' => 'campaign',
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'amount' => $campaign->amount_collected,
                    'status' => 'failed',
                    'error' => $result['message']
                ];
            }
        }

        return response()->json([
            'success' => true,
            'disbursement_summary' => $results,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 6) صرف جميع الطلبات المعلقة دفعة واحدة
    |--------------------------------------------------------------------------
    */
    public function disburseRequests()
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can disburse funds.'
            ], 403);
        }

        $requests = RequestModel::where('user_id', 1)  // الأدمن فقط
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->where('status', 'accepted')
            ->where('status_request', 'closed')
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($requests as $request) {
            $result = $request->disburseToAdmin();

            if ($result['success']) {
                $results['success']++;
                $results['details'][] = [
                    'type' => 'request',
                    'id' => $request->id,
                    'title' => $request->title,
                    'amount' => $request->amount_collected,
                    'status' => 'success'
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'type' => 'request',
                    'id' => $request->id,
                    'title' => $request->title,
                    'amount' => $request->amount_collected,
                    'status' => 'failed',
                    'error' => $result['message']
                ];
            }
        }

        return response()->json([
            'success' => true,
            'disbursement_summary' => $results,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 7) صرف جميع الحملات والطلبات دفعة واحدة
    |--------------------------------------------------------------------------
    */
    public function disburseAll()
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can disburse funds.'
            ], 403);
        }

        // صرف الحملات
        $campaignsResponse = $this->disburseCampaigns()->getData();
        $campaigns = $campaignsResponse->disbursement_summary;

        // صرف الطلبات
        $requestsResponse = $this->disburseRequests()->getData();
        $requests = $requestsResponse->disbursement_summary;

        return response()->json([
            'success' => true,
            'message' => 'Disbursement process completed.',
            'campaigns' => $campaigns,
            'requests' => $requests,
            'total_success' => $campaigns->success + $requests->success,
            'total_failed' => $campaigns->failed + $requests->failed,
        ], 200);
    }

    public function getCampaignsDisbursementReport($year, $month)
    {
        // تحقق من القيم
        if ($month < 1 || $month > 12 || $year < 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year or month.'
            ], 400);
        }

        // احصل على جميع الحملات المصروفة في الشهر المعين
        $campaigns = Campaign::whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->where('is_disbursed', true)
            ->with('admin:id,first_name,last_name,email')
            ->orderByDesc('updated_at')
            ->get();

        // احسب الإجمالي
        $totalAmount = $campaigns->sum('amount_collected');
        $totalCount = $campaigns->count();

        // تجهيز البيانات للتقرير
        $data = $campaigns->map(fn($c) => [
            'campaign_id' => $c->id,
            'title' => $c->title,
            'type' => $c->type,
            'admin_name' => trim($c->admin->first_name . ' ' . $c->admin->last_name),
            'admin_email' => $c->admin->email,
            'amount_needed' => round($c->amount_needed, 2),
            'amount_collected' => round($c->amount_collected, 2),
            'participation_type' => $c->participation_type,
            'status' => $c->status,
            'disbursed_date' => $c->updated_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'report' => [
                'period' => "$month/$year",
                'total_campaigns_disbursed' => $totalCount,
                'total_amount_usd' => round($totalAmount, 2),
                'currency' => 'USD',
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ],
            'campaigns' => $data
        ], 200);
    }

    /**
     * احصل على جميع الطلبات المصروفة في شهر معين (للتقارير)
     */
    public function getRequestsDisbursementReport($year, $month)
    {
        if ($month < 1 || $month > 12 || $year < 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year or month.'
            ], 400);
        }

        $requests = RequestModel::whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->where('is_disbursed', true)
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('updated_at')
            ->get();

        $totalAmount = $requests->sum('amount_collected');
        $totalCount = $requests->count();

        $data = $requests->map(fn($r) => [
            'request_id' => $r->id,
            'title' => $r->title,
            'request_type' => $r->request_type,
            'admin_name' => trim($r->user->first_name . ' ' . $r->user->last_name),
            'admin_email' => $r->user->email,
            'required_amount' => round($r->required_amount, 2),
            'amount_collected' => round($r->amount_collected, 2),
            'status' => $r->status,
            'disbursed_date' => $r->updated_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'report' => [
                'period' => "$month/$year",
                'total_requests_disbursed' => $totalCount,
                'total_amount_usd' => round($totalAmount, 2),
                'currency' => 'USD',
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ],
            'requests' => $data
        ], 200);
    }

    /**
     * تقرير شامل للصرفيات في شهر معين (حملات + طلبات)
     */
    public function getCompleteDisbursementReport($year, $month)
    {
        if ($month < 1 || $month > 12 || $year < 2000) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year or month.'
            ], 400);
        }

        // الحملات
        $campaigns = Campaign::whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->where('is_disbursed', true)
            ->get();

        // الطلبات
        $requests = RequestModel::whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->where('is_disbursed', true)
            ->get();

        $campaignsAmount = $campaigns->sum('amount_collected');
        $requestsAmount = $requests->sum('amount_collected');
        $totalAmount = $campaignsAmount + $requestsAmount;

        return response()->json([
            'success' => true,
            'report' => [
                'period' => "$month/$year",
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'summary' => [
                    'campaigns' => [
                        'count' => $campaigns->count(),
                        'total_amount' => round($campaignsAmount, 2),
                    ],
                    'requests' => [
                        'count' => $requests->count(),
                        'total_amount' => round($requestsAmount, 2),
                    ],
                    'total' => [
                        'count' => $campaigns->count() + $requests->count(),
                        'total_amount' => round($totalAmount, 2),
                        'currency' => 'USD',
                    ],
                ],
                'campaigns_details' => $campaigns->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'amount' => round($c->amount_collected, 2),
                ])->values(),
                'requests_details' => $requests->map(fn($r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'amount' => round($r->amount_collected, 2),
                ])->values(),
            ]
        ], 200);
    }
}
