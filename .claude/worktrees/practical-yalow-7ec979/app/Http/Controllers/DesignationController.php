<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Designation::with('department'))
                ->addColumn('department_name', fn($d) => $d->department?->name ?? '-')
                ->addColumn('status_badge', fn($d) => '<span class="badge bg-' . ($d->status === 'active' ? 'success' : 'danger') . '">' . ucfirst($d->status) . '</span>')
                ->addColumn('action', fn($d) => view('designations.partials.action', ['designation' => $d])->render())
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
        $departments = Department::where('status', 'active')->get();
        return view('designations.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'status'        => 'in:active,inactive',
        ]);
        $designation = Designation::create($data);
        $designation->load('department');
        ActivityLog::record('created', 'Designation',
            "Created designation: {$designation->name}" .
            ($designation->department ? " (Dept: {$designation->department->name})" : '')
        );
        return response()->json(['success' => true, 'message' => 'Designation created.', 'data' => $designation]);
    }

    public function update(Request $request, Designation $designation)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'status'        => 'in:active,inactive',
        ]);
        $designation->update($data);
        $designation->load('department');
        ActivityLog::record('updated', 'Designation',
            "Updated designation: {$designation->name}" .
            ($designation->department ? " (Dept: {$designation->department->name})" : '')
        );
        return response()->json(['success' => true, 'message' => 'Designation updated.', 'data' => $designation]);
    }

    public function destroy(Designation $designation)
    {
        $name = $designation->name;
        $designation->delete();
        ActivityLog::record('deleted', 'Designation', "Deleted designation: {$name}");
        return response()->json(['success' => true, 'message' => 'Designation deleted.']);
    }

    public function edit(Designation $designation)
    {
        return response()->json($designation);
    }

    public function byDepartment(Department $department)
    {
        return response()->json($department->designations()->where('status', 'active')->get(['id', 'name']));
    }
}
