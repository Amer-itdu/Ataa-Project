<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/signup', [UserController::class, 'signUp']);
Route::post('/signin', [UserController::class, 'signIn']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('signout', [UserController::class, 'signOut']);
    Route::get('/userprofile', [UserController::class, 'profile']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

