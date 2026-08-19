<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignDisbursalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\VolunteerHourController;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::post('/signup', [UserController::class, 'signUp']);
Route::post('/signin', [UserController::class, 'signIn']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [UserController::class, 'signOut']);
    Route::get('/userprofile', [UserController::class, 'profile']);
    Route::post('/userprofile/update', [UserController::class, 'updateProfile']);
    Route::post('/updateFcmToken', [UserController::class, 'updateFcmToken']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('storepatient', [RequestController::class, 'storePatientRequest']);
        Route::post('storeorphan', [RequestController::class, 'storeOrphanRequest']);
        Route::post('storeschool', [RequestController::class, 'storeSchoolRequest']);
        Route::post('storeuniversity', [RequestController::class, 'storeUniversityRequest']);
        Route::get('getpendingrequests', [RequestController::class, 'getPendingRequests']);
        Route::get('getpendingpatients', [RequestController::class, 'getPendingPatients']);
        Route::get('getpendingorphans', [RequestController::class, 'getPendingOrphans']);
        Route::get('getpendingschools', [RequestController::class, 'getPendingSchool']);
        Route::get('getpendinguniversities', [RequestController::class, 'getPendingUniversity']);

        Route::put('closeRequest/{id}', [RequestController::class, 'closeRequest']);
        Route::put('acceptRequest/{id}', [RequestController::class, 'acceptRequest']);
        Route::patch('rejectRequest/{requestId}', [RequestController::class, 'rejectRequest']);
    });




    Route::get('/check', function () {
        return [
            'auth_id' => Auth::id(),
            'auth_user' => Auth::user(),
            'token' => request()->bearerToken(),
        ];
    });
});


Route::post('/beneficiaries/store', [BeneficiaryController::class, 'store']);
Route::get('/beneficiaries', [BeneficiaryController::class, 'index']);
Route::put('/beneficiaries/update/{id}', [BeneficiaryController::class, 'update']);
Route::delete('/beneficiaries/delete/{id}', [BeneficiaryController::class, 'destroy']);



