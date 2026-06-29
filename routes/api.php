<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CampaignController;
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

// Public authentication routes 
//User routes

Route::post('/signup', [UserController::class, 'signUp']);
Route::post('/signin', [UserController::class, 'signIn']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [UserController::class, 'signOut']);
    Route::get('/userprofile', [UserController::class, 'profile']);
    Route::post('/userprofile/update', [UserController::class, 'updateProfile']);
    //Amer
    //status management for users by admin and sub_admin    

    //dashbored

    Route::get('/myDonationsFull', [UserController::class, 'myDonationsFull']);
    //dashboard


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
        Route::get('getopenacceptedrequests', [RequestController::class, 'getOpenAcceptedRequests']);
        Route::get('getopenacceptedpatients', [RequestController::class, 'getOpenAcceptedPatients']);
        Route::get('getopenacceptedorphans', [RequestController::class, 'getOpenAcceptedOrphans']);
        Route::get('getopenacceptedschools', [RequestController::class, 'getOpenAcceptedSchoolStudents']);
        Route::get('getopenaccepteduniversities', [RequestController::class, 'getOpenAcceptedUniversityStudents']);
        Route::put('closeRequest/{id}', [RequestController::class, 'closeRequest']);
        Route::put('acceptRequest/{id}', [RequestController::class, 'acceptRequest']);
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
    Route::post('/storecampaign', [CampaignController::class, 'createCampaign']);
    Route::put('/updatecampaign/{id}', [CampaignController::class, 'updateCampaign']);
    Route::delete('/deletecampaign/{id}', [CampaignController::class, 'deleteCampaign']);
    Route::patch('/closecampaign/{id}', [CampaignController::class, 'closeCampaign']);
    Route::get('getParticipationTypes', [CampaignController::class, 'getParticipationTypes']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/quickDonate', [DonationController::class, 'quickDonateToAssociation']);
    Route::post('/donate/{type}/{id}', [DonationController::class, 'donate'])
        ->where('type', 'request|campaign');
    Route::get('/mydonations', [DonationController::class, 'myDonationsSummary']);
});
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {

    // التطوع
    Route::post('/volunteer/{campaignId}', [VolunteerController::class, 'volunteerForCampaign']);
    //
    Route::get('getCampaignPendingVolunteers/{campaignId}',  [VolunteerController::class, 'getCampaignPendingVolunteers']);
    Route::get('getCampaignApprovedVolunteers/{campaignId}', [VolunteerController::class, 'getCampaignApprovedVolunteers']);
    Route::get('getCampaignRejectedVolunteers/{campaignId}', [VolunteerController::class, 'getCampaignRejectedVolunteers']);
    //
    Route::get('getCampaignVolunteers/{campaignId}', [VolunteerController::class, 'getCampaignVolunteers']);
    Route::patch('updateVolunteerStatus/{campaignId}/{volunteerId}', [VolunteerController::class, 'updateVolunteerStatus']);

    // الحملات الخاصة بالمستخدم كمتطوع
    Route::get('/myapprovedcampaigns', [VolunteerController::class, 'getMyApprovedCampaigns']);
    Route::get('/mypendingcampaigns', [VolunteerController::class, 'getMyPendingCampaigns']);

    // ساعات العمل (field_worker فقط للإضافة)
    Route::post('addVolunteerHours/{campaignId}/{volunteerId}', [VolunteerController::class, 'addVolunteerHours']);
    Route::get('getVolunteerHoursInCampaign/{campaignId}/{volunteerId}', [VolunteerController::class, 'getVolunteerHoursInCampaign']);
    Route::get('getMyVolunteerHours', [VolunteerController::class, 'getMyVolunteerHours']);
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
