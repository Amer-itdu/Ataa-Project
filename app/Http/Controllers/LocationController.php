<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Region;

class LocationController extends Controller
{
    // 🔹 جلب كل المحافظات
    public function getGovernorates()
    {
        return response()->json(
            Governorate::select('id', 'name')->get()
        );
    }

    // 🔹 جلب المناطق حسب المحافظة
    public function getRegions($governorateId)
    {
        return response()->json(
            Region::where('governorate_id', $governorateId)
                  ->select('id', 'name')
                  ->get()
        );
    }
}
