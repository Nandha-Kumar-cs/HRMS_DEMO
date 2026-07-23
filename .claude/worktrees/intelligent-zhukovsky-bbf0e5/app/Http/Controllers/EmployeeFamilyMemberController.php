<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeFamilyMember;
use Illuminate\Http\Request;

class EmployeeFamilyMemberController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'relationship'      => 'required|string|max:50',
            'dob'               => 'nullable|date',
            'occupation'        => 'nullable|string|max:100',
            'contact_number'    => 'nullable|string|max:30',
            'dependency_status' => 'required|in:dependent,independent',
        ]);

        $employee->familyMembers()->create($data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Family member "' . $data['name'] . '" added.')
            ->with('active_tab', 'family');
    }

    public function update(Request $request, EmployeeFamilyMember $familyMember)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:150',
            'relationship'      => 'required|string|max:50',
            'dob'               => 'nullable|date',
            'occupation'        => 'nullable|string|max:100',
            'contact_number'    => 'nullable|string|max:30',
            'dependency_status' => 'required|in:dependent,independent',
        ]);

        $familyMember->update($data);

        return response()->json(['success' => true, 'message' => 'Family member updated.']);
    }

    public function destroy(EmployeeFamilyMember $familyMember)
    {
        $employee = $familyMember->employee;
        $familyMember->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Family member removed.')
            ->with('active_tab', 'family');
    }

    public function show(EmployeeFamilyMember $familyMember)
    {
        return response()->json($familyMember);
    }
}
