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
        $employeeId = $request->employee_id;
        $fundTypeId = $request->benefit_fund_type_id;
        $startDate  = $request->start_date ? Carbon::parse($request->start_date)->toDateString() : null;
        $endDate    = $request->end_date ? Carbon::parse($request->end_date)->toDateString() : null;

        // Treat empty-string frequency as null so validation messages are clean
        $request->merge(['frequency' => $request->filled('frequency') ? $request->frequency : null]);

        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'benefit_fund_type_id' => 'required|exists:benefit_fund_types,id',
            'amount'               => 'required|numeric|min:0',
            // Frequency is required when a start_date is provided (recurring mode);
            // optional when using legacy effective_month only.
            'frequency'            => 'required_with:start_date|nullable|in:weekly,fortnightly,monthly,quarterly,half_yearly,annual',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'benefit_name'         => 'nullable|string|max:255',
            'effective_month'      => 'nullable|date',
            'status'               => 'required|in:active,inactive',
            'description'          => 'nullable|string|max:1000',
        ], [
            'frequency.required_with' => 'Please select a frequency when a start date is provided.',
        ]);

        // Default frequency to 'monthly' for legacy mode (no start_date, no explicit frequency)
        $frequency = $data['frequency'] ?? 'monthly';
        $data['frequency'] = $frequency;

        // ── Duplicate prevention: Check for active benefits with overlapping dates ──
        if ($data['status'] === 'active' && $frequency && $startDate) {
            $existing = EmployeeBenefit::where('employee_id', $employeeId)
                ->where('benefit_fund_type_id', $fundTypeId)
                ->where('status', 'active')
                ->where(function ($q) use ($startDate, $endDate) {
                    // Check for date range overlap
                    $q->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
                      ->where(function ($subQ) use ($startDate) {
                          $subQ->whereNull('end_date')
                               ->orWhereDate('end_date', '>=', $startDate);
                      });
                })
                ->exists();

            if ($existing) {
                return back()->withInput()->withErrors([
                    'benefit_fund_type_id' => 'This benefit is already active for this employee in the selected date range.',
                ]);
            }
        }

        // ── Set effective_month for legacy support ──
        if (!$data['effective_month'] && $startDate) {
            $data['effective_month'] = Carbon::parse($startDate)->startOfMonth()->toDateString();
        } elseif ($data['effective_month']) {
            $data['effective_month'] = Carbon::parse($data['effective_month'])->startOfMonth()->toDateString();
        }

        $data['added_by'] = auth()->id();

        $benefit = EmployeeBenefit::create($data);
        $benefit->load(['employee', 'fundType']);

        // ── Activity logging ──
        $msg = "Assigned benefit \"{$benefit->getDisplayName()}\" to {$benefit->employee->full_name} ({$benefit->employee->employee_code})" .
               " — ₹" . number_format($benefit->amount, 2);

        if ($frequency && $startDate) {
            $msg .= ", Frequency: " . $benefit->getFrequencyLabel();
            $msg .= ", From: " . Carbon::parse($startDate)->format('d M Y');
            if ($endDate) {
                $msg .= ", Until: " . Carbon::parse($endDate)->format('d M Y');
            } else {
                $msg .= " (Ongoing)";
            }
        } elseif ($benefit->effective_month) {
            $msg .= ", Month: " . Carbon::parse($benefit->effective_month)->format('M Y');
        }
        $msg .= ", Status: " . ucfirst($benefit->status);

        ActivityLog::record('created', 'Benefit', $msg);

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
        $startDate  = $request->start_date ? Carbon::parse($request->start_date)->toDateString() : ($employeeBenefit->start_date ? $employeeBenefit->start_date->toDateString() : null);
        $endDate    = $request->end_date ? Carbon::parse($request->end_date)->toDateString() : ($employeeBenefit->end_date ? $employeeBenefit->end_date->toDateString() : null);

        // Treat empty-string frequency as null so validation messages are clean
        $request->merge(['frequency' => $request->filled('frequency') ? $request->frequency : null]);

        $data = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'benefit_fund_type_id' => 'required|exists:benefit_fund_types,id',
            'amount'               => 'required|numeric|min:0',
            'frequency'            => 'required_with:start_date|nullable|in:weekly,fortnightly,monthly,quarterly,half_yearly,annual',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date|after_or_equal:start_date',
            'benefit_name'         => 'nullable|string|max:255',
            'effective_month'      => 'nullable|date',
            'status'               => 'required|in:active,inactive',
            'description'          => 'nullable|string|max:1000',
        ], [
            'frequency.required_with' => 'Please select a frequency when a start date is provided.',
        ]);

        // Default frequency to existing value or 'monthly' for legacy records
        $frequency = $data['frequency'] ?? $employeeBenefit->frequency ?? 'monthly';
        $data['frequency'] = $frequency;

        // ── Duplicate prevention (excluding current record) ──
        if ($data['status'] === 'active' && $frequency && $startDate) {
            $existing = EmployeeBenefit::where('employee_id', $data['employee_id'])
                ->where('benefit_fund_type_id', $data['benefit_fund_type_id'])
                ->where('status', 'active')
                ->where('id', '<>', $employeeBenefit->id)  // Exclude current record
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
                      ->where(function ($subQ) use ($startDate) {
                          $subQ->whereNull('end_date')
                               ->orWhereDate('end_date', '>=', $startDate);
                      });
                })
                ->exists();

            if ($existing) {
                return back()->withInput()->withErrors([
                    'benefit_fund_type_id' => 'This benefit is already active for this employee in the selected date range.',
                ]);
            }
        }

        // ── Set effective_month for legacy support ──
        if (!$data['effective_month'] && $startDate) {
            $data['effective_month'] = Carbon::parse($startDate)->startOfMonth()->toDateString();
        } elseif ($data['effective_month']) {
            $data['effective_month'] = Carbon::parse($data['effective_month'])->startOfMonth()->toDateString();
        }

        $employeeBenefit->update($data);
        $employeeBenefit->load(['employee', 'fundType']);

        // ── Activity logging ──
        $msg = "Updated benefit \"{$employeeBenefit->getDisplayName()}\" for {$employeeBenefit->employee->full_name} ({$employeeBenefit->employee->employee_code})" .
               " — ₹" . number_format($employeeBenefit->amount, 2);

        if ($frequency && $startDate) {
            $msg .= ", Frequency: " . $employeeBenefit->getFrequencyLabel();
            $msg .= ", From: " . Carbon::parse($startDate)->format('d M Y');
            if ($endDate) {
                $msg .= ", Until: " . Carbon::parse($endDate)->format('d M Y');
            } else {
                $msg .= " (Ongoing)";
            }
        } elseif ($employeeBenefit->effective_month) {
            $msg .= ", Month: " . Carbon::parse($employeeBenefit->effective_month)->format('M Y');
        }
        $msg .= ", Status: " . ucfirst($employeeBenefit->status);

        ActivityLog::record('updated', 'Benefit', $msg);

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
