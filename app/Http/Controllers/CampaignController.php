<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Donation;
use App\Models\User;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Requests\VolunteerForCampaignRequest;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CampaignController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CREATE CAMPAIGN
    |--------------------------------------------------------------------------
    */
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

        $campaign = Campaign::create($data);

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

    /*
    |--------------------------------------------------------------------------
    | UPDATE CAMPAIGN
    |--------------------------------------------------------------------------
    */
    public function updateCampaign(UpdateCampaignRequest $request, $id)
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

        unset($data['start_date']);

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

    /*
    |--------------------------------------------------------------------------
    | DELETE CAMPAIGN
    |--------------------------------------------------------------------------
    */
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
                'message' => 'Campaign not found.'
            ], 404);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully.'
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE CAMPAIGN
    |--------------------------------------------------------------------------
    */
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

   

    /*
    |--------------------------------------------------------------------------
    | GET CAMPAIGN DETAILS
    |--------------------------------------------------------------------------
    */
    public function getCampaignDetails($id)
    {
        $campaign = Campaign::with(['media', 'admin'])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

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
                'participation_type' => $campaign->participation_type,
                'accepts_donations' => $campaign->acceptsDonations(),
                'accepts_volunteers' => $campaign->acceptsVolunteers(),
                'status' => $campaign->status,
                'amount_needed' => $campaign->amount_needed,
                'amount_collected' => $campaign->amount_collected,
                'volunteers_needed' => $campaign->volunteers_needed,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
                'progress' => $campaign->progress,
                'time_remaining' => $campaign->time_remaining,
                'approved_volunteers_count' => $approvedVolunteers,
                'media' => $campaign->media,
            ]
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL CAMPAIGNS — مع فلترة
    |--------------------------------------------------------------------------
    */
    public function getCampaigns(Request $request)
    {
        $query = Campaign::with(['media', 'admin']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 🔥 فلترة حسب نوع المشاركة
        if ($request->filled('participation_type')) {
            $query->where('participation_type', $request->participation_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereNotIn('status', ['closed', 'cancelled', 'expired']);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $campaigns = $query->paginate($request->get('per_page', 10));

        $campaigns->getCollection()->transform(function ($campaign) {
            return [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'type' => $campaign->type,
                'participation_type' => $campaign->participation_type,
                'accepts_donations' => $campaign->acceptsDonations(),
                'accepts_volunteers' => $campaign->acceptsVolunteers(),
                'status' => $campaign->status,
                'amount_needed' => $campaign->amount_needed,
                'amount_collected' => $campaign->amount_collected,
                'progress' => $campaign->progress,
                'time_remaining' => $campaign->time_remaining,
                'volunteers_needed' => $campaign->volunteers_needed,
                'volunteers_joined' => $campaign->volunteers_joined,
                'media' => $campaign->media,
            ];
        });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | GET CAMPAIGN TYPES
    |--------------------------------------------------------------------------
    */
    public function getCampaignTypes()
    {
        return response()->json([
            'success' => true,
            'types' => [
                'educational',
                'medical',
                'humanitarian',
                'environmental',
            ],
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | GET PARTICIPATION TYPES (جديد) — مفيد للفرونت
    |--------------------------------------------------------------------------
    */
    public function getParticipationTypes()
    {
        return response()->json([
            'success' => true,
            'participation_types' => [
                'donation_only',
                'volunteer_only',
                'donation_and_volunteer',
            ],
        ], 200);
    }
}