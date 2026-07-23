<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfirmationLetterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id'       => 'required|exists:employees,id|unique:confirmation_letters,employee_id',
            'confirmation_date' => 'required|date',
        ];
    }
}
