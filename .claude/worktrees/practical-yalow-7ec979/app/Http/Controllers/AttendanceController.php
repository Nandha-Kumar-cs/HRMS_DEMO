<?php

namespace App\Http\Controllers;

use App\Helpers\WorkCalendar;
use App\Imports\AttendanceImport;
use App\Imports\MonthlyAttendanceImport;
use App\Helpers\AppSettings;
use App\Models\Attendance;
use App\Models\CompOff;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    /** Daily mark sheet */
    public function index(Request $request)
    {
        $date      = $request->get('date', today()->toDateString());
        $dateObj   = Carbon::parse($date);

        // Determine if selected date is a non-working day
        $isNonWorking    = !WorkCalendar::isWorkingDay($dateObj);
        $nonWorkingLabel = null;
        if ($dateObj->isSunday()) {
            $nonWorkingLabel = 'Sunday — this day is a weekly off.';
        } elseif (WorkCalendar::isFirstOrThirdSaturday($dateObj)) {
            $satCount = 0;
            $tmp = $dateObj->copy()->startOfMonth();
            while ($tmp->lte($dateObj)) { if ($tmp->isSaturday()) $satCount++; $tmp->addDay(); }
            $nonWorkingLabel = ($satCount === 1 ? '1st' : '3rd') . ' Saturday — this day is a weekly off.';
        } else {
            $holiday = \App\Models\Holiday::where('date', $date)->first();
            if ($holiday) $nonWorkingLabel = 'Public Holiday: ' . $holiday->name;
        }

        $employees = Employee::where('status', 'active')
            ->with(['attendance' => fn($q) => $q->whereDate('date', $date)])
            ->orderBy('full_name')
            ->get();

        $attendanceMap = Attendance::whereDate('date', $date)
            ->pluck('status', 'employee_id');

        return view('attendance.index', compact(
            'date', 'employees', 'attendanceMap', 'isNonWorking', 'nonWorkingLabel'
        ));
    }

    /** Bulk save attendance for a date */
    public function store(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'attendance'   => 'required|array',
            'attendance.*' => 'required|in:present,absent,half_day,late,on_leave,comp_off,on_duty',
        ]);

        $date = $request->date;

        // Pre-load employees for OT calculation (avoid N+1)
        $employeeIds = array_keys($request->attendance);
        $employeeMap = Employee::whereIn('id', $employeeIds)
            ->select('id', 'ot_enabled', 'fixed_salary')
            ->get()
            ->keyBy('id');

        // OT rules:
        //   Trigger    : checkout >= 20:30 (8:30 PM)
        //   OT start   : 18:15 (6:15 PM) — OT hours counted from this baseline
        //   Formula    : daily_rate  = Basic / days_in_month
        //                hourly_rate = daily_rate / 8
        //                ot_rate     = hourly_rate * 2
        //                OT amount   = ot_rate * OT_hours  (rounded to 2 dp)
        //                Basic       = 40% of fixed_salary
        // Read all timing thresholds from settings (admin-configurable)
        $otTriggerMins   = AppSettings::getOtTriggerMins();
        $otBaselineMins  = AppSettings::getOtBaselineMins();
        $officeStartMins = AppSettings::getOfficeStartMins(); // e.g. 09:00 = 540
        $daysInMonth    = Carbon::parse($date)->daysInMonth;  // actual days (28/29/30/31)

        foreach ($request->attendance as $employeeId => $status) {
            $checkIn  = $request->check_in[$employeeId]  ?? null;
            $checkOut = $request->check_out[$employeeId] ?? null;
            $emp      = $employeeMap[$employeeId] ?? null;

            // ── Rule 1: No checkout → mark as Absent ─────────────────────────
            // Only override when admin chose present/late but forgot checkout.
            // Explicitly chosen absent/on_leave/half_day are left untouched.
            if (in_array($status, ['present', 'late']) && empty($checkOut)) {
                $status = 'absent';
            }

            // ── Rules 2 & 3: Auto-detect Late / Half Day from check-in time ──
            // Rule 2: check-in > office_start but ≤ 2-hr cutoff → Late
            // Rule 3: check-in > 2-hr cutoff (office_start + 2h)  → Half Day
            // Only applies when not already absent/on_leave/comp_off/on_duty.
            if (!in_array($status, ['absent', 'on_leave', 'comp_off', 'on_duty']) && !empty($checkIn)) {
                try {
                    $inParts       = explode(':', $checkIn);
                    $inMins        = (int)$inParts[0] * 60 + (int)($inParts[1] ?? 0);
                    $halfDayCutoff = $officeStartMins + 2 * 60; // office_start + 2 hours

                    if ($inMins > $halfDayCutoff) {
                        // More than 2 hours late → Half Day
                        $status = 'half_day';
                    } elseif ($status === 'present' && $inMins > $officeStartMins) {
                        // Between office start and half-day cutoff → Late
                        $status = 'late';
                    }
                } catch (\Throwable) {}
            }

            $otHours = null;

            if ($emp && $emp->ot_enabled) {
                // Auto-calculate OT hours from checkout time — always overrides manual input
                if ($checkOut) {
                    try {
                        $outCarbon = Carbon::parse($checkOut);
                        $outMins   = $outCarbon->hour * 60 + $outCarbon->minute;

                        if ($outMins >= $otTriggerMins) {
                            $otMins  = $outMins - $otBaselineMins; // mins from 6:15 PM
                            $otHours = round($otMins / 60, 2);
                        }
                        // checkout before 8:30 PM → OT hours stays null
                    } catch (\Exception) {
                        // Unparsable time — leave OT null
                    }
                }
                // No checkout → OT null (clears any previously saved value)
            } else {
                // Manual entry for non-OT employees (hours only, amount calculated in salary slip)
                $raw = $request->ot_hours[$employeeId] ?? null;
                $otHours = ($raw !== null && $raw !== '') ? (float) $raw : null;
            }

            Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                [
                    'status'        => $status,
                    'check_in'      => $checkIn,
                    'check_out'     => $checkOut,
                    'remarks'       => $request->remarks[$employeeId] ?? null,
                    'working_hours' => $this->calcHours($checkIn, $checkOut),
                    'ot_hours'      => ($otHours > 0) ? $otHours : null,
                    'ot_amount'     => null,
                ]
            );
        }

        return back()->with('success', 'Attendance saved for ' . Carbon::parse($date)->format('d M Y') . '.');
    }

    /** Monthly report grid */
    public function report(Request $request)
    {
        $month     = (int) $request->get('month', now()->month);
        $year      = (int) $request->get('year',  now()->year);
        $startDate = Carbon::create($year, $month, 1);
        $endDate   = $startDate->copy()->endOfMonth();
        $days      = $startDate->daysInMonth;

        $employees = Employee::where('status', 'active')
            ->orderBy('full_name')
            ->get();

        $allRecords = Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        // Group by employee_id → day for the grid
        $records = $allRecords
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->keyBy(fn($r) => Carbon::parse($r->date)->day));

        // Non-working days map: day number => label
        $nonWorkingDays = [];
        $nwMap = WorkCalendar::getNonWorkingDates($startDate, $endDate);
        foreach ($nwMap as $dateStr => $label) {
            $nonWorkingDays[Carbon::parse($dateStr)->day] = $label;
        }

        // Working holidays: holidays declared as working day — day number => holiday name
        $workingHolidayDays = [];
        \App\Models\Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_working_day', true)
            ->get()
            ->each(function ($h) use (&$workingHolidayDays) {
                $workingHolidayDays[\Carbon\Carbon::parse($h->date)->day] = $h->name;
            });

        // Comp-off availed days: day number => holiday name (for column header icon)
        $compOffDays = [];
        \App\Models\CompOff::whereBetween('availed_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', 'availed')
            ->select('availed_date', 'holiday_name')
            ->distinct()
            ->get()
            ->each(function ($co) use (&$compOffDays) {
                $compOffDays[\Carbon\Carbon::parse($co->availed_date)->day] = $co->holiday_name;
            });

        $totalWorkingDays = WorkCalendar::countWorkingDays($startDate, $endDate);

        // ── Per-employee attendance summary stats ─────────────────────────────
        $lateGrace  = 90;           // 1h 30m monthly grace in minutes
        $cutoffMins = 9 * 60;  // 540 — 09:00 office start (minutes-since-midnight)

        // Full date-string set of non-working days for O(1) lookup.
        $nwDateSet = array_flip(array_keys($nwMap));

        /**
         * Parse any time representation to minutes-since-midnight (integer).
         * Handles: "09:45", "09:45:00", "2024-04-15 09:45:00", Carbon objects.
         * Uses pure string/integer math — no Carbon, no timezone issues.
         */
        $toMins = function ($raw): int {
            $str = (string) $raw;
            // If the string contains a space (datetime) or 'T' (ISO), grab the time part
            if (str_contains($str, ' ')) $str = trim(substr(strrchr($str, ' '), 1));
            if (str_contains($str, 'T')) $str = trim(substr(strrchr($str, 'T'), 1));
            $parts = explode(':', $str);
            return (int)($parts[0] ?? 0) * 60 + (int)($parts[1] ?? 0);
        };

        $empStats  = [];
        $lateByDay = [];   // [$empId][$dayNum] = late minutes — for grid display

        // Bulk-load leave requests for this month (avoid N+1)
        $mStart = $startDate->toDateString();
        $mEnd   = $endDate->toDateString();
        $leaveRequestsByEmp = LeaveRequest::with('leaveType')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->whereDate('end_date',   '>=', $mStart)
            ->whereDate('start_date', '<=', $mEnd)
            ->get()
            ->groupBy('employee_id');

        // Bulk-load comp-off records for this month (avoid N+1)
        $compOffsByEmp = CompOff::whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'availed')
            ->whereBetween('availed_date', [$mStart, $mEnd])
            ->get()
            ->groupBy('employee_id');

        foreach ($employees as $emp) {
            $empRecs   = $allRecords->where('employee_id', $emp->id);
            $lateMins  = 0;
            $absentCnt = 0;
            $halfCnt   = 0;
            $otHrs     = 0.0;
            $lateByDay[$emp->id] = [];

            // Calculate paid leave days: approved paid requests + comp-offs
            // (mirrors SalarySlipController::computePayroll logic for consistency)
            $paidLeaveDays = 0;
            $leaveReqs = $leaveRequestsByEmp->get($emp->id, collect());
            foreach ($leaveReqs as $req) {
                $isTypePaid = $req->leaveType?->is_paid ?? true;
                if (!$isTypePaid) continue;

                $cur = Carbon::parse(max((string)$req->start_date, $mStart));
                $end = Carbon::parse(min((string)$req->end_date, $mEnd));

                while ($cur->lte($end)) {
                    if (WorkCalendar::isWorkingDay($cur)) {
                        $paidLeaveDays++;
                    }
                    $cur->addDay();
                }
            }
            // Add comp-off days
            $paidLeaveDays += $compOffsByEmp->get($emp->id, collect())->count();

            foreach ($empRecs as $rec) {
                // OT accumulates for every day — employees can work overtime on
                // weekends/holidays too, so count it before the non-working day gate.
                if (($rec->ot_hours ?? 0) > 0) $otHrs += (float) $rec->ot_hours;

                // Skip non-working days for penalty calculations (late/absent/half-day)
                $recDateStr = Carbon::parse($rec->date)->toDateString();
                if (isset($nwDateSet[$recDateStr])) continue;

                // on_leave / comp_off / on_duty = not absent/late/half-day
                if (in_array($rec->status, ['on_leave', 'comp_off', 'on_duty'])) continue;

                // Absent: explicit absent OR present/late with no checkout
                if ($rec->status === 'absent' ||
                    (in_array($rec->status, ['present', 'late']) && empty($rec->check_out))) {
                    $absentCnt++;
                    continue;
                }

                if ($rec->status === 'half_day') {
                    $halfCnt++;
                    continue;
                }

                // Late — compute minutes-late using pure integer arithmetic
                if ($rec->status === 'late' && !empty($rec->check_in) && !empty($rec->check_out)) {
                    $ciMins  = $toMins($rec->check_in);
                    $dayLate = max(0, $ciMins - $cutoffMins);

                    $dayNum = (int) Carbon::parse($rec->date)->format('j');
                    $lateByDay[$emp->id][$dayNum] = $dayLate;
                    $lateMins += $dayLate;
                }
            }

            $exceeds       = $lateMins > $lateGrace;
            $exceededMins  = $exceeds ? ($lateMins - $lateGrace) : 0;
            $deductMins    = $exceeds ? ($lateMins * 2) : 0;
            $remainingPerm = max(0, $lateGrace - $lateMins);

            $empStats[$emp->id] = [
                'late_mins'      => $lateMins,
                'remaining_perm' => $remainingPerm,
                'exceeded_mins'  => $exceededMins,
                'deduct_mins'    => $deductMins,
                'half_days'      => $halfCnt,
                'absent_days'    => $absentCnt,
                'paid_leave_days'=> $paidLeaveDays,  // NEW: approved paid leaves + comp-offs
                'ot_hours'       => round($otHrs, 2),
                'late_exceeded'  => $exceeds,
            ];
        }

        return view('attendance.report', compact(
            'employees', 'records', 'month', 'year', 'days', 'startDate',
            'nonWorkingDays', 'workingHolidayDays', 'compOffDays', 'totalWorkingDays', 'empStats', 'lateByDay'
        ));
    }

    /** Handle Excel/XLS attendance import */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $import = new AttendanceImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        if (!empty($import->errors)) {
            return back()->with('import_error', implode(' | ', $import->errors));
        }

        return redirect()
            ->route('attendance.index', ['date' => $import->date])
            ->with('import_result', [
                'date'     => $import->date,
                'saved'    => $import->saved,
                'updated'  => $import->updated,
                'skipped'  => $import->skipped,
                'warnings' => $import->warnings,
            ]);
    }

    /** Handle monthly XLS attendance import (one block per employee) */
    public function importMonthly(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        $import = new MonthlyAttendanceImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        if (!empty($import->errors)) {
            return back()->with('import_error', implode('<br>', $import->errors));
        }

        return back()->with('monthly_import_result', [
            'month'    => $import->reportMonth,
            'employees'=> $import->empCount,
            'saved'    => $import->saved,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
            'warnings' => $import->warnings,
        ]);
    }

    private function calcHours(?string $in, ?string $out): ?float
    {
        if (!$in || !$out) return null;
        try {
            $diff = Carbon::parse($out)->diffInMinutes(Carbon::parse($in));
            return round($diff / 60, 2);
        } catch (\Exception) {
            return null;
        }
    }
}
