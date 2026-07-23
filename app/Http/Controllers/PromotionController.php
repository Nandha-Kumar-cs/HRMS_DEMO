<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeePromotion;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeePromotion::with(['employee', 'previousDesignation', 'newDesignation', 'department'])
                ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id));

            return DataTables::of($query)
                ->addColumn('employee_name', fn($r) => $r->employee->full_name . ' (' . $r->employee->employee_code . ')')
                ->addColumn('prev_desig', fn($r) => $r->previousDesignation?->name ?? '-')
                ->addColumn('new_desig', fn($r) => $r->newDesignation?->name ?? '-')
                ->addColumn('dept', fn($r) => $r->department?->name ?? '-')
                ->addColumn('effective_date_fmt', fn($r) => $r->effective_date->format('d M Y'))
                ->addColumn('action', function ($r) {
                    return '<a href="' . route('promotions.edit', $r) . '" class="btn btn-xs btn-outline-primary"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-xs btn-outline-danger btn-delete" data-url="' . route('promotions.destroy', $r) . '"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $employees = Employee::orderBy('full_name')->get();
        return view('promotions.index', compact('employees'));
    }

    public function create(Request $request)
    {
        $employees    = Employee::where('status', 'active')->orderBy('full_name')->get();
        $departments  = Department::where('status', 'active')->orderBy('name')->get();
        $designations = Designation::where('status', 'active')->orderBy('name')->get();
        $selected     = $request->employee ? Employee::find($request->employee) : null;
        return view('promotions.create', compact('employees', 'departments', 'designations', 'selected'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'              => 'required|exists:employees,id',
            'previous_designation_id'  => 'nullable|exists:designations,id',
            'new_designation_id'       => 'required|exists:designations,id',
            'department_id'            => 'nullable|exists:departments,id',
            'effective_date'           => 'required|date',
            'remarks'                  => 'nullable|string|max:1000',
        ]);

        $promotion = EmployeePromotion::create($data);

        // Update employee's designation (and department if changed)
        $updateData = ['designation_id' => $data['new_designation_id']];
        if (!empty($data['department_id'])) {
            $updateData['department_id'] = $data['department_id'];
        }
        $promotion->employee->update($updateData);

        $promotion->load(['employee', 'previousDesignation', 'newDesignation']);
        ActivityLog::record('created', 'Promotion',
            "Promoted {$promotion->employee->full_name} ({$promotion->employee->employee_code})" .
            ": " . ($promotion->previousDesignation?->name ?? '—') . " → {$promotion->newDesignation->name}" .
            " — Effective: " . $promotion->effective_date->format('d M Y')
        );

        return redirect()->route('promotions.index')->with('success', 'Promotion recorded and employee profile updated.');
    }

    public function edit(EmployeePromotion $promotion)
    {
        $employees    = Employee::orderBy('full_name')->get();
        $departments  = Department::where('status', 'active')->get();
        $designations = Designation::where('status', 'active')->get();
        return view('promotions.edit', compact('promotion', 'employees', 'departments', 'designations'));
    }

    public function update(Request $request, EmployeePromotion $promotion)
    {
        $data = $request->validate([
            'employee_id'              => 'required|exists:employees,id',
            'previous_designation_id'  => 'nullable|exists:designations,id',
            'new_designation_id'       => 'required|exists:designations,id',
            'department_id'            => 'nullable|exists:departments,id',
            'effective_date'           => 'required|date',
            'remarks'                  => 'nullable|string|max:1000',
        ]);

        $promotion->update($data);
        $promotion->load(['employee', 'previousDesignation', 'newDesignation']);
        ActivityLog::record('updated', 'Promotion',
            "Updated promotion for {$promotion->employee->full_name} ({$promotion->employee->employee_code})" .
            ": " . ($promotion->previousDesignation?->name ?? '—') . " → {$promotion->newDesignation->name}"
        );
        return redirect()->route('promotions.index')->with('success', 'Promotion updated.');
    }

    public function destroy(EmployeePromotion $promotion)
    {
        $promotion->load(['employee', 'newDesignation']);
        $desc = "Deleted promotion for {$promotion->employee->full_name} ({$promotion->employee->employee_code})" .
                " — {$promotion->newDesignation?->name}, " . $promotion->effective_date->format('d M Y');
        $promotion->delete();
        ActivityLog::record('deleted', 'Promotion', $desc);
        return response()->json(['success' => true, 'message' => 'Record deleted.']);
    }
}
