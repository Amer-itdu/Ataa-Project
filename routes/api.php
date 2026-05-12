<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/signup', [UserController::class, 'signUp']);
Route::post('/signin', [UserController::class, 'signIn']);


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/signout', [UserController::class, 'signOut']);

    
    Route::get('/userprofile', [UserController::class, 'profile']);

    
    Route::put('/userprofile/update', [UserController::class, 'updateProfile']);

    
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







