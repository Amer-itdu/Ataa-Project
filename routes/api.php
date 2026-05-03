<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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
});