Route::get('getCampaignDetails/{id}', [CampaignController::class, 'getCampaignDetails']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/quickDonate', [DonationController::class, 'quickDonateToAssociation']);
    Route::post('/donate/{type}/{id}', [DonationController::class, 'donate'])
        ->where('type', 'request|campaign');
    Route::get('/mydonations', [DonationController::class, 'myDonationsSummary']);
    Route::get('/myDonationsFull', [UserController::class, 'myDonationsFull']);
});
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {

    // نموذج التطوع العام
    Route::post('/volunteer/apply', [VolunteerController::class, 'submitVolunteerApplication']);
    Route::get('/volunteer/me',     [VolunteerController::class, 'getMyVolunteerApplication']);
    Route::get('/volunteer/skills', [VolunteerController::class, 'getVolunteerSkillsList']);

    // مراجعة طلبات التطوع العامة (أدمن) — لازم قبل route فيه {volunteerId}
    Route::get('/volunteer-applications/pending',   [VolunteerController::class, 'getPendingVolunteerApplications']);
    Route::get('/volunteer-applications/approved',  [VolunteerController::class, 'getApprovedVolunteerApplications']);
    Route::get('/volunteer-applications/rejected',  [VolunteerController::class, 'getRejectedVolunteerApplications']);
    Route::get('/volunteer-applications/suspended', [VolunteerController::class, 'getSuspendedVolunteerApplications']);
    //تابع متطوعين الجمعيه
    Route::get('/volunteer-applications/filter',    [VolunteerController::class, 'filterVolunteerApplications']);
    // قبول/رفض طلبات التطوع العامة (أدمن)
    Route::patch('/volunteer-applications/{volunteerId}', [VolunteerController::class, 'reviewVolunteerApplication']);

    // تعليق متطوع عام (أدمن فقط)
    Route::post('/volunteersuspend/{volunteerId}', [VolunteerController::class, 'suspendGeneralVolunteer']);

    // التطوع لحملة
    Route::post('/campaigns/volunteer/{campaignId}', [VolunteerController::class, 'volunteerForCampaign']);

    // متطوعين حملة معينة حسب الحالة — لازم قبل route فيه {volunteerId}
    Route::get('/campaigns/{campaignId}/volunteers/pending',  [VolunteerController::class, 'getCampaignPendingVolunteers']);
    Route::get('/campaigns/{campaignId}/volunteers/approved', [VolunteerController::class, 'getCampaignApprovedVolunteers']);
    Route::get('/campaigns/{campaignId}/volunteers/rejected', [VolunteerController::class, 'getCampaignRejectedVolunteers']);
    Route::get('/campaigns/{campaignId}/volunteers', [VolunteerController::class, 'getCampaignVolunteers']);

    // قبول/رفض متطوع بحملة (أدمن)
    Route::patch('/campaigns/{campaignId}/volunteers/{volunteerId}', [VolunteerController::class, 'updateVolunteerStatus']);

    // ساعات العمل
    Route::post('/campaigns/{campaignId}/volunteers/{volunteerId}/hours', [VolunteerController::class, 'addVolunteerHours']);
    Route::get('/campaigns/{campaignId}/volunteers/{volunteerId}/hours',  [VolunteerController::class, 'getVolunteerHoursInCampaign']);

    // حملات/ساعات المستخدم الحالي
    Route::get('/my-campaigns/approved', [VolunteerController::class, 'getMyApprovedCampaigns']);
    Route::get('/my-campaigns/pending',  [VolunteerController::class, 'getMyPendingCampaigns']);
    Route::get('/my-volunteer-hours',    [VolunteerController::class, 'getMyVolunteerHours']);
    Route::get('/approved-general-volunteers', [VolunteerController::class, 'getApprovedGeneralVolunteerApplications']);
    //new 8/19 
    //from
    //تابع متطوعين الحملات
    Route::get('/volunteers/summary', [VolunteerController::class, 'getAllVolunteersSummary']);

    // حملات متطوع معين
    Route::get('/volunteerscampaigns/{volunteerId}', [VolunteerController::class, 'getVolunteerCampaigns']);
    //to
});
//Dashboard routes dashboard 
//Dashboard routes
//Dashboard routes
//Dashboard routes
// Dashboard routes
//-------------------------------------------------------------------------------
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/createEmployee', [UserController::class, 'createEmployee']);
    Route::post('/approveUser/{id}', [UserController::class, 'approveUser']);
    Route::post('/setPending/{id}', [UserController::class, 'setPending']);

    Route::get('/getAllPendingUsers', [UserController::class, 'getAllPendingUsers']);
    Route::get('/getAllNonUserAccounts', [UserController::class, 'getAllNonUserAccounts']);
    Route::post('/changePassword', [UserController::class, 'changePassword']);

    Route::post('/promoteUser/{id}', [UserController::class, 'promoteUser']);
    Route::post('/demoteUser/{id}', [UserController::class, 'demoteUser']);
    Route::get('/listByRole/{role}', [UserController::class, 'listByRole']);


    Route::get('/getUserById/{id}', [UserController::class, 'getUserById']);
    Route::get('/getAllUsers', [UserController::class, 'getallUsers']);
    
    Route::post('/addBalanceToUser/{userId}', [UserController::class, 'addBalanceToUser']);
    //new 8/19
    Route::get('/getAdminWallet', [UserController::class, 'getAdminWallet']);
    //new
    Route::post('/setRejected/{id}', [UserController::class, 'setRejected']);
    Route::delete('/deleteUser/{id}', [UserController::class, 'deleteUser']);
    Route::post('/userprofile/update', [UserController::class, 'updateProfile']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    //salam
    Route::post('storepatient', [RequestController::class, 'storePatientRequest']);
    Route::post('storeorphan', [RequestController::class, 'storeOrphanRequest']);
    Route::post('storeschool', [RequestController::class, 'storeSchoolRequest']);
    Route::post('storeuniversity', [RequestController::class, 'storeUniversityRequest']);
    Route::get('/governorates', [LocationController::class, 'getGovernorates']);
    Route::get('/governorates/{id}/regions', [LocationController::class, 'getRegions']);
    //salam
    Route::get('getpendingrequests', [RequestController::class, 'getPendingRequests']);
    Route::get('getpendingpatients', [RequestController::class, 'getPendingPatients']);
    Route::get('getpendingorphans', [RequestController::class, 'getPendingOrphans']);
    Route::get('getpendingschools', [RequestController::class, 'getPendingSchool']);
    Route::get('getpendinguniversities', [RequestController::class, 'getPendingUniversity']);

    Route::put('closeRequest/{id}', [RequestController::class, 'closeRequest']);
    Route::put('acceptRequest/{id}', [RequestController::class, 'acceptRequest']);
    Route::patch('/rejectRequest/{requestId}', [RequestController::class, 'rejectRequest']);

    //marwa
    Route::get('getopenacceptedrequests', [RequestController::class, 'getOpenAcceptedRequests']);
    Route::get('getopenacceptedpatients', [RequestController::class, 'getOpenAcceptedPatients']);
    Route::get('getopenacceptedorphans', [RequestController::class, 'getOpenAcceptedOrphans']);
    Route::get('getopenacceptedschools', [RequestController::class, 'getOpenAcceptedSchoolStudents']);
    Route::get('getopenaccepteduniversities', [RequestController::class, 'getOpenAcceptedUniversityStudents']);
    Route::get('filterRequests', [RequestController::class, 'filterRequests']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    //statistics and KPIs
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/monthly-donations', [DashboardController::class, 'monthlyDonations']);
    Route::get('/dashboard/cases', [DashboardController::class, 'casesByStatus']);
    Route::get('/dashboard/recent-donations', [DashboardController::class, 'recentDonations']);
    Route::get('/dashboard/top-campaigns', [DashboardController::class, 'topCampaigns']);
    Route::get('/dashboard/cases-by-governorate', [DashboardController::class, 'casesByGovernorate']);
});
Route::get('/governorates', [LocationController::class, 'getGovernorates']);
Route::get('/governorates/{id}/regions', [LocationController::class, 'getRegions']);

