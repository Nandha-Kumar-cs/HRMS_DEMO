<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entity_id'          => 'nullable|exists:entities,id',
            'employee_code'      => 'required|unique:employees,employee_code',
            'full_name'          => 'required|string|max:255',
            'email'              => 'required|email|unique:employees,email',
            'phone'              => 'required|string|max:20',
            'dob'                => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'department_id'      => 'nullable|exists:departments,id',
            'designation_id'     => 'nullable|exists:designations,id',
            'status'             => 'in:active,inactive,on_leave',
            'joining_date'       => 'nullable|date',
            'probation_end'      => 'nullable|date',
            'reporting_manager_id' => 'nullable|exists:employees,id',
            'fixed_salary'       => 'required|numeric|min:0',
            'variable_salary'    => 'nullable|numeric|min:0',
            'photo'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'ot_enabled'         => 'nullable|boolean',
        ];
    }
}
