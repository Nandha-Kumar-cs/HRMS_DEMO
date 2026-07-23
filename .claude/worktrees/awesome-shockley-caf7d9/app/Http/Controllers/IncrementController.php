<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class IncrementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeIncrement::with('employee')
                ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id));

            return DataTables::of($query)
                ->addColumn('employee_name', fn($r) => $r->employee->full_name . ' (' . $r->employee->employee_code . ')')
                ->addColumn('effective_date_fmt', fn($r) => $r->effective_date->format('d M Y'))
                ->addColumn('status_badge', function ($r) {
                    $today = Carbon::today();
                    if ($r->effective_date->gt($today)) {
                        return '<span class="badge bg-warning text-dark">Upcoming</span>';
                    }
                    return '<span class="badge bg-success">Applied</span>';
                })
                ->addColumn('action', function ($r) {
                    return '<a href="' . route('increments.edit', $r) . '" class="btn btn-xs btn-outline-primary"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-xs btn-outline-danger btn-delete" data-url="' . route('increments.destroy', $r) . '"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['action', 'status_badge'])
                ->make(true);
        }

        $employees = Employee::orderBy('full_name')->get();
        return view('increments.index', compact('employees'));
    }

    public function create(Request $request)
    {
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::find($request->employee) : null;
        return view('increments.create', compact('employees', 'selected'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'new_salary'     => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'remarks'        => 'nullable|string|max:1000',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        // BUG-5 FIX: Always capture the employee's CURRENT salary as previous_salary
        // at the moment the increment record is created.  This creates an immutable
        // audit trail so that old payslips can always resolve what the salary WAS
        // before this increment became effective.
        $previousSalary = (float) $employee->fixed_salary;

        $data['previous_salary']      = $previousSalary;
        $data['increment_amount']     = $data['new_salary'] - $previousSalary;
        $data['increment_percentage'] = $previousSalary > 0
            ? round((($data['new_salary'] - $previousSalary) / $previousSalary) * 100, 2)
            : 0;

        $increment = EmployeeIncrement::create($data);

        // BUG-3 FIX: Only update employee.fixed_salary immediately if the effective
        // date is today or in the past.  Future-dated increments should NOT change
        // the employee's current salary — it will be picked up automatically by
        // getSalaryForMonth() when the payroll for that month is run.
        $effectiveDate = Carbon::parse($data['effective_date']);
        if ($effectiveDate->lte(Carbon::today())) {
            $employee->update(['fixed_salary' => $data['new_salary']]);
            $message = 'Increment recorded and salary updated to ₹' . number_format($data['new_salary'], 2) . '.';
        } else {
            $message = 'Increment scheduled for ' . $effectiveDate->format('d M Y')
                . '. Employee salary will apply from that date during payroll generation.';
        }

        ActivityLog::record('created', 'Increment',
            "Salary increment for {$employee->full_name} ({$employee->employee_code})" .
            ": ₹" . number_format($previousSalary, 2) . " → ₹" . number_format($data['new_salary'], 2) .
            " ({$increment->increment_percentage}%) — Effective: " . $effectiveDate->format('d M Y')
        );

        return redirect()->route('increments.index')->with('success', $message);
    }

    public function edit(EmployeeIncrement $increment)
    {
        $employees = Employee::orderBy('full_name')->get();
        return view('increments.edit', compact('increment', 'employees'));
    }

    public function update(Request $request, EmployeeIncrement $increment)
    {
        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'previous_salary' => 'required|numeric|min:0',
            'new_salary'      => 'required|numeric|min:0',
            'effective_date'  => 'required|date',
            'remarks'         => 'nullable|string|max:1000',
        ]);

        $data['increment_amount']     = $data['new_salary'] - $data['previous_salary'];
        $data['increment_percentage'] = $data['previous_salary'] > 0
            ? round((($data['new_salary'] - $data['previous_salary']) / $data['previous_salary']) * 100, 2)
            : 0;

        $increment->update($data);

        // BUG-5 FIX: When editing an increment, we update employee.fixed_salary
        // ONLY if this increment's effective_date is the LATEST one applied so far.
        // This prevents an edit to a middle-of-history increment from overwriting a
        // more recent salary.  Old salary slips are unaffected because they use
        // the snapshotted fixed_salary stored at generation time.
        $employee        = Employee::find($data['employee_id']);
        $effectiveDate   = Carbon::parse($data['effective_date']);
        $latestIncrement = EmployeeIncrement::where('employee_id', $data['employee_id'])
            ->whereDate('effective_date', '<=', Carbon::today()->toDateString())
            ->orderByDesc('effective_date')
            ->first();

        if ($latestIncrement && $latestIncrement->id === $increment->id) {
            // This is still the most recently applied increment → update live salary
            $employee->update(['fixed_salary' => $data['new_salary']]);
        }

        ActivityLog::record('updated', 'Increment',
            "Updated increment for {$employee->full_name} ({$employee->employee_code})" .
            ": ₹" . number_format($data['previous_salary'], 2) . " → ₹" . number_format($data['new_salary'], 2) .
            " — Effective: " . Carbon::parse($data['effective_date'])->format('d M Y')
        );

        return redirect()->route('increments.index')
            ->with('success', 'Increment updated. Old salary slips are unaffected.');
    }

    public function destroy(EmployeeIncrement $increment)
    {
        $employee     = $increment->employee;
        $wasApplied   = $increment->effective_date->lte(Carbon::today());
        $previousSal  = (float) $increment->previous_salary;   // salary BEFORE this increment

        $increment->delete();

        // ── Recalculate employee's live salary after deletion ──────────────────
        // Only matters if the deleted increment had already taken effect (today or past).
        if ($wasApplied) {
            // Find the most recent *remaining* applied increment
            $latest = EmployeeIncrement::where('employee_id', $employee->id)
                ->whereDate('effective_date', '<=', Carbon::today()->toDateString())
                ->orderByDesc('effective_date')
                ->orderByDesc('id')      // tiebreaker: last-created wins on same date
                ->first();

            if ($latest) {
                // Another increment is now the latest → its new_salary is current
                $employee->update(['fixed_salary' => $latest->new_salary]);
            } elseif ($previousSal > 0) {
                // This was the only applied increment → revert to what salary was
                // BEFORE this increment (captured in previous_salary at creation)
                $employee->update(['fixed_salary' => $previousSal]);
            }
            // If previousSal = 0 (badly-formed old record) leave salary as-is
        }

        $newSalary = (float) $employee->fresh()->fixed_salary;
        ActivityLog::record('deleted', 'Increment',
            "Deleted increment for {$employee->full_name} ({$employee->employee_code})" .
            " — Salary adjusted to ₹" . number_format($newSalary, 2)
        );

        return response()->json([
            'success' => true,
            'message' => 'Increment deleted. Employee salary has been adjusted to ₹'
                . number_format($newSalary, 2) . '.',
        ]);
    }

    /**
     * AJAX: return the current salary for a selected employee (used to
     * pre-fill the "previous salary" field in the create form).
     */
    public function currentSalary(Request $request)
    {
        $employee = Employee::findOrFail($request->employee_id);
        return response()->json([
            'fixed_salary'    => $employee->fixed_salary,
            'variable_salary' => $employee->variable_salary,
        ]);
    }
}
