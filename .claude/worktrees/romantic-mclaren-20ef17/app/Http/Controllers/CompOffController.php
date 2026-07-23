<?php

namespace App\Http\Controllers;

use App\Models\CompOff;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompOffController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        // Working holidays for the year
        $workingHolidays = Holiday::whereYear('date', $year)
            ->where('is_working_day', true)
            ->orderBy('date')
            ->get();

        $totalActive = Employee::where('status', 'active')->count();

        // Summary per holiday date: pending count, availed count
        $summaryMap = [];
        CompOff::whereYear('holiday_date', $year)
            ->selectRaw('holiday_date, status, COUNT(*) as cnt')
            ->groupBy('holiday_date', 'status')
            ->get()
            ->each(function ($row) use (&$summaryMap) {
                $d = Carbon::parse($row->holiday_date)->toDateString();
                $summaryMap[$d][$row->status] = (int) $row->cnt;
            });

        return view('comp-offs.index', compact('workingHolidays', 'summaryMap', 'totalActive', 'year'));
    }

    /** Grant comp off to ALL active employees for a holiday date */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:150',
        ]);

        $employees = Employee::where('status', 'active')->get();
        foreach ($employees as $emp) {
            CompOff::updateOrCreate(
                ['employee_id' => $emp->id, 'holiday_date' => $request->holiday_date],
                ['holiday_name' => $request->holiday_name, 'status' => 'pending']
            );
        }

        return back()->with('success', "Comp off granted to {$employees->count()} employee(s) for {$request->holiday_name}.");
    }

    /** Mark ALL pending comp offs for a holiday date as availed on the chosen date
     *  and auto-create attendance records (status = comp_off) for each employee. */
    public function bulkAvail(Request $request)
    {
        $request->validate([
            'holiday_date' => 'required|date',
            'availed_date' => 'required|date',
        ]);

        $pending = CompOff::where('holiday_date', $request->holiday_date)
            ->where('status', 'pending')
            ->get();

        foreach ($pending as $co) {
            // Mark comp off as availed
            $co->update([
                'availed_date' => $request->availed_date,
                'status'       => 'availed',
            ]);

            // Auto-mark attendance as comp_off on the availed date
            \App\Models\Attendance::updateOrCreate(
                ['employee_id' => $co->employee_id, 'date' => $request->availed_date],
                ['status' => 'comp_off', 'check_in' => null, 'check_out' => null,
                 'working_hours' => null, 'ot_hours' => null, 'ot_amount' => null,
                 'remarks' => 'Comp Off — ' . $co->holiday_name]
            );
        }

        $count = $pending->count();

        return back()->with('success', "{$count} comp off(s) marked as availed on "
            . Carbon::parse($request->availed_date)->format('d M Y')
            . ' and attendance updated.');
    }

    public function destroy(CompOff $compOff)
    {
        $compOff->delete();
        return back()->with('success', 'Comp off record removed.');
    }
}
