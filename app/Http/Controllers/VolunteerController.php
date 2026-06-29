<?php

namespace App\Http\Controllers;

use App\Http\Requests\VolunteerForCampaignRequest;
use App\Http\Requests\AddVolunteerHoursRequest;
use App\Models\Campaign;
use App\Models\Volunteer;
use App\Models\VolunteerHour;
use Illuminate\Support\Facades\Auth;

class VolunteerController extends Controller
{


    public function volunteerForCampaign(VolunteerForCampaignRequest $request, $campaignId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to volunteer.'
            ], 401);
        }

        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        if (!$campaign->acceptsVolunteers()) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign does not accept volunteers.'
            ], 400);
        }

        // 🔥 الحملة لازم تكون مفتوحة فعلياً
        if ($campaign->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This campaign is not open for volunteering.'
            ], 400);
        }

        // 🔥 منع التطوع لو اكتمل العدد المطلوب
        if (
            $campaign->volunteers_needed !== null
            && $campaign->volunteers_joined >= $campaign->volunteers_needed
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign has reached its volunteer limit.'
            ], 400);
        }

        $validated = $request->validated();

        $volunteer = $user->volunteer ?? Volunteer::create([
            'user_id'     => $user->id,
            'skills'      => $validated['skills'] ?? null,
            'description' => null,
            'status'      => 'active',
        ]);

        $already = $campaign->volunteers()
            ->where('volunteer_id', $volunteer->id)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => 'You have already volunteered for this campaign.'
            ], 409);
        }

        $campaign->volunteers()->attach($volunteer->id, [
            'status'         => 'pending',
            'assigned_date'  => null,
            'available_time' => $validated['available_time'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Volunteer request submitted successfully.',
        ], 201);
    }
    /*
    |--------------------------------------------------------------------------
    | 2) جميع المتطوعين لحملة معينة
    |--------------------------------------------------------------------------
    */
    public function getCampaignVolunteers($campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteers = $campaign->volunteers()
            ->with('user:id,first_name,last_name,email,phone')
            ->get()
            ->map(fn($v) => $this->formatVolunteerWithPivot($v));

        return response()->json([
            'success' => true,
            'volunteers' => $volunteers
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 3) جميع الحملات المقبول فيها المستخدم الحالي كمتطوع
    |--------------------------------------------------------------------------
    */
    public function getMyApprovedCampaigns()
    {
        $user = Auth::user();

        if (!$user || !$user->volunteer) {
            return response()->json([
                'success' => true,
                'campaigns' => []
            ], 200);
        }

        $campaigns = $user->volunteer->campaigns()
            ->wherePivot('status', 'approved')
            ->with('media')
            ->get()
            ->map(fn($c) => $this->formatCampaignWithPivot($c));

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 4) تطوعات المستخدم الحالي اللي لسا pending (ما انقبلت ولا انرفضت)
    |--------------------------------------------------------------------------
    */
    public function getMyPendingCampaigns()
    {
        $user = Auth::user();

        if (!$user || !$user->volunteer) {
            return response()->json([
                'success'   => true,
                'campaigns' => []
            ], 200);
        }

        $pivotRecords = $user->volunteer->volunteerCampaigns()
            ->where('status', 'pending')
            ->with('campaign.media')
            ->get();

        $campaigns = $pivotRecords->map(function ($pivot) {
            $campaign = $pivot->campaign;
            return [
                'id'                => $campaign->id,
                'title'             => $campaign->title,
                'type'              => $campaign->type,
                'status'            => $campaign->status,
                'progress'          => $campaign->progress,
                'time_remaining'    => $campaign->time_remaining,
                'volunteers_needed' => $campaign->volunteers_needed,
                'volunteers_joined' => $campaign->volunteers_joined,
                'media'             => $campaign->media,
                'my_status'         => $pivot->status,
                'assigned_date'     => $pivot->assigned_date,
                'available_time'    => $pivot->available_time,
                'notes'             => $pivot->notes,
            ];
        });

        return response()->json([
            'success'   => true,
            'campaigns' => $campaigns
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 5) قبول / رفض متطوع بحملة (أدمن)
    |--------------------------------------------------------------------------
    */
    public function updateVolunteerStatus(\Illuminate\Http\Request $request, $campaignId, $volunteerId)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update volunteer status.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,pending',
        ]);

        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $pivot = $campaign->volunteers()->where('volunteer_id', $volunteerId)->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Volunteer not found in this campaign.'
            ], 404);
        }

        $oldStatus = $pivot->pivot->status;
        $newStatus = $request->status;

        // لو نفس الحالة، ما في حاجة نعمل أي شي
        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => false,
                'message' => "Volunteer is already {$newStatus}."
            ], 400);
        }

        $campaign->volunteers()->updateExistingPivot($volunteerId, [
            'status'        => $newStatus,
            'assigned_date' => $newStatus === 'approved' ? now() : null,
        ]);

        // 🔥 تعديل العداد بدقة حسب التحول بين الحالات
        if ($oldStatus !== 'approved' && $newStatus === 'approved') {
            $campaign->increment('volunteers_joined');
        } elseif ($oldStatus === 'approved' && $newStatus !== 'approved') {
            $campaign->decrement('volunteers_joined');
        }

        // 🔥 تحديث حالة الحملة تلقائياً لو اكتمل عدد المتطوعين
        $campaign->refresh();
        $this->checkCampaignCompletion($campaign);

        return response()->json([
            'success'           => true,
            'message'           => "Volunteer {$newStatus} successfully.",
            'volunteers_joined' => $campaign->volunteers_joined,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 6) إضافة ساعات عمل لمتطوع بحملة معينة (field_worker فقط)
    |--------------------------------------------------------------------------
    */
    public function addVolunteerHours(AddVolunteerHoursRequest $request, $campaignId, $volunteerId)
    {
        $user = Auth::user();

        if ($user->role !== 'field_worker') {
            return response()->json([
                'success' => false,
                'message' => 'Only field workers can log volunteer hours.'
            ], 403);
        }

        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteer = Volunteer::find($volunteerId);

        if (!$volunteer) {
            return response()->json([
                'success' => false,
                'message' => 'Volunteer not found.'
            ], 404);
        }

        // 🔥 لازم يكون المتطوع مقبول بهذه الحملة فعلياً قبل تسجيل ساعات له
        $isApproved = $campaign->volunteers()
            ->wherePivot('status', 'approved')
            ->where('volunteer_id', $volunteer->id)
            ->exists();

        if (!$isApproved) {
            return response()->json([
                'success' => false,
                'message' => 'This volunteer is not approved for this campaign.'
            ], 400);
        }

        $validated = $request->validated();

        $entry = VolunteerHour::create([
            'volunteer_id'          => $volunteer->id,
            'campaign_id'           => $campaign->id,
            'date'                  => $validated['date'],
            'hours'                 => $validated['hours'],
            'activity_description' => $validated['activity_description'] ?? null,
        ]);

        return response()->json([
            'success'           => true,
            'message'           => 'Volunteer hours logged successfully.',
            'entry'             => $entry,
            'total_hours_in_campaign' => $volunteer->hours()->where('campaign_id', $campaign->id)->sum('hours'),
            'total_hours_overall'     => $volunteer->totalHours(),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 7) عرض ساعات متطوع بحملة معينة (مساعدة)
    |--------------------------------------------------------------------------
    */
    public function getVolunteerHoursInCampaign($campaignId, $volunteerId)
    {
        $entries = VolunteerHour::where('campaign_id', $campaignId)
            ->where('volunteer_id', $volunteerId)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success'     => true,
            'entries'     => $entries,
            'total_hours' => $entries->sum('hours'),
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 8) جميع ساعات تطوع المستخدم الحالي (مساعدة)
    |--------------------------------------------------------------------------
    */
    public function getMyVolunteerHours()
    {
        $user = Auth::user();

        if (!$user || !$user->volunteer) {
            return response()->json([
                'success'     => true,
                'entries'     => [],
                'total_hours' => 0,
            ], 200);
        }

        $entries = $user->volunteer->hours()
            ->with('campaign:id,title')
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'success'     => true,
            'entries'     => $entries,
            'total_hours' => $entries->sum('hours'),
        ], 200);
    }
    public function getCampaignPendingVolunteers($campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteers = $campaign->volunteers()
            ->wherePivot('status', 'pending')
            ->with('user:id,first_name,last_name,email,phone')
            ->get()
            ->map(fn($v) => $this->formatVolunteerWithPivot($v));

        return response()->json([
            'success'     => true,
            'campaign_id' => $campaign->id,
            'count'       => $volunteers->count(),
            'volunteers'  => $volunteers
        ], 200);
    }
    public function getCampaignApprovedVolunteers($campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteers = $campaign->volunteers()
            ->wherePivot('status', 'approved')
            ->with('user:id,first_name,last_name,email,phone')
            ->get()
            ->map(fn($v) => $this->formatVolunteerWithPivot($v));

        return response()->json([
            'success'     => true,
            'campaign_id' => $campaign->id,
            'count'       => $volunteers->count(),
            'volunteers'  => $volunteers
        ], 200);
    }
    public function getCampaignRejectedVolunteers($campaignId)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteers = $campaign->volunteers()
            ->wherePivot('status', 'rejected')
            ->with('user:id,first_name,last_name,email,phone')
            ->get()
            ->map(fn($v) => $this->formatVolunteerWithPivot($v));

        return response()->json([
            'success'     => true,
            'campaign_id' => $campaign->id,
            'count'       => $volunteers->count(),
            'volunteers'  => $volunteers
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — تنسيق الرد
    |--------------------------------------------------------------------------
    */
    private function formatVolunteerWithPivot($volunteer): array
    {
        return [
            'volunteer_id'   => $volunteer->id,
            'name'           => trim($volunteer->user->first_name . ' ' . $volunteer->user->last_name),
            'email'          => $volunteer->user->email,
            'phone'          => $volunteer->user->phone,
            'skills'         => $volunteer->skills,
            'status'         => $volunteer->pivot->status,
            'assigned_date'  => $volunteer->pivot->assigned_date,
            'available_time' => $volunteer->pivot->available_time,
            'notes'          => $volunteer->pivot->notes,
        ];
    }

    private function formatCampaignWithPivot($campaign): array
    {
        return [
            'id'                => $campaign->id,
            'title'             => $campaign->title,
            'type'              => $campaign->type,
            'status'            => $campaign->status,
            'progress'          => $campaign->progress,
            'time_remaining'    => $campaign->time_remaining,
            'volunteers_needed' => $campaign->volunteers_needed,
            'volunteers_joined' => $campaign->volunteers_joined,
            'media'             => $campaign->media,
            'my_status'         => $campaign->pivot->status,
            'assigned_date'     => $campaign->pivot->assigned_date,
            'available_time'    => $campaign->pivot->available_time,
            'notes'             => $campaign->pivot->notes,
        ];
    }
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
}
