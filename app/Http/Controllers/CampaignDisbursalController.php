<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\RequestModel;
use App\Models\DisbursementLog;

class CampaignDisbursalController extends Controller
{
   public function disburseCampaigns()
    {
        $campaigns = Campaign::where('status', 'in', ['completed', 'closed', 'ended', 'cancelled'])
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($campaigns as $campaign) {
            if ($campaign->disburseToAdmin()) {
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
                    'status' => 'failed'
                ];
            }
        }

        return $results;
    }

    /**
     * صرف جميع الطلبات المغلقة والمعلقة
     */
    public function disburseRequests()
    {
        $requests = RequestModel::where('status', 'in', ['completed', 'closed', 'ended', 'approved', 'finished'])
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($requests as $request) {
            if ($request->disburseToAdmin()) {
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
                    'status' => 'failed'
                ];
            }
        }

        return $results;
    }

    /**
     * صرف جميع الحملات والطلبات المعلقة
     */
    public function disburseAll()
    {
        $campaigns = $this->disburseCampaigns();
        $requests = $this->disburseRequests();

        return [
            'campaigns' => $campaigns,
            'requests' => $requests,
            'total_success' => $campaigns['success'] + $requests['success'],
            'total_failed' => $campaigns['failed'] + $requests['failed'],
            'total_amount' => $this->calculateTotalDisbursed(),
        ];
    }

    /**
     * صرف لـ Admin معين
     */
    public function disburseForAdmin($adminId)
    {
        $results = [
            'campaigns' => ['success' => 0, 'failed' => 0, 'details' => []],
            'requests' => ['success' => 0, 'failed' => 0, 'details' => []],
        ];

        // الحملات
        $campaigns = Campaign::where('user_id', $adminId)
            ->where('status', 'in', ['completed', 'closed', 'ended', 'cancelled'])
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->get();

        foreach ($campaigns as $campaign) {
            if ($campaign->disburseToAdmin()) {
                $results['campaigns']['success']++;
                $results['campaigns']['details'][] = [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'amount' => $campaign->amount_collected,
                ];
            } else {
                $results['campaigns']['failed']++;
            }
        }

        // الطلبات
        $requests = RequestModel::where('user_id', $adminId)
            ->where('status', 'in', ['completed', 'closed', 'ended', 'approved', 'finished'])
            ->where('is_disbursed', false)
            ->where('amount_collected', '>', 0)
            ->get();

        foreach ($requests as $request) {
            if ($request->disburseToAdmin()) {
                $results['requests']['success']++;
                $results['requests']['details'][] = [
                    'id' => $request->id,
                    'title' => $request->title,
                    'amount' => $request->amount_collected,
                ];
            } else {
                $results['requests']['failed']++;
            }
        }

        return $results;
    }

    /**
     * احسب إجمالي المصروف اليوم
     */
    public function calculateTotalDisbursed($date = null)
    {
        $query = DisbursementLog::where('status', 'completed');

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->sum('amount');
    }

    /**
     * احصل على سجل الصرف
     */
    public function getDisbursementLogs($limit = 50)
    {
        return DisbursementLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
