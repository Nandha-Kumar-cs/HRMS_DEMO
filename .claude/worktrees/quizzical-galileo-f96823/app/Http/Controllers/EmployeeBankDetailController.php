<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use Illuminate\Http\Request;

class EmployeeBankDetailController extends Controller
{
    /** Show add/edit form for one employee's bank details */
    public function edit(Employee $employee)
    {
        $employee->load('department', 'designation');
        $bankDetail = EmployeeBankDetail::where('employee_id', $employee->id)->first();
        return view('employee-bank-details.form', compact('employee', 'bankDetail'));
    }

    /** Create or update bank details (upsert) */
    public function upsert(Request $request, Employee $employee)
    {
        $existing = EmployeeBankDetail::where('employee_id', $employee->id)->first();

        $rules = [
            'bank_name'            => 'required|string|max:100',
            'account_holder_name'  => 'required|string|max:150',
            'account_number'       => 'required|string|max:30',
            'ifsc_code'            => ['required','string','max:20','regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'branch_name'          => 'required|string|max:100',
            'upi_id'               => 'nullable|string|max:100',
        ];

        // Unique account_number — ignore current employee's own record
        $uniqueRule = 'unique:employee_bank_details,account_number';
        if ($existing) $uniqueRule .= ',' . $existing->id;
        $rules['account_number'] = ['required','string','max:30', $uniqueRule];

        $validated = $request->validate($rules, [
            'ifsc_code.regex' => 'IFSC must be 11 characters (e.g. SBIN0001234).',
        ]);

        EmployeeBankDetail::updateOrCreate(
            ['employee_id' => $employee->id],
            $validated
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Bank details saved successfully.')
            ->with('active_tab', 'bank');
    }
}
