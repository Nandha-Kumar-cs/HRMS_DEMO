<?php

namespace App\Http\Controllers;

use App\Exports\LeaveStatusExport;
use App\Helpers\WorkCalendar;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class LeaveStatusController extends Controller
{
    /**
     * Monthly paid-leave quota per employee.
     * Each employee gets exactly 1 paid leave per month.
     * Only APPROVED leave requests can use this quota (max 1 day).
     * Absent/unapproved leave → salary deducted in payroll, not counted here.
     */
    const MONTHLY_QUOTA = 1;

    // ── Main page (yearly grid) ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $year        = (int) $request->get('year',       now()->year);
        $deptId      = $request->get('department');
        $search      = $request->get('search');
        $empStatus   = $request->get('emp_status', 'active');
        $leaveTypeId = $request->get('leave_type');

        // ── Employee query ────────────────────────────────────────────────────
        $employees = Employee::with('department')
            ->when($empStatus !== 'all', fn ($q) => $q->where('status', $empStatus))
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('full_name', 'like', "%{$search}%")
                   ->orWhere('employee_code', 'like', "%{$search}%");
            }))
            ->orderBy('full_name')
            ->paginate(30)
            ->withQueryString();

        // Must use getCollection() — collect(paginator) wraps metadata, not rows
        $employeeIds = $employees->getCollection()->pluck('id');

        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear   = Carbon::create($year, 12, 31)->endOfDay();

        // ── Load ALL approved leave requests for the year (with leave type) ────
        // leaveType.is_paid determines whether each leave request is paid or unpaid.
        $leavesByEmp = LeaveRequest::with('leaveType')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->whereDate('end_date',   '>=', $startOfYear->toDateString())
            ->whereDate('start_date', '<=', $endOfYear->toDateString())
            ->get()
            ->groupBy('employee_id');

        // ── Build yearly grid ─────────────────────────────────────────────────
        // $grid[$empId][$month] = ['days' => int, 'balance' => int]
        // $grid[$empId]['annual'] = ['days' => int, 'balance' => int, 'quota' => int]
        $grid   = [];
        $totals = array_fill(1, 12, ['quota' => 0, 'taken' => 0, 'paid_taken' => 0, 'unpaid_taken' => 0]);
        $totals['annual'] = ['quota' => 0, 'taken' => 0, 'paid_taken' => 0, 'unpaid_taken' => 0];

        foreach ($employees->getCollection() as $emp) {
            $empLeaves       = $leavesByEmp->get($emp->id, collect());
            $annualPaidDays  = 0;
            $annualUnpaidDays = 0;

            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::create($year, $m, 1)->startOfDay();
                $mEnd   = $mStart->copy()->endOfMonth();

                $monthLeaves = $empLeaves->filter(
                    fn ($r) => Carbon::parse($r->start_date)->lte($mEnd)
                            && Carbon::parse($r->end_date)->gte($mStart)
                );

                [$paidDays, $unpaidDays] = $this->countLeavesByType($monthLeaves, $mStart, $mEnd);

                $annualPaidDays   += $paidDays;
                $annualUnpaidDays += $unpaidDays;

                $grid[$emp->id][$m] = [
                    'paid_days'   => $paidDays,
                    'unpaid_days' => $unpaidDays,
                    'days'        => $paidDays + $unpaidDays,    // total for backward compat
                    'balance'     => self::MONTHLY_QUOTA - $paidDays, // paid-quota balance
                ];

                $totals[$m]['quota']        += self::MONTHLY_QUOTA;
                $totals[$m]['paid_taken']   += $paidDays;
                $totals[$m]['unpaid_taken'] += $unpaidDays;
                $totals[$m]['taken']        += $paidDays + $unpaidDays;
            }

            $monthsElapsed    = ($year == now()->year) ? now()->month : 12;
            $annualQuota      = $monthsElapsed * self::MONTHLY_QUOTA;
            $annualTotalDays  = $annualPaidDays + $annualUnpaidDays;

            $grid[$emp->id]['annual'] = [
                'days'        => $annualTotalDays,
                'paid_days'   => $annualPaidDays,
                'unpaid_days' => $annualUnpaidDays,
                'balance'     => $annualQuota - $annualPaidDays, // only paid leaves vs quota
                'quota'       => $annualQuota,
            ];

            $totals['annual']['quota']        += $annualQuota;
            $totals['annual']['paid_taken']   += $annualPaidDays;
            $totals['annual']['unpaid_taken'] += $annualUnpaidDays;
            $totals['annual']['taken']        += $annualTotalDays;
        }

        $departments = Department::orderBy('name')->get();
        $leaveTypes  = LeaveType::where('status', 'active')->orderBy('name')->get();

        return view('leave-status.index', compact(
            'employees', 'grid', 'departments', 'totals',
            'year', 'deptId', 'search', 'empStatus',
            'leaveTypes', 'leaveTypeId'
        ));
    }

    // ── Excel export ──────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $year        = (int) $request->get('year',       now()->year);
        $deptId      = $request->get('department');
        $search      = $request->get('search');
        $empStatus   = $request->get('emp_status', 'active');
        $leaveTypeId = $request->get('leave_type');

        // Load ALL employees (no pagination) for export
        $employees = Employee::with('department')
            ->when($empStatus !== 'all', fn ($q) => $q->where('status', $empStatus))
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('full_name', 'like', "%{$search}%")
                   ->orWhere('employee_code', 'like', "%{$search}%");
            }))
            ->orderBy('full_name')
            ->get();

        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear   = Carbon::create($year, 12, 31)->endOfDay();

        $leavesByEmp = LeaveRequest::with('leaveType')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->whereDate('end_date',   '>=', $startOfYear->toDateString())
            ->whereDate('start_date', '<=', $endOfYear->toDateString())
            ->get()
            ->groupBy('employee_id');

        $grid = [];
        foreach ($employees as $emp) {
            $empLeaves        = $leavesByEmp->get($emp->id, collect());
            $annualPaidDays   = 0;
            $annualUnpaidDays = 0;

            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::create($year, $m, 1)->startOfDay();
                $mEnd   = $mStart->copy()->endOfMonth();

                $monthLeaves = $empLeaves->filter(
                    fn ($r) => Carbon::parse($r->start_date)->lte($mEnd)
                            && Carbon::parse($r->end_date)->gte($mStart)
                );

                [$paidDays, $unpaidDays] = $this->countLeavesByType($monthLeaves, $mStart, $mEnd);

                $annualPaidDays   += $paidDays;
                $annualUnpaidDays += $unpaidDays;

                $grid[$emp->id][$m] = [
                    'paid_days'   => $paidDays,
                    'unpaid_days' => $unpaidDays,
                    'days'        => $paidDays + $unpaidDays,
                    'balance'     => self::MONTHLY_QUOTA - $paidDays,
                ];
            }

            $monthsElapsed = ($year == now()->year) ? now()->month : 12;
            $annualQuota   = $monthsElapsed * self::MONTHLY_QUOTA;

            $grid[$emp->id]['annual'] = [
                'days'        => $annualPaidDays + $annualUnpaidDays,
                'paid_days'   => $annualPaidDays,
                'unpaid_days' => $annualUnpaidDays,
                'balance'     => $annualQuota - $annualPaidDays,
                'quota'       => $annualQuota,
            ];
        }

        return Excel::download(
            new LeaveStatusExport($employees, $grid, $year),
            "leave-status-{$year}.xlsx"
        );
    }

    // ── Leave day counter (paid vs unpaid) ───────────────────────────────────

    /**
     * Count unique approved working-days split by leave type (paid / unpaid).
     * Uses leave_type.is_paid to classify each request.
     * Defaults to paid if no leave type is associated.
     *
     * @return array{0: int, 1: int}  [$paidDays, $unpaidDays]
     */
    private function countLeavesByType($monthLeaves, Carbon $mStart, Carbon $mEnd): array
    {
        $paidDates   = []; // date => true
        $unpaidDates = []; // date => true

        foreach ($monthLeaves as $req) {
            $isPaid = $req->leaveType?->is_paid ?? true;

            $cur = Carbon::parse($req->start_date->toDateString())->max($mStart)->startOfDay();
            $end = Carbon::parse($req->end_date->toDateString())->min($mEnd)->startOfDay();

            while ($cur->lte($end)) {
                $ds = $cur->toDateString();
                if (WorkCalendar::isWorkingDay($cur)
                    && !isset($paidDates[$ds])
                    && !isset($unpaidDates[$ds])) {
                    if ($isPaid) {
                        $paidDates[$ds] = true;
                    } else {
                        $unpaidDates[$ds] = true;
                    }
                }
                $cur->addDay();
            }
        }

        return [count($paidDates), count($unpaidDates)];
    }
}
