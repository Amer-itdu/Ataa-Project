<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSignUpRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Donor;
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
            // Set default account status to pending
            $validatedData['status'] = 'approved'; // 🔥 For testing, set to approved directly. Change to 'pending' in production.

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
    public function signin(SignInRequest $request)
    {
        $validated = $request->validated();

        $loginField = isset($validated['email']) ? 'email' : 'phone';
        $loginValue = $validated[$loginField];

        // Get user
        $user = User::where($loginField, $loginValue)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email/phone or password',
            ], 401);
        }

        // Check status
        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval.',
            ], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been rejected.',
            ], 403);
        }

        // Check password
        if (!Auth::attempt([$loginField => $loginValue, 'password' => $validated['password']])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email/phone or password',
            ], 401);
        }

        // 🔥 IMPORTANT: Use the $user you already have
        $token = $user->createToken('auth_Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Successful',
            'user'    => $user,
            'token'   => $token,
        ], 200);
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


    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // تحديث الحقول المسموح بها فقط
        $data = $request->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'address',
        ]);

        // إذا فيه باسورد جديد
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }
    public function addBalanceToUser(Request $request, $userId)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can add balance.'
            ], 403);
        }

        $request->validate([
            'currency' => 'required|in:USD,EUR,SAR,AED,EGP,SYP',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // إضافة الرصيد
        $user->addBalance($request->currency, $request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Balance added successfully.',
            'balances' => $user->balances
        ], 200);
    }

    public function myDonationsFull()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        if (!$user->donor) {
            return response()->json([
                'success' => true,
                'donations_count' => 0,
                'total_donated_usd' => "0.00",
                'donations' => []
            ]);
        }

        // ============================
        // 🔥 إحصائيات المستخدم (approved فقط)
        // ============================
        $donor = Donor::where('id', $user->donor->id)
            ->withCount(['donations' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->withSum(['donations as total_donated' => function ($q) {
                $q->where('status', 'approved');
            }], 'amount')
            ->first();

        // ============================
        // 🔥 قائمة التبرعات الموافق عليها فقط
        // ============================
        $donations = $user->donor->donations()
            ->where('status', 'approved')
            ->with('donationable')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($donation) {

                $target = $donation->donationable;
                $type = class_basename($donation->donationable_type);

                if ($type === 'Campaign') {

                    $details = [
                        'id' => $target->id,
                        'title' => $target->title,
                        'description' => $target->description,
                        'type' => $target->type,
                        'amount_needed' => $target->amount_needed,
                        'amount_collected' => $target->amount_collected,
                        'status' => $target->status,
                        'start_date' => $target->start_date,
                        'end_date' => $target->end_date,
                    ];
                } else {

                    $details = [
                        'id' => $target->id,
                        'request_id' => $target->request->id,
                        'request_title' => $target->request->title,
                        'request_description' => $target->request->description,
                        'request_type' => $target->request->request_type,
                        'status' => $target->request->status,
                        'beneficiary_id' => $target->request->beneficiary_id,
                    ];
                }

                return [
                    'donation_id' => $donation->id,
                    'type' => strtolower($type),
                    'amount_usd' => $donation->amount,
                    'original_amount' => $donation->original_amount,
                    'original_currency' => $donation->original_currency,
                    'status' => $donation->status,
                    'date' => $donation->created_at->format('Y-m-d H:i'),
                    'target_details' => $details
                ];
            });

        return response()->json([
            'success' => true,
            'donations_count' => $donor->donations_count,
            'total_donated_usd' => number_format($donor->total_donated ?? 0, 2),
            'donations' => $donations
        ]);
    }
    public function approveUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'User is already approved.',
            ], 400);
        }

        $user->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User approved successfully.',
            'user' => $user
        ], 200);
    }
    public function rejectUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'User is already rejected.',
            ], 400);
        }

        $user->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User rejected successfully.',
            'user' => $user
        ], 200);
    }
    public function setPending($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // إذا هو أصلاً pending
        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'User is already pending.',
            ], 400);
        }

        // 🔥 تحويل الحالة إلى pending
        $user->update([
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User status changed to pending successfully.',
            'user' => $user
        ], 200);
    }
    public function getAllPendingUsers()
    {
        $users = User::where('status', 'pending')->get();

        return response()->json([
            'success' => true,
            'count'   => $users->count(),
            'users'   => $users
        ], 200);
    }
    public function getAllNonUserAccounts()
    {
        $users = User::where('role', '!=', 'user')->where('role', '!=', 'admin')->get();

        return response()->json([
            'success' => true,
            'count'   => $users->count(),
            'users'   => $users
        ], 200);
    }
    public function createEmployee(AdminSignUpRequest $request)
    {
        $validated = $request->validated();

        // رفع صورة البروفايل إذا موجودة
        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $request->file('profile_image')
                ->store('profile_images', 'public');
        }

        // تشفير كلمة السر
        $validated['password'] = Hash::make($validated['password']);

        // إنشاء المستخدم
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully.',
            'user' => $user
        ], 201);
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // التحقق من كلمة السر الحالية
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 400);
        }

        // تحديث كلمة السر
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ], 200);
    }
}
