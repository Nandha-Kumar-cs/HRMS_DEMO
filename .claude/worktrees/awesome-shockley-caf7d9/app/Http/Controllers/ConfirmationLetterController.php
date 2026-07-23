<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConfirmationLetterRequest;
use App\Models\ActivityLog;
use App\Models\ConfirmationLetter;
use App\Models\Employee;
use App\Traits\HasSalaryGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ConfirmationLetterController extends Controller
{
    use HasSalaryGuard;

    public function index()
    {
        $letters = ConfirmationLetter::with('employee')->latest()->paginate(15);
        return view('confirmation-letters.index', compact('letters'));
    }

    public function create(Request $request)
    {
        $employees = Employee::orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::find($request->employee) : null;
        return view('confirmation-letters.create', compact('employees', 'selected'));
    }

    public function store(StoreConfirmationLetterRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);

        if ($redirect = $this->guardSalary($employee)) {
            return $redirect;
        }

        $letter = ConfirmationLetter::create($request->validated());
        ActivityLog::record('created', 'ConfirmationLetter',
            "Created confirmation letter for {$employee->full_name} ({$employee->employee_code})" .
            " — Confirmed on: " . ($letter->confirmation_date ? $letter->confirmation_date->format('d M Y') : '—')
        );
        return redirect()->route('confirmation-letters.show', $letter)->with('success', 'Confirmation letter created.');
    }

    public function show(ConfirmationLetter $confirmationLetter)
    {
        $confirmationLetter->load('employee.department', 'employee.designation', 'employee.entity');
        return view('confirmation-letters.show', compact('confirmationLetter'));
    }

    public function destroy(ConfirmationLetter $confirmationLetter)
    {
        $confirmationLetter->load('employee');
        $name = $confirmationLetter->employee->full_name;
        $code = $confirmationLetter->employee->employee_code;
        $confirmationLetter->delete();
        ActivityLog::record('deleted', 'ConfirmationLetter', "Deleted confirmation letter for {$name} ({$code})");
        return redirect()->route('confirmation-letters.index')->with('success', 'Confirmation letter deleted.');
    }

    public function pdf(ConfirmationLetter $confirmationLetter)
    {
        $confirmationLetter->load('employee.department', 'employee.designation', 'employee.entity');
        $pdf = Pdf::loadView('pdf.confirmation-letter', compact('confirmationLetter'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('confirmation-' . $confirmationLetter->employee->employee_code . '.pdf');
    }
}
