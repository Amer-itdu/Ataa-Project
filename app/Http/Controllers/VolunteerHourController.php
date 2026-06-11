<?php

namespace App\Http\Controllers;

use App\Models\VolunteerHour;
use Illuminate\Http\Request;

class VolunteerHourController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'volunteer_hours' => VolunteerHour::all()], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'volunteer_id' => 'required|exists:volunteers,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'activity_description' => 'nullable|string',
        ]);

        $hour = VolunteerHour::create($data);

        return response()->json(['success' => true, 'volunteer_hour' => $hour], 201);
    }

    public function show($id)
    {
        $hour = VolunteerHour::find($id);

        if (!$hour) {
            return response()->json(['success' => false, 'message' => 'Volunteer hour record not found.'], 404);
        }

        return response()->json(['success' => true, 'volunteer_hour' => $hour], 200);
    }

    public function update(Request $request, $id)
    {
        $hour = VolunteerHour::find($id);

        if (!$hour) {
            return response()->json(['success' => false, 'message' => 'Volunteer hour record not found.'], 404);
        }

        $data = $request->validate([
            'date' => 'sometimes|required|date',
            'hours' => 'sometimes|required|numeric|min:0',
            'activity_description' => 'nullable|string',
        ]);

        $hour->update($data);

        return response()->json(['success' => true, 'volunteer_hour' => $hour], 200);
    }

    public function destroy($id)
    {
        $hour = VolunteerHour::find($id);

        if (!$hour) {
            return response()->json(['success' => false, 'message' => 'Volunteer hour record not found.'], 404);
        }

        $hour->delete();

        return response()->json(['success' => true, 'message' => 'Volunteer hour record deleted successfully.'], 200);
    }
}
