<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BenefitFundType;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeBenefitController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeBenefit::with(['employee', 'fundType', 'addedBy']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('fund_type_id')) {
            $query->where('benefit_fund_type_id', $request->fund_type_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('effective_month', $request->month)
                  ->whereYear('effective_month', $request->year);
        }

        $benefits = $query->latest()->paginate(20)->withQueryString();
        $employees = Employee::orderBy('full_name')->get();
        $fundTypes = BenefitFundType::orderBy('name')->get();

        return view('employee-benefits.index', compact('benefits', 'employees', 'fundTypes'));
    }

    public function create(Request $request)
    {
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $fundTypes = BenefitFundType::active()->orderBy('name')->get();
        $selectedEmployeeId = $request->employee_id;
        return view('employee-benefits.create', compact('employees', 'fundTypes', 'selectedEmployeeId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'benefit_fund_type_id' => 'required|exists:benefit_fund_types,id',
            'amount'               => 'required|numeric|min:0',
            'effective_month'      => 'required|date',
            'status'               => 'required|in:active,inactive',
            'description'          => 'nullable|string|max:1000',
        ]);

        // Normalize effective_month to first of month
        $data['effective_month'] = Carbon::parse($data['effective_month'])->startOfMonth()->toDateString();
        $data['added_by']        = auth()->id();

        $benefit  = EmployeeBenefit::create($data);
        $benefit->load(['employee', 'fundType']);

        ActivityLog::record('created', 'Benefit',
            "Assigned benefit \"{$benefit->fundType->name}\" to {$benefit->employee->full_name} ({$benefit->employee->employee_code})" .
            " — Amount: ₹" . number_format($benefit->amount, 2) .
            ", Month: " . Carbon::parse($benefit->effective_month)->format('M Y') .
            ", Status: " . ucfirst($benefit->status)
        );

        return redirect()->route('employee-benefits.index')
            ->with('success', 'Employee benefit assigned.');
    }

    public function edit(EmployeeBenefit $employeeBenefit)
    {
        $employees = Employee::orderBy('full_name')->get();
        $fundTypes = BenefitFundType::orderBy('name')->get();
        return view('employee-benefits.edit', compact('employeeBenefit', 'employees', 'fundTypes'));
    }

    public function update(Request $request, EmployeeBenefit $employeeBenefit)
    {
        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'benefit_fund_type_id' => 'required|exists:benefit_fund_types,id',
            'amount'               => 'required|numeric|min:0',
            'effective_month'      => 'required|date',
            'status'               => 'required|in:active,inactive',
            'description'          => 'nullable|string|max:1000',
        ]);

        $data['effective_month'] = Carbon::parse($data['effective_month'])->startOfMonth()->toDateString();
        $employeeBenefit->update($data);
        $employeeBenefit->load(['employee', 'fundType']);

        ActivityLog::record('updated', 'Benefit',
            "Updated benefit \"{$employeeBenefit->fundType->name}\" for {$employeeBenefit->employee->full_name} ({$employeeBenefit->employee->employee_code})" .
            " — Amount: ₹" . number_format($employeeBenefit->amount, 2) .
            ", Month: " . Carbon::parse($employeeBenefit->effective_month)->format('M Y') .
            ", Status: " . ucfirst($employeeBenefit->status)
        );

        return redirect()->route('employee-benefits.index')->with('success', 'Benefit updated.');
    }

    public function destroy(EmployeeBenefit $employeeBenefit)
    {
        $employeeBenefit->load(['employee', 'fundType']);
        $desc = "Removed benefit \"{$employeeBenefit->fundType->name}\" from {$employeeBenefit->employee->full_name} ({$employeeBenefit->employee->employee_code})" .
                " — ₹" . number_format($employeeBenefit->amount, 2) .
                ", " . Carbon::parse($employeeBenefit->effective_month)->format('M Y');

        $employeeBenefit->delete();

        ActivityLog::record('deleted', 'Benefit', $desc);
        return back()->with('success', 'Benefit removed.');
    }
}
