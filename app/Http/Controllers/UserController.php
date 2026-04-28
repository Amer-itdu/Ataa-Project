<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\SignInRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Sign up a new user.
     *
     * @param SignUpRequest $request - Form Request class that validates signup data
     * @return \Illuminate\Http\JsonResponse
     */
public function signUp(SignUpRequest $request)
{
    $profilePath = null;
    $nationalIdPath = null;
    $passportPath = null;

    try {
        $validatedData = $request->validated();

        // Check if user already exists (safe search)
        $existingUser = User::where(function ($query) use ($validatedData) {
            if (!empty($validatedData['email'])) {
                $query->where('email', $validatedData['email']);
            }

            if (!empty($validatedData['phone'])) {
                $query->orWhere('phone', $validatedData['phone']);
            }
        })->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'User with this email or phone already exists.',
            ], 409);
        }

        // Upload profile image (optional)
        if ($request->hasFile('profile_image')) {
            $profilePath = $request->file('profile_image')->store('profile_images', 'public');
            $validatedData['profile_image'] = $profilePath;
        }

        // Upload national ID (required_without passport)
        if ($request->hasFile('national_id')) {
            $nationalIdPath = $request->file('national_id')->store('national_ids', 'public');
            $validatedData['national_id'] = $nationalIdPath;
        }

        // Upload passport (required_without national_id)
        if ($request->hasFile('international_passport')) {
            $passportPath = $request->file('international_passport')->store('passport_images', 'public');
            $validatedData['international_passport'] = $passportPath;
        }

        // Hash password
        $validatedData['password'] = Hash::make($validatedData['password']);

        // Create user
        $user = User::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'user'    => $user,
        ], 201);

    } catch (\Exception $e) {

        // Cleanup uploaded files if error happens
        if ($profilePath && Storage::disk('public')->exists($profilePath)) {
            Storage::disk('public')->delete($profilePath);
        }

        if ($nationalIdPath && Storage::disk('public')->exists($nationalIdPath)) {
            Storage::disk('public')->delete($nationalIdPath);
        }

        if ($passportPath && Storage::disk('public')->exists($passportPath)) {
            Storage::disk('public')->delete($passportPath);
        }

        Log::error('Sign up failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while registering the user',
        ], 500);
    }
}

    /**
     * Sign in a user.
     *
     * @param SignInRequest $request - Form Request class that validates signin data
     * @return \Illuminate\Http\JsonResponse
     */
public function signIn(SignInRequest $request)
{
    try {
        $validated = $request->validated();

        // Determine login method
        $credentials = [];

        if (!empty($validated['Email'])) {
            $credentials['email'] = $validated['Email'];
        }

        if (!empty($validated['Phone'])) {
            $credentials['phone'] = $validated['Phone'];
        }

        $credentials['password'] = $validated['password'];

        // Attempt login
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email/phone or password.',
            ], 401);
        }

        // Get user
        $user = User::where('email', $validated['Email'] ?? null)
            ->orWhere('phone', $validated['Phone'] ?? null)
            ->firstOrFail();

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            
        ], 200);

    } catch (\Exception $e) {
        Log::error('Sign in failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while signing in',
        ], 500);
    }
}



    /**
     * Sign out a user.
     */
    public function signOut(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'User signed out successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sign out failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ], 200);
    }
}

