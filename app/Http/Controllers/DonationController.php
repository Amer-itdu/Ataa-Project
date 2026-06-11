<?php

namespace App\Http\Controllers;

use App\Http\Requests\DonateRequest;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\RequestModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonationController extends Controller
{
public function quickDonateToAssociation(Request $request)
{
    /** @var User|null $user */
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required.'
        ], 401);
    }

    if ($user->role === 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Admins cannot donate.'
        ], 403);
    }

    $validated = $request->validate([
        'currency' => 'required|in:USD,EUR,SAR,AED,EGP,SYP',
        'amount' => 'required|numeric|min:1',
    ]);

    $amountInUSD = User::convertToUSD($validated['amount'], $validated['currency']);

    $admin = User::where('role', 'admin')->first();

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Admin account not found.'
        ], 500);
    }

    // 🔥 لا تخصم ولا تضيف رصيد الآن
    $donor = $user->donor ?? Donor::create([
        'user_id' => $user->id,
        'anonymous' => false,
    ]);

    $donation = Donation::create([
        'donor_id' => $donor->id,
        'amount' => $amountInUSD,
        'currency' => 'USD',
        'original_amount' => $validated['amount'],
        'original_currency' => $validated['currency'],
        'donationable_type' => User::class,
        'donationable_id' => $admin->id,
        'status' => 'pending', // NEW
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Donation submitted and awaiting admin approval.',
        'donation_id' => $donation->id,
    ], 200);
}
public function approveDonation($id)
{
    $admin = Auth::user();

    if (!$admin) {
        return response()->json(['message' => 'Authentication required.'], 401);
    }

    if ($admin->role !== 'admin') {
        return response()->json(['message' => 'Only admins can approve donations'], 403);
    }

    $donation = Donation::findOrFail($id);

    if ($donation->status !== 'pending') {
        return response()->json(['message' => 'Donation already processed'], 400);
    }

    // المتبرع الحقيقي
    $donorUser = $donation->donor->user;

    // تحقق من رصيد المتبرع
    if (!$donorUser->subtractBalance($donation->original_currency, $donation->original_amount)) {
        return response()->json(['message' => 'User does not have enough balance'], 400);
    }

    // ================================
    // 🔥 تحديد المستفيد حسب نوع التبرع
    // ================================

    $target = $donation->donationable;

    if ($donation->donationable_type === \App\Models\Campaign::class) {

        // تبرع لحملة → المصاري للجمعية
        $receiver = User::where('role', 'admin')->first();

    } else {

        // تبرع لحالة → المصاري لصاحب الحالة
        // Orphan / Patient / SchoolStudent / UniversityStudent
        $receiver = $target->request->user;
    }

    // إضافة الرصيد للمستفيد بالدولار
    $receiver->addBalance('USD', $donation->amount);

    // تحديث حالة التبرع
    $donation->update(['status' => 'approved']);

    return response()->json(['message' => 'Donation approved successfully']);
}
public function rejectDonation($id)
{
    $admin = Auth::user();

    if (!$admin) {
        return response()->json(['message' => 'Authentication required.'], 401);
    }

    if ($admin->role !== 'admin') {
        return response()->json(['message' => 'Only admins can reject donations'], 403);
    }

    $donation = Donation::findOrFail($id);

    if ($donation->status !== 'pending') {
        return response()->json(['message' => 'Donation already processed'], 400);
    }

    $donation->update(['status' => 'rejected']);

    return response()->json(['message' => 'Donation rejected successfully']);
}
public function getPendingDonations()
{
    $admin = Auth::user();

    if ($admin->role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Only admins can view pending donations.'
        ], 403);
    }

    $pending = Donation::where('status', 'pending')
        ->with(['donor.user']) // يجلب معلومات المتبرع
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'pending_donations' => $pending
    ]);
}
public function donate(DonateRequest $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required.'
        ], 401);
    }

    // 🔥 Prevent admin from donating
    if ($user->role === 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'Admins cannot donate.'
        ], 403);
    }

    // Validated data from DonateRequest
    $validated = $request->validated();

    // Convert amount to USD
    $amountInUSD = User::convertToUSD($validated['amount'], $validated['currency']);

    // ================================
    // 🔥 Determine donation target
    // ================================

    if ($validated['type'] === 'campaign') {

        // Donation to a campaign
        $target = Campaign::findOrFail($validated['id']);

    } else {

        // Donation to a beneficiary request (Patient, Orphan, Student…)
        $target = RequestModel::findOrFail($validated['id']);
    }

    // Create donor record if not exists
    $donor = $user->donor ?? Donor::create([
        'user_id' => $user->id,
        'anonymous' => false,
    ]);

    // Create donation as pending
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'amount' => $amountInUSD,
        'currency' => 'USD',
        'original_amount' => $validated['amount'],
        'original_currency' => $validated['currency'],
        'donationable_type' => get_class($target),
        'donationable_id' => $target->id,
        'status' => 'pending',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Donation submitted and awaiting admin approval.',
        'donation_id' => $donation->id
    ]);
}


}
