<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\VolunteerCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerController extends Controller
{
   public function getMyApprovedVolunteers()
{
    $user = Auth::user();

    if (!$user || !$user->volunteer) {
        return response()->json([
            'success' => false,
            'message' => 'You must be a volunteer.'
        ], 401);
    }

    $volunteer = $user->volunteer;

    $approvedCampaigns = $volunteer->campaigns()
        ->wherePivot('status', 'approved')
        ->get();

    $approvedCount = $approvedCampaigns->count();

    $approved = $approvedCampaigns->map(function ($campaign) {
        return [
            'campaign_id' => $campaign->id,
            'title' => $campaign->title,
            'type' => $campaign->type,
            'status' => $campaign->pivot->status,
            'assigned_date' => $campaign->pivot->assigned_date,
        ];
    });

    return response()->json([
        'success' => true,
        'approved_count' => $approvedCount,
        'approved_volunteers' => $approved
    ], 200);
}
public function joinCampaign($campaignId)
{
    $user = Auth::user();

    if (!$user->volunteer) {
        return response()->json([
            'success' => false,
            'message' => 'You are not registered as a volunteer.'
        ], 403);
    }

    $volunteer = $user->volunteer;

    // تأكد أنه ما تطوع قبل
    if (VolunteerCampaign::where('volunteer_id', $volunteer->id)
                         ->where('campaign_id', $campaignId)
                         ->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'You already applied to this campaign.'
        ], 409);
    }

    // إضافة طلب تطوع بحالة pending
    VolunteerCampaign::create([
        'volunteer_id' => $volunteer->id,
        'campaign_id' => $campaignId,
        'status' => 'pending',
        'assigned_date' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Volunteer request submitted and waiting for admin approval.'
    ]);
}
public function approveVolunteer($volunteerCampaignId)
{
    $record = VolunteerCampaign::findOrFail($volunteerCampaignId);

    // إذا كان أصلاً approved لا نزيد العدد مرة ثانية
    if ($record->status === 'approved') {
        return response()->json([
            'success' => false,
            'message' => 'Volunteer already approved.'
        ], 409);
    }

    // تحديث حالة التطوع
    $record->update([
        'status' => 'approved'
    ]);

    // زيادة عدد المتطوعين في الحملة
    $campaign = $record->campaign;
    $campaign->increment('volunteers_joined');

    return response()->json([
        'success' => true,
        'message' => 'Volunteer approved successfully and campaign volunteer count updated.'
    ]);
}
public function rejectVolunteer($volunteerCampaignId)
{
    $record = VolunteerCampaign::findOrFail($volunteerCampaignId);

    // إذا كان Approved → لازم ننقص عدد المتطوعين
    if ($record->status === 'approved') {
        $campaign = $record->campaign;
        $campaign->decrement('volunteers_joined');
    }

    // إذا كان أصلاً Rejected → لا نعمل شي
    if ($record->status === 'rejected') {
        return response()->json([
            'success' => false,
            'message' => 'Volunteer already rejected.'
        ], 409);
    }

    // تغيير الحالة إلى rejected
    $record->update([
        'status' => 'rejected'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Volunteer rejected successfully.'
    ]);
}



}
