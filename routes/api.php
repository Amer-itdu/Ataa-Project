<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
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
    Route::put('/userprofile/update', [UserController::class, 'updateProfile']);
    //Amer
    //status management for users by admin and sub_admin    
    Route::post('/approveUser/{id}', [UserController::class, 'approveUser']);
    Route::post('/rejectUser/{id}', [UserController::class, 'rejectUser']);
    Route::post('/setPending/{id}', [UserController::class, 'setPending']);
    Route::get('/getAllPendingUsers', [UserController::class, 'getAllPendingUsers']);
    Route::get('/getAllNonUserAccounts', [UserController::class, 'getAllNonUserAccounts']);
    //dashbored
    Route::post('/createEmployee', [UserController::class, 'createEmployee']);
    Route::post('/changePassword', [UserController::class, 'changePassword']);

    Route::post('/addBalanceToUser/{userId}', [UserController::class, 'addBalanceToUser']);
    Route::get('/myDonationsFull', [UserController::class, 'myDonationsFull']);
    //dashboard
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/monthly-donations', [DashboardController::class, 'monthlyDonations']);
    Route::get('/dashboard/cases', [DashboardController::class, 'casesByStatus']);
    Route::get('/dashboard/recent-donations', [DashboardController::class, 'recentDonations']);
    Route::get('/dashboard/top-campaigns', [DashboardController::class, 'topCampaigns']);
    //
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    



    Route::post('/requests/patient', [RequestController::class, 'storePatientRequest']);
    Route::post('/requests/orphan', [RequestController::class, 'storeOrphanRequest']);
    Route::post('/requests/school', [RequestController::class, 'storeSchoolRequest']);
    Route::post('/requests/university', [RequestController::class, 'storeUniversityRequest']);


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


// Campaign routes
// Route::get('/campaigns', [CampaignController::class, 'getCampaigns']);
// Route::get('/campaigns/{id}', [CampaignController::class, 'getCampaign']);
Route::get('getCampaignDetails/{id}', [CampaignController::class, 'getCampaignDetails']);
Route::get('/getActiveCampaigns', [CampaignController::class, 'getActiveCampaigns']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/storecampaign', [CampaignController::class, 'createCampaign']);
    Route::put('/updatecampaign/{id}', [CampaignController::class, 'updateCampaign']);
    Route::delete('/deletecampaign/{id}', [CampaignController::class, 'deleteCampaign']);
    Route::patch('/closecampaign/{id}', [CampaignController::class, 'closeCampaign']);
    Route::post('/volunteer/{campaignId}', [CampaignController::class, 'volunteerForCampaign']);
});
// Volunteer routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/myapprovedvolunteers', [VolunteerController::class, 'getMyApprovedVolunteers']);
});
// Donation routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/quickDonate', [DonationController::class, 'quickDonateToAssociation']);
    Route::post('/approveDonation/{id}', [DonationController::class, 'approveDonation']);
    Route::post('/rejectDonation/{id}', [DonationController::class, 'rejectDonation']);
    Route::get('/pendingDonations', [DonationController::class, 'getPendingDonations']);
    Route::post('/donate', [DonationController::class, 'donate']);
});
