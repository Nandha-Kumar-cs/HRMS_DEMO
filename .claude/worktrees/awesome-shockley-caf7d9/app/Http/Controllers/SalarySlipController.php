<?php

namespace App\Http\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\WorkCalendar;
use App\Http\Requests\StoreSalarySlipRequest;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeBonus;
use App\Models\EmployeeIncrement;
use App\Models\LeaveRequest;
use App\Models\LoanRepayment;
use App\Models\SalaryComponent;
use App\Models\Department;
use App\Models\SalarySlip;
use App\Traits\HasSalaryGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalarySlipController extends Controller
{
    use HasSalaryGuard;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $month      = $request->get('month');
        $year       = $request->get('year');
        $search     = $request->get('search');
        $department = $request->get('department');

        $query = SalarySlip::with('employee.department')
            ->orderByDesc('year')->orderByDesc('month');

        if ($month)      $query->where('month', $month);
        if ($year)       $query->where('year', $year);
        if ($search)     $query->whereHas('employee', fn($q) =>
                             $q->where('full_name', 'like', "%$search%")
                               ->orWhere('employee_code', 'like', "%$search%"));
        if ($department) $query->whereHas('employee', fn($q) =>
                             $q->where('department_id', $department));

        $slips       = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('salary-slips.index', compact(
            'slips', 'departments', 'month', 'year', 'search', 'department'
        ));
    }

    // ── Monthly Payroll Calculation Register ──────────────────────────────────

    public function calculate(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        $totalWorkingDays = WorkCalendar::countWorkingDays($startDate, $endDate);

        // Cache salary components — rarely change, no need to hit DB on every page load
        $components = \Illuminate\Support\Facades\Cache::remember(
            'salary_components_all',
            now()->addHours(6),
            fn () => SalaryComponent::all()
        );

        $employees = Employee::where('status', 'active')
            ->with([
                'department',
                'designation',
                'attendance'  => fn ($q) => $q->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]),
                'salarySlips' => fn ($q) => $q->where('month', $month)->where('year', $year),
                'activeLoans',
            ])
            ->orderBy('full_name')
            ->get();

        // Bulk-load ALL increments for active employees in one query (avoids N+1
        // inside getSalaryForMonth when iterating the employee list).
        $employeeIds  = $employees->pluck('id');
        $allIncrements = EmployeeIncrement::whereIn('employee_id', $employeeIds)->get();

        $rows = $employees->map(function (Employee $emp) use ($month, $year, $totalWorkingDays, $components, $allIncrements) {
            // Use effective salary for this month (increment-aware, no extra DB hit).
            $effectiveSalary = $this->getSalaryForMonth($emp, $month, $year, $allIncrements);

            $data = $this->computePayroll(
                $emp,
                $emp->attendance,
                $totalWorkingDays,
                $components,
                $month,
                $year,
                $effectiveSalary['fixed_salary'],
                $effectiveSalary['variable_salary']
            );
            $data['employee'] = $emp;
            $data['slip']     = $emp->salarySlips->first();
            return $data;
        });

        // Payroll summary totals
        $totals = [
            'gross'       => $rows->sum('gross_salary'),
            'deductions'  => $rows->sum('total_deductions'),
            'net'         => $rows->sum('net_salary'),
            'pf'          => $rows->sum(fn ($r) => $r['deductions']['PF'] ?? 0),
            'esi'         => $rows->sum(fn ($r) => $r['deductions']['ESI'] ?? 0),
            'absent_late' => $rows->sum(fn ($r) => ($r['absent_deduction'] ?? 0) + ($r['late_deduction'] ?? 0)),
            'ot_amount'   => $rows->sum(fn ($r) => $r['ot_amount'] ?? 0),
            'other_ded'   => $rows->sum(function ($r) {
                $pfEsi   = ($r['deductions']['PF'] ?? 0) + ($r['deductions']['ESI'] ?? 0);
                $attDed  = ($r['absent_deduction'] ?? 0) + ($r['late_deduction'] ?? 0);
                return max(0, ($r['total_deductions'] ?? 0) - $pfEsi - $attDed);
            }),
        ];

        return view('salary-slips.calculate', compact('rows', 'month', 'year', 'totalWorkingDays', 'startDate', 'totals'));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $employees = Employee::orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::with('activeLoans')->find($request->employee) : null;
        return view('salary-slips.create', compact('employees', 'selected'));
    }

    public function store(StoreSalarySlipRequest $request)
    {
        $employee = Employee::with('activeLoans')->findOrFail($request->employee_id);

        if ($redirect = $this->guardSalary($employee)) {
            return $redirect;
        }

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── Check if a payslip already exists for this employee / month / year ──
        $existing = SalarySlip::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && !$request->boolean('force_regenerate')) {
            return redirect()
                ->route('salary-slips.create', ['employee' => $employee->id])
                ->withInput()
                ->with('slip_exists', [
                    'slip_id'    => $existing->id,
                    'name'       => $employee->full_name,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                    'year'       => $year,
                    'net'        => $existing->net_salary,
                ]);
        }

        // ── Run payroll calculation ───────────────────────────────────────────
        $totalWorkingDays = WorkCalendar::countWorkingDays($startDate, $endDate);
        $components = \Illuminate\Support\Facades\Cache::remember(
            'salary_components_all',
            now()->addHours(6),
            fn () => SalaryComponent::all()
        );

        $attendanceRecords = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        if ($existing) {
            // ── REGENERATE: resolve salary from live increment history ──────────
            // Industry standard: regeneration re-reads the authoritative salary
            // history so that corrections (deleted increments, direct salary edits)
            // are immediately reflected.  The salary stored on the slip row is
            // updated to the freshly-resolved value so the payslip PDF is correct.
            $effectiveSalary = $this->getSalaryForMonth($employee, $month, $year);

            $data = $this->computePayroll(
                $employee,
                $attendanceRecords,
                $totalWorkingDays,
                $components,
                $month,
                $year,
                $effectiveSalary['fixed_salary'],
                $effectiveSalary['variable_salary']
            );

            // Persist the refreshed salary snapshot AND all recomputed fields
            $existing->update([
                'fixed_salary'       => $effectiveSalary['fixed_salary'],
                'variable_salary'    => $effectiveSalary['variable_salary'],
                'allowances'         => $data['allowances'],
                'deductions'         => $data['deductions'],
                'net_salary'         => $data['net_salary'],
                'attendance_summary' => $data['attendance_summary'],
            ]);
            $slip = $existing->fresh();

            $workingDays = $data['attendance_summary']['total_working_days'] ?? '--';
            return redirect()
                ->route('salary-slips.show', $slip)
                ->with('success', "Payslip for {$employee->full_name} ({$workingDays} working days) has been regenerated successfully.");
        }

        // ── NEW SLIP: resolve the salary that was effective in this payroll month ──
        // BUG-2 / BUG-3 FIX: Look up increment history so April slip uses April
        // salary even if the employee was subsequently incremented.
        $effectiveSalary = $this->getSalaryForMonth($employee, $month, $year);
        $ctcSnapshot     = $effectiveSalary['fixed_salary'];
        $varSnapshot     = $effectiveSalary['variable_salary'];

        $data = $this->computePayroll(
            $employee,
            $attendanceRecords,
            $totalWorkingDays,
            $components,
            $month,
            $year,
            $ctcSnapshot,
            $varSnapshot
        );

        $payload = [
            'employee_id'        => $employee->id,
            'month'              => $month,
            'year'               => $year,
            'fixed_salary'       => $ctcSnapshot,   // ← effective-month salary snapshot
            'variable_salary'    => $varSnapshot,
            'allowances'         => $data['allowances'],
            'deductions'         => $data['deductions'],
            'net_salary'         => $data['net_salary'],
            'attendance_summary' => $data['attendance_summary'],
        ];

        $slip = SalarySlip::create($payload);

        // ── Record loan repayments (only on first generation) ─────────────────
        // Only process loans that are active for this payroll month:
        //   • Disbursed on or before the payroll month end
        //   • Payroll month does not exceed the loan's final deduction month
        foreach ($employee->activeLoans as $loan) {
            if (!$loan->isActiveForPayroll($month, $year)) {
                continue; // outside the loan tenure — skip
            }
            if ($loan->date_given && $loan->date_given->gt($endDate)) {
                continue; // loan disbursed after this payroll period — skip
            }

            LoanRepayment::create([
                'employee_loan_id' => $loan->id,
                'amount_paid'      => $loan->monthly_deduction,
                'payment_date'     => $endDate->toDateString(),
                'salary_slip_id'   => $slip->id,
                'note'             => 'Auto-deducted via salary slip ' . $month . '/' . $year,
            ]);

            $loan->increment('paid_months');
            if ($loan->fresh()->paid_months >= $loan->total_months) {
                $loan->update(['status' => 'completed']);
            }
        }

        ActivityLog::record('generated', 'SalarySlip',
            "Generated salary slip for {$employee->full_name} ({$employee->employee_code}) — " . date('F', mktime(0,0,0,$month,1)) . " {$year}"
        );

        return redirect()->route('salary-slips.show', $slip)->with('success', 'Salary slip generated successfully.');
    }

    // ── Show / PDF ────────────────────────────────────────────────────────────

    public function show(SalarySlip $salarySlip)
    {
        $salarySlip->load('employee.department', 'employee.designation', 'employee.entity');

        // Find the increment record that determined this payslip's salary
        $lastDayOfMonth = \Carbon\Carbon::create($salarySlip->year, $salarySlip->month, 1)
            ->endOfMonth()->toDateString();
        $effectiveIncrement = EmployeeIncrement::where('employee_id', $salarySlip->employee_id)
            ->whereDate('effective_date', '<=', $lastDayOfMonth)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        return view('salary-slips.show', compact('salarySlip', 'effectiveIncrement'));
    }

    public function pdf(SalarySlip $salarySlip)
    {
        $salarySlip->load('employee.department', 'employee.designation', 'employee.entity');
        $pdf = Pdf::loadView('pdf.salary-slip', compact('salarySlip'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream(
            'salary-slip-' . $salarySlip->employee->employee_code
            . '-' . $salarySlip->month . '-' . $salarySlip->year . '.pdf'
        );
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(SalarySlip $salarySlip)
    {
        $salarySlip->load('loanRepayments.loan');

        // Reverse auto-created loan repayments so loan balances stay accurate
        foreach ($salarySlip->loanRepayments as $repayment) {
            $loan = $repayment->loan;
            if ($loan && $loan->paid_months > 0) {
                $loan->decrement('paid_months');
                // Re-open completed/closed loans that had their final payment reversed
                if (in_array($loan->status, ['completed', 'closed'])) {
                    $loan->update(['status' => 'active']);
                }
            }
            $repayment->delete();
        }

        $employee = $salarySlip->employee;
        $label    = $salarySlip->month_name . ' ' . $salarySlip->year;
        $salarySlip->delete();

        ActivityLog::record('deleted', 'SalarySlip',
            "Deleted salary slip for {$employee->full_name} ({$employee->employee_code}) — {$label}"
        );

        return redirect()
            ->route('salary-slips.index')
            ->with('success', "Salary slip for {$employee->full_name} ({$label}) deleted successfully. Any associated loan repayments have been reversed.");
    }

    // ── Effective Salary Resolver ─────────────────────────────────────────────

    /**
     * Determine the correct CTC (fixed_salary) that was active for a given
     * payroll month by walking the increment history.
     *
     * Three-step resolution:
     *
     *  Step 1 — Applicable increment (on or before month end)
     *    Find the most recent increment whose effective_date ≤ last day of the
     *    target month.  That increment's new_salary is authoritative for this month.
     *    Secondary sort by id breaks ties when two revisions share the same date
     *    (e.g. a direct edit and a manual increment both created on the same day).
     *
     *  Step 2 — Look-ahead (future increment exists)
     *    If no increment was active for this month, look for the EARLIEST increment
     *    whose effective_date is AFTER the last day of this month.  Its
     *    previous_salary tells us what the salary WAS before that revision took
     *    effect — i.e. the correct salary for this historical month.
     *    This prevents an April salary change from bleeding into a March payslip.
     *    Guard: only use this if previous_salary > 0 (skip malformed records).
     *
     *  Step 3 — Absolute fallback
     *    No increment history exists at all.  Use employee.fixed_salary directly.
     *    This path is only hit for employees who were never touched by the
     *    increment module and whose salary was never changed after slip generation.
     *
     * @param  Employee  $employee
     * @param  int       $month    1-12
     * @param  int       $year
     * @return array{fixed_salary: float, variable_salary: float}
     */
    /**
     * Resolve the effective salary for a given month/year.
     *
     * @param  \Illuminate\Support\Collection|null $allIncrements  Pre-loaded increment
     *         collection for this employee (pass from bulk-load to avoid N+1).
     *         When null the method falls back to individual DB queries.
     */
    private function getSalaryForMonth(
        Employee   $employee,
        int        $month,
        int        $year,
        ?Collection $allIncrements = null
    ): array {
        $lastDayOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        if ($allIncrements !== null) {
            // Use pre-loaded collection (zero extra DB queries)
            $empIncrements = $allIncrements->where('employee_id', $employee->id);

            // Step 1: Most recent increment ≤ end of month
            $applicableIncrement = $empIncrements
                ->filter(fn($i) => $i->effective_date <= $lastDayOfMonth)
                ->sortByDesc('effective_date')->sortByDesc('id')
                ->first();

            if ($applicableIncrement) {
                return [
                    'fixed_salary'    => (float) $applicableIncrement->new_salary,
                    'variable_salary' => (float) $employee->variable_salary,
                ];
            }

            // Step 2: Look-ahead — earliest increment AFTER this month
            $nextIncrement = $empIncrements
                ->filter(fn($i) => $i->effective_date > $lastDayOfMonth)
                ->sortBy('effective_date')->sortBy('id')
                ->first();
        } else {
            // ── Step 1: Most recent increment on or before end of month ──────────
            $applicableIncrement = EmployeeIncrement::where('employee_id', $employee->id)
                ->whereDate('effective_date', '<=', $lastDayOfMonth)
                ->orderByDesc('effective_date')
                ->orderByDesc('id')   // tiebreaker: last-created wins on same date
                ->first();

            if ($applicableIncrement) {
                return [
                    'fixed_salary'    => (float) $applicableIncrement->new_salary,
                    'variable_salary' => (float) $employee->variable_salary,
                ];
            }

            // ── Step 2: Look-ahead — earliest increment AFTER this month ─────────
            $nextIncrement = EmployeeIncrement::where('employee_id', $employee->id)
                ->whereDate('effective_date', '>', $lastDayOfMonth)
                ->orderBy('effective_date')
                ->orderBy('id')
                ->first();
        }

        // Step 2 result (shared path)
        if (isset($nextIncrement) && $nextIncrement && (float) $nextIncrement->previous_salary > 0) {
            return [
                'fixed_salary'    => (float) $nextIncrement->previous_salary,
                'variable_salary' => (float) $employee->variable_salary,
            ];
        }

        // ── Step 3: No increment history at all — use employee.fixed_salary ──
        return [
            'fixed_salary'    => (float) $employee->fixed_salary,
            'variable_salary' => (float) $employee->variable_salary,
        ];
    }

    // ── Shared Payroll Calculation Engine ─────────────────────────────────────

    /**
     * Compute full payroll for one employee given their attendance collection.
     *
     * @param  float|null  $ctcOverride       If provided, overrides employee.fixed_salary
     *                                         (used for snapshot-based regeneration and
     *                                          effective-month salary resolution).
     * @param  float|null  $variableOverride   Same for variable_salary.
     */
    private function computePayroll(
        Employee   $employee,
        Collection $attendanceRecords,
        int        $totalWorkingDays,
        Collection $components,
        ?int       $month            = null,
        ?int       $year             = null,
        ?float     $ctcOverride      = null,
        ?float     $variableOverride = null
    ): array {
        // BUG-2 / BUG-3 FIX: Use the overridden (effective / snapshotted) salary
        // instead of the employee's current fixed_salary.
        $ctcPerMonth    = $ctcOverride    ?? (float) $employee->fixed_salary;
        $variableSalary = $variableOverride ?? (float) $employee->variable_salary;

        // Payroll end-date (used for loan date validation)
        $slipEndDate = ($month && $year)
            ? Carbon::create($year, $month, 1)->endOfMonth()
            : now()->endOfMonth();

        // ── Attendance breakdown ──────────────────────────────────────────────
        // Safety rule: present/late records with no checkout are treated as absent
        // (store() auto-sets this going forward; this handles older records).
        $noCheckoutAbsent = $attendanceRecords
            ->filter(fn($r) => in_array($r->status, ['present', 'late']) && empty($r->check_out))
            ->count();
        // on_duty records don't require check_in/check_out — count them directly

        $presentDays  = $attendanceRecords->whereIn('status', ['present', 'late', 'on_duty'])->count() - $noCheckoutAbsent;
        $compOffDays  = $attendanceRecords->where('status', 'comp_off')->count();
        $halfDays     = $attendanceRecords->where('status', 'half_day')->count();
        $leaveDays    = $attendanceRecords->where('status', 'on_leave')->count();
        $lateDays    = $attendanceRecords->where('status', 'late')
                           ->filter(fn($r) => !empty($r->check_out))  // only fully clocked-in days
                           ->count();

        // ── Leave classification (paid vs unpaid) ─────────────────────────────
        // Rule 1: APPROVED leave requests → paid leave (no salary deduction),
        //         regardless of leave type.  This includes Medical Leave, Casual
        //         Leave, etc. — approval is the only deciding factor.
        //         We check leave-request dates (not just attendance status) so
        //         approved leave is honoured even when the attendance record is
        //         missing or marked "absent".
        // Rule 2: Each employee also gets 1 free paid leave per month for any
        //         remaining unapproved on_leave attendance records.
        // Rule 3: NOT-APPROVED leave requests → treated as unpaid (absent).
        //         Unapproved on_leave records beyond the free quota = absent.

        // Step A — collect on_leave attendance date strings
        $leaveDateStrings = $attendanceRecords->where('status', 'on_leave')
            ->map(fn($r) => Carbon::parse($r->date)->toDateString())
            ->values()->all();

        // Step B — classify approved leave requests by leave type (paid vs unpaid)
        // Approved PAID leave (is_paid = true)  → no salary deduction
        // Approved UNPAID leave (is_paid = false) → salary deducted (treated as absent)
        $approvedPaidDates   = []; // date => true  (from paid-type leave requests)
        $approvedUnpaidDates = []; // date => true  (from unpaid-type leave requests)
        $approvedPaidDays    = 0;
        $approvedUnpaidDays  = 0;

        if ($month && $year) {
            $mStart = Carbon::create($year, $month, 1)->toDateString();
            $mEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

            $approvedRequests = LeaveRequest::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('end_date',   '>=', $mStart)
                ->whereDate('start_date', '<=', $mEnd)
                ->get();

            foreach ($approvedRequests as $req) {
                // Default to paid if leave type is not set
                $isTypePaid = $req->leaveType?->is_paid ?? true;

                $cur = Carbon::parse(max((string) $req->start_date, $mStart));
                $end = Carbon::parse(min((string) $req->end_date,   $mEnd));

                while ($cur->lte($end)) {
                    $ds = $cur->toDateString();
                    // Each date counted only once across all leave requests
                    if (WorkCalendar::isWorkingDay($cur)
                        && !isset($approvedPaidDates[$ds])
                        && !isset($approvedUnpaidDates[$ds])) {
                        if ($isTypePaid) {
                            $approvedPaidDates[$ds] = true;
                            $approvedPaidDays++;
                        } else {
                            $approvedUnpaidDates[$ds] = true;
                            $approvedUnpaidDays++;
                        }
                    }
                    $cur->addDay();
                }
            }
        }

        // Step C — unapproved on_leave days: attendance on_leave not covered by any approved request
        // These are always treated as absent (salary deducted).
        $allApprovedDates    = $approvedPaidDates + $approvedUnpaidDates;
        $unapprovedLeaveDays = 0;
        foreach ($leaveDateStrings as $ds) {
            if (!isset($allApprovedDates[$ds])) {
                $unapprovedLeaveDays++;
            }
        }

        // ── Leave type-based paid/unpaid rule ─────────────────────────────────
        // • Approved leave with is_paid = true  → fully paid, NO deduction
        // • Approved leave with is_paid = false → treated as absent, salary deducted
        // • Unapproved on_leave attendance      → treated as absent, salary deducted
        // • Absent attendance                   → salary deducted
        $paidLeaveDays = $approvedPaidDays;

        // Comp-off counts as paid leave — employee earned it by working on a holiday
        $paidLeaveDays += $compOffDays;

        // absentDays = every working day not covered by present/paid leave (incl. comp-off):
        //   includes approved unpaid leave + unapproved on_leave + actual absent days
        $absentDays = max(0, $totalWorkingDays - $presentDays - ($halfDays * 0.5) - $paidLeaveDays);

        // ── OT: sum hours from attendance records ─────────────────────────────
        // OT amount is NOT stored in attendance — it is calculated here so the
        // formula is always applied consistently at payroll time.
        $otHours = 0.0;
        foreach ($attendanceRecords as $rec) {
            if ($rec->ot_hours > 0) $otHours += (float) $rec->ot_hours;
        }
        $otHours = round($otHours, 2);

        // ── Late minutes ─────────────────────────────────────────────────────
        // Only count 'late' records with a checkout — no checkout means absent.
        // Non-working days (holidays, weekends, off-Saturdays) are excluded.
        // Uses pure integer arithmetic (minutes-since-midnight) to avoid any
        // Carbon timezone/parsing ambiguity regardless of how the time is stored.
        $lateMinutes  = 0;
        $cutoffMinsSS = AppSettings::getOfficeStartMins(); // configurable via Settings → OT Settings

        // Build non-working date set once — O(1) per-record lookups thereafter
        $nwDateSetSS = [];
        if ($month && $year) {
            $mStart = Carbon::create($year, $month, 1)->startOfDay();
            $mEnd   = $mStart->copy()->endOfMonth();
            $nwDateSetSS = array_flip(array_keys(WorkCalendar::getNonWorkingDates($mStart, $mEnd)));
        }

        foreach ($attendanceRecords->where('status', 'late')->filter(fn($r) => !empty($r->check_out) && !empty($r->check_in)) as $rec) {
            // Skip non-working days
            if (isset($nwDateSetSS[Carbon::parse($rec->date)->toDateString()])) continue;

            // Parse check_in to minutes-since-midnight — handles "09:45", "09:45:00",
            // "2024-04-15 09:45:00" and Carbon objects without any timezone risk.
            $ciStr = (string) $rec->check_in;
            if (str_contains($ciStr, ' ')) $ciStr = trim(substr(strrchr($ciStr, ' '), 1));
            if (str_contains($ciStr, 'T')) $ciStr = trim(substr(strrchr($ciStr, 'T'), 1));
            $ciParts     = explode(':', $ciStr);
            $ciMins      = (int)($ciParts[0] ?? 0) * 60 + (int)($ciParts[1] ?? 0);
            $lateMinutes += max(0, $ciMins - $cutoffMinsSS);
        }

        // ── Step 1: Component allowances (% or fixed of CTC per Month) ───────
        $allowances = [];
        foreach ($components->where('type', 'allowance') as $comp) {
            if (in_array(strtolower(trim($comp->name)), ['pf', 'esi'])) continue;
            $allowances[$comp->name] = $comp->calculation_type === 'percentage'
                ? round(($comp->value / 100) * $ctcPerMonth, 2)
                : round((float) $comp->value, 2);
        }

        // ── Step 2: Extract Basic Salary (needed for PF and per-hour rate) ───
        $basicSalary = 0.0;
        foreach ($allowances as $name => $amount) {
            if (strtolower(trim($name)) === 'basic') {
                $basicSalary = $amount;
                break;
            }
        }
        if ($basicSalary <= 0) {
            $basicSalary = round($ctcPerMonth * 0.40, 2); // fallback: 40% of CTC
        }

        // ── Step 3: Special Allowance to fill CTC remainder ──────────────────
        $componentSum = array_sum($allowances);
        if ($ctcPerMonth > $componentSum + 0.01) {
            $allowances['Special Allowance'] = round($ctcPerMonth - $componentSum, 2);
        }

        // ── Step 4: Variable Pay (kept separate from CTC) ────────────────────
        if ($variableSalary > 0) {
            $allowances['Variable Pay'] = (float) $variableSalary;
        }

        // ── Step 5: OT placeholder — amount is calculated after Step 6 ──────
        // (calendarDays and basicSalary are both required for the formula.
        //  The actual $otAmount is resolved and injected into $allowances
        //  right after $calendarDays is determined in Step 6.)

        // ── Step 5b: Employee Benefit Funds (active for this month, including recurring) ──────────
        $benefits      = [];
        $benefitsTotal = 0;
        if ($month && $year) {
            $activeBenefits = EmployeeBenefit::with('fundType')
                ->where('employee_id', $employee->id)
                ->activeForPayrollMonth($month, $year)  // NEW: supports frequency-based recurrence
                ->get();
            foreach ($activeBenefits as $b) {
                // Use custom benefit_name if provided, else fund type name
                $displayName = $b->benefit_name ?? ($b->fundType?->name ?? 'Benefit Fund');

                // Multiply per-occurrence amount by number of occurrences in this month
                // e.g. weekly ₹1,500 × 4 weeks = ₹6,000 for a 30-day month
                $occurrences = $b->occurrencesInMonth($month, $year);
                $totalAmount = round((float) $b->amount * $occurrences, 2);

                // Annotate key with frequency × occurrences for clarity on the slip
                $freqLabel = $b->getFrequencyLabel();
                $key = ($occurrences > 1)
                    ? "{$displayName} ({$freqLabel} × {$occurrences})"
                    : $displayName;

                if (isset($benefits[$key])) {
                    $key .= ' #' . $b->id;
                }
                $benefits[$key] = $totalAmount;
                $benefitsTotal  += $totalAmount;
            }
        }

        // ── Step 5c: Bonuses & Incentives (approved for this month) ──────────
        $bonuses      = [];
        $bonusesTotal = 0;
        if ($month && $year) {
            $monthBonuses = EmployeeBonus::where('employee_id', $employee->id)
                ->forMonth($month, $year)
                ->approved()
                ->get();
            foreach ($monthBonuses as $bn) {
                $label = ($bn->type_label ?? ucfirst($bn->type)) . ' — ' . $bn->reason;
                if (isset($bonuses[$label])) {
                    $label .= ' #' . $bn->id;
                }
                $bonuses[$label] = (float) $bn->amount;
                $bonusesTotal   += (float) $bn->amount;
            }
        }

        $totalAdditional = $benefitsTotal + $bonusesTotal;

        // Merge into allowances with marker prefixes
        foreach ($benefits as $name => $amt) {
            $allowances['[BENEFIT] ' . $name] = $amt;
        }
        foreach ($bonuses as $name => $amt) {
            $allowances['[BONUS] ' . $name] = $amt;
        }

        // NOTE: $grossSalary is computed AFTER OT is injected below (Step 6).
        // Do NOT compute it here — OT must be included in gross.

        // ── Step 6: Per-day and per-hour rates ───────────────────────────────
        // Per-day  = CTC / CALENDAR DAYS in the month (Jan=31, Apr=30, Feb=28/29).
        // This is the standard Indian payroll formula — the denominator is total
        // calendar days, not just the working days.  The absent deduction still
        // uses the count of absent *working* days (Step 10) so the math is:
        //   gross = full monthly CTC
        //   deduction = (absent working days) × (CTC / calendar days)
        // Per-hour = per-day / 8 working hours.
        // NOTE: $calendarDays is also used for the OT rate (Step 5 uses it
        // after it is computed here). The OT label is added to allowances below.
        $calendarDays  = ($month && $year)
            ? Carbon::create($year, $month, 1)->daysInMonth
            : 30;                                   // safe fallback

        // ── OT amount (calculated here, after basicSalary and calendarDays are known) ──
        // Formula: daily_rate  = Basic / days_in_month
        //          hourly_rate = daily_rate / 8
        //          ot_rate     = hourly_rate × 2
        //          OT amount   = ot_rate × total_OT_hours  (rounded to 2 dp / paise)
        $otAmount = 0.0;
        if ($otHours > 0 && $basicSalary > 0 && $calendarDays > 0) {
            $otRate   = ($basicSalary / $calendarDays / 8) * 2;
            $otAmount = round($otHours * $otRate, 2);
        }
        if ($otAmount > 0) {
            $allowances['Overtime (' . $otHours . ' hrs)'] = $otAmount;
        }

        // ── Gross salary — computed here so OT, benefits & bonuses are all included ──
        $grossSalary = array_sum($allowances);

        // Per-day and per-hour rates are based on Basic salary (not CTC)
        $perDayRate    = $calendarDays > 0 ? round($basicSalary / $calendarDays, 4) : 0;
        $oneHourSalary = $perDayRate / 8;           // per-hour based on per-day rate
        $perHourRate   = $oneHourSalary;

        $absentDeduction = round($absentDays * $perDayRate, 2);

        // ── Late deduction: configurable monthly grace, 2× penalty ──────────
        // • ≤ grace min total late in month → no deduction at all
        // • > grace min total late in month → deduct TOTAL late × 2
        //   (e.g. 1h 40m total late → 1:40 + 1:40 = 3:20 deducted)
        $lateGraceMinutes       = \App\Helpers\AppSettings::getMonthlyGraceMinutes();
        $remainingLatePermission = max(0, $lateGraceMinutes - $lateMinutes);
        $deductableLateMinutes   = 0;
        $lateDeduction           = 0.0;

        if ($lateMinutes > $lateGraceMinutes) {
            $deductableLateMinutes = $lateMinutes * 2;          // 2× full amount
            $lateDeduction         = round($deductableLateMinutes * ($oneHourSalary / 60), 2);
        }

        // ── Step 7: Component deductions ─────────────────────────────────────
        $deductions = [];
        foreach ($components->where('type', 'deduction') as $comp) {
            if (in_array(strtolower(trim($comp->name)), ['pf', 'esi'])) continue;
            $deductions[$comp->name] = $comp->calculation_type === 'percentage'
                ? round(($comp->value / 100) * $ctcPerMonth, 2)
                : round((float) $comp->value, 2);
        }

        // ── Step 8: PF from Basic Salary (not CTC) ────────────────────────────
        $deductions['PF'] = $basicSalary <= 15000
            ? round($basicSalary * 0.12, 2)
            : 1800.00;

        // ── Step 9: ESI — eligible if CTC < 21,000; rate read from salary_components ──
        // Admin can change the rate from Settings → Salary Components (ESI row, value field).
        // Falls back to 0.75% if no ESI component is configured.
        $esiComp = $components->first(fn($c) => strtolower(trim($c->name)) === 'esi');
        $esiRate = $esiComp ? ((float) $esiComp->value / 100) : 0.0075;
        if ($ctcPerMonth < 21000) {
            $esi = round($ctcPerMonth * $esiRate, 2);
            if ($esi > 0) $deductions['ESI'] = $esi;
        }

        // ── Step 10: Attendance deductions ────────────────────────────────────
        if ($absentDeduction > 0) {
            $parts = [];
            if ($approvedUnpaidDays > 0) {
                $parts[] = $approvedUnpaidDays . ' unpaid leave';
            }
            if ($unapprovedLeaveDays > 0) {
                $parts[] = $unapprovedLeaveDays . ' unapproved leave';
            }
            $label = 'Absent (' . number_format($absentDays, 1) . ' days'
                   . ($parts ? ', incl. ' . implode(' + ', $parts) : '')
                   . ')';
            $deductions[$label] = $absentDeduction;
        }
        if ($lateDeduction > 0) {
            // Format: "Late (2× penalty — 1h 40m total → 3h 20m charged)"
            $fmt = fn(int $m) => ($m >= 60 ? intdiv($m, 60) . 'h ' : '') . ($m % 60) . 'm';
            $deductions['Late (2× penalty — ' . $fmt($lateMinutes) . ' total → ' . $fmt($deductableLateMinutes) . ' charged)'] = $lateDeduction;
        }

        // ── Step 11: Loan deductions ──────────────────────────────────────────
        // Only include loans that are within their active repayment tenure:
        //   • Disbursed on or before the last day of this payroll month
        //   • Payroll month has not yet exceeded the final deduction month
        //     (final month = startOfMonth(date_given) + total_months)
        if ($employee->relationLoaded('activeLoans')) {
            foreach ($employee->activeLoans as $loan) {
                // Skip loans disbursed after this payroll period
                if ($loan->date_given && $loan->date_given->gt($slipEndDate)) {
                    continue;
                }
                // Skip loans whose full tenure has already passed
                if (!$loan->isActiveForPayroll($month ?? now()->month, $year ?? now()->year)) {
                    continue;
                }
                $deductions[ucfirst($loan->type) . ' Deduction #' . $loan->id] = (float) $loan->monthly_deduction;
            }
        }

        $netSalary = max(0, $grossSalary - array_sum($deductions));

        // ── Attendance summary JSON ───────────────────────────────────────────
        $attendanceSummary = ($attendanceRecords->isNotEmpty() && $totalWorkingDays > 0)
            ? [
                'total_working_days'        => $totalWorkingDays,
                'present_days'              => $presentDays,
                'half_days'                 => $halfDays,
                'leave_days'                => $leaveDays,
                'paid_leave_days'           => $paidLeaveDays,           // approved paid-type leave days
                'approved_paid_days'        => $approvedPaidDays,        // same, for display
                'approved_unpaid_days'      => $approvedUnpaidDays,      // approved unpaid-type leave days
                'unapproved_leave_days'     => $unapprovedLeaveDays,     // on_leave without approved request
                'late_days'                 => $lateDays,
                'absent_days'               => round($absentDays, 2),
                'no_checkout_absent'        => $noCheckoutAbsent,
                'ot_hours'                  => $otHours,
                'ot_amount'                 => $otAmount,
                'late_minutes'              => $lateMinutes,
                'late_grace_minutes'        => $lateGraceMinutes,
                'remaining_late_permission' => $remainingLatePermission,
                'deductable_late_mins'      => $deductableLateMinutes,
                'late_deduction'            => $lateDeduction,
                'per_day_salary'            => round($perDayRate, 2),
                'per_hour_rate'             => round($perHourRate, 2),
                'calendar_days'             => $calendarDays,
                'basic_salary'              => $basicSalary,
                'ctc_per_month'             => $ctcPerMonth,
                'benefits_total'            => $benefitsTotal,
                'bonuses_total'             => $bonusesTotal,
                'additional_total'          => $totalAdditional,
            ]
            : null;

        return [
            'allowances'                => $allowances,
            'deductions'                => $deductions,
            'gross_salary'              => round($grossSalary, 2),
            'total_deductions'          => round(array_sum($deductions), 2),
            'net_salary'                => round($netSalary, 2),
            'absent_days'               => round($absentDays, 2),
            'absent_deduction'          => $absentDeduction,
            'late_days'                 => $lateDays,
            'late_minutes'              => $lateMinutes,
            'late_grace_minutes'        => $lateGraceMinutes,
            'remaining_late_permission' => $remainingLatePermission,
            'deductable_late_mins'      => $deductableLateMinutes,
            'late_deduction'            => $lateDeduction,
            'ot_hours'                  => $otHours,
            'ot_amount'                 => $otAmount,
            'present_days'              => $presentDays,
            'half_days'                 => $halfDays,
            'leave_days'                => $leaveDays,
            'paid_leave_days'           => $paidLeaveDays,
            'approved_paid_days'        => $approvedPaidDays,
            'approved_unpaid_days'      => $approvedUnpaidDays,
            'unapproved_leave_days'     => $unapprovedLeaveDays,
            'basic_salary'              => $basicSalary,
            'ctc_per_month'             => $ctcPerMonth,
            'benefits'                  => $benefits,
            'bonuses'                   => $bonuses,
            'benefits_total'            => $benefitsTotal,
            'bonuses_total'             => $bonusesTotal,
            'additional_total'          => $totalAdditional,
            'attendance_summary'        => $attendanceSummary,
        ];
    }
}
