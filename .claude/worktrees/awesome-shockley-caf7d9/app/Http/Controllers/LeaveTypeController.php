<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $types = LeaveType::orderBy('name')->get();
        return view('leave-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100|unique:leave_types',
            'days_allowed' => 'required|integer|min:0|max:365',
            'carry_forward'=> 'nullable|boolean',
            'is_paid'      => 'nullable|boolean',
            'status'       => 'required|in:active,inactive',
        ]);

        $data['carry_forward'] = $request->boolean('carry_forward');
        $data['is_paid']       = $request->boolean('is_paid');

        $type = LeaveType::create($data);
        return response()->json(['success' => true, 'message' => 'Leave type created.', 'data' => $type]);
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('leave_types')->ignore($leaveType->id)],
            'days_allowed' => 'required|integer|min:0|max:365',
            'carry_forward'=> 'nullable|boolean',
            'is_paid'      => 'nullable|boolean',
            'status'       => 'required|in:active,inactive',
        ]);

        $data['carry_forward'] = $request->boolean('carry_forward');
        $data['is_paid']       = $request->boolean('is_paid');

        $leaveType->update($data);
        return response()->json(['success' => true, 'message' => 'Leave type updated.', 'data' => $leaveType]);
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();
        return response()->json(['success' => true, 'message' => 'Leave type deleted.']);
    }
}
