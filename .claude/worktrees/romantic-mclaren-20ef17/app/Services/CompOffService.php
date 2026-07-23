<?php

namespace App\Services;

use App\Helpers\WorkCalendar;
use App\Models\Attendance;
use App\Models\CompOffCredit;
use App\Models\CompOffWorkingDay;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;

class CompOffService
{
    // ── Leave type lookup ─────────────────────────────────────────────────────

    public function getCompOffLeaveType(): ?LeaveType
    {
        return LeaveType::where('is_comp_off', true)->where('status', 'active')->first();
    }

    // ── Balance ───────────────────────────────────────────────────────────────

    /**
     * Comp off balance for an employee.
     * Balance = total credited records - total approved comp off days used.
     */
    public function getBalance(int|Employee $employee): int
    {
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;

        $credited = CompOffCredit::where('employee_id', $employeeId)
            ->where('status', 'credited')
            ->count();

        $used = $this->getUsedDays($employeeId);

        return max(0, $credited - $used);
    }

    /**
     * Total approved comp off days used (across all time).
     */
    public function getUsedDays(int $employeeId): int
    {
        $type = $this->getCompOffLeaveType();
        if (!$type) return 0;

        return (int) LeaveRequest::where('employee_id', $employeeId)
            ->where('leave_type_id', $type->id)
            ->where('status', 'approved')
            ->sum('days_requested');
    }

    /**
     * Validate that an employee has enough balance.
     * Returns error message string on failure, null on success.
     *
     * @param int      $employeeId
     * @param int      $daysRequested
     * @param int|null $excludeRequestId  Exclude this leave request from used-days count (for edits)
     */
    public function checkBalance(int $employeeId, int $daysRequested, ?int $excludeRequestId = null): ?string
    {
        $type = $this->getCompOffLeaveType();
        if (!$type) {
            return 'Comp Off leave type is not configured. Please contact admin.';
        }

        $credited = CompOffCredit::where('employee_id', $employeeId)
            ->where('status', 'credited')
            ->count();

        $used = (int) LeaveRequest::where('employee_id', $employeeId)
            ->where('leave_type_id', $type->id)
            ->where('status', 'approved')
            ->when($excludeRequestId, fn ($q) => $q->where('id', '!=', $excludeRequestId))
            ->sum('days_requested');

        $balance = max(0, $credited - $used);

        if ($balance < $daysRequested) {
            $emp = Employee::find($employeeId);
            return 'Insufficient Comp Off balance for '
                . ($emp?->full_name ?? 'this employee')
                . '. Available: ' . $balance . ' day(s), Requested: ' . $daysRequested . ' day(s). '
                . 'Earn Comp Off credits by working on public holidays or weekly offs.';
        }

        return null;
    }

    // ── Auto-credit from attendance ───────────────────────────────────────────

    /**
     * Called by AttendanceObserver after any attendance record is saved.
     * Creates a comp off credit if employee is present on a company-declared
     * working holiday (or any naturally non-working day).
     * Cancels existing credit if employee is marked absent/on_leave.
     */
    public function processAttendanceCredit(Attendance $attendance): void
    {
        $dateObj    = Carbon::parse($attendance->date);
        $dateString = $dateObj->toDateString();

        // Resolve classification: declared working day takes priority, then natural non-working day
        $declared = CompOffWorkingDay::forDate($dateString);

        if (!$declared && WorkCalendar::isWorkingDay($dateObj)) {
            // Regular working day — no comp off applies
            return;
        }

        if ($declared) {
            $dayType     = $declared->day_type;
            $holidayName = $declared->holiday_name;
        } else {
            [$dayType, $holidayName] = $this->classifyNonWorkingDay($dateObj);
        }

        $presentStatuses = ['present', 'late', 'half_day'];

        if (in_array($attendance->status, $presentStatuses)) {
            // Employee worked on a non-working day → credit or restore
            CompOffCredit::updateOrCreate(
                [
                    'employee_id' => $attendance->employee_id,
                    'work_date'   => $dateObj->toDateString(),
                ],
                [
                    'day_type'     => $dayType,
                    'holiday_name' => $holidayName,
                    'status'       => 'credited',
                ]
            );
        } else {
            // Employee not present → cancel credit (if any)
            CompOffCredit::where('employee_id', $attendance->employee_id)
                ->where('work_date', $dateObj->toDateString())
                ->update(['status' => 'cancelled']);
        }
    }

    /**
     * Bulk re-scan attendance records across a date range and sync comp off credits.
     * Useful for backfilling historical data.
     * Returns count of attendance records processed.
     */
    public function syncCreditsForRange(Carbon $from, Carbon $to): int
    {
        // Collect non-working dates + any declared company working days in the range
        $declaredDates = CompOffWorkingDay::whereBetween('work_date', [
            $from->toDateString(), $to->toDateString(),
        ])->pluck('work_date')->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())->toArray();

        $nonWorkingDates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (!WorkCalendar::isWorkingDay($d)) {
                $nonWorkingDates[] = $d->toDateString();
            }
        }

        $eligibleDates = array_unique(array_merge($nonWorkingDates, $declaredDates));

        if (empty($eligibleDates)) return 0;

        $attendances = Attendance::whereIn('date', $eligibleDates)->get();

        foreach ($attendances as $att) {
            $this->processAttendanceCredit($att);
        }

        return $attendances->count();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Classify a non-working day into (day_type, holiday_name).
     *
     * @return array{0: string, 1: string}
     */
    public function classifyNonWorkingDay(Carbon $date): array
    {
        if ($date->isSunday()) {
            return ['sunday', 'Sunday'];
        }

        if (WorkCalendar::isFirstOrThirdSaturday($date)) {
            $satCount = 0;
            $tmp = $date->copy()->startOfMonth();
            while ($tmp->lte($date)) {
                if ($tmp->isSaturday()) $satCount++;
                $tmp->addDay();
            }
            return ['saturday', ($satCount === 1 ? '1st' : '3rd') . ' Saturday'];
        }

        $holiday = Holiday::where('date', $date->toDateString())->first();
        return ['public_holiday', $holiday?->name ?? 'Public Holiday'];
    }
}
