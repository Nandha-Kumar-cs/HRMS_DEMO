<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Department::query())
                ->addColumn('status_badge', fn($d) => '<span class="badge bg-' . ($d->status === 'active' ? 'success' : 'danger') . '">' . ucfirst($d->status) . '</span>')
                ->addColumn('action', fn($d) => view('departments.partials.action', ['department' => $d])->render())
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
        return view('departments.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'status' => 'in:active,inactive']);
        $dept = Department::create($data);
        ActivityLog::record('created', 'Department', "Created department: {$dept->name}");
        return response()->json(['success' => true, 'message' => 'Department created.', 'data' => $dept]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'status' => 'in:active,inactive']);
        $department->update($data);
        ActivityLog::record('updated', 'Department', "Updated department: {$department->name}");
        return response()->json(['success' => true, 'message' => 'Department updated.', 'data' => $department]);
    }

    public function destroy(Department $department)
    {
        $name = $department->name;
        $department->delete();
        ActivityLog::record('deleted', 'Department', "Deleted department: {$name}");
        return response()->json(['success' => true, 'message' => 'Department deleted.']);
    }

    public function edit(Department $department)
    {
        return response()->json($department);
    }
}
