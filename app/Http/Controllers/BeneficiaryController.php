<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function store(StoreBeneficiaryRequest $request)
{
   $beneficiary = Beneficiary::create([
    'full_name'   => $request->full_name,
    'mother_name' => $request->mother_name,
    'address'     => $request->address,
    'email'       => $request->email,
    'phone'       => $request->phone,
]);

    return response()->json([
        'message' => 'Beneficiary created successfully',
        'data'    => $beneficiary
    ], 201);
}


public function index()
{
    return response()->json([
        'data' => Beneficiary::all()
    ]);
}


public function update(Request $request, $id)
{
    $beneficiary = Beneficiary::find($id);

    if (!$beneficiary) {
        return response()->json([
            'message' => 'Beneficiary not found.'
        ], 404);
    }

    if (!$request->hasAny(['full_name', 'mother_name', 'address', 'email', 'phone'])) {
    return response()->json([
        'message' => 'No data provided to update.'
    ], 400);
}

    $updated = $beneficiary->update([
        'full_name'   => $request->full_name   ?? $beneficiary->full_name,
        'mother_name' => $request->mother_name ?? $beneficiary->mother_name,
        'address'     => $request->address     ?? $beneficiary->address,
        'email'       => $request->email       ?? $beneficiary->email,
        'phone'       => $request->phone       ?? $beneficiary->phone,
    ]);

    if (!$updated) {
        return response()->json([
            'message' => 'Failed to update beneficiary.'
        ], 500);
    }

    return response()->json([
        'message' => 'Beneficiary updated successfully',
        'data'    => $beneficiary
    ]);
}

public function destroy($id)
{
    $beneficiary = Beneficiary::find($id);

    if (!$beneficiary) {
        return response()->json([
            'message' => 'Beneficiary not found.'
        ], 404);
    }

    $beneficiary->delete();

    return response()->json([
        'message' => 'Beneficiary deleted successfully'
    ]);
}


}
