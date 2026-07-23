<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncrementLetterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id'          => 'required|exists:employees,id',
            'old_salary'           => 'required|numeric|min:0',
            'new_salary'           => 'required|numeric|min:0',
            'increment_percentage' => 'nullable|numeric|min:0',
            'effective_date'       => 'required|date',
        ];
    }
}
