<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferLetterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id'  => 'required|exists:employees,id',
            'offer_date'   => 'required|date',
            'joining_date' => 'required|date',
            'salary'       => 'required|numeric|min:0',
            'terms'        => 'nullable|string',
        ];
    }
}
