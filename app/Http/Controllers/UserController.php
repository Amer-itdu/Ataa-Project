<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminSignUpRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\PromoteUserRequest;
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

            $query = User::query();

            if (!empty($validatedData['email'])) {
                $query->where('email', $validatedData['email']);
            }

            if (!empty($validatedData['phone'])) {
                $query->orWhere('phone', $validatedData['phone']);
            }

            $existingUser = $query->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User with this email or phone already exists.',
                ], 409);
            }

            if ($request->hasFile('profile_image')) {
                $profilePath = $request->file('profile_image')->store('profile_images', 'public');
                $validatedData['profile_image'] = $profilePath;
            }

            if ($request->hasFile('national_id')) {
                $nationalIdPath = $request->file('national_id')->store('national_ids', 'public');
                $validatedData['national_id'] = $nationalIdPath;
            }

            if ($request->hasFile('international_passport')) {
                $passportPath = $request->file('international_passport')->store('passport_images', 'public');
                $validatedData['international_passport'] = $passportPath;
            }

            $validatedData['password'] = Hash::make($validatedData['password']);

            $validatedData['status'] = 'approved';
            // 👤 إنشاء المستخدم
            $user = User::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'user'    => $user,
            ], 201);
        } catch (\Exception $e) {

            // 🧹 تنظيف الملفات المرفوعة عند حدوث خطأ
            foreach ([$profilePath, $nationalIdPath, $passportPath] as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
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
        $validated = $request->validated();

        $oldProfileImage = $this->normalizeStoragePath($user->profile_image);
        $oldNationalId = $this->normalizeStoragePath($user->national_id);
        $oldPassport = $this->normalizeStoragePath($user->international_passport);

        $newProfileImagePath = null;
        $newNationalIdPath = null;
        $newPassportPath = null;

        $debug = [
            'has_profile_image' => $request->hasFile('profile_image'),
            'has_national_id' => $request->hasFile('national_id'),
            'has_international_passport' => $request->hasFile('international_passport'),
            'old_profile_image' => $user->profile_image,
            'old_national_id' => $user->national_id,
            'old_passport' => $user->international_passport,
            'old_profile_image_exists' => $oldProfileImage ? Storage::disk('public')->exists($oldProfileImage) : false,
            'old_national_id_exists' => $oldNationalId ? Storage::disk('public')->exists($oldNationalId) : false,
            'old_passport_exists' => $oldPassport ? Storage::disk('public')->exists($oldPassport) : false,
        ];

        if ($request->hasFile('profile_image')) {
            $newProfileImagePath = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $newProfileImagePath;
            $debug['new_profile_image_path'] = $newProfileImagePath;
        }

        if ($request->hasFile('national_id')) {
            $newNationalIdPath = $request->file('national_id')->store('national_ids', 'public');
            $validated['national_id'] = $newNationalIdPath;
            $debug['new_national_id_path'] = $newNationalIdPath;
        }

        if ($request->hasFile('international_passport')) {
            $newPassportPath = $request->file('international_passport')->store('passport_images', 'public');
            $validated['international_passport'] = $newPassportPath;
            $debug['new_passport_path'] = $newPassportPath;
        }

        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        try {
            $user->update($validated);

            if ($newProfileImagePath && $oldProfileImage && Storage::disk('public')->exists($oldProfileImage)) {
                Storage::disk('public')->delete($oldProfileImage);
                $debug['deleted_old_profile_image'] = true;
            }

            if ($newNationalIdPath && $oldNationalId && Storage::disk('public')->exists($oldNationalId)) {
                Storage::disk('public')->delete($oldNationalId);
                $debug['deleted_old_national_id'] = true;
            }

            if ($newPassportPath && $oldPassport && Storage::disk('public')->exists($oldPassport)) {
                Storage::disk('public')->delete($oldPassport);
                $debug['deleted_old_passport'] = true;
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh(),
                'debug' => $debug,
            ], 200);
        } catch (\Exception $e) {
            foreach ([$newProfileImagePath, $newNationalIdPath, $newPassportPath] as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Profile update failed: ' . $e->getMessage());

            $debug['exception'] = $e->getMessage();
            $debug['updated'] = false;

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
                'debug' => $debug,
            ], 500);
        }
    }

    //  دالة مساعدة لتطبيع مسارات الملفات المخزنة
    private function normalizeStoragePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = ltrim($path, '/');

        // حذف أي بادئات يمكن أن تمنع Storage::disk('public') من إيجاد الملف
        return preg_replace('#^(?:storage/app/public/|storage/app/|storage/|public/)#', '', $path);
    }
    //_______________________________________________________________________ 
    //_______________________________________________________________________
    // over this function is done
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
        /** @var \App\Models\User $user */
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
    
    public function promoteUser(PromoteUserRequest $request, $id)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can promote users.',
            ], 403);
        }

        $targetUser = User::findOrFail($id);

        if ($targetUser->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change the role of an admin.',
            ], 400);
        }

        if ($targetUser->role === $request->role) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this role.',
            ], 400);
        }

        $oldRole = $targetUser->role;

        $targetUser->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => "User role updated from {$oldRole} to {$request->role} successfully.",
            'user' => $targetUser,
        ], 200);
    }

    public function demoteUser($id)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can demote users.',
            ], 403);
        }

        $targetUser = User::findOrFail($id);

        if ($targetUser->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change the role of an admin.',
            ], 400);
        }

        if ($targetUser->role === 'user') {
            return response()->json([
                'success' => false,
                'message' => 'User already has the default role.',
            ], 400);
        }

        $oldRole = $targetUser->role;

        $targetUser->update([
            'role' => 'user',
        ]);

        return response()->json([
            'success' => true,
            'message' => "User role reverted from {$oldRole} to user successfully.",
            'user' => $targetUser,
        ], 200);
    }

    
    public function listByRole($role)
    {
        if (!in_array($role, ['admin', 'sub_admin', 'field_worker', 'user'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role.',
            ], 400);
        }

        $users = User::where('role', $role)
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'role', 'status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }
    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ], 200);
    }
    public function getallUsers()
    {
        $users = User::all();

        return response()->json([
            'success' => true,
            'count'   => $users->count(),
            'users'   => $users
        ], 200);
    }
}
