<?php

namespace App\Http\Controllers;

use App\Helpers\WorkCalendar;
use App\Imports\HolidayImport;
use App\Models\Holiday;
use App\Models\HolidayType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year     = (int) $request->get('year', now()->year);
        $holidays = Holiday::with('holidayType')
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        $types      = HolidayType::orderBy('name')->get();
        $weeklyOffs = WorkCalendar::getFirstAndThirdSaturdays($year);

        return view('holidays.index', compact('holidays', 'year', 'weeklyOffs', 'types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'            => 'required|date|unique:holidays,date',
            'name'            => 'required|string|max:150',
            'holiday_type_id' => 'nullable|exists:holiday_types,id',
        ]);

        Holiday::create([
            'date'            => $request->date,
            'name'            => $request->name,
            'holiday_type_id' => $request->holiday_type_id,
        ]);

        return back()->with('success', 'Holiday "' . $request->name . '" added successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'year'            => 'required|integer|min:2000|max:2099',
            'holiday_type_id' => 'nullable|exists:holiday_types,id',
        ]);

        $year   = (int) $request->year;
        $typeId = $request->holiday_type_id;
        $import = new HolidayImport($year, $typeId);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $msg = "{$import->imported} holiday(s) imported for {$year}.";
        if ($import->skipped) {
            $msg .= " {$import->skipped} row(s) skipped.";
        }

        return back()
            ->with('success', $msg)
            ->with('import_errors', $import->errors);
    }
}
