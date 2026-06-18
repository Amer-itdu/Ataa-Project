<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Donation;
use App\Models\User;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\StoreDonationRequest;
use App\Http\Requests\VolunteerForCampaignRequest;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CampaignController extends Controller
{
    public function createCampaign(StoreCampaignRequest $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can create campaigns.'
            ], 403);
        }

        $data = $request->validated();


        $data['user_id'] = $user->id;
        $data['amount_collected'] = 0;
        $data['volunteers_joined'] = 0;
        $data['status'] = $data['status'] ?? 'open';

        // إنشاء الحملة
        $campaign = Campaign::create($data);

        // رفع الصور
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('campaign_media', 'public');
                $campaign->media()->create(['image' => $path]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully.',
            'campaign' => $campaign->load('media')
        ], 201);
    }

    public function updateCampaign(StoreCampaignRequest $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update campaigns.'
            ], 403);
        }

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $data = $request->validated();

        // 🔥 منع تعديل بداية الحملة
        unset($data['start_date']);

        // 🔥 التحقق من أن end_date أكبر من اليوم
        if (isset($data['end_date'])) {
            if (Carbon::parse($data['end_date'])->lte(now())) {
                return response()->json([
                    'success' => false,
                    'message' => 'End date must be a future date.'
                ], 422);
            }
        }

        $mediaFiles = $request->file('media', []);
        unset($data['media']);

        $campaign->update($data);

        if (!empty($mediaFiles)) {
            foreach ($mediaFiles as $file) {
                $path = $file->store('campaign_media', 'public');
                $campaign->media()->create(['image' => $path]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully.',
            'campaign' => $campaign->load('media')
        ], 200);
    }

    public function deleteCampaign($id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete campaigns.'
            ], 403);
        }

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,

            ], 404);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully.'
        ], 200);
    }
    public function closeCampaign($id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can close campaigns.'
            ], 403);
        }

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        if ($campaign->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is already closed.'
            ], 409);
        }

        $campaign->status = 'closed';
        $campaign->save();

        return response()->json([
            'success' => true,
            'message' => 'Campaign closed successfully.',
            'campaign' => $campaign
        ], 200);
    }

    public function volunteerForCampaign(VolunteerForCampaignRequest $request, $campaignId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to volunteer.'
            ], 401);
        }

        // إنشاء ملف متطوع تلقائيًا إذا غير موجود
        if (!$user->volunteer) {
            $volunteer = Volunteer::create([
                'user_id' => $user->id,
                'skills' => null,
                'description' => null,
                'status' => 'active',
            ]);
        } else {
            $volunteer = $user->volunteer;
        }

        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        // منع التطوع المكرر
        $already = $campaign->volunteers()
            ->where('volunteer_id', $volunteer->id)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => 'You have already volunteered for this campaign.'
            ], 409);
        }

        // ربط المتطوع بالحملة
        $campaign->volunteers()->attach($volunteer->id, [
            'status' => 'pending',
            'assigned_date' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Volunteer request submitted successfully.',
        ], 201);
    }

    public function getCampaignDetails($id)
    {
        $campaign = Campaign::with(['media', 'admin'])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        // نسبة التقدم
        $progress = $campaign->progress;

        // الوقت المتبقي
        $timeRemaining = $campaign->time_remaining;

        // عدد المتطوعين المقبولين فقط
        $approvedVolunteers = $campaign->volunteers()
            ->wherePivot('status', 'approved')
            ->count();

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
                'progress' => $progress,
                'time_remaining' => $timeRemaining,
                'approved_volunteers_count' => $approvedVolunteers,
            ]
        ], 200);
    }
    public function getActiveCampaigns()
    {
        $campaigns = Campaign::where('status', '!=', 'closed')
            ->with(['media', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'type' => $campaign->type,
                    'status' => $campaign->status,
                    'progress' => $campaign->progress,
                    'time_remaining' => $campaign->time_remaining,
                    'volunteers_count' => $campaign->volunteers()->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns
        ], 200);
    }
   
}
