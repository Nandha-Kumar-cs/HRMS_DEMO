<?php

namespace App\Http\Controllers;

use App\Models\HolidayType;
use Illuminate\Http\Request;

class HolidayTypeController extends Controller
{
    public function index()
    {
        $types = HolidayType::withCount('holidays')->orderBy('name')->get();
        return view('holiday-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:80|unique:holiday_types,name',
            'color' => 'required|string|max:20',
        ]);
        $type = HolidayType::create($data);
        return response()->json(['success' => true, 'message' => 'Holiday type created.', 'data' => $type]);
    }

    public function update(Request $request, HolidayType $holidayType)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:80|unique:holiday_types,name,' . $holidayType->id,
            'color' => 'required|string|max:20',
        ]);
        $holidayType->update($data);
        return response()->json(['success' => true, 'message' => 'Holiday type updated.', 'data' => $holidayType]);
    }

    public function destroy(HolidayType $holidayType)
    {
        if ($holidayType->holidays()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete — there are holidays using this type.',
            ], 422);
        }
        $holidayType->delete();
        return response()->json(['success' => true, 'message' => 'Holiday type deleted.']);
    }

    public function edit(HolidayType $holidayType)
    {
        return response()->json($holidayType);
    }
}
