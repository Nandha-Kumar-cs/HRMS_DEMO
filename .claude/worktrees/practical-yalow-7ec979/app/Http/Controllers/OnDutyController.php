<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OnDuty;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OnDutyController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        $start = Carbon::create($year, $month, 1)->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // OD records for the selected month grouped by date
        $odRecords = OnDuty::with(['employee', 'createdBy'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get()
            ->groupBy(fn($od) => $od->date->toDateString());

        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();

        return view('on-duties.index', compact('odRecords', 'employees', 'month', 'year'));
    }

    /**
     * Assign OD for one or many employees on a given date.
     * Also auto-creates/updates the attendance record as 'on_duty'.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
            'date'           => 'required|date',
            'reason'         => 'nullable|string|max:255',
        ]);

        $date      = $request->date;
        $reason    = $request->reason;
        $createdBy = auth()->id();
        $saved     = 0;
        $skipped   = 0;

        foreach ($request->employee_ids as $empId) {
            $existing = OnDuty::where('employee_id', $empId)->where('date', $date)->first();
            if ($existing) { $skipped++; continue; }

            OnDuty::create([
                'employee_id' => $empId,
                'date'        => $date,
                'reason'      => $reason,
                'created_by'  => $createdBy,
            ]);

            // Auto-mark attendance as on_duty
            Attendance::updateOrCreate(
                ['employee_id' => $empId, 'date' => $date],
                [
                    'status'        => 'on_duty',
                    'check_in'      => null,
                    'check_out'     => null,
                    'working_hours' => null,
                    'remarks'       => 'On Duty' . ($reason ? ': ' . $reason : ''),
                ]
            );

            $saved++;
        }

        $msg = "OD assigned to {$saved} employee(s) for " . Carbon::parse($date)->format('d M Y') . '.';
        if ($skipped) $msg .= " {$skipped} already had OD for this date.";

        return back()->with('success', $msg);
    }

    /**
     * Remove an OD record and revert the attendance record.
     */
    public function destroy(OnDuty $onDuty)
    {
        // Remove attendance record that was auto-created
        Attendance::where('employee_id', $onDuty->employee_id)
            ->where('date', $onDuty->date->toDateString())
            ->where('status', 'on_duty')
            ->delete();

        $onDuty->delete();

        return back()->with('success', 'OD record removed.');
    }
}
