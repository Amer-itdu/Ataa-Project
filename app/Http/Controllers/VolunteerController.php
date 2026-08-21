<?php

namespace App\Http\Controllers;

use App\Http\Requests\VolunteerForCampaignRequest;
use App\Http\Requests\AddVolunteerHoursRequest;
use App\Http\Requests\VolunteerApplicationRequest;
use App\Models\Campaign;
use App\Models\Volunteer;
use App\Models\VolunteerHour;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class VolunteerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1) تقديم طلب تطوع عام (نموذج التطوع)
    |--------------------------------------------------------------------------
    */
    public function submitVolunteerApplication(VolunteerApplicationRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to apply.'
            ], 401);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot submit volunteer applications.'
            ], 403);
        }

        // فقط إذا كان عنده طلب عام سابق فعلاً بنمنعه
        if ($user->volunteer && $user->volunteer->general_application) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a volunteer application.',
                'status'  => $user->volunteer->status,
            ], 409);
        }

        $validated = $request->validated();

        // updateOrCreate: لو عنده سجل من تطوع لحملة سابقًا، نكمّله بدل ما نعمل تكرار
        $volunteer = Volunteer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone'               => $validated['phone'],
                'gender'              => $validated['gender'],
                'occupation'          => $validated['occupation'] ?? null,
                'governorate_id'      => $validated['governorate_id'],
                'skills'              => $validated['skills'],
                'availability'        => $validated['availability'] ?? null,
                'description'         => $validated['description'],
                'agreed_to_terms'     => true,
                'agreed_to_terms_at'  => now(),
                'status'              => Volunteer::normalizeStatus('pending'),
                'general_application' => true,
            ]
        );

        return response()->json([
            'success'   => true,
            'message'   => 'Your volunteer application has been submitted and is under review.',
            'volunteer' => $volunteer,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 2) حالة طلب التطوع الخاص بالمستخدم الحالي
    |--------------------------------------------------------------------------
    */
    public function getMyVolunteerApplication()
    {
        $user = Auth::user();

        if (!$user || !$user->volunteer || !$user->volunteer->general_application) {
            return response()->json([
                'success'     => true,
                'has_applied' => false,
                'volunteer'   => null,
            ], 200);
        }

        $volunteer = $user->volunteer->load('governorate');

        return response()->json([
            'success'       => true,
            'has_applied'   => true,
            'volunteer'     => $volunteer,
            'skills_labels' => collect($volunteer->skills)
                ->map(fn($key) => Volunteer::skillsList()[$key] ?? $key)
                ->values(),
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 3) قبول / رفض / تعليق طلب تطوع عام (أدمن)
    |--------------------------------------------------------------------------
    */
 public function reviewVolunteerApplication(\Illuminate\Http\Request $request, $volunteerId)
{
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Only admins can review volunteer applications.'
        ], 403);
    }

    $request->validate([
        'status' => 'required|in:approved,rejected,pending,suspended',
    ]);

    $volunteer = Volunteer::find($volunteerId);

    if (!$volunteer) {
        return response()->json([
            'success' => false,
            'message' => 'Volunteer application not found.'
        ], 404);
    }

    // لا يمكن مراجعة سجل لم يقدّم أصلاً طلب تطوع عام
    if (!$volunteer->general_application) {
        return response()->json([
            'success' => false,
            'message' => 'This record is not a general volunteer application.'
        ], 400);
    }

    if ($volunteer->status === $request->status) {
        return response()->json([
            'success' => false,
            'message' => "Application is already {$request->status}."
        ], 400);
    }

    $volunteer->update(['status' => Volunteer::normalizeStatus($request->status)]);

    // 🔔 إرسال إشعار للمتطوع
    try {
        if ($volunteer->user) {
            $statusMessages = [
                'approved'  => 'تم قبول طلب تطوعك، مبروك!',
                'rejected'  => 'نأسف، تم رفض طلب تطوعك.',
                'pending'   => 'طلب تطوعك قيد المراجعة.',
                'suspended' => 'تم تعليق طلب تطوعك.',
            ];

            app(NotificationService::class)->sendToUser(
                $volunteer->user,
                'تحديث حالة طلب التطوع',
                $statusMessages[$request->status] ?? 'تم تحديث حالة طلب تطوعك.'
            );
        }
    } catch (\Exception $e) {
        Log::warning('Notification failed but volunteer application reviewed: ' . $e->getMessage());
    }

    return response()->json([
        'success'   => true,
        'message'   => "Volunteer application {$request->status} successfully.",
        'volunteer' => $volunteer,
    ], 200);
}

    /*
    |--------------------------------------------------------------------------
    | 4) قوائم طلبات التطوع العامة حسب الحالة (أدمن)
    |--------------------------------------------------------------------------
    */
    public function getPendingVolunteerApplications()
    {
        return $this->getVolunteerApplicationsByStatus('pending');
    }

    public function getApprovedVolunteerApplications()
    {
        return $this->getVolunteerApplicationsByStatus('approved');
    }

    public function getApprovedGeneralVolunteerApplications()
    {
        $volunteers = Volunteer::where('status', 'approved')
            ->where('general_application', true)
            ->with(['user:id,first_name,last_name,email,phone', 'governorate:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($v) => $this->formatVolunteerApplication($v));

        return response()->json([
            'success' => true,
            'count'   => $volunteers->count(),
            'data'    => $volunteers,
        ], 200);
    }

    public function getRejectedVolunteerApplications()
    {
        return $this->getVolunteerApplicationsByStatus('rejected');
    }

    public function getSuspendedVolunteerApplications()
    {
        return $this->getVolunteerApplicationsByStatus('suspended');
    }

    private function getVolunteerApplicationsByStatus(string $status)
    {
        $volunteers = Volunteer::where('status', $status)
            ->where('general_application', true)
            ->with(['user:id,first_name,last_name,email,phone', 'governorate:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($v) => $this->formatVolunteerApplication($v));

        return response()->json([
            'success' => true,
            'count'   => $volunteers->count(),
            'data'    => $volunteers
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 5) فلترة عامة لطلبات التطوع (أدمن) — دالة مستقلة إضافية
    |--------------------------------------------------------------------------
    */
    public function filterVolunteerApplications(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'status'         => 'nullable|in:pending,approved,rejected,suspended',
            'gender'         => 'nullable|in:male,female',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'skill' => 'nullable|in:' . implode(',', Volunteer::skillsList()),
        ]);

        $query = Volunteer::with(['user:id,first_name,last_name,email,phone', 'governorate:id,name'])
            ->where('general_application', true);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }

        if ($request->filled('skill')) {
            $query->whereJsonContains('skills', $request->skill);
        }

        $volunteers = $query->get()->map(fn($v) => $this->formatVolunteerApplication($v));

        return response()->json([
            'success' => true,
            'count'   => $volunteers->count(),
            'data'    => $volunteers
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 6) قائمة المهارات الثابتة (مساعدة للفرونت)
    |--------------------------------------------------------------------------
    */
    public function getVolunteerSkillsList()
    {
        return response()->json([
            'success' => true,
            'skills'  => Volunteer::skillsList(),
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 7) التطوع لحملة معينة
    |--------------------------------------------------------------------------
    */
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

        if ($campaign->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This campaign is not open for volunteering.'
            ], 400);
        }

        if (
            $campaign->volunteers_needed !== null
            && $campaign->volunteers_joined >= $campaign->volunteers_needed
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign has reached its volunteer limit.'
            ], 400);
        }

        // التطوع لحملة مستقل عن الطلب العام، لكن الموافقة على الشروط إلزامية
        $volunteer = $user->volunteer;

        if ($volunteer) {
            // موجود مسبقًا (من طلب عام أو حملة سابقة) — نسجل آخر موافقة
            $volunteer->update([
                'agreed_to_terms'    => true,
                'agreed_to_terms_at' => now(),
            ]);
        } else {
            $volunteer = Volunteer::create([
                'user_id'             => $user->id,
                'agreed_to_terms'     => true,
                'agreed_to_terms_at'  => now(),
                'general_application' => false,
                'status'              => 'approved',
            ]);
        }

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
            'available_time' => null,
            'notes'          => $request->notes ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Volunteer request submitted successfully.',
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 8) جميع المتطوعين لحملة معينة (كل الحالات)
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
            'success'    => true,
            'volunteers' => $volunteers
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 9) متطوعين حملة معينة حسب الحالة (دوال منفصلة)
    |--------------------------------------------------------------------------
    */
    public function getCampaignPendingVolunteers($campaignId)
    {
        return $this->getCampaignVolunteersByStatus($campaignId, 'pending');
    }

    public function getCampaignApprovedVolunteers($campaignId)
    {
        return $this->getCampaignVolunteersByStatus($campaignId, 'approved');
    }

    public function getCampaignRejectedVolunteers($campaignId)
    {
        return $this->getCampaignVolunteersByStatus($campaignId, 'rejected');
    }

    private function getCampaignVolunteersByStatus($campaignId, string $status)
    {
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.'
            ], 404);
        }

        $volunteers = $campaign->volunteers()
            ->wherePivot('status', $status)
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
    | 10) جميع الحملات المقبول فيها المستخدم الحالي كمتطوع
    |--------------------------------------------------------------------------
    */
    public function getMyApprovedCampaigns()
    {
        $user = Auth::user();

        if (!$user || !$user->volunteer) {
            return response()->json([
                'success'   => true,
                'campaigns' => []
            ], 200);
        }

        $campaigns = $user->volunteer->campaigns()
            ->wherePivot('status', 'approved')
            ->with('media')
            ->get()
            ->map(fn($c) => $this->formatCampaignWithPivot($c));

        return response()->json([
            'success'   => true,
            'campaigns' => $campaigns
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | 11) تطوعات المستخدم الحالي اللي لسا pending
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
    | 12) قبول / رفض متطوع بحملة معينة (أدمن)
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

    if ($oldStatus === $newStatus) {
        return response()->json([
            'success' => false,
            'message' => "Volunteer is already {$newStatus}."
        ], 400);
    }

    // 🔥 تحديث الـ pivot
    $campaign->volunteers()->updateExistingPivot($volunteerId, [
        'status'        => $newStatus,
        'assigned_date' => $newStatus === 'approved' ? now() : null,
    ]);

    $campaign->refresh();
    $this->checkCampaignCompletion($campaign);

    // 🔔 إرسال إشعار للمتطوع
    try {
        if ($pivot->user) {
            $statusMessages = [
                'approved' => 'تم قبولك للتطوع في حملة "' . $campaign->title . '".',
                'rejected' => 'نأسف، تم رفض طلب تطوعك لحملة "' . $campaign->title . '".',
                'pending'  => 'طلب تطوعك لحملة "' . $campaign->title . '" قيد المراجعة.',
            ];

            app(NotificationService::class)->sendToUser(
                $pivot->user,
                'تحديث حالة التطوع بالحملة',
                $statusMessages[$newStatus] ?? 'تم تحديث حالة تطوعك.'
            );
        }
    } catch (\Exception $e) {
        Log::warning('Notification failed but volunteer status updated: ' . $e->getMessage());
    }

    return response()->json([
        'success'           => true,
        'message'           => "Volunteer {$newStatus} successfully.",
        'volunteers_joined' => $campaign->volunteers_joined,
    ], 200);
}

    /*
    |--------------------------------------------------------------------------
    | 13) إضافة ساعات عمل لمتطوع بحملة معينة (field_worker فقط)
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
            'activity_description'  => $validated['activity_description'] ?? null,
        ]);

        return response()->json([
            'success'                 => true,
            'message'                 => 'Volunteer hours logged successfully.',
            'entry'                   => $entry,
            'total_hours_in_campaign' => $volunteer->hours()->where('campaign_id', $campaign->id)->sum('hours'),
            'total_hours_overall'     => $volunteer->totalHours(),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 14) عرض ساعات متطوع بحملة معينة
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
    | 15) جميع ساعات تطوع المستخدم الحالي
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

    public function issueVolunteerCertificate()
    {
        $user = Auth::user();
        $volunteer = $user?->volunteer;

        if (!$volunteer) {
            return response()->json([
                'success' => false,
                'message' => 'Volunteer profile not found.',
            ], 404);
        }

        $totalHours = (float) $volunteer->totalHours();
        if ($totalHours < 100) {
            return response()->json([
                'success' => false,
                'message' => 'At least 100 volunteer hours are required.',
                'total_hours' => $totalHours,
                'required_hours' => 100,
            ], 403);
        }

        if (!$volunteer->certificate_token) {
            $volunteer->update([
                'certificate_token' => (string) Str::uuid(),
                'certificate_issued_at' => now(),
            ]);
        }

        $data = [
            'token' => $volunteer->certificate_token,
            'certificate_number' => 'VOL-' . str_pad((string) $volunteer->id, 6, '0', STR_PAD_LEFT),
            'verification_url' => url('/api/volunteers/certificates/' . $volunteer->certificate_token),
            'volunteer_name' => trim($user->first_name . ' ' . $user->last_name),
            'total_hours' => $totalHours,
            'issued_at' => $volunteer->certificate_issued_at?->format('Y-m-d'),
        ];

        return Pdf::loadView('certificates.volunteer', $data)
            ->setPaper('a4', 'landscape')
            ->download('volunteer-certificate-' . $data['certificate_number'] . '.pdf');
    }

    public function verifyVolunteerCertificate(string $token)
    {
        $volunteer = Volunteer::with('user')
            ->where('certificate_token', $token)
            ->first();

        if (!$volunteer) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found or token is invalid.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'certificate' => [
                'owner' => $volunteer->user ? [
                    'id' => $volunteer->user->id,
                    'name' => trim($volunteer->user->first_name . ' ' . $volunteer->user->last_name),
                    'email' => $volunteer->user->email,
                ] : null,
                'volunteer_name' => $volunteer->user
                    ? trim($volunteer->user->first_name . ' ' . $volunteer->user->last_name)
                    : null,
                'total_hours' => (float) $volunteer->totalHours(),
                'issued_at' => $volunteer->certificate_issued_at?->format('Y-m-d H:i:s'),
                'token' => $volunteer->certificate_token,
            ],
        ]);
    }
    public function getAllVolunteerApplications(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view all volunteers.'
            ], 403);
        }

        $request->validate([
            'sort_by'  => 'nullable|in:created_at,status,name',
            'sort_dir' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Volunteer::with(['user:id,first_name,last_name,email,phone', 'governorate:id,name']);

        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        if ($sortBy === 'name') {
            // ترتيب بالاسم بدون مباشرة (لأنه في join)
            $query->leftJoin('users', 'volunteers.user_id', '=', 'users.id')
                ->select('volunteers.*')
                ->orderBy('users.first_name', $sortDir)
                ->orderBy('users.last_name', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $volunteers = $query->paginate($request->get('per_page', 15));

        $volunteers->getCollection()->transform(function ($v) {
            return [
                'volunteer_id'  => $v->id,
                'name'          => trim($v->user->first_name . ' ' . $v->user->last_name),
                'email'         => $v->user->email,
                'phone'         => $v->phone,
                'gender'        => $v->gender,
                'occupation'    => $v->occupation,
                'governorate'   => $v->governorate->name ?? null,
                'skills'        => $v->skills,
                'availability'  => $v->availability,
                'status'        => $v->status,
                'applied_at'    => $v->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $volunteers->total(),
            'data'    => $volunteers,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — تنسيق الرد
    |--------------------------------------------------------------------------
    */
    private function formatVolunteerApplication($v): array
    {
        return [
            'volunteer_id'  => $v->id,
            'name'          => trim($v->user->first_name . ' ' . $v->user->last_name),
            'email'         => $v->user->email,
            'phone'         => $v->phone,
            'gender'        => $v->gender,
            'occupation'    => $v->occupation,
            'governorate'   => $v->governorate->name ?? null,
            'skills'              => $v->skills,
            'availability'        => $v->availability,
            'description'         => $v->description,
            'status'              => $v->status,
            'general_application' => (bool) $v->general_application,
            'applied_at'          => $v->created_at,
        ];
    }

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

    /*
    |--------------------------------------------------------------------------
    | 15) تعليق متطوع عام (أدمن فقط)
    |--------------------------------------------------------------------------
    */
  public function suspendGeneralVolunteer(\Illuminate\Http\Request $request, $volunteerId)
{
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Only admins can suspend volunteers.'
        ], 403);
    }

    $volunteer = Volunteer::find($volunteerId);

    if (!$volunteer) {
        return response()->json([
            'success' => false,
            'message' => 'Volunteer not found.'
        ], 404);
    }

    if (!$volunteer->general_application) {
        return response()->json([
            'success' => false,
            'message' => 'This record is not a general volunteer application.'
        ], 400);
    }

    if ($volunteer->status === 'suspended') {
        return response()->json([
            'success' => false,
            'message' => 'Volunteer is already suspended.'
        ], 400);
    }

    $volunteer->update([
        'status' => 'suspended'
    ]);

    // 🔔 إرسال إشعار للمتطوع
    try {
        if ($volunteer->user) {
            app(NotificationService::class)->sendToUser(
                $volunteer->user,
                'تعليق طلب التطوع',
                'تم تعليق طلب تطوعك من قبل الإدارة.'
            );
        }
    } catch (\Exception $e) {
        Log::warning('Notification failed but volunteer suspended: ' . $e->getMessage());
    }

    return response()->json([
        'success'   => true,
        'message'   => 'Volunteer has been suspended successfully.',
        'volunteer' => $volunteer->load('user:id,first_name,last_name,email,phone', 'governorate:id,name'),
    ], 200);
}



    /**
     * احصل على جميع المتطوعين للحملات (general_application = 0) مع عدد الحملات والساعات
     */ public function getAllVolunteersSummary(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view all volunteers.'
            ], 403);
        }

        $request->validate([
            'sort_by'  => 'nullable|in:created_at,total_hours,campaigns_count',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        // 🔥 الـ syntax الصحيح
        $query = Volunteer::where('general_application', 0)
            ->whereHas('campaigns', function ($q) {
                $q->where('volunteer_campaign.status', 'approved');  // ✅ الجدول.الحقل
            })
            ->with([
                'user:id,first_name,last_name,email,phone',
                'governorate:id,name',
                'campaigns' => function ($q) {
                    $q->where('volunteer_campaign.status', 'approved');
                },
                'hours'
            ]);

        $volunteers = $query->get();

        $formattedVolunteers = $volunteers->map(function (Volunteer $volunteer) {
            $campaignsCount = $volunteer->campaigns()
                ->where('volunteer_campaign.status', 'approved')
                ->count();

            $totalHours = $volunteer->totalHours();

            return [
                'volunteer_id'    => $volunteer->id,
                'name'            => trim($volunteer->user->first_name . ' ' . $volunteer->user->last_name),
                'email'           => $volunteer->user->email,
                'phone'           => $volunteer->user->phone ?? 'N/A',
                'gender'          => $volunteer->gender,
                'occupation'      => $volunteer->occupation,
                'governorate'     => $volunteer->governorate->name ?? null,
                'skills'          => $volunteer->skills,
                'status'          => $volunteer->status,
                'campaigns_count' => $campaignsCount,
                'total_hours'     => round($totalHours, 2),
                'applied_at'      => $volunteer->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        if ($sortBy === 'total_hours') {
            $formattedVolunteers = $formattedVolunteers->sortBy('total_hours');
            if ($sortDir === 'desc') {
                $formattedVolunteers = $formattedVolunteers->reverse();
            }
        } elseif ($sortBy === 'campaigns_count') {
            $formattedVolunteers = $formattedVolunteers->sortBy('campaigns_count');
            if ($sortDir === 'desc') {
                $formattedVolunteers = $formattedVolunteers->reverse();
            }
        } else {
            $formattedVolunteers = $formattedVolunteers->sortByDesc('applied_at');
            if ($sortDir === 'asc') {
                $formattedVolunteers = $formattedVolunteers->reverse();
            }
        }

        return response()->json([
            'success' => true,
            'count'   => $formattedVolunteers->count(),
            'data'    => $formattedVolunteers->values()
        ], 200);
    }

    /**
     * احصل على حملات متطوع معين (general_application = 0)
     */
    public function getVolunteerCampaigns($volunteerId, Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view volunteer campaigns.'
            ], 403);
        }

        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'sort_by' => 'nullable|in:created_at,title',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        // 🔥 المتطوع لازم يكون general_application = 0
        $volunteer = Volunteer::where('id', $volunteerId)
            ->where('general_application', 0)
            ->with('user:id,first_name,last_name')
            ->first();

        if (!$volunteer) {
            return response()->json([
                'success' => false,
                'message' => 'Volunteer not found or invalid.'
            ], 404);
        }

        $query = $volunteer->campaigns();

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->wherePivot('status', $request->status);
        }

        $campaigns = $query->get();

        // تنسيق البيانات
        $formattedCampaigns = $campaigns->map(function ($campaign) use ($volunteer) {
            $hoursInCampaign = $volunteer->hours()
                ->where('campaign_id', $campaign->id)
                ->sum('hours');

            return [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'participation_type' => $campaign->participation_type,
                'amount_needed' => round($campaign->amount_needed, 2),
                'amount_collected' => round($campaign->amount_collected, 2),
                'progress' => $campaign->progress,
                'volunteers_needed' => $campaign->volunteers_needed,
                'volunteers_joined' => $campaign->volunteers_joined,
                'volunteer_status' => $campaign->pivot->status,
                'assigned_date' => $campaign->pivot->assigned_date,
                'available_time' => $campaign->pivot->available_time,
                'notes' => $campaign->pivot->notes,
                'hours_in_campaign' => round($hoursInCampaign, 2),
            ];
        });

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        if ($sortBy === 'title') {
            $formattedCampaigns = $formattedCampaigns->sortBy('title');
        } else {
            $formattedCampaigns = $formattedCampaigns->sortByDesc('assigned_date');
        }

        if ($sortDir === 'asc') {
            $formattedCampaigns = $formattedCampaigns->reverse();
        }

        return response()->json([
            'success' => true,
            'volunteer' => [
                'volunteer_id' => $volunteer->id,
                'name' => trim($volunteer->user->first_name . ' ' . $volunteer->user->last_name),
            ],
            'campaigns_count' => $formattedCampaigns->count(),
            'campaigns' => $formattedCampaigns->values()
        ], 200);
    }
}
