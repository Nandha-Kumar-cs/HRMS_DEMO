<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use Illuminate\Http\Request;

class EmployeeBonusController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeBonus::with(['employee', 'addedBy']);

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('type'))        $query->where('type', $request->type);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('month'))       $query->where('payroll_month', $request->month);
        if ($request->filled('year'))        $query->where('payroll_year', $request->year);

        $bonuses   = $query->latest()->paginate(20)->withQueryString();
        $employees = Employee::orderBy('full_name')->get();

        return view('employee-bonuses.index', compact('bonuses', 'employees'));
    }

    public function create(Request $request)
    {
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $selectedEmployeeId = $request->employee_id;
        return view('employee-bonuses.create', compact('employees', 'selectedEmployeeId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:' . implode(',', array_keys(EmployeeBonus::TYPES)),
            'amount'        => 'required|numeric|min:0',
            'reason'        => 'required|string|max:255',
            'payroll_month' => 'required|integer|min:1|max:12',
            'payroll_year'  => 'required|integer|min:2000|max:2099',
            'remarks'       => 'nullable|string|max:1000',
            'status'        => 'required|in:pending,approved,rejected',
        ]);

        $data['added_by'] = auth()->id();
        $bonus    = EmployeeBonus::create($data);
        $employee = Employee::find($data['employee_id']);
        ActivityLog::record('created', 'Bonus',
            "Added " . ucfirst($bonus->type) . " of ₹" . number_format($bonus->amount, 2) .
            " for {$employee->full_name} ({$employee->employee_code})" .
            " — {$bonus->reason} (Status: {$bonus->status})"
        );

        return redirect()->route('employee-bonuses.index')->with('success', 'Bonus / Incentive added.');
    }

    public function edit(EmployeeBonus $employeeBonus)
    {
        $employees = Employee::orderBy('full_name')->get();
        return view('employee-bonuses.edit', compact('employeeBonus', 'employees'));
    }

    public function update(Request $request, EmployeeBonus $employeeBonus)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:' . implode(',', array_keys(EmployeeBonus::TYPES)),
            'amount'        => 'required|numeric|min:0',
            'reason'        => 'required|string|max:255',
            'payroll_month' => 'required|integer|min:1|max:12',
            'payroll_year'  => 'required|integer|min:2000|max:2099',
            'remarks'       => 'nullable|string|max:1000',
            'status'        => 'required|in:pending,approved,rejected',
        ]);

        $employeeBonus->update($data);
        $employeeBonus->load('employee');
        ActivityLog::record('updated', 'Bonus',
            "Updated " . ucfirst($employeeBonus->type) . " for {$employeeBonus->employee->full_name} ({$employeeBonus->employee->employee_code})" .
            " — ₹" . number_format($employeeBonus->amount, 2) . ", Status: {$employeeBonus->status}"
        );

        return redirect()->route('employee-bonuses.index')->with('success', 'Bonus updated.');
    }

    public function destroy(EmployeeBonus $employeeBonus)
    {
        $employeeBonus->load('employee');
        $desc = ucfirst($employeeBonus->type) . " of ₹" . number_format($employeeBonus->amount, 2) .
                " for {$employeeBonus->employee->full_name} ({$employeeBonus->employee->employee_code})";
        $employeeBonus->delete();
        ActivityLog::record('deleted', 'Bonus', "Deleted {$desc}");
        return back()->with('success', 'Bonus removed.');
    }

    public function approve(EmployeeBonus $employeeBonus)
    {
        $employeeBonus->load('employee');
        $employeeBonus->update(['status' => 'approved']);
        ActivityLog::record('approved', 'Bonus',
            "Approved " . ucfirst($employeeBonus->type) . " of ₹" . number_format($employeeBonus->amount, 2) .
            " for {$employeeBonus->employee->full_name} ({$employeeBonus->employee->employee_code})"
        );
        return back()->with('success', 'Bonus approved.');
    }

    public function reject(EmployeeBonus $employeeBonus)
    {
        $employeeBonus->load('employee');
        $employeeBonus->update(['status' => 'rejected']);
        ActivityLog::record('rejected', 'Bonus',
            "Rejected " . ucfirst($employeeBonus->type) . " of ₹" . number_format($employeeBonus->amount, 2) .
            " for {$employeeBonus->employee->full_name} ({$employeeBonus->employee->employee_code})"
        );
        return back()->with('success', 'Bonus rejected.');
    }
}
