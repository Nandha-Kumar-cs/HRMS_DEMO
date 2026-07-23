<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeTemplateExport;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Imports\EmployeeImport;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $employees = Employee::with(['department', 'designation', 'offerLetter', 'confirmationLetter'])
                ->withCount(['salarySlips', 'incrementLetters'])
                ->select('employees.*');

            return DataTables::of($employees)
                ->addColumn('department_name', fn($e) => $e->department?->name ?? '-')
                ->addColumn('designation_name', fn($e) => $e->designation?->name ?? '-')
                ->addColumn('ctc', fn($e) => number_format($e->fixed_salary + $e->variable_salary, 2))
                ->addColumn('variable', fn($e) => number_format($e->variable_salary, 2))
                ->addColumn('status_badge', function ($e) {
                    $colors = ['active' => 'success', 'inactive' => 'danger', 'on_leave' => 'warning'];
                    $color  = $colors[$e->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $e->status)) . '</span>';
                })
                ->addColumn('action', fn($e) => view('employees.partials.action-dropdown', ['employee' => $e])->render())
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('employees.index');
    }

    public function create()
    {
        $entities     = Entity::orderBy('name')->get();
        $departments  = Department::where('status', 'active')->get();
        $designations = Designation::where('status', 'active')->get();
        $managers     = Employee::where('status', 'active')->orderBy('full_name')->get();
        return view('employees.create', compact('entities', 'departments', 'designations', 'managers'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $data['ot_enabled'] = $request->boolean('ot_enabled');

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('employee-photos', 'public');
        }

        $employee = Employee::create($data);

        ActivityLog::record('created', 'Employee',
            "Added employee: {$employee->full_name} ({$employee->employee_code})"
        );

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        if (request()->ajax() && request()->wantsJson()) {
            $employee->load(['department', 'designation', 'reportingManager']);
            return response()->json($employee);
        }
        $employee->load([
            'entity', 'department', 'designation', 'reportingManager',
            'increments', 'promotions.previousDesignation', 'promotions.newDesignation', 'promotions.department',
            'loans.repayments', 'documents', 'assetAssignments.asset',
            'salarySlips', 'offerLetter', 'confirmationLetter',
            'familyMembers', 'benefits.fundType', 'bonuses',
        ]);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $entities      = Entity::orderBy('name')->get();
        $departments   = Department::where('status', 'active')->get();
        $designations  = Designation::where('status', 'active')->get();
        $managers      = Employee::where('status', 'active')->where('id', '!=', $employee->id)->orderBy('full_name')->get();
        // Lock the CTC field when increment history exists — salary must be changed via Increments
        $hasIncrements = $employee->increments()->exists();
        return view('employees.edit', compact('employee', 'entities', 'departments', 'designations', 'managers', 'hasIncrements'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        // ── Load relationships so old labels are available for change tracking ──
        $employee->loadMissing(['department', 'designation', 'entity', 'reportingManager']);

        // ── Snapshot old values BEFORE the update ─────────────────────────────
        $oldFixed    = (float) $employee->fixed_salary;
        $oldVariable = (float) $employee->variable_salary;

        $oldSnapshot = [
            'Full Name'         => $employee->full_name,
            'Email'             => $employee->email,
            'Phone'             => $employee->phone,
            'Gender'            => $employee->gender ? ucfirst($employee->gender) : '—',
            'Date of Birth'     => $employee->dob?->format('d M Y') ?? '—',
            'Status'            => ucfirst(str_replace('_', ' ', $employee->status ?? '')),
            'Joining Date'      => $employee->joining_date?->format('d M Y') ?? '—',
            'Probation End'     => $employee->probation_end?->format('d M Y') ?? '—',
            'Entity'            => $employee->entity?->name ?? '—',
            'Department'        => $employee->department?->name ?? '—',
            'Designation'       => $employee->designation?->name ?? '—',
            'Reporting Manager' => $employee->reportingManager?->full_name ?? '—',
            'CTC/Month'         => '₹' . number_format($oldFixed, 2),
            'OT Enabled'        => $employee->ot_enabled ? 'Yes' : 'No',
        ];

        $data = $request->validated();
        $data['ot_enabled'] = $request->boolean('ot_enabled');

        // Handle photo removal
        if ($request->boolean('remove_photo') && $employee->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);
            $data['photo_path'] = null;
        }

        // Handle photo upload (new file overrides remove_photo if both sent)
        if ($request->hasFile('photo')) {
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('employee-photos', 'public');
        }

        $employee->update($data);

        // Reload relationships so new labels are available
        $employee->refresh()->load(['department', 'designation', 'entity', 'reportingManager']);

        $newFixed    = (float) $employee->fixed_salary;
        $newVariable = (float) $employee->variable_salary;

        // ── New snapshot ───────────────────────────────────────────────────────
        $newSnapshot = [
            'Full Name'         => $employee->full_name,
            'Email'             => $employee->email,
            'Phone'             => $employee->phone,
            'Gender'            => $employee->gender ? ucfirst($employee->gender) : '—',
            'Date of Birth'     => $employee->dob?->format('d M Y') ?? '—',
            'Status'            => ucfirst(str_replace('_', ' ', $employee->status ?? '')),
            'Joining Date'      => $employee->joining_date?->format('d M Y') ?? '—',
            'Probation End'     => $employee->probation_end?->format('d M Y') ?? '—',
            'Entity'            => $employee->entity?->name ?? '—',
            'Department'        => $employee->department?->name ?? '—',
            'Designation'       => $employee->designation?->name ?? '—',
            'Reporting Manager' => $employee->reportingManager?->full_name ?? '—',
            'CTC/Month'         => '₹' . number_format($newFixed, 2),
            'OT Enabled'        => $employee->ot_enabled ? 'Yes' : 'No',
        ];

        // ── Build diff ────────────────────────────────────────────────────────
        $changes = [];
        foreach ($oldSnapshot as $field => $oldVal) {
            $newVal = $newSnapshot[$field] ?? '—';
            if ($oldVal !== $newVal) {
                $changes[] = ['field' => $field, 'from' => $oldVal, 'to' => $newVal];
            }
        }

        // Flag photo changes
        if ($request->boolean('remove_photo')) {
            $changes[] = ['field' => 'Photo', 'from' => 'Had photo', 'to' => 'Removed'];
        } elseif ($request->hasFile('photo')) {
            $changes[] = ['field' => 'Photo', 'from' => 'Previous photo', 'to' => 'Updated'];
        }

        // ── Industry-standard salary revision tracking ─────────────────────────
        if (abs($newFixed - $oldFixed) > 0.005) {
            EmployeeIncrement::create([
                'employee_id'          => $employee->id,
                'previous_salary'      => $oldFixed,
                'new_salary'           => $newFixed,
                'increment_amount'     => round($newFixed - $oldFixed, 2),
                'increment_percentage' => $oldFixed > 0
                    ? round((($newFixed - $oldFixed) / $oldFixed) * 100, 2)
                    : 0,
                'effective_date'       => today()->toDateString(),
                'remarks'              => 'Salary revised via employee profile (direct edit)',
            ]);
        }

        ActivityLog::recordWithChanges(
            'updated',
            'Employee',
            "Updated employee: {$employee->full_name} ({$employee->employee_code})",
            $changes
        );

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        $code = $employee->employee_code;

        if ($employee->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $employee->delete();

        ActivityLog::record('deleted', 'Employee', "Deleted employee: {$name} ({$code})");

        return response()->json(['success' => true, 'message' => 'Employee deleted successfully.']);
    }

    // ── Excel Import ──────────────────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new EmployeeImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return back()->with('import_error', 'File validation failed: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        return redirect()->route('employees.index')->with('import_result', [
            'imported' => $import->imported,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
            'errors'   => $import->errors,
            'warnings' => $import->warnings,
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new EmployeeTemplateExport(), 'employee-import-template.xlsx');
    }

    // ── Salary status for letter guards (AJAX) ────────────────────────────

    public function salaryStatus(Employee $employee)
    {
        return response()->json([
            'fixed_salary'    => (float) $employee->fixed_salary,
            'variable_salary' => (float) $employee->variable_salary,
            'has_salary'      => ($employee->fixed_salary > 0 || $employee->variable_salary > 0),
            'full_name'       => $employee->full_name,
        ]);
    }
}