//new 
//campaings for sedra
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/storecampaign', [CampaignController::class, 'createCampaign']);
    Route::put('/updatecampaign/{id}', [CampaignController::class, 'updateCampaign']);
    Route::delete('/deletecampaign/{id}', [CampaignController::class, 'deleteCampaign']);
    Route::patch('/closecampaign/{id}', [CampaignController::class, 'closeCampaign']);
    Route::get('getParticipationTypes', [CampaignController::class, 'getParticipationTypes']);
    Route::get('/campaignsfilter', [CampaignController::class, 'filterCampaigns']);
    Route::get('/campaigns', [CampaignController::class, 'getCampaigns']);
    Route::get('/campaigns/types', [CampaignController::class, 'getCampaignTypes']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'getCampaignDetails']);
});
//Marwa
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/campaignsfilter', [CampaignController::class, 'filterCampaigns']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('getopenacceptedpatients', [RequestController::class, 'getOpenAcceptedPatients']);
    Route::get('getopenacceptedorphans', [RequestController::class, 'getOpenAcceptedOrphans']);
    Route::get('getopenacceptedschools', [RequestController::class, 'getOpenAcceptedSchoolStudents']);
    Route::get('getopenaccepteduniversities', [RequestController::class, 'getOpenAcceptedUniversityStudents']);
    Route::get('getopenacceptedrequests', [RequestController::class, 'getOpenAcceptedRequests']);
    Route::get('filterRequests', [RequestController::class, 'filterRequests']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/quickDonate', [DonationController::class, 'quickDonateToAssociation']);
    Route::post('/donate/{type}/{id}', [DonationController::class, 'donate'])
        ->where('type', 'request|campaign');
    Route::get('/donations/all', [DonationController::class, 'getAllDonations']);
});

// Campaign Disbursal APIs
Route::middleware(['auth:sanctum'])->group(function () {
    // الصرف الفردي للحملات والطلبات
    Route::post('/disburse/campaign/{campaignId}', [CampaignDisbursalController::class, 'disburseCampaign']);
    Route::get('/disburse/campaigns/pending', [CampaignDisbursalController::class, 'getPendingCampaignDisbursements']);
    Route::post('/disburse/request/{requestId}', [CampaignDisbursalController::class, 'disburseRequest']);
    Route::get('/disburse/requests/pending', [CampaignDisbursalController::class, 'getPendingRequestDisbursements']);
    Route::post('/disburse/campaigns-all', [CampaignDisbursalController::class, 'disburseCampaigns']);
    Route::post('/disburse/requests-all', [CampaignDisbursalController::class, 'disburseRequests']);
    Route::post('/disburse/all', [CampaignDisbursalController::class, 'disburseAll']);
});
Route::middleware('auth:sanctum')->group(function () {
    // تقارير الصرف
    Route::get('/reports/campaigns-disbursement/{year}/{month}', [CampaignDisbursalController::class, 'getCampaignsDisbursementReport']);
    Route::get('/reports/requests-disbursement/{year}/{month}', [CampaignDisbursalController::class, 'getRequestsDisbursementReport']);
    Route::get('/reports/complete-disbursement/{year}/{month}', [CampaignDisbursalController::class, 'getCompleteDisbursementReport']);
});
